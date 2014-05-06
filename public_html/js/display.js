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
    
   
    
;
    
    
    
    
    //show the main view
    $('#main').show();
    
}