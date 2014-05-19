<?php

/*
 * Receive necessary information to create a replacement redaction
 */

include '../redactor.php';

session_start();

$redactor = $_SESSION['redactor'];

$oldImage = $_GET['original'];

//remove the previous redaction associated with this image
$redactor->removeImageRedaction($oldImage);

$redactor->returnState();

?>