
//show the document's text content ready for redaction
function showText(){    
    
    //get the document 
    var document = $('#main').data('doc');
    var text = document.doc;
    
    //get the redactions
    var redactions = $('#main').data('paraRedactions');
    
    //add a back button
    $banner = clearBanner();
    $backBtn = $('<button></button>');
    $backBtn.addClass('btn btn-default');
    $backBtn.append("Save and Return");
    $backBtn.click(function(){
       saveRedactions(); 
    });        
    $banner.append($backBtn);
    
    //add overview
    $overview = $('<h3></h3>');
    $overview.attr('id', 'text-overview');
    $banner.append($overview);
    
    
    //update the sidebar to display navigable contents
    $sidebar = clearSidebar();
    $sidebar.addClass("text-sidebar");
    
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
    
    //apply any redactions which may be present already
    for (i = 0; i < redactions.length; i++){
        var redaction = redactions[i];
       
        if(redaction.type === "para"){
            $checkbox = $('#check-' + redaction.value);
            $checkbox.click();
        } 
    }
    updateTextRedactions();
    
}

//takes an id so later we can see which paragraphs have been chosen for redaction
function makeCheckBox($div, id){
    
    //make a check box
    $check = $('<input>');
    $check.addClass('check');
    $check.attr('id', 'check-' + id);
    $check.attr('type', 'checkbox');
    $check.attr('data-id', id);
    $check.prop("checked", true);
    
    //apply some functionality
    $check.change(function(){    
        if($(this).is(":checked")) {
            $div.removeClass("redact");         
        }else{
            $div.addClass("redact");
        }        
        updateTextRedactions();
    });
    return $check;    
}

function addPara(para, $view){       
    
    if (para.text !== "") {

        $paraDiv = $('<div></div>');

        $para = $('<p></p>');
        $para.append(para.text);

        $check = makeCheckBox($paraDiv, para.id);
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
    
    $check = makeCheckBox($headingDiv, heading.id);
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
    
    $check = makeCheckBox($captionDiv, caption.id);
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
    
    if (image.caption !== ''){
        
        $bold = $('<b></b>');
        $bold.append(image.caption);    
        $para.append('<br></br>').append($bold);
    }
    
    $check = makeCheckBox($imageDiv, image.id);
    $imageDiv.append($check);
    
    $imageDiv.append($para);    
    $view.append($imageDiv); 
}

/*
 * get all the check boxes which have been clicked and for eachget the id to
 * create a new paraRedaction in the PHP side of things
 */ 
function saveRedactions(){
    
    //get the unticked boxes
    $checked = $('.redact .check');    
    
    //get a list  of the selected paras
    var redactIds = new Array();
    $checked.each(function(){
       redactIds.push($(this).data('id'));
    });
    
    //ping list off to the server
    $.getJSON("./php/inputs/paraRedaction.php?callback=?", {ids: redactIds},
    function(res) {
        handleResult(res[0], res[1]);
    });
}

function updateTextRedactions(){
    
    $checkboxes = $('.check');
    $checked = $('.redact .check');
    
    var percentage = (($checked.length / $checkboxes.length) * 100).toFixed(2);
    
    var redactText = " Redactions (";
    if ($checked.length === 1)
        redactText = " Redaction (";
    
    var overview = $checked.length + redactText + percentage + "%)";   
    $('#text-overview').empty();    
    $('#text-overview').append(overview);
}