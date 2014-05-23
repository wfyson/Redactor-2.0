<?php

/* 
 * Writers for various openXml documents - may share some bits, but actually a bit
 * unlikely. Will need to be given a copy of the original file and a list of
 * changes to be implemented 
 */

/*
 * Common elements that occur for writing any documents using the Office Open XML
 * format are implemented here
 */

class PowerPointWriter extends OpenXmlWriter implements DocumentWriter
{    
    public function __construct($docName, $document, $redactions=null)
    {   
        parent::__construct($docName, $document, $redactions);
        
        //setup complete, loop through the redactions        
        if ($redactions != null)
        {
            foreach($redactions as $redaction)
            {
                $type = $redaction->getType();
                ChromePhp::log($type);
                switch($type){
                    case 'replace':
                        $this->enactReplaceRedaction($redaction);
                        break;
                    case 'licence':
                        $prefix = 'ppt/media/';
                        $this->enactLicenceRedaction($redaction, $prefix);
                        break;
                    case 'obscure':
                        $prefix = 'ppt/media/';
                        $this->enactObscureRedaction($redaction, $prefix);
                        break;           
                }
            }
        }   
    }
    
    
    public function enactReplaceRedaction($replaceRedaction)
    {
        //first replace the image
        $oldPath = 'ppt/media/' . $replaceRedaction->oldImageName;
        $this->writeWebImage($replaceRedaction->newImage, $oldPath);
                      
        //and then add captions where appropriate...                
        $slideRels = $this->document->getImageRels($replaceRedaction->oldImageName);
               
        //read through the slide files and see if the slide no corresponds with a key in the slideRels array
        //for each slide that is changed make a record of its name and the new xml
        $newXml = array();
        $this->zip = zip_open($this->newPath);        
        $zipEntry = zip_read($this->zip);
        while ($zipEntry != false)
        {                       
            $entryName = zip_entry_name($zipEntry);
            if (strpos($entryName, 'ppt/slides/slide') !== FALSE)
            {
                //get the slide number
                $slideFile = basename($entryName);
                $slideNo = substr($slideFile, 0, strpos($slideFile, '.'));
                $no = substr($slideNo, 5);   
                
                if (array_key_exists($no, $slideRels))
                {
                    $xml = $this->writeCaption($zipEntry, $slideRels[$no], $replaceRedaction->caption);
                    $newXml[$entryName] = $xml;
                }
            }       
            $zipEntry = zip_read($this->zip);
        }
        zip_close($this->zip);
        
        //write the changes to the zip archive
        //open the zip archive ready to write
        //create a zip object
        $this->zipArchive = new ZipArchive();
        $this->zipArchive->open($this->newPath);        
        foreach($newXml as $entry => $xml)
        {
            $this->zipArchive->addFromString($entry, $xml);
        }
        $this->zipArchive->close();             
    }
    
    /*
     * Add a caption to slide to attribute an image
     */    
    public function writeCaption($zipEntry, $slideRels, $caption)
    {  
        ChromePhp::log($slideRels);
                
        //read the xml        
        $slide = zip_entry_read($zipEntry, zip_entry_filesize($zipEntry));
        $xml = simplexml_load_string($slide);      
        
        //get the maximum value for an id
        $maxId = 0;
        $ids = $xml->xpath('//p:cNvPr/@id');
        foreach($ids as $id)
        {            
            if((int)$id > $maxId)
            {
                $maxId = (int)$id;
            }
        }
        //if there is a position then place the caption accordingly
        if (count($slideRels->positions) > 0)
        {            
            $relId = $slideRels->relId;

            $doc = new DOMDocument();
            $doc->loadXML($slide);

            $xpath = new DOMXPath($doc);
            $treeQuery = '//p:spTree';
            $tree = $xpath->query($treeQuery)->item(0);
            $picQuery = '//p:pic';
            $pics = $xpath->query($picQuery);
            foreach ($pics as $pic) {
                $blipQuery = 'p:blipFill/a:blip/@r:embed';
                $blip = $xpath->query($blipQuery, $pic)->item(0);
                if ($blip->value == $relId) {
                    $maxId++;

                    //get the position information
                    $offQuery = 'p:spPr/a:xfrm/a:off';
                    $off = $xpath->query($offQuery, $pic)->item(0);
                    $x = $off->getAttribute('x');
                    $y = $off->getAttribute('y');

                    //get the size information
                    $extQuery = 'p:spPr/a:xfrm/a:ext';
                    $ext = $xpath->query($extQuery, $pic)->item(0);
                    $cx = $ext->getAttribute('cx');
                    $cy = $ext->getAttribute('cy');

                    //create the text box with caption
                    $sp = $this->createCaption($doc, $maxId, $x, $y, $cx, $cy, $caption);

                    //get sibling
                    $siblingQuery = 'following-sibling::*[1]';
                    $siblings = $xpath->query($siblingQuery, $pic);

                    if ($siblings->length > 0) {
                        $sibling = $siblings->item(0);
                        $tree->insertBefore($sp, $sibling);
                    } else {
                        $tree->appendChild($sp);
                    }
                }
            }
        }
        //no position information - so must be background picture so place the
        //caption at the bottom of the slide
        else
        {
            //get access to the slide's tree structure
            $doc = new DOMDocument();
            $doc->loadXML($slide);

            $xpath = new DOMXPath($doc);
            $treeQuery = '//p:spTree';
            $tree = $xpath->query($treeQuery)->item(0);
            
            //create a caption
            $x = 0;
            $y = $this->document->getSlideHeight();
            $cx = $this->document->getSlideWidth();
            $cy = 0-246221;
            $sp = $this->createCaption($doc, $maxId, $x, $y, $cx, $cy, $caption);
            
            $tree->appendChild($sp);
        }

        //return the amended XML
        return $doc->saveXML();
    }
    
