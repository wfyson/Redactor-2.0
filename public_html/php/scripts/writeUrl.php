<?php

include '../redactor.php';

session_start();

$id = session_id();

$path = '../../sessions/' . $id . '/';

if (!file_exists($path)) {
    mkdir($path, 0777, true);
}

$url = $_GET['doc'];

$file = fopen($url,"rb");

$filename = basename($url);
$newfile = fopen($path . $filename, "wb");

if ($newfile)
{
    while (!feof($file))
    {
        // Write the url file to the directory.
        fwrite($newfile, fread($file, 1024 * 8), 1024 * 8);
    }
}

$target = $path . $filename;

//initialize the redactor by passing it the filepath to the newly uploaded file
$redactor = new Redactor($target);
$_SESSION['redactor'] = $redactor;

?>