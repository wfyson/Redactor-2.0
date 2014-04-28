<?php

/* 
 * Definitions for the various tyes of redactions that can take place.
 * Should probably include some sort of interface to ensure they all work
 * consistently.
 */

class ReplaceRedaction{
            
    private $oldImageName;
    private $newImage;
                
    public function __construct($oldImageName, $newImage)
    {
        $this->oldImageNo = $oldImageName;
        $this->newImage = $newImage;        
    }        
}


    
?>