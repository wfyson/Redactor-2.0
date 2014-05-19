/*
 * Receives a document and displays the content to the user
 * Receives information about the document, plus a list of images
 */
function initDisplay(){
    //get the document 
    var document = $('#main').data('doc');   
    
    //get the redactions
    var paraRedactions = $('#main').data('paraRedactions');
    var imageRedactions = $('#main').data('imageRedactions');
    var totalNo = paraRedactions.length + imageRedactions.length;
    
    //first hide the file upload stuff
    $('#initial').hide();
    
    //show the banner controls
    $banner = clearBanner();
    $redactBtn = $('<button></button>');
    $redactBtn.addClass('btn btn-default');
    $redactBtn.append("Redact");
    
    //disable button if no redactions
    if (totalNo === 0){
        $redactBtn.addClass("disabled");        
    }

    $redactBtn.click(function(){
       commitRedactions(); 
    });
    
    $banner.append($redactBtn);
    
    //overview of redactions
    $overview = $('<h3></h3>');
    $overview.attr('id', 'overview');
    var redactText = " Redactions";
    if (totalNo === 1)
        redactText = " Redaction";    
    var overview = totalNo + redactText;       
    $overview.append(overview);    
    $banner.append($overview);
    
    //show basic sidebar information for the document
    $sidebar = clearSidebar();
    //$sidebar = $('#sidebar');    
    
    //thumbnail
    $thumbnail = $('<img></img>');
    $thumbnail.addClass('img-thumbnail');
    if (document.thumbnail === 'n/a'){
        defaultThumbnail($thumbnail, document.type);
    }else{
        $thumbnail.attr('src', document.thumbnail);
    }
    $sidebar.append($thumbnail);    
    
    //name of document
    $name = $('<h4></h4>');
    $name.addClass('doc-title');
    $name.append(document.title);
    $sidebar.append($name);
    
    //show the main view
    $view = clearView();
    
    //list the redactable elements
    $list = $('<ul></ul>');
    
    //if a document with text, show the text as the first entry
    if (document.type === "docx"){
        newText($list);        
    }
    
    //list the images    
    for(var i = 0; i < document.images.length; i++){
        newImage(document.images[i], i, document.images.length);
    }
    
    $view.append($list);
    
    //show the main view
    $('#main').show();
    
}

//shows a thumbnail based on doc type
function defaultThumbnail($thumbnail, type){
    if(type === "docx"){
        $thumbnail.attr('src', 'img/docx_thumb.png');
    }    
}

//shows a button for redacting text
function newText($list){    
    //a visible button
    $item = $('<li></li>');
    
    $textBox = $('<div></div>');
    $textBox.addClass('entry text-entry bg-info');
    
    //add an icon
    $imgPrev = $('<div></div>');
    $imgPrev.addClass('img-preview');
    $img = $('<img></img>');
    $img.attr('src', 'img/text.png');
    $imgPrev.append($img);
    
    //label
    $labelDiv = $('<div></div>');    
    $labelDiv.addClass('meta');  
    $label = $('<h3></h3>');
    $label.append("Redact Text...");
    $labelDiv.append($label);    
    
    //construct the entry
    $textBox.append($imgPrev).append($labelDiv);
    $item.append($textBox);
    $list.append($item);
        
    //needs some clicking functionality
    $item.click(function(){
        showText();
    });
}

//shows the images within the document so they can be selected for redaction
function newImage(image, i, total){
    
    i = i + 1;
    $item = $('<li></li>');    
    
    $imageBox = $('<div></div>');
    $imageBox.addClass('entry');
    
    //see if there is a redaction for this image
    var redaction = getRedaction(image);    
    
    //apply background based on licence information
    if (redaction !== null){                        
        $imageBox.addClass('bg-success');
        
        switch (redaction.type){
            case "replace":
                displayReplaceEntry(image, redaction);
                break;
            case "licence":
                displayLicenceEntry(image, redaction);
                break;                
        }
    }else{
        displayImageEntry(image);
    }
      
    //needs some clicking functionality
    $item.click({param1: i, param2: total}, function(event){
        showImage(image, event.data.param1, event.data.param2);        
    });
}

