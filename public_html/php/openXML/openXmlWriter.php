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
    protected $docName;
    protected $document;
    protected $paraRedactions;
    protected $imageRedactions;
    protected $newPath;
    protected $zipArchive; //the zip we open and will make all changes to
    
    protected $exifTypes = array("jpg", "JPG", "jpeg", "JPEG");
    
    public function __construct($docName, $document, $paraRedactions=null, $imageRedactions=null)
    {        
        $this->id = session_id();
        $this->docName = $docName;
        $this->document = $document;
        $this->file = $this->document->getFilepath();        
        $this->paraRedactions = $paraRedactions;   
        $this->imageRedactions = $imageRedactions; 
        
        //make a copy of the original file so that we can alter it and send it back with a new name
        $split = explode('.', basename($this->docName));      
        $sessionDoc = $split[0] . '_' . $split[1];
        $this->newPath = '../../sessions/' . $this->id . '_' . $sessionDoc . '_' . $split[0] . '_redacted.' . $split[1];             
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
        $tempPath = '../../sessions/' . $this->id . '_' . str_replace('.', '_', basename($this->docName)) . '_images_' . $newName;
        
        copy($webImage, $tempPath);
              
        //simply overwrite the old image with the new one                        
        $this->zipArchive->addFile($tempPath, $oldImage);                
        
        $this->zipArchive->close();        
        
        /*
         * delete the copy of the new image (but maybe keep it one day if we 
         * want to create a repository of CC images or some such thing)
         */
    }
    
    //prefix specifies location of images for the document type
    public function enactLicenceRedaction($redaction, $prefix=null)
    {
        //first create a copy of the image
        $copy = $this->copyImage($redaction->imageName);   
        
        $split = explode('.', basename($copy));
        
        //create a writer based on image format
        if (in_array($split[1], $this->exifTypes)){
            $metadataWriter = new ExifWriter($copy, $redaction->licence); //creating the writer, also writes the metadata - may want to searate these two processes later
        }        
        
        //metadata written, now add it to the zip
        //open the zip archive ready to write
        //create a zip object
        $this->zipArchive = new ZipArchive();
        $this->zipArchive->open($this->newPath);
        
        //simply overwrite the old image with the new one                        
        $this->zipArchive->addFile($copy, $prefix . $redaction->imageName); 
        $this->zipArchive->close();                  
    }
    
    public function enactObscureRedaction($redaction, $prefix=null)
    {        
        //open the zip archive ready to write
        //create a zip object
        $this->zipArchive = new ZipArchive();
        $this->zipArchive->open($this->newPath);
        
        //simply overwrite the old image with the new one                        
        $this->zipArchive->addFile('../../' . $redaction->newImage, $prefix . $redaction->oldImageName); 
        $this->zipArchive->close();
    }
    
    //makes a copy of the specified image so it can be altered in some manner
    public function copyImage($image){                
        $oldPath = '../../sessions/' . $this->id . '_' . str_replace('.', '_', basename($this->docName)) . '_images_' . $image;        
        $split = explode('.', $image);      
        $newName = $split[0] . '_new.' . $split[1];    
        $newPath = '../../sessions/' . $this->id . '_' . str_replace('.', '_', basename($this->docName)) . '_images_' . $newName;
        copy($oldPath, $newPath);
        
        return $newPath;
    }
    
}

?>