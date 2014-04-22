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
        
        ChromePhp::log('Hello there!!!');
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
    
    public function __construct($filepath){
        
        $this->filepath = $filepath;                
        
    }
    
    public function getImageLinks(){
        return $this->imageLinks;
    }
        
}

/*
 * A representation of a .pptx
 */
class PowerPoint extends OpenXmlDocument{
    
    public function __construct($filepath){
        
        parent::__construct($filepath);
        
        //create a reader for the powerpoint
        $this->reader = new PowerPointReader($this->filepath);                
        
        //get the thumbnail for the document
        $this->thumbnailLink = $this->reader->readThumbnail();
        
        //get the image links
        $this->imageLinks = $this->reader->readImages('ppt');         
        foreach ($this->imageLinks as $url)
        {
            $redactorImage = new RedactorImage($url);
            $this->redatorImages[] = $redactorImage;
        }        
    }   
}

/*
 * A representation of a .docx
 */
class Word extends OpenXmlDocument{
    
}

/*
 * A PowerPoint document is made up of a number of slides, each of which can
 * posess a number of rels to images.
 */
class Slide{
    
    private $rels = array();
    
    public function __construct($rels){
        $this->rels = $rels;
    }    
}

/*
 * Used by pptx's to relate slides to images.
 */
class SlideImageRel{
    
    private $relId;
    private $imageName;
    
    public function __construct($relId, $imageName){
        $this->relId = $relId;
        $this->imageName = $imageName;
    }
}
    
?>