<?php

/* 
 * Writers for various openXml documents - may share some bits, but actually a bit
 * unlikely. Will need to be given a copy of the original file and a list of
 * changes to be implemented * 
 */

/*
 * An interface for defining a writer for any file inputs
 */
interface DocumentWriter
{
    
}

/*
 * Common elements that occur for writing any documents using the Office Open XML
 * format are implemented here
 */

abstract class OpenXmlWriter
{
    protected $file;
    protected $changes;

    public function __construct($file, $changes)
    {

        $this->file = $file;
        $this->changes = $changes;
    }
    
}

?>