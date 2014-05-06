<?php

class WordWriter extends OpenXmlWriter implements DocumentWriter
{
    public function __construct($document, $redactions=null)
    {   
        parent::__construct($document, $redactions);
        
        //setup complete, loop through the redactions        
        if ($redactions != null)
        {
            foreach($redactions as $redaction)
            {
                $type = $redaction->getType();
                switch($type){
                    case 'replace':
                        $this->enactReplaceRedaction($redaction);
                        break;
                    case 'heading':
                        $this->enactHeadingRedaction($redaction);
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
     * Redact headings within the main text of a document
     */    
    public function enactHeadingRedaction($headingRedaction)
    {
        //need to read through all the paras
        $this->zip = zip_open($this->newPath);        
        $zipEntry = zip_read($this->zip);
        while ($zipEntry != false)
        {                       
            $entryName = zip_entry_name($zipEntry);
            if (strpos($entryName, 'word/document.xml') !== FALSE)
            {                               
                $xml = $this->redactHeading($zipEntry, $headingRedaction->headingId);
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
    
    public function redactHeading($zipEntry, $headingId)
    {        
        $currentId = 0;
        $delete = false;
        $firstDeletedPara = false;
        $deleteLevel = -1;
        
        //read the xml        
        $doc = zip_entry_read($zipEntry, zip_entry_filesize($zipEntry));
                
        $dom = new DOMDocument();
        $dom->loadXML($doc);                    
                        
        $xpath = new DOMXPath($dom);
        $wpQuery = '//w:p';
        $wps = $xpath->query($wpQuery);
        foreach($wps as $wp)
        {    
            //if the style is a heading            
            $styleQuery = 'w:pPr/w:pStyle/@w:val';
            $style = $xpath->query($styleQuery, $wp)->item(0);                       
            if (strpos($style->value, 'Heading') === 0) 
            {           
                $currentId++;
                
                //determine heading level
                $headingLevel = intval(substr($style->value, 7));
                //reached the next section that hasn't been marked for deletion
                if ($headingLevel <= $deleteLevel)
                {
                    //stop deleting
                    $delete = false;  
                }
                
                if ($currentId == $headingId)
                {
                    //set variables so future wps get deleted
                    $deleteLevel = $headingLevel;
                    $delete = true;  
                    $firstDeletedPara = true;
                    
                    //change heading
                    $wtQuery = 'descendant::w:t[1]';                    
                    $wtFirst = $xpath->query($wtQuery, $wp)->item(0);               
                    $wtFirst->nodeValue = "Content Redacted";
                    $restQuery = 'descendant::w:t[position() > 1]';
                    $wtRest = $xpath->query($restQuery, $wp);
                    foreach ($wtRest as $wt)
                    {
                        $wt->nodeValue = "";
                    }
                }
            }
            else
            {
                //change text to comment on redaction
                if ($firstDeletedPara && $delete)
                {
                    //change text
                    $wtQuery = 'descendant::w:t[1]';
                    $wtFirst = $xpath->query($wtQuery, $wp)->item(0);
                    $wtFirst->nodeValue = "Content Redacted on " . date("d.m.y");

                    $restQuery = 'descendant::w:t[position() > 1]';
                    $wtRest = $xpath->query($restQuery, $wp);
                    foreach ($wtRest as $wt)
                    {
                        $wt->nodeValue = "";
                    }                    
                    $firstDeletedPara = false;
                }
                else
                {              
                    //if in delete mode delete the contents of the para
                    if ($delete)
                    {
                        while ($wp->hasChildNodes())
                        {
                            $wp->removeChild($wp->firstChild);
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