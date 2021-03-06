<?php

class WordWriter extends OpenXmlWriter implements DocumentWriter
{
    public function __construct($docName, $document, $imageRedactions=null, $paraRedactions=null)
    {   
        parent::__construct($docName, $document, $paraRedactions, $imageRedactions);
        
        //setup complete, loop through the redactions        
        if ($paraRedactions != null)
        {
            foreach($paraRedactions as $redaction)
            {
                $this->enactParaRedaction($redaction);
            }
        }  
                
        if ($imageRedactions != null)
        {
            foreach($imageRedactions as $redaction)
            {
                $type = $redaction->getType();
                switch($type){
                    case 'replace':
                        $this->enactReplaceRedaction($redaction);
                        break;
                    case 'licence':
                        $prefix = 'word/media/';
                        $this->enactLicenceRedaction($redaction, $prefix);
                        break;
                    case 'obscure':
                        $prefix = 'word/media/';
                        $this->enactObscureRedaction($redaction, $prefix);
                        break;
                
            /*
             * more to follow here!!
             */               
                }
            }
        }   
    }
      
    public function enactReplaceRedaction($replaceRedaction)
    {
        //first replace the image
        $oldPath = 'word/media/' . $replaceRedaction->oldImageName;
        $this->writeWebImage($replaceRedaction->newImage, $oldPath);
        
        //and then write a caption        
        //first get the rel id for the image
        $relId = $this->document->getImageRel($replaceRedaction->oldImageName);             
        
        $this->zip = zip_open($this->newPath);        
        $zipEntry = zip_read($this->zip);
        while ($zipEntry != false)
        {                       
            $entryName = zip_entry_name($zipEntry);
            if (strpos($entryName, 'word/document.xml') !== FALSE)
            {                               
                $xml = $this->writeCaption($zipEntry, $relId, $replaceRedaction->caption);
            }
            
            $zipEntry = zip_read($this->zip);
        }        
        zip_close($this->zip);
        
        //write the changes to the zip archive
        //create, write then save the zip object
        $this->zipArchive = new ZipArchive();
        $this->zipArchive->open($this->newPath);  
        $this->zipArchive->addFromString('word/document.xml', $xml);        
        $this->zipArchive->close();  
        
    }
    
    public function writeCaption($zipEntry, $relId, $caption)
    {        
        //read the xml        
        $doc = zip_entry_read($zipEntry, zip_entry_filesize($zipEntry));
                
        $dom = new DOMDocument();
        $dom->loadXML($doc);                    
        
        $xpath = new DOMXPath($dom);
        $xpath->registerNamespace('pic', 'http://schemas.openxmlformats.org/drawingml/2006/picture');
        $picQuery = '//pic:pic';
        $pics = $xpath->query($picQuery);
        foreach($pics as $pic)
        {          
            $blipQuery = 'pic:blipFill/a:blip/@r:embed';
            $blip = $xpath->query($blipQuery, $pic)->item(0);
            if($blip->value == $relId)
            {
                //create the text box with caption
                $caption = $this->createCaption($dom, $caption);
                
                //get w:r ancestor
                $wrQuery = 'ancestor::w:r';
                $wrs = $xpath->query($wrQuery, $pic);
                $r = $wrs->item(0);
                
                //get w:p ancestor
                $wpQuery = 'ancestor::w:p';
                $wps = $xpath->query($wpQuery, $pic);
                $p = $wps->item(0);
               
                //get sibling
                $siblingQuery = 'following-sibling::*[1]';
                $siblings = $xpath->query($siblingQuery, $r);

                if ($siblings->length > 0)
                {
                    $sibling = $siblings->item(0);
                    $p->insertBefore($caption, $sibling);
                }
                else
                {
                    $p->appendChild($caption);
                }                 
            }
        }
        //return the amended XML
        return $dom->saveXML();
    }
    
    /*
     * Boring XML stuff for actually creating a text box
     */
    public function createCaption($doc, $caption)
    {
        $r = $doc->createElement('w:r');
        
        $rPr = $doc->createElement('w:rPr');
        
        $color = $doc->createElement('w:color');
        $color->setAttribute('w:val', 'auto');
        
        $lang = $doc->createElement('w:lang');
        $lang->setAttribute('w:val', 'en-GB');
        
        $rPr->appendChild($color);
        $rPr->appendChild($lang);
        
        $t = $doc->createElement("w:t");
        $t->setAttribute('xml:space', 'preserve');
        $t->nodeValue = $caption;
        
        $r->appendChild($rPr);
        $r->appendChild($t);
        
        return $r;
    }
    
    /*
     * Redact paragraphs within the main text of a document
     */    
    public function enactParaRedaction($paraRedaction)
    {
        //need to read through all the paras
        $this->zip = zip_open($this->newPath);        
        $zipEntry = zip_read($this->zip);
        while ($zipEntry != false)
        {                       
            $entryName = zip_entry_name($zipEntry);
            if (strpos($entryName, 'word/document.xml') !== FALSE)
            {                               
                $xml = $this->redactPara($zipEntry, $paraRedaction->id);
            }
            
            $zipEntry = zip_read($this->zip);
        }        
        zip_close($this->zip);  
        //write the changes to the zip archive
        //create, write then save the zip object
        $this->zipArchive = new ZipArchive();
        $this->zipArchive->open($this->newPath);  
        $this->zipArchive->addFromString('word/document.xml', $xml);        
        $this->zipArchive->close(); 
    }   
    
