<?php

/*
 * Receive necessary information to create a replacement redaction
 */

include '../redactor.php';

session_start();

$redactor = $_SESSION['redactor'];

$oldImage = $_GET['original'];
$licence = $_GET['licence'];

//remove the previous redaction associated with this image
$redactor->removeImageRedaction($oldImage);

$newRedaction = new LicenceRedaction($oldImage, $licence);

//add the redaction to the redactor
$redactor->addImageRedaction($oldImage, $newRedaction);

$redactor->returnState();

?>