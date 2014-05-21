<?php

/*
 * Receive necessary information to create a replacement redaction
 */

include '../redactor.php';

session_start();

$redactor = $_SESSION['redactor'];

$oldImage = $_GET['original'];
$newImage = $_GET['newimage'];
$licence = $_GET['licence'];
$caption = $_GET['caption'];

$title = $_GET['newtitle'];
$owner = $_GET['owner'];
$ownerUrl = $_GET['ownerurl'];
$imageUrl = $_GET['imageurl'];

//remove the previous redaction associated with this image
$redactor->removeImageRedaction($oldImage);

$newRedaction = new ReplaceRedaction($oldImage, $newImage, $licence, $caption, $title, $owner, $ownerUrl, $imageUrl);

//add the redaction to the redactor
$redactor->addImageRedaction($oldImage, $newRedaction);

$redactor->returnState();

?>