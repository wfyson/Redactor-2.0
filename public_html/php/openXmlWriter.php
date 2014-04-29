<?php

/* 
 * Writers for various openXml documents - may share some bits, but actually a bit
 * unlikely. Will need to be given a copy of the original file and a list of
 * changes to be implemented 
 * 
 * Good overview of reading and creating zips here: http://devzone.zend.com/985/dynamically-creating-compressed-zip-archives-with-php/
 */

include 'ChromePhp.php';
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
    protected $file;
    protected $document;
    protected $changes;
    protected $zipArchive;
    
    public function __construct($document=null, $changes=null)
    {        
        $id = session_id();
        $this->document = $document;
        $this->file = $document;
        //$this->file = $file = $this->document->getFilepath();        
        $this->changes = $changes;     
        
        //make a copy of the original file so that we can alter it and send it back with a new name
        $split = explode('.', basename($this->file));        
        $newPath = $id . '/' . $split[0] . '_redacted.' . $split[1];             
        copy($this->file, $newPath);
        
        //create a zip object
        $this->zipArchive = new ZipArchive();
        
        //open output file for writing
        if ($this->zipArchive->open($newPath) !== TRUE)
        {
            die ("Could not open archive");
        }
        
        ChromePhp::log("ready to start writing!!!");
        
        $this->enactReplaceRedaction($redaction);
        
        /*
         * need to iterate through the changes, but if each change leaves the
         * document in a clean, ready to deliver state, we can apply one after 
         * another, providing we source the old stuff from the copy each time...
         * as per Mark's Haskell-y approach               
         */                                        
    }
    
    public function writeZip()
    {
        $this->zipArchive->close();
    }
    
    /*
     * Replaces one image with another
     */    
    public function writeImage($oldImage, $newImage)
    {
        //get new image from its specified location and write to server
        
              
        //simply overwrite the old image with the new one
        $testPath1 = $id . '/images/Penguins.jpg';
        $testPath2 = 'ppt/media/image2.jpeg';
                        
        $zip->addFile($testPath1, $testPath2);                
        
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
     * Obscure an image (although may actually want to do this in the JS
     */
    public function obscureImage($image)
    {
        
    }
    
}

class PowerPointWriter extends OpenXmlWriter implements DocumentWriter
{    
    public function enactReplaceRedaction($replaceRedaction)
    {
        ChromePhp::log("replace redaction!!!");
        
        //first replace the image
        
                
        //and then captions where appropriate
        
        //test case where image name = "image1.jpeg" but image name would come from the redaction object in reality
        //$slideRels = $this->document->getImageRels("image3.jpeg");
        
        //read through the slide files and see if the slide no corresponds with a key in the slideRels array
        $this->zip = zip_open($this->file);        
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
                
                //if (array_key_exists($no, $slideRels))
                //{
                    ChromePhp::log("reading slide..." . $no);
                    $this->writeCaption($zipEntry, $slideRels[$no], "test caption");
                //}
            }
            
            
            
            $zipEntry = zip_read($this->zip);
        }
        
        
        
        
        
        
        
        /*
         * Can probably write in the nex XML using $zip->addFromString('test.txt', 'file content goes here');
         * That way we just get a string of xml and write it to the existing file
         */
                       
                
                
        /*
         * With image changes, for each image we need to know what slide it         
         * features on and what it's RelID is for that slide (so basically 
         * generate an associative array of slides to relIDs - but that is a job
         * for the PowerPoint object.)    
         * 
         * The powerpoint now has a list of images to slide/rel pairings along with
         * coordinates for each occurrence     
         */
        
    }
    
    /*
     * Add a caption to slide to attribute an image
     */    
    public function writeCaption($zipEntry, $slideRels, $caption)
    {
        ChromePhp::log("caption writing!!!");
        
        
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
        
        //assume a relId of rId2 (which would normally be acquired via $slideRels->relId;
        $relId = "rId2";
        
        //or doing it with a DOM
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
                ChromePhp::log($doc->saveXML());     
            }
        }   
    }
    
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
    public function enactReplaceRedaction($replaceRedaction)
    {
        //simply replace the image
        
    }
    
    /*
     * Redact headings within the main text of a document
     */    
    public function redactText()
    {
        
    }   
}

$writer = new PowerPointWriter('bimdgfur4gefkdturqm8uvo5m2/test.pptx');
//$reader->readWord();

?>