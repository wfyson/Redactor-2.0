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
    private $doc;
    private $docName;
    private $redactions = array(); //a list of all the redactions to send to a writer
    private $paraRedactions = array(); //a list of all para related redactions
    private $imageRedactions = array(); //each image may only ever have one redaction associated with it... stored here!
    
    public function __construct($docname, $filepath){  
		$this->docName = $docname;
        $this->filepath = $filepath;
        //get the format of the uploaded file
        $this->format = substr($filepath, strrpos($filepath, '.'));                
        switch ($this->format) {
            case ".pptx":                
                $reader = new PowerPointReader($this->docName, $this->filepath);
                $this->doc = $reader->readPowerPoint();
                $this->initImageRedactionArray($this->doc->getRedactorImages());
            break;
            case ".docx":
                $reader = new WordReader($this->docName, $this->filepath);
                $this->doc = $reader->readWord();
                $this->initImageRedactionArray($this->doc->getRedactorImages());
            break;
        }         
        
        //test the writer here   
        //$redaction1 = new LicenceRedaction('image1.jpeg', 'CC-BY');
        //$redaction2 = new ParaRedaction(32);
        //$redaction3 = new ParaRedaction(219);
        //$redactions[] = $redaction1;
        //$redactions[] = $redaction2;
        //$redactions[] = $redaction3;
        //$writer = new WordWriter($this->doc, $redactions);        
        
        //construct the representation of the document that has been uploaded
        ChromePhp::log("returing");
        $this->returnState();        
    }
    
    public function getDocName(){
        return $this->docName;
    }
    
    //creates an array where image redactions are stored
    public function initImageRedactionArray($images){
        
        foreach($images as $image){
            $this->imageRedactions[$image->getName()] = null;
        }        
    }
    
    //add a redaction for an image
    public function addImageRedaction($image, $redaction)
    {
        $this->imageRedactions[$image] = $redaction;
    }
    
    //remove an image's redaction
    public function removeImageRedaction($image)
    {
        $this->imageRedactions[$image] = null;
    }
    
    //add a para redaction - usually called as part of a batch of redactions being added
    public function addParaRedaction($redaction)
    {
        $this->paraRedactions[] = $redaction;
    }
    
    //removes all para redactions
    public function removeParaRedactions()
    {
        $this->paraRedactions = array();
        return $this->paraRedactions;
    }
    
    /*
     * return a JSON representation of each redaction
     */    
    public function redactionsToJSON()
    {        
        //send redactions to client
        $json = array();
        $paraRedactions = array();
        $imageRedactions = array();
        foreach($this->paraRedactions as $paraRedaction){                                    
            $paraRedactions[] = $paraRedaction->generateJSON();
        }
        foreach($this->imageRedactions as $imageRedaction){  
            if ($imageRedaction !== null)
            {
                $imageRedactions[] = $imageRedaction->generateJSON();
            }            
        }
        $json['paraRedactions'] = $paraRedactions;
        $json['imageRedactions'] = $imageRedactions;
        
        return $json;        
    }
    
    /*
     * return the current state of the redactor (the document and the redactions
     * and simultaneoulsy update the session variable
     */
    public function returnState()
    {        
        $_SESSION['redactor'] = $this;        
        
        $docJSON = $this->doc->generateJSON();    
        
        $redactionJSON = $this->redactionsToJSON();

        $results = array($docJSON, $redactionJSON); 
        //ping everything back to the main page so the user can start interacting with it
        echo $_GET['callback'] . '(' . json_encode($results) . ')';        
    }
    
    public function commitRedactions()
    {      
        ChromePhp::log("redacting!!");
        
        //get the image redactions
        $imageRedactions = array();
        foreach($this->imageRedactions as $redaction)
        {
            if ($redaction !== null)
            {
                $imageRedactions[] = $redaction;
            }
        }
        //get appropriate writer
        switch ($this->format) {
            case ".pptx":                                
                $writer = new PowerPointWriter($this->docName, $this->doc, $imageRedactions); 
            break;
            case ".docx":
                $writer = new WordWriter($this->docName, $this->doc, $imageRedactions, $this->paraRedactions); 
            break;
        }       
        //ping back a link to the newly redacted document
        $link = substr($writer->returnDownloadLink(), 6);
        $split = explode('.', $this->docName);
        $downloadName = $split[0] . '_redacted.' . $split[1];
        $results = array($link, $downloadName);
        echo $_GET['callback'] . '(' . json_encode($results) . ')';   
    }
}

?>