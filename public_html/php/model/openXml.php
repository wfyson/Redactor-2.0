<?php

/* 
 * Defintions for the various classes that will be used to represent an 
 * office open xml document. Will likely include an image class (that should
 * probably have an interface in case images come from other document types in
 * the future) and an abstract class for office open xml documents that a Word
 * and PowerPoint class can both extend...
 */

/*
 * A class to represent images. Images are constructed with a URL and then 
 * available metadata is collected
 */
class RedactorImage{
            
    private $url;
    private $format;
    
    public function __construct($url){
        
        $this->url = $url;
        
        //get the name and format of the image
        $this->name = basename($url);
        $this->format = substr($url, strpos($url, '.'));
        
        ChromePhp::log($this->name);
        
        $metadataReader = new ExifReader($url);
        //$metadataReader->readField("artist");      
    }
    
    public function generateJSON(){
        
    }
    
}




/*
 * The shared properties and methods of representing an Office Open XML document
 */
abstract class OpenXmlDocument{
    
    protected $filepath;
    protected $reader;
    protected $thumbnailLink;
    protected $imageLinks;
    protected $redactorImages = array();
    
    public function __construct($filepath, $thumbnailLink, $imageLinks){
        
        $this->filepath = $filepath; 
        
        $this->thumbnailLink = $thumbnailLink;
        
        $this->imageLinks = $imageLinks;
        
        //get redactor images
        foreach ($this->imageLinks as $url)
        {
            //$redactorImage = new RedactorImage($url);
            //$this->redactorImages[] = $redactorImage;
        }   
    }        
}

/*
 * A representation of a .pptx
 */
class PowerPoint extends OpenXmlDocument{
    
    private $rels;
    private $slideHeight;
    
    public function __construct($filepath, $thumbnailLink, $imageLinks, $rels, $slideHeight)
    {       
        ChromePhp::log($rels);
        
        parent::__construct($filepath, $thumbnailLink, $imageLinks);
        
        $this->rels = $rels;
        $this->slideHeight = $slideHeight;
        
        
    }        
        
}

/*
 * A representation of a .docx
 */
class Word extends OpenXmlDocument{
    
}


/*
 * Used by pptx's to relate slides to images.
 */
class SlideImageRel{
    
    private $relId;
    private $slideNo;
    private $imageName;
    
    public function __construct($relId, $slideNo, $imageName){
        $this->relId = $relId;
        $this->slideNo = $slideNo;
        $this->imageName = $imageName;        
    }
}
    
?>