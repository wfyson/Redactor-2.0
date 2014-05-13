<?php

/*
 * Responsible for actually committing the redactions!!
 */

include '../redactor.php';

session_start();

$redactor = $_SESSION['redactor'];

$redactor->commitRedactions();

?>