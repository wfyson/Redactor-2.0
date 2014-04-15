<?php

/*
 * Initialises redactor  
 */

include 'openXmlReader.php';

class Redactor{
    
    private $filepath;
    private $reader;
    
    public function __construct($filepath){        
        $this->filepath = $filepath;
        
        //get the format of the uploaded file
        $format = substr($filepath, strpos($filepath, '.'));
    
        switch ($format) {
            case ".pptx":
                ChromePhp::log('Hello console!');                
                $this->reader = new PowerPointReader($this->filepath);    
            break;
            case ".docx":
                $this->reader = new WordReader($this->filepath);
            break;
        }         
        
        //gather everything we need to populate the page once the document has been written
        $this->init();        
    }
    
    public function init(){
        
        $images = $this->reader->readImages();
        
        //ping everything back to tjhe main page so the user can start interacting with it
        echo $_GET['callback'] . '(' . "{'result' : '" . "geronimo" . "'}" . ')';        
    }
    
}

?>