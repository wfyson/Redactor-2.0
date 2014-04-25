<?php

/* come up with an interface that defines a reader for office open xml documents 
 * or maybe an abstract class for those bits which are similar which then a wordReader
 * or powerpointReader could extend to implement their own bits
 * 
 * each would need to return a list of images and associated data with those images as JSON
 */

//include 'model/openXml.php';
//include 'ChromePhp.php';
//example for logging: ChromePhp::log('Hello console!');

/*
 * An interface for defining a reader for any file inputs
 */
interface DocumentReader
{
    //get all the images within a document
    public function readImages();
}

/*
 * Common elements that occur for reading any documents using the Office Open XML
 * format are implemented here
 */

abstract class OpenXmlReader
{

    protected $file;
    protected $zip;
    protected $imageLinks = array();
    protected $imagePath;
    
    public function __construct($file)
    {
        ChromePhp::log("allons-y");
        ChromePhp::log($file);
        
        //set up things for reading the file
        //create directory for images
        $id = session_id();
        $this->imagePath = $id . '/images/';
        
        if (!file_exists($this->imagePath)) {
            mkdir($this->imagePath, 0777, true);
        }

        $this->file = $file;
    }    
    
    //read the image from the powerpoint and write it to the server and return the link
    public function readImage($entryName, $zipEntry)
    {          
        $imagePath = '';
        $img = zip_entry_read($zipEntry, zip_entry_filesize($zipEntry));                
        if ($img !== null)
        {
            $imagePath = $this->imagePath . basename($entryName); 
            file_put_contents($imagePath, $img);                      
        }
        return $imagePath;
    }
}

/*
 * A reader for .pptx documents - also needs to read the size of slides so background
 * images can be redacted and captioned appropriately
 */
define("IMAGE_REL_TYPE", "http://schemas.openxmlformats.org/officeDocument/2006/relationships/image");

class PowerPointReader extends OpenXmlReader
{    
    private $relList = array();
    private $slideHeight;
    
    public function readPowerPoint()
    {        
        $this->zip = zip_open($this->file);        
        $zipEntry = zip_read($this->zip);
        while ($zipEntry != false)
        {
            //read through all the files, call appropriate functions for each            
            $entryName = zip_entry_name($zipEntry);
            
            //for image files
            if (strpos($entryName, 'ppt/media/') !== FALSE)
            {                
                $this->imageLinks[] = $this->readImage($entryName, $zipEntry);
            }     
            
            //for thumbnail file
            if (strpos($entryName, 'docProps/thumbnail') !== FALSE)
            {
                $this->thumbnail = $this->readImage($entryName, $zipEntry);
            }
            
            //for rels file
            if (strpos($entryName, 'ppt/slides/_rels/') !== FALSE)
            { 
                $newRels = $this->readSlideImageRels($entryName, $zipEntry);
                $this->relList = array_merge($this->relList, $newRels);
            }
            
            //to get the slide height
            if (strpos($entryName, 'ppt/presentation.xml') !== FALSE)
            {
                $this->slideHeight = $this->readSlideHeight($zipEntry);
            }
            
            $zipEntry = zip_read($this->zip);
        }        
        
        //construct and then return a powerpoint
        $powerpoint = new PowerPoint($this->file, $this->thumbnail,
                $this->imageLinks, $this->relList, $this->slideHeight);
        
        return $powerpoint;        
    }
    
    //get the rels that match slides to images
    public function readSlideImageRels($entryName, $zipEntry)
    {
        $relList = array();
        
        $slideFile = basename($entryName);
        $slideNo = substr($slideFile, 0, strpos($slideFile, '.'));
        $no = $slideNo = substr($slideNo, 5);

        $rels = zip_entry_read($zipEntry, zip_entry_filesize($zipEntry));
        $xml = simplexml_load_string($rels);
        
        for ($i = 0; $i < $xml->count(); $i++) {
            $record = $xml->Relationship{$i};
            $type = $record->attributes()->Type;
            $cmp = strcmp($type, constant("IMAGE_REL_TYPE"));
            if ($cmp == 0) {
                $id = $record->attributes()->Id;
                $target = $record->attributes()->Target;

                ChromePhp::log($no . '... ' . $id . '...' . $target);

                $rel = new SlideImageRel($no, (string) $id, (string) $target);
                $relList[] = $rel;
            }
        }
        return $relList;
    }
    
