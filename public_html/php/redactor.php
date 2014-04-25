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


class Redactor{
    
    private $filepath;
    private $document; //a representation of the document to be redacted
    
    public function __construct($filepath){    
        
        $this->filepath = $filepath;
        
        //get the format of the uploaded file
        $format = substr($filepath, strpos($filepath, '.'));                
        
        switch ($format) {
            case ".pptx":
                $reader = new PowerPointReader($this->filepath);
                $powerpoint = $reader->readPowerPoint();                
                $this->document = $powerpoint;
            break;
            case ".docx":
                $reader = new WordReader($this->filepath);
                $word = $reader->readWord();
            break;
        }         
        
        //test the writer here
        $writer = new PowerPointWriter($this->document->getFilepath());
        
        
        
        //construct the representation of the document that has been uploaded
        $this->init();        
    }
    
    public function init(){
        
        //ChromePhp::log("returning!!!");
        
        $json = $this->document->generateJSON();    
        
        //ChromePhp::log($json);
        
        //ping everything back to tjhe main page so the user can start interacting with it
        echo $_GET['callback'] . '(' . "{'result' : " . $json . "}" . ')';        
    }
    
}

?>