    /*
     * Boring XML stuff for actually creating a text box
     */
    public function createCaption($doc, $id, $x, $y, $cx, $cy, $caption)
    {
        $sp = $doc->createElement('p:sp');
        
        $nvSpPr = $doc->createElement('p:nvSpPr');
        
        $cNvPr = $doc->createElement('p:cNvPr');
        $cNvPr->setAttribute('id', $id);
        $cNvPr->setAttribute('name', "TextBox");
        
        $cNvSpPr = $doc->createElement("p:cNvSpPr");
        $cNvSpPr->setAttribute("txBox", "1");
    
        $nvPr = $doc->createElement("p:nvPr");

        $spPr = $doc->createElement("p:spPr");

        $xfrm = $doc->createElement("a:xfrm");

        $off = $doc->createElement("a:off");
        $off->setAttribute('x', $x);
        $off->setAttribute('y', $y + $cy);

        $ext = $doc->createElement("a:ext");
        $ext->setAttribute('cx', $cx);
        $ext->setAttribute('cy', '246221');

        $prstGeom = $doc->createElement("a:prstGeom");
        $prstGeom->setAttribute('prst', "rect");

        $avLst = $doc->createElement("a:avLst");

        $noFill = $doc->createElement("a:noFill");

        $txBody = $doc->createElement("p:txBody");

        $bodyPr = $doc->createElement("a:bodyPr");
        $bodyPr->setAttribute("wrap", "square");
        $bodyPr->setAttribute("rtlCol", "0");

        $spAutoFit = $doc->createElement("a:spAutoFit");

        $lstStyle = $doc->createElement("a:lstStyle");

        $p = $doc->createElement("a:p");

        $r = $doc->createElement("a:r");

        $rPr = $doc->createElement("a:rPr");
        $rPr->setAttribute("lang", "en-GB");
        $rPr->setAttribute("sz", "1000");
        $rPr->setAttribute("dirty", "0");
        $rPr->setAttribute("smtClean", "0");

        $t = $doc->createElement("a:t");
        $t->nodeValue = $caption;

        $endParaRPr = $doc->createElement("a:endParaRPr");
        $endParaRPr->setAttribute('lang', "en-GB");
        $endParaRPr->setAttribute("sz", "1000");
        $endParaRPr->setAttribute("dirty", "0");

        //nvSpPr
        $nvSpPr->appendChild($cNvPr);
        $nvSpPr->appendChild($cNvSpPr);
        $nvSpPr->appendChild($nvPr);

        //spPr
        $xfrm->appendChild($off);
        $xfrm->appendChild($ext);

        $prstGeom->appendChild($avLst);

        $spPr->appendChild($xfrm);
        $spPr->appendChild($prstGeom);
        $spPr->appendChild($noFill);

        //txBody
        $bodyPr->appendChild($spAutoFit);

        $r->appendChild($rPr);
        $r->appendChild($t);

        $p->appendChild($r);
        $p->appendChild($endParaRPr);

        $txBody->appendChild($bodyPr);
        $txBody->appendChild($lstStyle);
        $txBody->appendChild($p);

        $sp->appendChild($nvSpPr);
        $sp->appendChild($spPr);
        $sp->appendChild($txBody);
        
        return $sp;
    }
}


?>