    //get the slideh height to position captions when background images are replaced
    public function readSlideHeight($zipEntry)
    {        
        $ppt = zip_entry_read($zipEntry, zip_entry_filesize($zipEntry));
        $xml = simplexml_load_string($ppt);
        
        $presentation = $xml->xpath('//p:presentation');
        $sldSz = $presentation[0]->xpath('p:sldSz');
       
        return (string)$sldSz[0]->attributes()->cy;      
    }        
}

/*
 * A reader for .docx documents - also needs to read the text and heading hierarchy
 * of the document so content between headings can be redacted
 */

define("XMLNS_PIC", "http://schemas.openxmlformats.org/drawingml/2006/picture");

class WordReader extends OpenXmlReader
{
    private $rels;
    private $document;
    
    public function readWord()
    {
        $this->zip = zip_open($this->file);        
        $zipEntry = zip_read($this->zip);
        while ($zipEntry != false)
        {
            //read through all the files, call appropriate functions for each            
            $entryName = zip_entry_name($zipEntry);
            
            //for image files
            if (strpos($entryName, 'word/media/') !== FALSE)
            {                
                $this->imageLinks[] = $this->readImage($entryName, $zipEntry);
            }     
            
            //for thumbnail file
            if (strpos($entryName, 'docProps/thumbnail') !== FALSE)
            {
                $this->thumbnail = $this->readImage($entryName, $zipEntry);
            }
            
            //for image rels
            if (strpos($entryName, 'word/_rels/document.xml.rels') !== FALSE)
            {
                $this->rels = $this->readRels($zipEntry);       
            }           
            
            //for document content
            if (strpos($entryName, 'word/document.xml') !== FALSE)
            {
                $this->document = $this->readText($zipEntry);
            }          
            
            $zipEntry = zip_read($this->zip);
        }
    
    }
    
    /*
     * Create an associative array to link rel ids to images
     */    
    public function readRels($zipEntry)
    {        
        $relList = array();                       

        $rels = zip_entry_read($zipEntry, zip_entry_filesize($zipEntry));
        $xml = simplexml_load_string($rels);
        
        for ($i = 0; $i < $xml->count(); $i++) {
            $record = $xml->Relationship{$i};
            $type = $record->attributes()->Type;
            $cmp = strcmp($type, constant("IMAGE_REL_TYPE"));
            if ($cmp == 0)
            {
                $id = $record->attributes()->Id;
                $target = basename($record->attributes()->Target);                   
                $relList[(string)$id] = $target;           
            }
        }
        return $relList;        
    }
    
    /*
     * Read the text of a Word document creating a hierarchy of headings
     * Includes reading of images within the text (represented by their relIDs)
     * and captions.
     */    
    public function readText($zipEntry)
    {        
        $doc = zip_entry_read($zipEntry, zip_entry_filesize($zipEntry));
        $xml = simplexml_load_string($doc);

        //create a root section
        $rootPara = new WordText("Root");
        $root = new WordHeading($rootPara, 0);
        
        $paras = $xml->xpath('//w:p');
        
        $i = 0;
        $currentHeading = $root;
        while ($i < count($paras))
        {                        
            $para = $paras[$i];

            $reading = $this->readPara($para, $currentHeading);

            if ($reading->getType() == "heading")
            {
                $currentHeading = $reading->getParent();
                $currentHeading->addPara($reading);
            } else
            {
                $currentHeading->addPara($reading);
            }
            $i++;
        }

        $root->display();        
        ChromePhp::log("DONE!!!");
        return $root;
    }
    
