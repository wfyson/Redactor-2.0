<?php

/* come up with an interface that defines a reader for office open xml documents 
 * or maybe an abstract class for those bits which are similar which then a wordReader
 * or powerpointReader could extend to implement their own bits
 * 
 * each would need to return a list of images and associated data with those images as JSON
 */

/*
 * An interface for defining a reader for any file inputs
 */
interface DocumentReader{
    
    //get all the images within a document
    public function getImages();   
    
}

/*
 * Common elements that occur for reading any documents using the Office Open XML
 * format are implemented here
 */
abstract class OpenXmlReader{

    private $file;
    private $zip;
    
    public function __construct($file){
        
        ChromePhp::log("allons-y");
        
        $this->file = $file;
        
        $this->zip = zip_open($file);
        
        $zip_entry = zip_read($this->zip);
        while($zip_entry != false){
            ChromePhp::log(zip_entry_name($zip_entry));
            $zip_entry = zip_read($this->zip);
        }
        
        //$zip_entry = zip_read($this->zip);
        
        
    }
}

/*
 * A reader for .pptx documents - also needs to read the size of slides so background
 * images can be redacted and captioned appropriately
 */
class PowerPointReader extends OpenXmlReader{
    
    //public function getImages(){
        
    //}
    
}

/*
 * A reader for .docx documents - also needs to read the text and heading hierarchy
 * of the document so content between headings can be redacted
 */
class WordReader extends OpenXmlReader{
    
}



?>