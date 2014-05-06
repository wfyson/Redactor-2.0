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
        $this->newPath = '../../sessions/' . $this->id . '/' . $split[0] . '_redacted.' . $split[1];             
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

?>