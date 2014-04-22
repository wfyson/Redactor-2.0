<?php

/* come up with an interface that defines a reader for office open xml documents 
 * or maybe an abstract class for those bits which are similar which then a wordReader
 * or powerpointReader could extend to implement their own bits
 * 
 * each would need to return a list of images and associated data with those images as JSON
 */

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

    public function __construct($file)
    {
        ChromePhp::log("allons-y");
        ChromePhp::log($file);

        $this->file = $file;
        $this->zip = zip_open($file);
    }
    
    //get the document thumbnail and write it to the server
    public function readThumbnail()
    {
        //create directory for images
        $id = session_id();
        $path = $id . '/images/';
        if (!file_exists($path)) {
            mkdir($path, 0777, true);
        }
        
        $imagePath = '';
        $zip_entry = zip_read($this->zip);
        while ($zip_entry != false)
        {
            $entryName = zip_entry_name($zip_entry);
            if (strpos($entryName, 'docProps/thumbnail') !== FALSE)
            {
                $img = zip_entry_read($zip_entry, zip_entry_filesize($zip_entry));                
                if ($img !== null)
                {                    
                        $imagePath = $path . basename($entryName); 
                        file_put_contents($imagePath, $img);                                        
                }
                break;            
            }
            $zip_entry = zip_read($this->zip);
        }
        return $imagePath;        
    }
    
    //read the images from the powerpoint and write them to the server, returning  list of the links
    public function readImages($type)
    {
        //create directory for images
        $id = session_id();
        $path = $id . '/images/';
        if (!file_exists($path)) {
            mkdir($path, 0777, true);
        }
        
        $imageLinks = array();
        
        $zip_entry = zip_read($this->zip);
        while ($zip_entry != false)
        {
            $entryName = zip_entry_name($zip_entry);
            if (strpos($entryName, $type . '/media/') !== FALSE)
            {
                $img = zip_entry_read($zip_entry, zip_entry_filesize($zip_entry));                
                if ($img !== null)
                {                    
                        $imagePath = $path . basename($entryName); 
                        file_put_contents($imagePath, $img);  
                        $imageLinks[] = $imagePath;                        
                }
            }
            $zip_entry = zip_read($this->zip);
        }
        return $imageLinks;        
    }
}

/*
 * A reader for .pptx documents - also needs to read the size of slides so background
 * images can be redacted and captioned appropriately
 */

define("IMAGE_REL_TYPE", "http://schemas.openxmlformats.org/officeDocument/2006/relationships/image");

class PowerPointReader extends OpenXmlReader
{
    
    
    //get the rels that match slides to images
    public function readSlideImageRels()
    {       
        $zip_entry = zip_read($this->zip);
        while ($zip_entry != false)
        {
            $entryName = zip_entry_name($zip_entry);
            if (strpos($entryName, 'ppt/slides/_rels/') !== FALSE)
            {          
                echo '---New Slide---<br><br>';
                
                $slideNo = basename($entryName);
                echo $slideNo . '<br>';
                
                $rels = zip_entry_read($zip_entry, zip_entry_filesize($zip_entry));
                $xml = simplexml_load_string($rels);
                print_r($xml);
                
                echo '<br><br>---Stuff---<br><br>';
                
                for($i = 0; $i < $xml->count(); $i++)
                {
                    $record = $xml->Relationship{$i};
                    $type = $record->attributes()->Type;
                    $cmp = strcmp($type, constant("IMAGE_REL_TYPE"));
                    if ($cmp == 0){
                        
                        $id = $record->attributes()->Id;
                        $target = $record->attributes()->Target;
                        //$rel = new SlideImageRel($id, $target);                        
                    }
                    
                }
                
                
            }
            $zip_entry = zip_read($this->zip);
        }   
    }
}

/*
 * A reader for .docx documents - also needs to read the text and heading hierarchy
 * of the document so content between headings can be redacted
 */

class WordReader extends OpenXmlReader
{
    
}

$reader = new PowerPointReader('sd3pkd4bs2q2lmuvasc61oon85/test.pptx');
$reader->readSlideImageRels();

?>