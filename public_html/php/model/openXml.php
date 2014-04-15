<?php

/* 
 * Defintions for the various classes that will be used to represent an 
 * office open xml document. Will likely include an image class (that should
 * probably have an interface in case images come from other document types in
 * the future) and an abstract class for office open xml documents that a Word
 * and PowerPoint class can both extend...
 */

/*
 * An interface for document images, should support for documents beyond
 * Office Open XML be implemented.
 */
interface RedactorImage{
    
    public function generateJSON();
    
}

/*
 * Represents an image from an Office Open XML document and can produce JSON
 * that can be sent to the page to render the representation of the document
 */
class OpenXmlImage implements RedactorImage{
        
}

/*
 * The shared properties of methods of representing an Office Open XML document
 */
abstract class OpenXmlDocument{
    
}

/*
 * A representationf of a .pptx
 */
class PowerPoint extends OpenXmlDocument{
    
}

/*
 * A representation of a .docx
 */
class Word extends OpenXmlDocument{
    
}



?>