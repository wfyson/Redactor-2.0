<?php

/*
 * Receive text redactions from the client and update the redactor 
 */

include '../redactor.php';

session_start();

$ids = $_GET['ids'];
$redactor = $_SESSION['redactor'];

//remove all previously stored redactions
$redactor->removeParaRedactions();

if ($ids !== null)
{
    foreach ($ids as $id){           
        $redaction = new ParaRedaction($id);    
        $redactor->addRedaction($redaction);    
    }
}

$redactor->returnState();

?>