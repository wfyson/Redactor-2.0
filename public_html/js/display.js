/*
 * Receives a document and displays the content to the user
 * Receives information about the document, plus a list of images
 */
function initDisplay(document){
    console.log(document);
    
    //first hide the file upload stuff
    $('#initial').hide();
    
    //show basic sidebar information for the document
    $sidebar = $('#sidebar');
    
    //thumbnail
    $thumbnail = $('<img></img>');
    $thumbnail.attr('src', document.thumbnail);
    $sidebar.append($thumbnail);    
    
    //name of document
    $name = $('<p></p>');
    $name.append(document.title);
    $sidebar.append($name);
    
    //show the main view
    $view = $('#view');
    
    //if a document with text, show the text as the first entry
    if (document.type == ".docx"){
        
    }
    
    
    //loop through images
    document.images.forEach(newImage, $view);    
    
    //show the main view
    $('#main').show();
    
}

//shows a button for redacting text
function newText(){
    
    //a visibile button
    
    //needs some clicking functionality
}

//shows the images within the document so they can be selected for redaction
function newImage(image){
    
    $imageBox = $('<div></div>');
    $imageBox.addClass('image-box');
    
    $img = $('<img></img>');
    $img.attr('src', (image.link));
    
    $imageBox.append($img);
    $view.append($imageBox);
    
    //add a class based on licence information
    
    //needs some clicking functionality
    
    
}