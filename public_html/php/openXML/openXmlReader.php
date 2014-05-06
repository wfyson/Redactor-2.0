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
        $this->imagePath = '../../sessions/' . $id . '/images/';
        
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


?>