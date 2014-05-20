<?php

/*
 * Pixelate a given image and return a link to the pixelated version 
 */

include '../debug/ChromePhp.php';

//get image info
$image = $_GET['image'];
$split = explode('.', $image);
$format = strtolower($split[1]);

//get references to the old image and a place for the new one
$oldPath = '../../' . $image;  
$newPath = '../../' . $split[0] . '_new.' . $split[1]; 
    
//create an image reader depending on format
switch ($format){
    case "jpeg":
        $img = imagecreatefromjpeg($oldPath);
        break;
    case "jpg":
        $img = imagecreatefromjpeg($oldPath);
        break;
    case "png":
        $img = imagecreatefrompng($oldPath);
        break;
}

//pixelate the image
if($img && imagefilter($img, IMG_FILTER_PIXELATE, 20, true))
{
    //write the file back again
    switch ($format)
    {
        case "jpeg":
            $img = imagejpeg($img, $newPath);
            break;
        case "jpg":
            $img = imagejpeg($img, $newPath);
            break;
        case "png":
            $img = imagepng($img, $newPath);
            break;
    }    
}

echo $_GET['callback'] . '(' . json_encode(substr($newPath, 6)) . ')';

?>