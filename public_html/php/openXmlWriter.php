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
    
    public function __construct($document, $changes=null)
    {

        $id = session_id();
        $this->document = $document;
        $this->file = $file = $this->document->getFilepath();        
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
        
        $this->enactReplaceRedaction(null);
        
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
        
        //test case where image name = "image1.jpeg"
        $slideRels = $this->document->getImageRels("image1.jpeg");
        
        //read through the slide files and see if the slide no corresponds with a key in the slideRels array
        $this->zip = zip_open($this->file);        
        $zipEntry = zip_read($this->zip);
        while ($zipEntry != false)
        {                       
            $entryName = zip_entry_name($zipEntry);
            
            
            
            
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
    public function writeCaption($xml, $x, $y, $caption)
    {
        
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

?>