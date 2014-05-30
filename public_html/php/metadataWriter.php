<?php

/* 
 * An interface for a metadata writer and then implementations of the writer
 * for various potential metadata formats (starting off with EXIF).
 * 
 * See http://commons.wikimedia.org/wiki/Commons:Exif for more on image formats
 * and the metadata they support
 */

require_once('pel/PelJpeg.php');

/*
 * EXIF writer
 */
class ExifWriter{
    
    private $image;
    private $jpeg;
    private $exif;
    private $tiff;
    private $ifd;
    
    /*
     * create the writer with an image to write 
     */
    public function __construct($image, $value){               
        
        $this->image = $image;
        
        $this->jpeg = new PelJpeg($this->image);
        $this->exif = $this->jpeg->getExif();
        
        //if no exif data is present we need to add some
        if ($this->exif === null)
        {
            $this->exif = new PelExif();
            $this->jpeg->setExif($this->exif);
            
            /* We then create an empty TIFF structure in the APP1 section. */
            $this->tiff = new PelTiff();
            $this->exif->setTiff($this->tiff);    
        }
        else
        {
            $this->tiff = $this->exif->getTiff();
        }
        $this->ifd = $this->tiff->getIfd();

        if ($this->ifd == null)
        {
            $this->ifd = new PelIfd(PelIfd::IFD0);
            $this->tiff->setIfd($this->ifd);
        }
        
        $entry = $this->ifd->getEntry(PelTag::COPYRIGHT);
        if ($entry == null)
        {
            $entry = new PelEntryAscii(PelTag::COPYRIGHT, $value);
            $this->ifd->addEntry($entry);
        }
        else
        {
            $entry->setValue($value);
        }
        
        $this->jpeg->saveFile($this->image);
    }
    
    //write the metadata
    public function writeField($field, $value)
    {
       switch ($field){
           case "copyright":               
                
               break;
       }       
    }
}

class PNGWriter{
    
    public function __construct($image, $value){               
        
        //create PNG reader first
        $pngReader = new PNGReader($image);
        $pngReader->write_metadata("Software", "mysoftware");
                
    }
}

?>