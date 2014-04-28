<?php

/* 
 * Writers for various openXml documents - may share some bits, but actually a bit
 * unlikely. Will need to be given a copy of the original file and a list of
 * changes to be implemented 
 * 
 * Good overview of reading and creating zips here: http://devzone.zend.com/985/dynamically-creating-compressed-zip-archives-with-php/
 */

/*
 * An interface for defining a writer for any file inputs
 */
interface DocumentWriter
{
    //functions for each of the redaction types    
    public function enactReplaceRedaction();
}

/*
 * Common elements that occur for writing any documents using the Office Open XML
 * format are implemented here
 */

abstract class OpenXmlWriter
{
    protected $file;
    protected $changes;

    public function __construct($file, $changes=null)
    {

        $id = session_id();
        
        $this->file = $file;
        $this->changes = $changes;     
        
        //make a copy of the original file so that we can alter it and send it back with a new name
        $split = explode('.', basename($this->file));        
        $newPath = $id . '/' . $split[0] . '_redacted.' . $split[1];             
        copy($this->file, $newPath);
        
        //create a zip object
        $zip = new ZipArchive();
        
        //open output file for writing
        if ($zip->open($newPath) !== TRUE)
        {
            die ("Could not open archive");
        }
        
        ChromePhp::log("writing!!!");
        
        /*
         * need to iterate through the changes, but if each change leaves the
         * document in a clean, ready to deliver state, we can apply one after 
         * another, providing we source the old stuff from the copy each time...
         * as per Mark's Haskell-y approach
         * 
         * With image changes, for each image we need to know what slide it
         * features on and what it's RelID is for that slide (so basically 
         * generate an associative array of slides to relIDs - but that is a job
         * for the PowerPoint object.)
         */
        
        
        //can simply overwrite with new stuff
        $testPath1 = $id . '/images/Penguins.jpg';
        $testPath2 = 'ppt/media/image2.jpeg';
        
        $zip->addFile($testPath1, $testPath2);
        
        $zip->close();
    }
    
    /*
     * Replaces one image with another
     */    
    public function writeImage($oldImage, $newImage)
    {
        
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
    public function enactReplaceRedaction()
    {
        //first replace the image
        
        //and then captions where appropriate
        
    }
    
    /*
     * Add a caption to slide to attribute an image
     */    
    public function writeCaption($x, $y, $caption)
    {
        
    }   
}

class WordWriter extends OpenXmlWriter implements DocumentWriter
{
    public function enactReplaceRedaction()
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

?>