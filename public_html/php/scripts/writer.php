<?php

$id = $_POST['id'];

$path = '../../sessions/' . $id . '_' . str_replace('.', '_', $_REQUEST['name']) . '_';

if (!file_exists($path)) {
    //mkdir($path, 0777, true);
}

if (!isset($_REQUEST['name']))
    throw new Exception('Name required');
if (!preg_match('/^[-a-z0-9_][-a-z0-9_.]*$/i', $_REQUEST['name']))
    throw new Exception('Name error');

if (!isset($_REQUEST['index']))
    throw new Exception('Index required');
if (!preg_match('/^[0-9]+$/', $_REQUEST['index']))
    throw new Exception('Index error');

if (!isset($_FILES['file']))
    throw new Exception('Upload required');
if ($_FILES['file']['error'] != 0)
    throw new Exception('Upload error');

$target = $path . $_REQUEST['name'] . '-' . $_REQUEST['index'];

move_uploaded_file($_FILES['file']['tmp_name'], $target);

// Might execute too quickly.
sleep(1);
?>