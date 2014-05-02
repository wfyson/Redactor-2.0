<?php

/*
 * Initialises redactor  
 */

include 'ChromePhp.php';
//example for logging: ChromePhp::log('Hello console!');

include 'openXmlReader.php';
include 'metadataReader.php';
include 'model/openXml.php';
include 'openXmlWriter.php';
include 'redaction.php';


class Redactor{
    
    private $filepath;
    //private $document; //a representation of the document to be redacted
    
    public function __construct($filepath){    
        
        $this->filepath = $filepath;
        
        //get the format of the uploaded file
        $format = substr($filepath, strpos($filepath, '.'));                
        
        switch ($format) {
            case ".pptx":
                $reader = new PowerPointReader($this->filepath);
                $doc = $reader->readPowerPoint();
            break;
            case ".docx":
                $reader = new WordReader($this->filepath);
                $doc = $reader->readWord();
                //ChromePhp::log($word);
                //$this->document = $word;
            break;
        }         
        
        //test the writer here        
        $redactions = array();
        $redaction = new ReplaceRedaction('image1.jpg', 'http://farm1.staticflickr.com//1//1106973_8376728259_b.jpg', "testing!!");
        $redactions[] = $redaction;
        $writer = new WordWriter($doc, $redactions);        
        
        //construct the representation of the document that has been uploaded
        $this->init($doc);        
    }
    
    public function init($doc){
        
        ChromePhp::log("returning!!!");
        
        $json = $doc->generateJSON();    
        //$json = json_encode("hello");
        //ping everything back to the main page so the user can start interacting with it
        echo $_GET['callback'] . '(' . "{'result' : " . $json . "}" . ')';        
    }
    
}

?>