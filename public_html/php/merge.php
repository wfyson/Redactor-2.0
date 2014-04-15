<?php

/*
 * Merge the slices of a file together, before initializing the redactor...
 */
include 'ChromePhp.php';
//example for logging: ChromePhp::log('Hello console!');

include 'redactor.php';

session_start();

$id = session_id();

$path = $id . '/';

if(!isset($_REQUEST['name'])) throw new Exception('Name required');
if(!preg_match('/^[-a-z0-9_][-a-z0-9_.]*$/i', $_REQUEST['name'])) throw new Exception('Name error');

if(!isset($_REQUEST['index'])) throw new Exception('Index required');
if(!preg_match('/^[0-9]+$/', $_REQUEST['index'])) throw new Exception('Index error');

$target = $path . $_REQUEST['name'];
$dst = fopen($target, 'wb');

for($i = 0; $i < $_REQUEST['index']; $i++) {
    $slice = $target . '-' . $i;
    $src = fopen($slice, 'rb');
    stream_copy_to_stream($src, $dst);
    fclose($src);
    unlink($slice);
}

fclose($dst);

//initialize the redactor by passing it the filepath to the newly uploaded file
$redactor = new Redactor($target);
$_SESSION['redactor'] = $redactor;

?>