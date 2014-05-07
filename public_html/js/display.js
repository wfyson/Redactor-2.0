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
    $thumbnail.addClass('img-thumbnail');
    $thumbnail.attr('src', document.thumbnail);
    $sidebar.append($thumbnail);    
    
    //name of document
    $name = $('<h4></h4>');
    $name.addClass('doc-title');
    $name.append(document.title);
    $sidebar.append($name);
    
    //show the main view
    $view = $('#view');
    
    //if a document with text, show the text as the first entry
    if (document.type == ".docx"){
        
    }
    
    
    //list the images
    $list = $('<ul></ul>');
    document.images.forEach(newImage, $list);       
    $view.append($list);
    
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
    
    $item = $('<li></li>');
    
    $imageBox = $('<div></div>');
    $imageBox.addClass('img-entry');
    
    //add class based on licence information
    $imageBox.addClass('bg-danger');
    
    //add the image
    $imgPrev = $('<div></div>');
    $imgPrev.addClass('img-preview');
    $img = $('<img></img>');
    $img.attr('src', (image.link));
    $imgPrev.append($img);
    
    //add the metadata
    $meta = $('<div></div>');
    $meta.addClass('meta');
    
    //name
    $name = $('<span></span>');
    $nameLabel = $('<b>Title: </b>');  
    $name.append($nameLabel).append(image.name);
    $meta.append($name);
    
    //artist
    $artist = $('<span></span>');
    $artistLabel = $('<b>Artist: </b>');  
    $artist.append($artistLabel).append(image.artist);
    $meta.append($artist);
    
    //licence
    $licence = $('<span></span>');
    $licenceLabel = $('<b>Licence: </b>');  
    $licence.append($licenceLabel).append(image.copyright);
    $meta.append($licence);
    
    //construct the entry
    $imageBox.append($imgPrev);
    $imageBox.append($meta);    
    $item.append($imageBox);
    $list.append($item);
    
    //needs some clicking functionality
    
    
}