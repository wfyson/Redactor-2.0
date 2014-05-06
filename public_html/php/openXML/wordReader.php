<?php

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
        
        //construct and then return a word document - may want to insert a link to a preset thumbnail image
        $word = new Word($this->file, "n/a",
                $this->imageLinks, $this->rels, $this->document);
        
        return $word;  
    
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

        $this->headingId = 0;
        
        //create a root section
        $rootPara = new WordText("Root");
        $root = new WordHeading($headingId, $rootPara, 0);
        $this->headingId++;
        
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
                $heading = new WordHeading($this->headingId, $text, $headingLevel, $newParent);
                $this->headingId++;
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
                    $wordImage = new WordImage((string)$relID[0]);
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
    
    public function getContent(); //a simple to return representation of the content
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
        //echo '<br>' . $this->text . '<br>';
    }
    
    public function getType()
    {
        return "text";
    }
    
    public function getContent()
    {        
        return $this->text;
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
        //echo '<br><br>---IMAGE...' . $this->relID . '---<br><br>';
    }
    
    public function getType()
    {
        return "image";
    }
    
    public function getContent()
    {        
        //return a link ideally...
        return $this->relID;
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
        //echo '<b>' . $this->text->getText() . '</b><br><br>';
    }
    
    public function getType()
    {
        return "caption";
    }
    
    public function getContent()
    {               
        return $this->text->getText();
    }
}

class WordHeading implements WordReadable
{
    private $id;
    private $title;
    private $level;
    private $parent;
    private $paraArray = array();
    
    public function __construct($id, $title, $level, $parent=null)
    {
        $this->id = $id;
        $this->title = $title;
        $this->level = $level;       
        $this->parent = $parent;
    }
    
    public function addPara($para)
    {
        $this->paraArray[] = $para;
    }
    
    public function getId()
    {
        return $this->id;
    }
    
    public function getLevel()
    {
        return $this->level;
    }
    
    public function getParent()
    {
        return $this->parent;
    }
    
    public function getTitle()
    {
        return $this->title;
    }
    
    public function getParaArray()
    {
        return $this->paraArray;
    }
    
    public function display()
    {/*
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
        }*/
    }
    
    public function getType(){
        return "heading";
    }
    
    public function getContent()
    {        
        return $this->title->getText();
    }
}

?>