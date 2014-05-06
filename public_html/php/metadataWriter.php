<?php

/* 
 * An interface for a metadata writer and then implementations of the writer
 * for various potential metadata formats (starting off with EXIF).
 * 
 * See http://commons.wikimedia.org/wiki/Commons:Exif for more on image formats
 * and the metadata they support
 */


/*
 * EXIF writer
 */
class ExifWriter{
    
    private $image;
    private $values;
    
    /*
     * create the writer with an image to write and an associative array of 
     * fields and values to write
     */
    public function __construct($image, $values){        
        $this->image = $image;       
        
        $this->values = $values; 
    }
    
    //write the metadata
    public function writeField()
    {
       
    }
}

?>