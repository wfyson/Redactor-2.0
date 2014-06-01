<?php

include '../debug/ChromePhp.php';

session_start();

$id = (string)session_id();

$files = scandir("../../sessions/");

foreach($files as $file){ // iterate files
    if ((strpos($file, "images") !== FALSE) && (strpos($file, $id) !== FALSE))
        unlink('../../sessions/' . $file);
}

session_destroy();
?>