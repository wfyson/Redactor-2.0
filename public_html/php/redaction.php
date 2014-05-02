<?php

/* 
 * Definitions for the various tyes of redactions that can take place.
 */

interface Redaction
{
    public function getType();
}

/*
 * Replace one image with another from the Web. Need to know which image to
 * replace, a caption for the image and a link to the new image. Could also be
 * used if we want to replace an image with a placeholder
 */ 
class ReplaceRedaction implements Redaction
{
            
    public $oldImageName, $newImage, $caption;
                
    public function __construct($oldImageName, $newImage, $caption)
    {
        $this->oldImageName = $oldImageName;
        $this->newImage = $newImage;  
        $this->caption = $caption;
    }
    
    public function getType()
    {
        return 'replace';
    }
}

/*
 * Add a licence to the metadata of an existing image within the document.
 * Need to know which image we want to add the licence to and what the licence 
 * will be (and possibly captioning information..?)
 */
class LicenceRedaction implements Redaction
{
    public $imageName, $licence;
    
    public function __construct($imageName, $licence)
    {
        $this->imageName = $imageName;
        $this->licence = $licence;
    }
    
    public function getType()
    {
        return 'licence';
    }    
}

/*
 * Obscure an image in a document. Need to know which image to obscure and a
 * local copy of the obscured image. 
 */


/*
 * Redact a heading and all the content within it. Only available for Word documents
 */
class HeadingRedaction implements Redaction
{
    public $headingId;
    
    public function __construct($id)
    {
        $this->headingId = $id;
    }
    
    public function getType()
    {
        return 'heading';
    }    
}
    
?>