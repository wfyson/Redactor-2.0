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
switch ($format) {
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
$size = getimagesize($oldPath);
$height = $size[1];
$width = $size[0];
$pixelate_x = 40;
$pixelate_y = 40;
// start from the top-left pixel and keep looping until we have the desired effect
for ($y = 0; $y < $height; $y += $pixelate_y + 1) {

    for ($x = 0; $x < $width; $x += $pixelate_x + 1) {
        // get the color for current pixel
        $rgb = imagecolorsforindex($img, imagecolorat($img, $x, $y));

        // get the closest color from palette
        $color = imagecolorclosest($img, $rgb['red'], $rgb['green'], $rgb['blue']);
        imagefilledrectangle($img, $x, $y, $x + $pixelate_x, $y + $pixelate_y, $color);
    }
}


//write the file back again
switch ($format) {
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


echo $_GET['callback'] . '(' . json_encode(substr($newPath, 6)) . ')';
?>