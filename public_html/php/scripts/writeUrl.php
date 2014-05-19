<?php

include '../redactor.php';

session_start();

$id = session_id();

$url = $_GET['doc'];
$filename = basename($url);

$path = '../../sessions/' . $id . '/' . str_replace('.', '_', $filename) . '/';

ChromePhp::log($path);

if (!file_exists($path)) {
    mkdir($path, 0777, true);
}

$file = fopen($url,"rb");


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
?>