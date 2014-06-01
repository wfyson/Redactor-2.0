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
        
        $this->exif = @exif_read_data($url); //'@' removes a warning generated by a bug in exif_read_data 
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

/*
 * PNG reader - for more on PNG metadata see here: http://dev.exiv2.org/projects/exiv2/wiki/The_Metadata_in_PNG_files
 * 
 * More info on reading at: http://stackoverflow.com/questions/2190236/how-can-i-read-png-metadata-from-php
 */
class PNGReader implements MetadataReader{
    
    private $url;
    private $_fp;
    private $_chunks;
    
    public function __construct($url){             
        $this->url = $url;
        
        $this->_fp = fopen($url, 'r+');
                
        if (!$this->_fp)
            throw new Exception('Unable to open file');
        
        // Read the magic bytes and verify
        $header = fread($this->_fp, 8);

        if ($header != "\x89PNG\x0d\x0a\x1a\x0a")
            throw new Exception('Is not a valid PNG image');

        // Loop through the chunks. Byte 0-3 is length, Byte 4-7 is type
        $chunkHeader = fread($this->_fp, 8);
        
        while ($chunkHeader) {
            // Extract length and type from binary data
            $chunk = @unpack('Nsize/a4type', $chunkHeader);
                        
            // Store position into internal array
            if ($this->_chunks[$chunk['type']] === null)
                $this->_chunks[$chunk['type']] = array ();
            $this->_chunks[$chunk['type']][] = array (
                'offset' => ftell($this->_fp),
                'size' => $chunk['size']
            );

            //Skip to next chunk (over body and CRC)
            fseek($this->_fp, $chunk['size'] + 4, SEEK_CUR);

            //Read next chunk header
            $chunkHeader = fread($this->_fp, 8);
        } 
    }
    

    // Returns all chunk content of said type
    public function get_chunks($type) {
       
        if ($this->_chunks[$type] === null)
            return null;

        $chunks = array ();

        foreach ($this->_chunks[$type] as $chunk) {
            if ($chunk['size'] > 0) {
                fseek($this->_fp, $chunk['offset'], SEEK_SET);
                $chunks[] = fread($this->_fp, $chunk['size']);
            } else {
                $chunks[] = '';
            }
        }

        return $chunks;
    }
    
    public function write_metadata($pngField, $value){

        $content = $pngField . $value;
        
        $textChunks = null;
        $textChunks = $this->_chunks['tEXt'];        
        if ($textChunks !== null) //metadata chunks already exist - grab everything after it, make changes, append everything on the end
        {
            $fieldPresent = false;
            foreach($textChunks as $chunk)
            {
                fseek($this->_fp, $chunk['offset'], SEEK_SET);
                $entry = fread($this->_fp, $chunk['size']);
                $strpos = strpos($entry, $pngField);           
                if ($strpos === 0)
                {
                    $fieldPresent = true;
                    
                    //grab everything that comes after this
                    //get start position
                    fseek($this->_fp, $chunk['offset']+$chunk['size']+4, SEEK_SET);
                    $backupStart = ftell($this->_fp);
                    
                    //get end position
                    fseek($this->_fp, 0, SEEK_END);
                    $backupSize = ftell($this->_fp) - $backupStart;    
                    
                    //back to start and read everything up to the end
                    fseek($this->_fp, $backupStart, SEEK_SET);
                    $backup = fread($this->_fp, $backupSize);
                    
                    //now change the chunk
                    fseek($this->_fp, $chunk['offset']-8, SEEK_SET);
                    $pos = ftell($this->_fp);
                    $this->write_meta_chunk($pos, $content);
                    
                    //and now append everything back on the end
                    fwrite($this->_fp, $backup);
                    
                    //and truncate to actual length
                    $end = ftell($this->_fp);
                    ftruncate($this->_fp , $end);
                    
                    //finished
                    fclose($this->_fp); 
                    
                    break;
                }
            }
            if(!($fieldPresent))
            {
                //this field has yet to be found so we want to add it alongside existing tEXt chunks
                //get the position of the last tEXt chunk
                $chunk = end($textChunks);
                
                //grab everything that comes after this
                //get start position
                fseek($this->_fp, $chunk['offset']+$chunk['size']+4, SEEK_SET);
                $backupStart = ftell($this->_fp);
                
                //get end position
                fseek($this->_fp, 0, SEEK_END);
                $backupSize = ftell($this->_fp) - $backupStart;

                //back to start and read everything up to the end
                fseek($this->_fp, $backupStart, SEEK_SET);
                $backup = fread($this->_fp, $backupSize);
                
                //now add a new the chunk
                fseek($this->_fp, $backupStart, SEEK_SET);
                $pos = ftell($this->_fp);
                $this->write_meta_chunk($pos, $content);
                
                //and now append everything back on the end
                fwrite($this->_fp, $backup);

                //and truncate to actual length
                $end = ftell($this->_fp);
                ftruncate($this->_fp, $end);

                //finished
                fclose($this->_fp);
            }
        }
        else    //there are no metadata chunks so add some
        {
            //go to the IEND chunk
            $iend = $this->_chunks['IEND'][0];
            fseek($this->_fp, $iend['offset']-8, SEEK_SET);
            
            $pos = ftell($this->_fp);
            
            //write the header            
            $this->write_meta_chunk($pos, $content);
            
            //now write the end chunk
            $pos = ftell($this->_fp);
            $size = 0;
            $type = 'IEND';        
            $header = @pack('Na4', $size, $type);
            fwrite($this->_fp, $header, 8);
            
            //write the crc - based on the type bytes and content
            //first go to the latter 4 bytes of the header
            fseek($this->_fp, $pos+4, SEEK_SET);
            //read until the latter 4 header bytes and end of the content
            $crcString = fread($this->_fp, 4+$size);                 
            $crc = crc32($crcString);
            //write the crc
            fwrite($this->_fp, $crc, 4);
            
            //finished
            fclose($this->_fp);  
        }        
    }    
    
    public function write_meta_chunk($beginning, $content)
    {
        //write the header            
        $size = strlen($content);
        $type = 'tEXt';        
        $header = @pack('Na4', $size, $type);
        fwrite($this->_fp, $header, 8);
        fwrite($this->_fp, $content, $size);
            
        //write the crc - based on the type bytes and content
        //first go to the latter 4 bytes of the header
        fseek($this->_fp, $beginning+4, SEEK_SET);
        //read until the latter 4 header bytes and end of the content
        $crcString = fread($this->_fp, 4+$size);                 
        $crc = crc32($crcString);
        //write the crc
        fwrite($this->_fp, $crc, 4);
    }
    
    public function readField($field) {
        //first convert passed field in to PNG format
        $pngField = null;
        switch ($field){
            case "copyright":
                $pngField = "Copyright";
                break;
            case "artist":
                $pngField = "Author";
                break;
        }

        if ($pngField != null)
        {
            //default value
            $field = "No data";
            
            //get the value in this image
            $rawTextData = $this->get_chunks('tEXt');
            if($rawTextData !== null)
            {
                foreach($rawTextData as $metadataChunk)
                {
                    if (strpos($metadataChunk, $pngField) === 0)
                    {                    
                        $field = substr($metadataChunk, strlen($pngField));
                    }
                }     
            }
        }else{
            $field = "error: Invalid field";
        }        
        return $field;         
    }
}

?>