    public function readPara($para, $parent)
    {
        //check the style of the para
        $style = $para[0]->xpath('w:pPr/w:pStyle');           
        if ($style[0] != null) // a style is present
        {
            $val = $style[0]->xpath('@w:val');
            $styleVal = $val[0];
            
            //if the style is a heading            
            if (strpos($styleVal, 'Heading') === 0) 
            {
                //determine heading level
                $headingLevel = intval(substr($styleVal, 7));
                
                //read the text of the para
                $text = $this->readParaText($para);                
                
                //determine the new heading's parent
                if ($headingLevel > $parent->getLevel())
                {
                    //a level deeper
                    $newParent = $parent;
                }
                else
                {
                    $difference = $parent->getLevel() - $headingLevel;
                    $i = 0;
                    while ($i < $difference)
                    {
                        $newParent = $parent->getParent();
                        $parent = $newParent;
                        $i++;
                    }
                }
                
                //create a new heading
                $heading = new WordHeading($text, $headingLevel, $newParent);
                return $heading;
            }
            
            //if the stye is a caption
            if (strpos($styleVal, "Caption") === 0)
            {
                //return a caption thing
                $text = $this->readParaText($para);
                $wordCaption = new WordCaption($text);
                return $wordCaption;
            }  
            
            //style present but we're not interested
            $text = $this->readParaText($para);
            return $text;
        } 
        
        //check if there is a picture
        $positioning = $para[0]->xpath('w:r/w:drawing/*');
        if ($positioning[0] != null){
            $positioning[0]->registerXPathNamespace('a', 'http://schemas.openxmlformats.org/drawingml/2006/main');
            $graphicData = $positioning[0]->xpath('a:graphic/a:graphicData');
            $graphicData[0]->registerXPathNamespace('pic', 'http://schemas.openxmlformats.org/drawingml/2006/picture');
            $pic = $graphicData[0]->xpath('pic:pic');
            if ($pic[0] != null) 
            { 
                    //get the rel id
                    $relTag = $pic[0]->xpath('pic:blipFill/a:blip');
                    $relID = $relTag[0]->xpath('@r:embed');                    
                    $wordImage = new WordImage($relID[0]);
                    return $wordImage;                    
              
            }
        }
        
        //else nothing interesting going on so just read text
        $text = $this->readParaText($para);
        return $text;        
    }
    
    public function readParaText($para)
    {
        $text = '';
        $textTags = $para[0]->xpath('w:r/w:t');
        foreach($textTags as $wt){
            $text = $text . $wt[0];
        }
        $result = new WordText($text);
        return $result;
    }
}

//describes the methods that a readable component of a word document must implement
interface WordReadable
{    
    public function display();
    
    public function getType();
}

class WordText implements WordReadable
{
    private $text;
    
    public function __construct($text)
    {
        $this->text = $text;
    }
    
    public function getText()
    {
        return $this->text;
    }
    
    public function display()
    {
        echo '<br>' . $this->text . '<br>';
    }
    
    public function getType()
    {
        return "text";
    }
}

class WordImage implements WordReadable
{
    private $relID;
    
    public function __construct($relID)
    {
        $this->relID = $relID;
    }
    
    public function display()
    {
        echo '<br><br>---IMAGE...' . $this->relID . '---<br><br>';
    }
    
    public function getType()
    {
        return "image";
    }
}

class WordCaption implements WordReadable
{
    private $text;
    
    public function __construct($text)
    {
        $this->text = $text;
    }
    
    public function getText()
    {
        return $this->text;
    }
    
    public function display()
    {
        echo '<b>' . $this->text->getText() . '</b><br><br>';
    }
    
    public function getType()
    {
        return "caption";
    }
}

class WordHeading implements WordReadable
{
    private $title;
    private $level;
    private $parent;
    private $paraArray = array();
    
    public function __construct($title, $level, $parent=null)
    {
        $this->title = $title;
        $this->level = $level;       
        $this->parent = $parent;
    }
    
    public function addPara($para)
    {
        $this->paraArray[] = $para;
    }
    
    public function getLevel()
    {
        return $this->level;
    }
    
    public function getParent()
    {
        return $this->parent;
    }
    
    public function display()
    {
        echo '<br><br>';
        
        $i = $this->level;
        while ($i > 0)
        {
            echo '.........';
            $i--;
        }   
        echo '---' . $this->title->getText() . '---<br><br>';        
        foreach($this->paraArray as $para){
            $para->display();
        }
    }
    
    public function getType(){
        return "heading";
    }
}

//$reader = new WordReader('4ogefe4nerf4d1137lop1qe1e0/transfer.docx');
//$reader->readWord();


?>