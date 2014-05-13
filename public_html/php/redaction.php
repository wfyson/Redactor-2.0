<?php

/* 
 * Definitions for the various tyes of redactions that can take place.
 */

interface Redaction
{
    public function getType();
    
    public function generateJSON();
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
    
    public function generateJSON(){                
        
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
    
    public function generateJSON(){
        
    }
}


/*
 * Obscure an image in a document. Need to know which image to obscure and a
 * local copy of the obscured image. 
 */
class ObscureRedaction implements Redaction
{
    public $imageName;
    
    public function __construct($imageName)
    {
        $this->imageName = $imageName;
    }
    
    public function getType()
    {
        return 'obscure';
    }   
    
    public function generateJSON(){
        
    }
}


/*
 * Redact a heading and all the content within it. Only available for Word documents
 */
class ParaRedaction implements Redaction
{
    public $id;
    
    public function __construct($id)
    {
        $this->id = $id;
    }
    
    public function getType()
    {
        return 'para';
    }    
    
    public function generateJSON(){        
        $json = array();
        
        $json['type'] = 'para';
        $json['value'] = $this->id;
        
        return $json;
    }
}
    
?>