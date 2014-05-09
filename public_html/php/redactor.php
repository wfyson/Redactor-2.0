<?php

/*
 * Initialises redactor  
 */

include 'debug/ChromePhp.php';
//example for logging: ChromePhp::log('Hello console!');

//openXML
include 'openXML/openXml.php';
include 'openXML/openXmlReader.php';
include 'openXML/powerpointReader.php';
include 'openXML/wordReader.php';
include 'openXML/openXmlWriter.php';
include 'openXML/powerpointWriter.php';
include 'openXML/wordWriter.php';

//metadata reading and writing
include 'metadataReader.php';
include 'metadataWriter.php';

//redactions
include 'redaction.php';


class Redactor{
    
    private $filepath;
    //private $document; //a representation of the document to be redacted
    
    public function __construct($filepath){           
        $this->filepath = $filepath;
        //get the format of the uploaded file
        $format = substr(basename($filepath), strpos(basename($filepath), '.'));                
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
        //$redactions = array();
        //$redaction = new HeadingRedaction(3);
        //$redactions[] = $redaction;
        //$writer = new WordWriter($doc, $redactions);        
        
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