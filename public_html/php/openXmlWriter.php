<?php

/* 
 * Writers for various openXml documents - may share some bits, but actually a bit
 * unlikely. Will need to be given a copy of the original file and a list of
 * changes to be implemented 
 */

//include 'redaction.php';
//include 'ChromePhp.php';
//example for logging: ChromePhp::log('Hello console!');

/*
 * An interface for defining a writer for any file inputs
 */
interface DocumentWriter
{
    //functions for each of the redaction types    
    public function enactReplaceRedaction($replaceRedaction);
}

/*
 * Common elements that occur for writing any documents using the Office Open XML
 * format are implemented here
 */

abstract class OpenXmlWriter
{
    protected $id;
    protected $file;    
    protected $document;
    protected $redactions;
    protected $newPath;
    protected $zipArchive; //the zip we open and will make all changes to
    
    public function __construct($document, $redactions=null)
    {        
        $this->id = session_id();
        //$this->id = 'qk40mfe336c5r0jo4s423nlt62';
        $this->document = $document;
        //$this->file = $document;
        $this->file = $this->document->getFilepath();        
        $this->redactions = $redactions;             
        
        //make a copy of the original file so that we can alter it and send it back with a new name
        $split = explode('.', basename($this->file));        
        $this->newPath = $this->id . '/' . $split[0] . '_redacted.' . $split[1];             
        copy($this->file, $this->newPath);                            
        
        //now the specific implementations of the class loop through the redactions...
    }
    
    /*
     * Takes a link to an image online, writes it to the server and then inserts
     * it into the document
     */    
    public function writeWebImage($webImage, $oldImage)
    {
        //open the zip archive ready to write
        //create a zip object
        $this->zipArchive = new ZipArchive();
        $this->zipArchive->open($this->newPath);
        
        //get new image from its specified location and write to server
        $tempPath = $this->id . '/images/' . basename($webImage);
        copy($webImage, $tempPath);
              
        //simply overwrite the old image with the new one                        
        $this->zipArchive->addFile($tempPath, $oldImage);                
        
        $this->zipArchive->close();        
        
        /*
         * delete the copy of the new image (but maybe keep it one day if we 
         * want to create a repository of CC images or some such thing)
         */
    }
    
    /*
     * Add licence to an image
     */    
    public function writeLicence($image, $licence)
    {
        //see PEL stuff from the old redactor??
    }
    
    /*
     * Obscure an image (although may actually want to do this in the JS!!)
     */
    public function obscureImage($image)
    {
        
    }
    
}

class PowerPointWriter extends OpenXmlWriter implements DocumentWriter
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
        
        $relId = $slideRels->relId;
        
        $doc = new DOMDocument();
        $doc->loadXML($slide);
              
        $xpath = new DOMXPath($doc);
        $treeQuery = '//p:spTree';
        $tree = $xpath->query($treeQuery)->item(0);
        $picQuery = '//p:pic';
        $pics = $xpath->query($picQuery);
        foreach($pics as $pic)
        {          
            $blipQuery = 'p:blipFill/a:blip/@r:embed';
            $blip = $xpath->query($blipQuery, $pic)->item(0);
            if($blip->value == $relId)
            {
                $maxId++;
                
                //get the position information
                $offQuery = 'p:spPr/a:xfrm/a:off';                
                $off = $xpath->query($offQuery, $pic)->item(0);
                $x = $off->getAttribute('x');
                $y = $off->getAttribute('y');
                
                $extQuery = 'p:spPr/a:xfrm/a:ext';
                $ext = $xpath->query($extQuery, $pic)->item(0);
                $cx = $ext->getAttribute('cx');
                $cy = $ext->getAttribute('cy');
                
                //create the text box with caption
                $sp = $this->createCaption($doc, $maxId, $x, $y, $cx, $cy, $caption);
                
                //get sibling
                $siblingQuery = 'following-sibling::*[1]';
                $siblings = $xpath->query($siblingQuery, $pic);

                if ($siblings->length > 0)
                {
                    $sibling = $siblings->item(0);
                    $tree->insertBefore($sp, $sibling);
                }
                else
                {
                    $tree->appendChild($sp);
                }  
            }
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
        $off->setAttribute('y', $y);

        $ext = $doc->createElement("a:ext");
        $ext->setAttribute('cx', $cx);
        $ext->setAttribute('cy', $cy);

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

//$redactions = array();
//$redaction = new HeadingRedaction(4);
//$redactions[] = $redaction;
//$writer = new WordWriter('qk40mfe336c5r0jo4s423nlt62/transfer.docx', $redactions);
//$reader->readWord();

?>