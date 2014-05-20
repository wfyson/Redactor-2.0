<?php

/*
 * Receive necessary information to create a replacement redaction
 */

include '../redactor.php';

session_start();

$redactor = $_SESSION['redactor'];

$oldImage = $_GET['original'];
$newImage = $_GET['newimage'];

//remove the previous redaction associated with this image
$redactor->removeImageRedaction($oldImage);

$newRedaction = new ObscureRedaction($oldImage, $newImage);

//add the redaction to the redactor
$redactor->addImageRedaction($oldImage, $newRedaction);

$redactor->returnState();

?>