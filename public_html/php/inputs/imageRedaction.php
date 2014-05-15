<?php

/*
 * Receive necessary information to create a replacement redaction
 */

include '../redactor.php';

session_start();

$redactor = $_SESSION['redactor'];

$oldImage = $_GET['oldimage'];
$newImage = $_GET['newimage'];
$caption = $_GET['caption'];
$type = $_GET['type'];

//remove the previous redaction associated with this image
$redactor->removeImageRedaction($oldImage);

switch ($type){
    case "replace":
        $newRedaction = new ReplaceRedaction($oldImage, $newImage, $caption);
    break;

}

//add the redaction to the redactor
$redactor->addImageRedaction($oldImage, $newRedaction);

$redactor->returnState();

?>