    public function redactPara($zipEntry, $id)
    {        
        
        $currentId = 2;
        
        //read the xml        
        $doc = zip_entry_read($zipEntry, zip_entry_filesize($zipEntry));
                
        $dom = new DOMDocument();
        $dom->loadXML($doc);                    
        
        $xpath = new DOMXPath($dom);
        
        $bodyQuery = '/w:document/w:body'; 
        $body = $xpath->query($bodyQuery)->item(0);
        //get the paragraphs and tables
        $wpQuery = 'w:p | w:tbl';
        $wps = $xpath->query($wpQuery, $body);
        foreach($wps as $wp)
        {      
            //redact a p entry
            if ($wp->nodeName === "w:p")
            {    
                //check if there is a picture
                //get the picture position
                $positionQuery = 'w:r/w:drawing/*';
                $position = $xpath->query($positionQuery, $wp)->item(0);

                if ($position != null) {
                    $graphicQuery = 'a:graphic/a:graphicData';
                    $xpath->registerNamespace('a', 'http://schemas.openxmlformats.org/drawingml/2006/main');
                    $graphic = $xpath->query($graphicQuery, $position)->item(0);
                    $picQuery = 'pic:pic';
                    $xpath->registerNamespace('pic', 'http://schemas.openxmlformats.org/drawingml/2006/picture');
                    $pic = $xpath->query($picQuery, $graphic)->item(0);
                    if ($pic != null) {
                        $currentId++;
                        if ($currentId == $id) {
                            //first remove all children
                            while ($wp->hasChildNodes()) {
                                $wp->removeChild($wp->firstChild);
                            }

                            //add image redacted text
                            $new = $this->createCaption($dom, "Image Redacted");
                            $wp->appendChild($new);

                            //return the amended XML
                            return $dom->saveXML();
                        }
                        continue;
                    }
                }

                //get the style
                $styleQuery = 'w:pPr/w:pStyle/@w:val';
                $style = $xpath->query($styleQuery, $wp)->item(0);

                if ($style != null) {
                    //check if header
                    if (strpos($style->value, 'Heading') === 0) {
                        $currentId++;

                        if ($currentId == $id) {
                            //change heading
                            $wtQuery = 'descendant::w:t[1]';
                            $wtFirst = $xpath->query($wtQuery, $wp)->item(0);
                            $wtFirst->nodeValue = "Heading Redacted";
                            $restQuery = 'descendant::w:t[position() > 1]';
                            $wtRest = $xpath->query($restQuery, $wp);
                            foreach ($wtRest as $wt) {
                                $wt->nodeValue = "";
                            }
                            //return the amended XML
                            return $dom->saveXML();
                        }
                        continue;
                    }

                    //check if caption
                    if (strpos($style->value, 'Caption') === 0) {
                        $currentId++;
                        if ($currentId == $id) {
                            //change caption
                            $wtQuery = 'descendant::w:t[1]';
                            $wtFirst = $xpath->query($wtQuery, $wp)->item(0);
                            $wtFirst->nodeValue = "Caption Redacted";
                            $restQuery = 'descendant::w:t[position() > 1]';
                            $wtRest = $xpath->query($restQuery, $wp);
                            foreach ($wtRest as $wt) {
                                $wt->nodeValue = "";
                            }
                            //return the amended XML
                            return $dom->saveXML();
                        }
                        continue;
                    }

                    //style present but not interested
                    $currentId++;
                    if ($currentId == $id) {
                        $wtQuery = 'descendant::w:t[1]';
                        $wtFirst = $xpath->query($wtQuery, $wp)->item(0);
                        $wtFirst->nodeValue = "Paragraph Redacted";
                        $restQuery = 'descendant::w:t[position() > 1]';
                        $wtRest = $xpath->query($restQuery, $wp);
                        foreach ($wtRest as $wt) {
                            $wt->nodeValue = "";
                        }
                        //return the amended XML
                        return $dom->saveXML();
                    }
                    continue;
                }

                //no style present                   
                //nothing of interest so simply deal with the normal paragraph
                $currentId++;
                if ($currentId == $id) {
                    $wtQuery = 'descendant::w:t[1]';
                    $wtFirst = $xpath->query($wtQuery, $wp)->item(0);
                    $wtFirst->nodeValue = "Paragraph Redacted";
                    $restQuery = 'descendant::w:t[position() > 1]';
                    $wtRest = $xpath->query($restQuery, $wp);
                    foreach ($wtRest as $wt) {
                        $wt->nodeValue = "";
                    }
                    //return the amended XML
                    return $dom->saveXML();
                }
            }
            //redact a table entry
            if ($wp->nodeName === "w:tbl")
            {
                $currentId++;
                $rowQuery = 'w:tr';
                $rows = $xpath->query($rowQuery, $wp);  
                foreach($rows as $row)
                {
                    $currentId++;
                    $cellQuery = 'w:tc';
                    $cells = $xpath->query($cellQuery, $row);
                    foreach($cells as $cell)
                    {
                        $currentId++;
                        if ($currentId == $id)
                        {
                            $cellWpQuery = 'w:p[1]';
                            $firstWp = $xpath->query($cellWpQuery, $cell)->item(0);                           
                            
                            //remove all t from first wp
                            //first remove all children
                            while ($firstWp->hasChildNodes()) {
                                $firstWp->removeChild($firstWp->firstChild);
                            }
                            //and add redaction text
                            $new = $this->createCaption($dom, "Cell Redacted");
                            $firstWp->appendChild($new);
                           
                            //remove remaining wps                            
                            $restQuery = 'descendant::w:p[position() > 1]';
                            $wpRest = $xpath->query($restQuery, $cell);
                            foreach ($wpRest as $remainingWp) {
                                $cell->removeChild($remainingWp);
                            }
                            //return the amended XML
                            return $dom->saveXML();
                        }                  
                    }
                }
            }
        }  
        //return the amended XML
        return $dom->saveXML();        
    }
}

?>