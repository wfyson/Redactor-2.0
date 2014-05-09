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
        //$metadataReader = new ExifReader($url);
        //$this->artist = $metadataReader->readField("artist"); 
        //$this->copyright = $metadataReader->readField("copyright"); 
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
        $json['link'] = substr($this->url, 6);
        
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
        //ChromePhp::log($rels);
        
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
        $json["thumbnail"] = substr($this->thumbnailLink, 6);
        
        
        //image JSON
        $images = array();
        foreach($this->redactorImages as $image)
        {
            $images[] = $image->generateJSONData();            
        }
        
        $json["images"] = $images;
        
        return json_encode($json);
    }
    
    public function getImageRels($imageName)
    {
        return $this->rels[$imageName];
    }
        
}

/*
 * A representation of a .docx
 */
class Word extends OpenXmlDocument
{
    private $document;
    private $rels;
    
    public function __construct($filepath, $thumbnailLink, $imageLinks, $rels, $document)
    {               
        ChromePhp::log("wizard");
        
        parent::__construct($filepath, $thumbnailLink, $imageLinks);       
        
        $this->rels = $rels;
        $this->document = $document;
    }       
    
    public function generateJSON()
    {
        ChromePhp::log("generating JSON!!!");
        
        $json = array();
        
        //type of document
        $json["type"] = "docx";                
        
        //title of presentation
        $json["title"] = basename($this->filepath);
        
        //thumbnail
        $json["thumbnail"] = $this->thumbnailLink;
        
        //text
        
        $jsonDoc = array();
        $root = $this->document;
        $paraArray = $root->getParaArray();
        foreach($paraArray as $para)
        {
            $jsonArray = array();
            $type = $para->getType();                        
            $jsonArray["type"] = $type;
            if ($type == "heading")
            {
                $jsonArray["id"] = $para->getId();
                $jsonArray["text"] = $para->getContent();
                $jsonArray["level"] = $para->getLevel(); 
                $jsonDoc[] = $jsonArray; 
            }
            else
            {
                if ($type == "image")
                {
                    $relId = $para->getContent();
                    $jsonArray["image"] = $this->rels[$relId];
                    $jsonDoc[] = $jsonArray;
                }
                else
                {
                    $jsonArray["text"] = $para->getContent();
                    $jsonDoc[] = $jsonArray;
                }
            }            
        }
        $json["doc"] = $jsonDoc;
        
        //image JSON
        $images = array();
        foreach($this->redactorImages as $image)
        {
            $images[] = $image->generateJSONData();            
        }
        
        $json["images"] = $images;
        
        return json_encode($json);
    }
    
    public function getImageRel($imageName)
    {
        $relId = array_search($imageName, $this->rels);
        return $relId;
    }
}

/*
 * Used to represent an image within a slide
 */
class SlideRel
{
    public $relId;
    public $positions = array();
    
    public function __construct($relId)
    {
        $this->relId = $relId;           
    }
    
    public function addPosition($position)
    {
        $this->positions[] = $position;
    }
}

class ImagePosition
{
    public $x, $y, $w, $h;
    
    public function __construct($x, $y, $w, $h)
    {
        $this->x = $x;
        $this->y = $y;
        $this->w = $w;
        $this->h = $h;
    }
}

    
?>