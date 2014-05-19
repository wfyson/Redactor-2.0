<?php

/* 
 * An interface for a metadata reader and then implementations of the reader
 * for various potential metadata formats (starting off with EXIF).
 * 
 * See http://commons.wikimedia.org/wiki/Commons:Exif for more on image formats
 * and the metadata they support
 */

/*
 * An interface for defining a reader for any file inputs
 */
interface MetadataReader
{
    public function readField($field);
}

/*
 * EXIF reader - works with JPG, ...
 */
class ExifReader implements MetadataReader{
    
    private $url;
    private $exif = array();
    
    public function __construct($url){        
        $this->url = $url;       
        
        $this->exif = exif_read_data($url);      
    }
    
    /*
     * example of exif array...
     * Array ( [FileName] => image3.jpeg [FileDateTime] => 1397746244 [FileSize] => 69279 [FileType] => 2 [MimeType] => image/jpeg [SectionsFound] => ANY_TAG, IFD0, THUMBNAIL, EXIF [COMPUTED] => Array ( [html] => width="400" height="398" [Height] => 398 [Width] => 400 [IsColor] => 1 [ByteOrderMotorola] => 1 [Thumbnail.FileType] => 2 [Thumbnail.MimeType] => image/jpeg ) [ImageDescription] => President Bush, along with first lady, Laura Bush, and members of the Waco Midway Little League Softball World Series championship team, react as Bush accidentally drops his dog, Barney, Saturday, Aug. 30, 2003, at TSTC Airfield in Waco, Texas. Bush quickly scooped up the dog who was not injured. (AP Photo/Duane A. Laverty) [Orientation] => 1 [XResolution] => 2000000/10000 [YResolution] => 2000000/10000 [ResolutionUnit] => 2 [Software] => Adobe Photoshop CS3 Windows [DateTime] => 2008:05:09 14:03:06 [Artist] => DUANE A. LAVERTY [Exif_IFD_Pointer] => 532 [THUMBNAIL] => Array ( [Compression] => 6 [XResolution] => 72/1 [YResolution] => 72/1 [ResolutionUnit] => 2 [JPEGInterchangeFormat] => 670 [JPEGInterchangeFormatLength] => 10346 ) [ColorSpace] => 1 [ExifImageWidth] => 400 [ExifImageLength] => 398 )
     */    
    public function readField($field)
    {
        $exifField = null;
        switch ($field){
            case "copyright":
                $exifField = "Copyright";
                break;
            case "artist":
                $exifField = "Artist";
                break;
        }
        
        if ($exifField != null)
        {
            $field = "No data";
            if (array_key_exists($exifField, $this->exif))
            {   
                $field = $this->exif[$exifField];
            }                        
        }else{
            $field = "error: Invalid field";
        }
        
        return $field; 
    }
}

?>