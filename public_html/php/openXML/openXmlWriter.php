<?php

/* 
 * Writers for various openXml documents - may share some bits, but actually a bit
 * unlikely. Will need to be given a copy of the original file and a list of
 * changes to be implemented 
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
    protected $id;
    protected $file;    
    protected $document;
    protected $paraRedactions;
    protected $imageRedactions;
    protected $newPath;
    protected $zipArchive; //the zip we open and will make all changes to
    
    protected $exifTypes = array("jpg", "JPG", "jpeg", "JPEG");
    
    public function __construct($document, $paraRedactions=null, $imageRedactions=null)
    {        
        $this->id = session_id();
        $this->document = $document;
        $this->file = $this->document->getFilepath();        
        $this->paraRedactions = $paraRedactions;   
        $this->imageRedactions = $imageRedactions; 
        
        //make a copy of the original file so that we can alter it and send it back with a new name
        $split = explode('.', basename($this->file));      
        $sessionDoc = $split[0] . '_' . $split[1];
        $this->newPath = '../../sessions/' . $this->id . '/' . $sessionDoc . '/' . $split[0] . '_redacted.' . $split[1];             
        copy($this->file, $this->newPath);                            
        
        //now the specific implementations of the class loop through the redactions... (see the non-abstract classes)
    }
    
    public function returnDownloadLink()
    {
        return $this->newPath;
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
        $split = explode('.', basename($oldImage));      
        $newName = $split[0] . '_new.' . $split[1];      
        $tempPath = '../../sessions/' . $this->id . '/' . str_replace('.', '_', basename($this->file)) . '/images/' . $newName;
        
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
    
    public function enactLicenceRedaction($redaction, $prefix)
    {
        //first create a copy of the image
        $copy = $this->copyImage($redaction->imageName);   
        
        $split = explode('.', basename($copy));
        
        //create a writer based on image format
        if (in_array($split[1], $this->exifTypes)){
            $metadataWriter = new ExifWriter($copy, $redaction->licence);
        }
        
        //$metadataWriter->writeField("copyright", $redaction->licence);
        
        //metadata written, now add it to the zip
        //open the zip archive ready to write
        //create a zip object
        $this->zipArchive = new ZipArchive();
        $this->zipArchive->open($this->newPath);
        
        //simply overwrite the old image with the new one                        
        $this->zipArchive->addFile($copy, $prefix . $redaction->imageName); 
        $this->zipArchive->close();    
        
        
    }
    
    public function copyImage($image){                
        $oldPath = '../../sessions/' . $this->id . '/' . str_replace('.', '_', basename($this->file)) . '/images/' . $image;        
        $split = explode('.', $image);      
        $newName = $split[0] . '_new.' . $split[1];    
        $newPath = '../../sessions/' . $this->id . '/' . str_replace('.', '_', basename($this->file)) . '/images/' . $newName;
        copy($oldPath, $newPath);
        
        return $newPath;
    }
    
}

?>