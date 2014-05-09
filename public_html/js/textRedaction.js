
//show the document's text content ready for redaction
function showText(){    
    
    var document = $('#main').data('doc');
    var text = document.doc;
    
    //update the view to display the text
    $view = clearView();
    for (i = 0; i < text.length; i++){
        switch(text[i].type){
            case "text":
                addPara(text[i], $view);
                break;
            case "heading":
                addHeading(text[i], $view);
                break;
        }
    }
    
    
    //update the sidebar to display navigable contents
    $sidebar = clearSidebar();
}

function addPara(para, $view){
    
}

function addHeading(heading, $view){
    
}


