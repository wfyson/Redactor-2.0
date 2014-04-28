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
    
    //metadata
    private $artist;
    private $copyright;
    
    public function __construct($url){
        
        $this->url = $url;
        
        //get the name and format of the image
        $split = explode('.', basename($url));
        $this->name = $split[0];
        $this->format = $split[1];        
        
        //check image type here or in the metadata reader? TODO!!        
        $metadataReader = new ExifReader($url);
        $this->artist = $metadataReader->readField("artist"); 
        $this->copyright = $metadataReader->readField("copyright"); 
    }
    
    /*
     * Generate a JSON object for this image that returns information such as 
     * a link, licence data and any other metadata we might care to mention...
     */    
    public function generateJSONData(){
        
        $json = array();
        
        $json['name'] = $this->name;
        $json['artist'] = $this->artist;
        $json['copyright'] = $this->copyright;
        $json['link'] = $this->url;
        
        return $json;
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
            $redactorImage = new RedactorImage($url);
            $this->redactorImages[] = $redactorImage;
        }   
    }  
    
    public function getFilepath()
    {
        return $this->filepath;
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
    
    public function generateJSON()
    {
        $json = array();
        
        //type of document
        $json["type"] = "pptx";
        
        //title of presentation
        $json["title"] = basename($this->filepath);
        
        //thumbnail
        $json["thumbnail"] = $this->thumbnailLink;
        
        
        //image JSON
        $images = array();
        foreach($this->redactorImages as $image)
        {
            $images[] = $image->generateJSONData();            
        }
        
        $json["images"] = $images;
        
        return json_encode($json);
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