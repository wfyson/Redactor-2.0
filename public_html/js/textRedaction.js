
//show the document's text content ready for redaction
function showText(){    
    
    var document = $('#main').data('doc');
    var text = document.doc;
    
    //update the sidebar to display navigable contents
    $sidebar = clearSidebar();
    $sidebar.removeClass("col-md-offset-1");
    $sidebar.addClass("text-sidebar col-md-offset-7");
    
    //update the view to display the text
    $view = clearView();
    $view.addClass("text-view");
    
    for (i = 0; i < text.length; i++){
        switch(text[i].type){
            case "text":
                addPara(text[i], $view);
                break;
            case "heading":
                addHeading(text[i], $view, $sidebar);
                break;
            case "caption":
                addCaption(text[i], $view);
                break;
            case "image":
                addImage(text[i], $view);
                break;
        }
    }        
}

//takes an id so later we can see which paragraphs have been chosen for redaction
function makeCheckBox($div, $id){
    
    //make a check box
    $check = $('<input>');
    $check.addClass('check');
    $check.attr('type', 'checkbox');
    
    //apply some functionality
    $check.change(function(){        
        if($(this).is(":checked")) {
            $div.addClass("redact");           
        }else{
            $div.removeClass("redact");
        }        
    });
    return $check;    
}

function addPara(para, $view){   
    
    if (para.text !== "") {

        $paraDiv = $('<div></div>');

        $para = $('<p></p>');
        $para.append(para.text);

        $check = makeCheckBox($paraDiv);
        $paraDiv.append($check);
        
        $paraDiv.append($para);
        $view.append($paraDiv);
    }
}

function addHeading(heading, $view, $sidebar){

    //add the heading to the main view
    var htmlHeading = heading.level + 1;
    
    $headingDiv = $('<div></div>');   
    $headingDiv.addClass('heading');
    $heading = $('<h' + htmlHeading + '></h' + htmlHeading + '>');
    $heading.attr('id', heading.id);
    $heading.append(heading.text);  
    
    $check = makeCheckBox($headingDiv);
    $headingDiv.append($check);
    
    $headingDiv.append($heading);    
    $view.append($headingDiv);
    
    //add the heading to the table of contents
    $linkP = $('<p></p>');
    $linkP.addClass('indent-' + htmlHeading);
    
    $linkA = $('<a></a>');
    $linkA.attr('href', '#' + heading.id);
    $linkA.append(heading.text);
    
    $linkP.append($linkA);
    $sidebar.append($linkP);       
}

function addCaption(caption, $view){ 
    
    $captionDiv = $('<div></div>');    
    $para = $('<p></p>');

    $bold = $('<b></b>');
    $bold.append(caption.text);
    
    $para.append($bold);
    
    $check = makeCheckBox($captionDiv);
    $captionDiv.append($check);
    
    $captionDiv.append($para);    
    $view.append($captionDiv);    
}

function addImage(image, $view){
    
    $imageDiv = $('<div></div>');    
    $para = $('<p></p>');

    $img = $('<img></img>');
    $img.attr('src', image.link);
    
    $para.append($img);
    
    $check = makeCheckBox($imageDiv);
    $imageDiv.append($check);
    
    $imageDiv.append($para);    
    $view.append($imageDiv); 
}