function displayImageEntry(image){
    
    //background
    var copyright = ["CC0", "CC BY", "CC BY-SA", "CC BY-ND", "CC BY-NC", "CC BY-NC-SA", "CC BY-NC-ND"];        
    if ($.inArray(image.copyright, copyright)){
        $imageBox.addClass('bg-danger');
    }else{
        $imageBox.addClass('bg-success');
    }
    
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
}

function displayReplaceEntry(image, redaction){
    //background
    $imageBox.addClass('bg-success');
    
    //add the old image
    $imgPrev = $('<div></div>');
    $imgPrev.addClass('img-preview');
    $img = $('<img></img>');
    $img.attr('src', (image.link));
    $imgPrev.append($img);
  
    //add the new image
    $newImgPrev = $('<div></div>');
    $newImgPrev.addClass('img-preview');
    $newImg = $('<img></img>');
    $newImg.attr('src', (redaction.newimage));
    $newImgPrev.append($newImg);
    
    //add the metadata
    $meta = $('<div></div>');
    $meta.addClass('meta');
    
    //new title
    $title = $('<span></span>');
    $titleLabel = $('<b>Title: </b>');  
    $title.append($titleLabel).append(redaction.newTitle);
    $meta.append($title);
    
    //new owner
    $owner = $('<span></span>');
    $ownerLabel = $('<b>Artist: </b>');  
    $owner.append($ownerLabel).append(redaction.owner);
    $meta.append($owner);
    
    //new licence
    $licence = $('<span></span>');
    $licenceLabel = $('<b>Licence: </b>');  
    $licence.append($licenceLabel).append(redaction.licence);
    $meta.append($licence);
    
    //construct the entry
    $imageBox.append($imgPrev);
    $imageBox.append($newImgPrev);
    $imageBox.append($meta);    
    $item.append($imageBox);
    $list.append($item);
    
}

function displayLicenceEntry(image, redaction){
    //background
    $imageBox.addClass('bg-success');
    
    //add the image
    $imgPrev = $('<div></div>');
    $imgPrev.addClass('img-preview');
    $img = $('<img></img>');
    $img.attr('src', (image.link));
    $imgPrev.append($img);
    
    //add the metadata
    $meta = $('<div></div>');
    $meta.addClass('meta');
    
    //new licence
    $licence = $('<span></span>');
    $licenceLabel = $('<b>Added Licence: </b>');  
    $licence.append($licenceLabel).append(redaction.licence);
    $meta.append($licence);
    
    //construct the entry
    $imageBox.append($imgPrev);
    $imageBox.append($meta);    
    $item.append($imageBox);
    $list.append($item);
    
}

//when displaying an image, use this function to check if it has a redaction or not
function getRedaction(image){
    var imageRedactions = $('#main').data('imageRedactions');
    var imagePath = image.name + '.' + image.format;
    for(var i = 0; i < imageRedactions.length; i++){
        var redaction = imageRedactions[i];
        
        if (redaction.original === imagePath){ //a match has been found
            return redaction;
        }
    }
    //no match was found
    return null;
}

//used to clear the banner when we want to put new content on it
function clearBanner(){
     $banner = $('#banner');
     $banner.removeClass();
     $banner.addClass('col-md-8');
     $banner.empty();
     return $banner;
}

//used to clear the sidebar when we want to put new content on it
function clearSidebar(){
     $sidebar = $('#sidebar');
     $sidebar.removeClass();
     $sidebar.addClass('col-md-3 col-md-offset-7');
     $sidebar.empty();
     return $sidebar;
}

//used to clear the view when we want to put new content on it
function clearView(){
    $view = $('#view');
    $view.removeClass();
    $view.addClass('col-md-8');
    $view.empty(); 
    return $view;
}

//ask the server to commit the redactions and present a link
function commitRedactions(){
    $.getJSON("../public_html/php/scripts/commit.php?callback=?",
    function(res) {
        $overview = $('#overview');
        $overview.empty();
        
        $link = $('<a></a>');
        $link.attr('href', res);
        $link.append("Click to download...");

        $overview.append($link);
    });
}