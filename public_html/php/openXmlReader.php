<?php

/* come up with an interface that defines a reader for office open xml documents 
 * or maybe an abstract class for those bits which are similar which then a wordReader
 * or powerpointReader could extend to implement their own bits
 * 
 * each would need to return a list of images and associated data with those images as JSON
 */

include 'model/openXml.php';
include 'ChromePhp.php';
//example for logging: ChromePhp::log('Hello console!');

/*
 * An interface for defining a reader for any file inputs
 */
interface DocumentReader
{

    //get all the images within a document
    public function readImages();
}

/*
 * Common elements that occur for reading any documents using the Office Open XML
 * format are implemented here
 */

abstract class OpenXmlReader
{

    protected $file;
    protected $zip;
    protected $imageLinks = array();
    protected $imagePath;
    
    public function __construct($file)
    {
        ChromePhp::log("allons-y");
        ChromePhp::log($file);
        
        //set up things for reading the file
        //create directory for images
        $id = session_id();
        $this->imagePath = $id . '/images/';
        
        if (!file_exists($this->imagePath)) {
            mkdir($this->imagePath, 0777, true);
        }

        $this->file = $file;
    }    
    
    //read the image from the powerpoint and write it to the server and return the link
    public function readImage($entryName, $zipEntry)
    {          
        $imagePath = '';
        $img = zip_entry_read($zipEntry, zip_entry_filesize($zipEntry));                
        if ($img !== null)
        {
            $imagePath = $this->imagePath . basename($entryName); 
            file_put_contents($imagePath, $img);                      
        }
        return $imagePath;
    }
}

/*
 * A reader for .pptx documents - also needs to read the size of slides so background
 * images can be redacted and captioned appropriately
 */
define("IMAGE_REL_TYPE", "http://schemas.openxmlformats.org/officeDocument/2006/relationships/image");

class PowerPointReader extends OpenXmlReader
{    
    private $relList = array();
    private $slideSize;
    
    public function readPowerPoint()
    {        
        $this->zip = zip_open($this->file);        
        $zipEntry = zip_read($this->zip);
        while ($zipEntry != false)
        {
            //read through all the files, call appropriate functions for each            
            $entryName = zip_entry_name($zipEntry);
            
            //for image files
            if (strpos($entryName, 'ppt/media/') !== FALSE)
            {                
                $this->imageLinks[] = $this->readImage($entryName, $zipEntry);
            }     
            
            //for thumbnail file
            if (strpos($entryName, 'docProps/thumbnail') !== FALSE)
            {
                $this->thumbnail = $this->readImage($entryName, $zipEntry);
            }
            
            //for rels file
            if (strpos($entryName, 'ppt/slides/_rels/') !== FALSE)
            { 
                $newRels = $this->readSlideImageRels($entryName, $zipEntry);
                $this->relList = array_merge($this->relList, $newRels);
            }
            
            //to get the slide height
            if (strpos($entryName, 'ppt/presentation.xml') !== FALSE)
            {
                $this->slideHeight = $this->readSlideHeight($zipEntry);
            }
            
            $zipEntry = zip_read($this->zip);
        }        
        
        //construct and then return a powerpoint
        $powerpoint = new PowerPoint($this->file, $this->thumbnail,
                $this->imageLinks, $this->relList, $this->slideHeight);
        
        return $powerpoint;        
    }
    
    //get the rels that match slides to images
    public function readSlideImageRels($entryName, $zipEntry)
    {
        $relList = array();
        
        $slideFile = basename($entryName);
        $slideNo = substr($slideFile, 0, strpos($slideFile, '.'));
        $no = $slideNo = substr($slideNo, 5);

        $rels = zip_entry_read($zipEntry, zip_entry_filesize($zipEntry));
        $xml = simplexml_load_string($rels);
        
        for ($i = 0; $i < $xml->count(); $i++) {
            $record = $xml->Relationship{$i};
            $type = $record->attributes()->Type;
            $cmp = strcmp($type, constant("IMAGE_REL_TYPE"));
            if ($cmp == 0) {
                $id = $record->attributes()->Id;
                $target = $record->attributes()->Target;

                ChromePhp::log($no . '... ' . $id . '...' . $target);

                $rel = new SlideImageRel($no, (string) $id, (string) $target);
                $relList[] = $rel;
            }
        }
        return $relList;
    }
    
    //get the slideh height to position captions when background images are replaced
    public function readSlideHeight($zipEntry)
    {        
        $ppt = zip_entry_read($zipEntry, zip_entry_filesize($zipEntry));
        $xml = simplexml_load_string($ppt);
        
        $presentation = $xml->xpath('//p:presentation');
        $sldSz = $presentation[0]->xpath('p:sldSz');
       
        return (string)$sldSz[0]->attributes()->cy;      
    }        
}

/*
 * A reader for .docx documents - also needs to read the text and heading hierarchy
 * of the document so content between headings can be redacted
 */

class WordReader extends OpenXmlReader
{
    public function readWord()
    {
        $this->zip = zip_open($this->file);        
        $zipEntry = zip_read($this->zip);
        while ($zipEntry != false)
        {
            //read through all the files, call appropriate functions for each            
            $entryName = zip_entry_name($zipEntry);
            
            //for image files
            if (strpos($entryName, 'word/media/') !== FALSE)
            {                
                $this->imageLinks[] = $this->readImage($entryName, $zipEntry);
            }     
            
            //for thumbnail file
            if (strpos($entryName, 'docProps/thumbnail') !== FALSE)
            {
                $this->thumbnail = $this->readImage($entryName, $zipEntry);
            }
            
            //for document content
            if (strpos($entryName, 'word/document.xml') !== FALSE)
            {
                $this->readText($zipEntry);
            }
            
            
            
            $zipEntry = zip_read($this->zip);
        }
    
    }
    
    public function readText($zipEntry)
    {        
        $doc = zip_entry_read($zipEntry, zip_entry_filesize($zipEntry));
        $xml = simplexml_load_string($doc);
        
        $paras = $xml->xpath('//w:p');
        
        $i = 0;
        while ($i < count($paras))
        {
            $para = $paras[$i];
            
           $wrArray = $para[0]->xpath('w:r');
           
           foreach($wrArray as $wr){
               $text = $wr[0]->xpath('w:t');           
               
               echo $text[0];               
           }
           echo '<br><br>';
           
           
            
            
            //$this->readPara($para);
            $i++; 
        }
        
        //$presentation = $xml->xpath('//p:presentation');
        //$sldSz = $presentation[0]->xpath('p:sldSz');
       
        //return (string)$sldSz[0]->attributes()->cy;           
    }
    
    public function readPara($para)
    {
        ChromePhp::log($para);
        
        $text = $para[0]->xpath('w:t');
        echo count($text);
        
        //$style = $para->xpath('//w:pStyle');
        
        echo '<br><br>';
        
        
    }
}

$reader = new WordReader('fajllgpnt8bhcv93oqj6nonr73/headings.docx');
$reader->readWord();


?>