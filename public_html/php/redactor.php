<?php

/*
 * Initialises redactor  
 */

include 'ChromePhp.php';
//example for logging: ChromePhp::log('Hello console!');

include 'openXmlReader.php';
include 'model/openXml.php';

class Redactor{
    
    private $filepath;
    private $document; //a representation of the document to be redacted
    
    public function __construct($filepath){        
        $this->filepath = $filepath;
        
        //get the format of the uploaded file
        $format = substr($filepath, strpos($filepath, '.'));                
        
        switch ($format) {
            case ".pptx":
                ChromePhp::log('Hello console!');                 
                $this->document = new PowerPoint($this->filepath);
            break;
            case ".docx":
                $this->reader = new WordReader($this->filepath);
            break;
        }         
        
        //construct the representation of the document that has been uploaded
        $this->init();        
    }
    
    public function init(){
        
        
        
        //ping everything back to tjhe main page so the user can start interacting with it
        echo $_GET['callback'] . '(' . "{'result' : '" . "geronimo" . "'}" . ')';        
    }
    
}

?>