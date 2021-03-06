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
    
    private $exifTypes = array("jpg", "JPG", "jpeg", "JPEG");
    private $pngTypes = array("png", "PNG");
    
    public function __construct($name, $url){
        
        $this->url = $url;
        $metadataReader = null;
        
        //get the name and format of the image
        $split = explode('.', basename($url));
        $this->name = $name;
        $this->format = $split[1];        
        
        if (in_array($this->format, $this->exifTypes))
        {
            $metadataReader = new ExifReader($url);
        }
        
        if (in_array($this->format, $this->pngTypes))
        {
            $metadataReader = new PNGReader($url);
        }
        if ($metadataReader !== null)
        {
           $this->artist = $metadataReader->readField("artist"); 
           $this->copyright = $metadataReader->readField("copyright");  
        }else{
            //say metadata not readble
            $this->artist = "Cannot read metadata"; 
            $this->copyright = "Cannot read metadata";  
        }
    }
    
    public function getName(){
        return $this->name;
    }
    
    /*
     * Generate a JSON object for this image that returns information such as 
     * a link, licence data and any other metadata we might care to mention...
     */    
    public function generateJSONData(){
        
        $json = array();
        
        $json['name'] = $this->name;
        $json['format'] = $this->format;
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
    
    protected $docName;
    protected $localPath;   
    protected $filepath;
    protected $reader;
    protected $thumbnailLink;
    protected $imageLinks;
    protected $redactorImages = array();
    
    public function __construct($docName, $localPath, $filepath, $thumbnailLink, $imageLinks){
        
        $this->docName = $docName;
        
        $this->localPath = $localPath;
        
        $this->filepath = $filepath; 
        
        $this->thumbnailLink = $thumbnailLink;
        
        $this->imageLinks = $imageLinks;
        
        //get redactor images
        foreach ($this->imageLinks as $name => $url)
        {
            $redactorImage = new RedactorImage($name, $url);
            $this->redactorImages[] = $redactorImage;
        }   
    }  
    
    public function getFilepath()
    {
        return $this->filepath;
    }
    
    public function getRedactorImages()
    {
        return $this->redactorImages;
    }
}

/*
 * A representation of a .pptx
 */
class PowerPoint extends OpenXmlDocument{
    
    private $rels;
    private $slideWidth;
    private $slideHeight;
    
    public function __construct($docName, $localPath, $filepath, $thumbnailLink, $imageLinks, $rels, $slideWidth, $slideHeight)
    {       
        //ChromePhp::log($rels);
        
        parent::__construct($docName, $localPath, $filepath, $thumbnailLink, $imageLinks);       
        
        $this->rels = $rels;
        $this->slideWidth = $slideWidth;
        $this->slideHeight = $slideHeight;
    }       
    
    public function generateJSON()
    {
        $json = array();
        
        //type of document
        $json["type"] = "pptx";
        
        //title of presentation
        $json["title"] = $this->docName;
        
        //thumbnail
        $json["thumbnail"] = substr($this->thumbnailLink, 6);
                
        //image JSON
        $images = array();
        foreach($this->redactorImages as $image)
        {
            $images[] = $image->generateJSONData();            
        }
        
        $json["images"] = $images;
        
        return $json;
    }
    
    public function getImageRels($imageName)
    {
        return $this->rels[$imageName];
    }
    
    public function getSlideWidth()
    {
        return $this->slideWidth;
    }
    
    public function getSlideHeight()
    {
        return $this->slideHeight;
    }
        
}

/*
 * A representation of a .docx
 */
class Word extends OpenXmlDocument
{
    private $document;
    private $rels;
    
    public function __construct($docName, $localPath, $filepath, $thumbnailLink, $imageLinks, $rels, $document)
    {               
        parent::__construct($docName, $localPath, $filepath, $thumbnailLink, $imageLinks);       
        
        $this->rels = $rels;
        $this->document = $document;
    }       
    
    public function generateJSON()
    {
        ChromePhp::log("generating JSON!!!");
        
        $json = array();
        
        //type of document
        $json["type"] = "docx";                
        
        //title of word doc
        $json["title"] = $this->docName;
        
        //thumbnail
        $json["thumbnail"] = $this->thumbnailLink;
        
        //text        
        $jsonDoc = array();
        $root = $this->document;        
        $paraArray = $root->getParaArray();
        foreach($paraArray as $para)
        {
            //ChromePhp::log($para);
            $jsonArray = array();
            $type = $para->getType();                        
            $jsonArray["id"] = $para->getId();
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
                    $jsonArray["link"] = substr(($this->localPath . $this->rels[$relId]), 6);
                    
                    $caption = $para->getCaption();
                    if ($caption !== null)
                    {
                        $jsonArray["caption"] = $caption->getContent();
                    }
                    else
                    {
                        $jsonArray["caption"] = "";
                    }
                    
                    $jsonDoc[] = $jsonArray;
                }
                else
                {
                    if ($type == "table")
                    {
                        $jsonArray["rows"] = array();
                        
                        $rows = $para->getContent();
                        foreach($rows as $row)
                        {
                            $jsonRow = array();
                            $jsonRow["cells"] = array();                            
                            $cells = $row->getContent();
                            foreach($cells as $cell)
                            {   
                                $jsonCell = array();
                                $jsonCell["id"] = $cell->getId();
                                $jsonCell["paras"] = array();
                                $paras = $cell->getContent();                                
                                foreach($paras as $para)
                                {
                                    $jsonCellValue = array();                                                                        
                                    if ($para->getType() === "image")
                                    {
                                        $relId = $para->getContent();
                                        $jsonCellValue["type"] = "image";
                                        $jsonCellValue["link"] = substr(($this->localPath . $this->rels[$relId]), 6);
                                        array_push($jsonCell["paras"], $jsonCellValue);
                                    }
                                    else
                                    {
                                        $jsonCellValue["type"] = "text";
                                        $jsonCellValue["text"] = $para->getContent();
                                        array_push($jsonCell["paras"], $jsonCellValue);
                                    }                                    
                                }
                                array_push($jsonRow["cells"], $jsonCell);
                            }                            
                            array_push($jsonArray["rows"], $jsonRow);                             
                        }                                                                                                                    
                        $jsonDoc[] = $jsonArray;
                    }
                    else
                    {
                        $jsonArray["text"] = $para->getContent();
                        $jsonDoc[] = $jsonArray;
                    }
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
        
        return $json;
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