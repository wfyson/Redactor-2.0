<?php

/*
 * A reader for .pptx documents - also needs to read the size of slides so background
 * images can be redacted and captioned appropriately
 */
define("IMAGE_REL_TYPE", "http://schemas.openxmlformats.org/officeDocument/2006/relationships/image");

class PowerPointReader extends OpenXmlReader
{    
    private $relList = array();
    private $slideHeight;
    
    public function readPowerPoint()
    {                
        $this->zip = zip_open($this->file);        
        $zipEntry = zip_read($this->zip);
        while ($zipEntry != false)
        {
            //read through all the files, call appropriate functions for each            
            $entryName = zip_entry_name($zipEntry);
            
            //for image files
            if (strpos($entryName, 'ppt/media/') !== FALSE)
            {                
                $split = explode('.', basename($entryName));
                $this->imageLinks[$split[0]] = $this->readImage($entryName, $zipEntry);
            }     
            
            //for thumbnail file
            if (strpos($entryName, 'docProps/thumbnail') !== FALSE)
            {
                $this->thumbnail = $this->readImage($entryName, $zipEntry);
            }
            
            //for rels file
            if (strpos($entryName, 'ppt/slides/_rels/') !== FALSE)
            { 
                $this->relList = $this->readSlideImageRels($this->relList, $entryName, $zipEntry);                
            }
            
            //to get the slide height
            if (strpos($entryName, 'ppt/presentation.xml') !== FALSE)
            {
                $size = $this->readSlideSize($zipEntry);
                $this->slideWidth = $size[0];
                $this->slideHeight = $size[1];
            }
            
            $zipEntry = zip_read($this->zip);
        }
        
        //create an associative array of slides to rels
        //for each image, get the slides it is associated with 
        $this->slideRels = array();
        foreach($this->relList as $imageName => $rels)
        {                      
            //go through the rels for each image
            foreach($rels as $slideNo => $slideRel)
            {
                if(array_key_exists($slideNo, $this->slideRels))
                {
                    array_push($this->slideRels[$slideNo], $slideRel->relId);
                }
                else
                {
                    $this->slideRels[$slideNo] = array();
                    array_push($this->slideRels[$slideNo], $slideRel->relId);
                }
            }          
        }            
        
        //now update the slide rel associations with locations for each one
        $this->zip = zip_open($this->file);
        $zipEntry = zip_read($this->zip);
        while ($zipEntry != false)
        {
            //read through all the files, call appropriate functions for each            
            $entryName = zip_entry_name($zipEntry);
            
            //for image files
            if (strpos($entryName, 'ppt/slides/slide') !== FALSE)
            {                
                $this->relList = $this->readImageLocations($entryName, $zipEntry, $this->slideRels, $this->relList);
            }  
            
            $zipEntry = zip_read($this->zip);
        }          
        
        
        //construct and then return a powerpoint
        $powerpoint = new PowerPoint($this->docName, $this->imagePath, $this->file, $this->thumbnail,
                $this->imageLinks, $this->relList, $this->slideWidth, $this->slideHeight);
        
        return $powerpoint;        
    }
    
    /*
     * produce an associative array that matchs image names to slide, rel combinations
     * each of which will later be given a list of coordinates that indicate where that image
     * appears in the slide
     */
    public function readSlideImageRels($relList, $entryName, $zipEntry)
    {
        //get the slide number        
        $slideFile = basename($entryName);
        $slideNo = substr($slideFile, 0, strpos($slideFile, '.'));
        $no = substr($slideNo, 5);

        $rels = zip_entry_read($zipEntry, zip_entry_filesize($zipEntry));
        $xml = simplexml_load_string($rels);
        
        //loop through each of the rels listed
        for ($i = 0; $i < $xml->count(); $i++) {
            $record = $xml->Relationship{$i};
            $type = $record->attributes()->Type;
            $cmp = strcmp($type, constant("IMAGE_REL_TYPE"));
            if ($cmp == 0) {
                
                //get the rel id
                $id = $record->attributes()->Id;
                
                //get the image name
                $target = (string) $record->attributes()->Target;
                $imageName = basename($target);   
                
                //create a SlideRel
                $slideRel = new SlideRel((string) $id);
                //associate it with an image
                if (array_key_exists($imageName, $relList))
                {                    
                    $imageRels = $relList[$imageName];
                    $imageRels[$no] = $slideRel;
                    $relList[$imageName] = $imageRels;
                }
                else
                {
                    $imageRels = array();
                    $imageRels[$no] = $slideRel;
                    $relList[$imageName] = $imageRels;
                }
            }
        }
        //ChromePhp::log($relList);
        
        return $relList;
    }
    
    
    //Read the locations of images within a slide
    public function readImageLocations($entryName, $zipEntry, $slideRels, $relList)
    {        
        //get the slide number        
        $slideFile = basename($entryName);
        $slideNo = substr($slideFile, 0, strpos($slideFile, '.'));
        $no = substr($slideNo, 5);     
        
        //read the xml
        $slide = zip_entry_read($zipEntry, zip_entry_filesize($zipEntry));
        $xml = simplexml_load_string($slide);
        
        //get the rels for this slide
        $rels = $slideRels[$no];
        
        //get the pictures' blip elements, where the relIds are stored
        $pics = $xml->xpath('//p:pic');
        foreach($pics as $pic)
        {
            $blips = $pic->xpath('p:blipFill/a:blip');
            $blipRelId = $blips[0]->xpath('@r:embed');
            if (in_array($blipRelId[0], $rels))
            {
                //get position and size of this image
                $off = $pic->xpath('p:spPr/a:xfrm/a:off');                
                $ext = $pic->xpath('p:spPr/a:xfrm/a:ext');
                
                $x = $off[0]->xpath('@x');
                $y = $off[0]->xpath('@y');
                
                $cx = $ext[0]->xpath('@cx');
                $cy = $ext[0]->xpath('@cy');
                
                $position = new ImagePosition((string)$x[0], (string)$y[0], (string)$cx[0], (string)$cy[0]);

                $relList = $this->addPosition($relList, $no, $blipRelId[0], $position);                
            }
        }
        return $relList;
    }
    
    // adds position information to a slide/rel pairing    
    public function addPosition($relList, $slide, $relId, $position)
    {        
        foreach($relList as $imageName => $rels)
        {                      
            //go through the rels for each image
            foreach($rels as $slideNo => $slideRel)
            {
                if (($slideNo == $slide) && ($slideRel->relId == $relId))
                {
                    $slideRel->addPosition($position);
                    return $relList;
                }                               
            }          
        }
        //nothing happened (but this should never happen!)
        return $relList;
    }
    
    //get the slideh height to position captions when background images are replaced
    public function readSlideSize($zipEntry)
    {        
        $ppt = zip_entry_read($zipEntry, zip_entry_filesize($zipEntry));
        $xml = simplexml_load_string($ppt);
        
        $presentation = $xml->xpath('//p:presentation');
        $sldSz = $presentation[0]->xpath('p:sldSz');
       
        $cx = (string)$sldSz[0]->attributes()->cx;      
        $cy = (string)$sldSz[0]->attributes()->cy;      
        $result = array($cx, $cy);
        
        return $result; 
    }        
}

?>