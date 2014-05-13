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
    private $format;
    private $redactions = array();
    private $doc;
    //private $document; //a representation of the document to be redacted
    
    public function __construct($filepath){           
        $this->filepath = $filepath;
        //get the format of the uploaded file
        $this->format = substr(basename($filepath), strpos(basename($filepath), '.'));                
        switch ($this->format) {
            case ".pptx":                
                $reader = new PowerPointReader($this->filepath);
                $this->doc = $reader->readPowerPoint();
            break;
            case ".docx":
                $reader = new WordReader($this->filepath);
                $this->doc = $reader->readWord();
                //ChromePhp::log($word);
                //$this->document = $word;
            break;
        }         
        
        //test the writer here        
        //$redactions = array();
        //$redaction1 = new ParaRedaction(217);
        //$redaction2 = new ParaRedaction(218);
        //$redaction3 = new ParaRedaction(219);
        //$redactions[] = $redaction1;
        //$redactions[] = $redaction2;
        //$redactions[] = $redaction3;
        //$writer = new WordWriter($doc, $redactions);        
        
        //construct the representation of the document that has been uploaded
        $this->returnState();        
    }
    
    public function addRedaction($redaction)
    {
        $this->redactions[] = $redaction;
    }
    
    public function removeParaRedactions()
    {
        foreach($this->redactions as $index => $redaction)
        {
            if ($redaction->getType() == 'para')
            {
                unset($this->redactions[$index]);
            }
        }
        return $this->redactions;
    }
    
    /*
     * return a JSON representation of each redaction
     */    
    public function redactionsToJSON()
    {
        
        $json = array();
        foreach($this->redactions as $redaction){
            $json[] = $redaction->generateJSON();
        }
        return $json;
        
    }
    
    /*
     * return the current state of the redactor (the document and the redactions
     * and simultaneoulsy update the session variable
     */
    public function returnState()
    {        
        $_SESSION['redactor'] = $this;
        
        ChromePhp::log("returning!!!");
        
        $docJSON = $this->doc->generateJSON();    
        
        $redactionJSON = $this->redactionsToJSON();

        $results = array($docJSON, $redactionJSON); 
    
        //ping everything back to the main page so the user can start interacting with it
        echo $_GET['callback'] . '(' . json_encode($results) . ')';        
    }
    
    public function commitRedactions()
    {
        //get appropriate write
        switch ($this->format) {
            case ".pptx":                
                $writer = new PowerPointWriter($this->doc, $rhis->redactions); 
            break;
            case ".docx":
                $writer = new WordWriter($this->doc, $this->redactions); 
            break;
        }       
        
        //ping back a link to the newly redacted document
        $link = substr($writer->returnDownloadLink(), 6);
        echo $_GET['callback'] . '(' . json_encode($link) . ')';
        
        
    }
    
    
    
}

?>