
//show the image ready for redaction
function showImage(image){    
    
    var document = $('#main').data('doc');         
    
    //update the banner
    $banner = clearBanner();
    $backBtn = $('<button></button>');
    $backBtn.addClass('btn btn-default');
    $backBtn.append("Save and Return");
    $backBtn.click(function(){
       saveImageRedaction(); 
    });        
    $banner.append($backBtn);
    
    //update the sidebar to display redaction options
    $sidebar = clearSidebar();
    $sidebar.addClass("image-sidebar");
    setupSidebar($sidebar, image);    
    
    //update the view to display the image
    $view = clearView();
    $view.addClass("img-view");         
    
    $image = $('<img></img>');
    $image.attr('src', image.link);
    
    $view.append($image);
    
    //save a reference to the image
    $view.data('image', image);
    
}

function setupSidebar($sidebar, image){
    
    //thumbnail
    $thumbnail = $('<img></img>');
    $thumbnail.addClass('img-thumbnail');
    $thumbnail.attr('src', image.link);
    $sidebar.append($thumbnail);
    
    $search = setupSearch(image);

    $licence = setupLicence(image);
    
    $obscure = setupObscure(image);
    
    $sidebar.append($search);
    $sidebar.append($licence);
    $sidebar.append($obscure);
}

function setupSearch(image){
    //image searches
    $search = $('<div></div>');
    $search.addClass('option-div');
    
    $searchTitle = $('<div></div>');
    $searchTitle.addClass("option-title");
    
    $searchHeading = $('<h4></h4>');
    $searchHeading.append("Image Search");
    
    $searchHelp = $('<span></span>');
    $searchHelp.addClass("glyphicon glyphicon-question-sign");
    $searchHelp.attr('data-toggle', 'tooltip');
    $searchHelp.attr('data-placement', 'right');
    $searchHelp.attr('title', 'Search for replacement images using Creative Commons licence search engines.');
    $searchHelp.tooltip();
    
    $searchTitle.append($searchHeading).append($searchHelp);
    
    //search
    $searchForm = $('<div></div>');
    $searchForm.attr('id', 'search-form');
    $searchForm.addClass('form-group');        
    $searchText = $('<input>');
    $searchText.attr('id', 'search-txt');
    $searchText.addClass('form-control');
    $searchText.attr('type', 'text');
    $searchForm.append($searchText);
    
    
    //commercial usage
    $commercial = $('<div></div>');
    $commercial.addClass('checkbox');
    $commercialLabel = $('<label></label>');
    $commercialCheck = $('<input>');
    $commercialCheck.attr('id', 'commercial-check');
    $commercialCheck.attr('type', 'checkbox');
    $commercialLabel.append($commercialCheck);
    $commercialLabel.append("Commercial");    
    $commercial.append($commercialLabel);
    
    //derivate usage
    $derivative = $('<div></div>');    
    $derivative.addClass('checkbox');
    $derivativeLabel = $('<label></label>');
    $derivativeCheck = $('<input>');
    $derivativeCheck.attr('id', 'derivative-check');
    $derivativeCheck.attr('type', 'checkbox');
    $derivativeLabel.append($derivativeCheck);
    $derivativeLabel.append("Derivatives");    
    $derivative.append($derivativeLabel);
    
    //search button
    $group = $('<div></div>');
    $group.addClass('btn-group');
    $btn = $('<button>');
    $btn.addClass('btn btn-default dropdown-toggle');
    $btn.attr('data-toggle', 'dropdown');
    $btn.append("Search ");
    $caret = $('<span></span>');
    $caret.addClass('caret');
    $btn.append($caret);
    
    $options = $('<ul></ul>');
    $options.addClass('dropdown-menu');
    $options.attr('role', 'menu');
    
    $flickr = $('<li></li>');
    $flickrLink = $('<a></a>');
    $flickrLink.attr('href', '#');
    $flickrLink.append("Flickr");
    $flickr.append($flickrLink);
    $options.append($flickr);
    
    $flickrLink.click(function(){
       imageSearch("flickr");
    });
    
    $google = $('<li></li>');
    $googleLink = $('<a></a>');
    $googleLink.attr('href', '#');
    $googleLink.append("Google");
    $google.append($googleLink);
    $options.append($google);
    
    $googleLink.click(function(){
       imageSearch("google");
    });
    
    $group.append($btn);
    $group.append($options);
    
    $loading = $('<img></img>');
    $loading.attr('id', 'loading');
    $loading.attr('src', 'img/loading.gif');

    $search.append($searchTitle);
    $search.append($searchForm);
    $search.append($commercial);
    $search.append($derivative);
    
    $submit = $('<div></div>');
    $submit.addClass('submit');
    $submit.append($group).append($loading);   
    $search.append($submit);
    
    return $search;
}

function setupLicence(image){
    //add a licence option
    $licence = $('<div></div>');
    $licence.addClass('option-div');
    
    $licenceTitle = $('<div></div>');
    $licenceTitle.addClass("option-title");
    
    $licenceHeading = $('<h4></h4>');
    $licenceHeading.append("Image Search");
    
    $licenceHelp = $('<span></span>');
    $licenceHelp.addClass("glyphicon glyphicon-question-sign");
    $licenceHelp.attr('data-toggle', 'tooltip');
    $licenceHelp.attr('data-placement', 'right');
    $licenceHelp.attr('title', 'Choose a Creative Commons licence to apply to the image.');
    $licenceHelp.tooltip();
    
    $licenceTitle.append($licenceHeading).append($licenceHelp);
    
    $licenceSelect = $('<select></select>');
    $licenceSelect.addClass('form-control');
    var licences = ["CC0", "CC BY", "CC BY-SA", "CC BY-ND", "CC BY-NC", "CC BY-NC-SA", "CC BY-NC-ND"];
    for (var i = 0; i < licences.length; i++){
        $option = $('<option></option>');
        $option.append(licences[i]);
        $licenceSelect.append($option);
    }    
    $licence.append($licenceTitle);
    $licence.append($licenceSelect);
    
    return $licence;
}

function setupObscure(image){
    //obscure the image
    $obscure = $('<div></div>');
    $obscure.addClass('option-div last');
    
    $obscureTitle = $('<div></div>');
    $obscureTitle.addClass("option-title");

    $obscureHeading = $('<h4></h4>');
    $obscureHeading.append("Image Search");

    $obscureHelp = $('<span></span>');
    $obscureHelp.addClass("glyphicon glyphicon-question-sign");
    $obscureHelp.attr('data-toggle', 'tooltip');
    $obscureHelp.attr('data-placement', 'right');
    $obscureHelp.attr('title', 'Trnasform the image to obscure its content.');
    $obscureHelp.tooltip();

    $obscureTitle.append($obscureHeading).append($obscureHelp);
    
    $obscureBtn = $('<button></button>');
    $obscureBtn.addClass('btn btn-default');
    $obscureBtn.attr('type', 'button');
    $obscureBtn.append("Obscure");
    
    $obscure.append($obscureTitle);
    $obscure.append($obscureBtn);
    
    return $obscure;
}

function imageSearch(engine){
    
    //first check there are search terms entered!!
    var tags = $('#search-txt').val();    
    if (tags === ""){
        console.log("argghh");
        $('#search-form').addClass("has-error");
    }else{
    
        //show loading icon
        $('#loading').show();
        
        //remove error class if present
        $('#search-form').removeClass("has-error");

        //get image search engine to use
        var url;
        switch (engine) {
            case "flickr":
                url = '../public_html/php/scripts/flickr.php?callback=?'
                break;
            case "google":
                url = '../public_html/php/scripts/google.php?callback=?';
                break;
        }
        
        var commercial = $('#commercial-check').is(':checked');
        var derivative = $('#derivative-check').is(':checked');

        //ping search request off to the server
        $.getJSON(url, {tags: tags, com: commercial, derv: derivative, page: 1},
        function(res) {
            displaySearchResults(res.results);
        });
    }
}

function displaySearchResults(results){
    
    console.log(results);
    
    //hide the loading icon
    $('#loading').hide();
    
    $view = clearView();
    $view.addClass('img-view');
    
    $row = $('<div></div>');
    $row.addClass('image-row');
    //go through the first row of images
    for(var i = 0; i < 4; i++){
       
        if (i < results.length){
            
            var result = results[i];
            
            $image = $('<div></div>');
            $image.addClass('search-prev');
            
            $img = $('<img></img>');                 
            $img.attr('src', result.sizes.Small);
            
            $image.click(function(){
                selectNewImage(result);
            });
            
            $image.append($img);
            $row.append($image);                        
        }                        
    }
    
    $view.append($row);
    
    //and now the second row
    $secondRow = $('<div></div>');
    $secondRow.addClass('image-row');
    for(var i = 4; i < 8; i++){
       
        if (i < results.length){
            
            var result = results[i];
            
            $image = $('<div></div>');
            $image.addClass('search-prev');
            
            $img = $('<img></img>');            
            
            $img.attr('src', result.sizes.Small);
            
            $image.click(function(){
                selectNewImage(result);
            });
            
            $image.append($img);
            $secondRow.append($image);                        
        }                        
    }
    
    $view.append($secondRow);
    
    //and now some controls
    $controlRow = $('<div></div>');
    $controlRow.addClass('control-row');
    
    $prevBtn = $('<button></button>');
    $prevBtn.addClass('btn btn-default prev');
    $prevBtn.append("Previous");
    
    $nextBtn = $('<button></button>');
    $nextBtn.addClass('btn btn-default next');
    $nextBtn.append("Next");
    
    $controlRow.append($prevBtn).append($nextBtn);
    $view.append($controlRow);
}

function selectNewImage(image){

    //setup the view
    $view = clearView();    
    $view.addClass('new-img-view');

    //TODO - a function to get the largest image size link available (to guarantee a valid option is being presented for this image)
    var newLink = getLargestSize(image);
    
    //display the new image    
    $newImage = $('<img></img>');
    $newImage.attr('src', newLink);
    $view.append($newImage);
    
    //and display some metadata about it... (licence, link to the original, author, etc.)
  
    //get old image
    var oldImage = $view.data('image');
    console.log(oldImage);
    var oldImagePath = oldImage.name + '.' + oldImage.format;
    
    //generate a caption
    var caption = image.title + ", " + image.owner + ", " + image.licence;
    
    //store the required information to make a redaction of this type 
    var replaceRedaction = new ReplaceRedaction(oldImagePath, image.sizes.Large, caption);
    
    $view.data('redaction', replaceRedaction);
}

function getLargestSize(image){
    var sizes = image.sizes;
    if (sizes.Large !== undefined){
        return sizes.Large;
    }
    if (sizes.Medium_800 !== undefined){
        return sizes.Medium_800;
    }
    if (sizes.Medium_640 !== undefined){
        return sizes.Medium_640;
    }
    if (sizes.Medium !== undefined){
        return sizes.Medium;
    }
    if (sizes.Small_320 !== undefined){
        return sizes.Small_320;
    }
    if (sizes.Small !== undefined){
        return sizes.Small;
    }
}

function saveImageRedaction(){
    
    $view = $('#view');

    var redaction = $view.data('redaction');

    //ping redaction off to the server
    $.getJSON("../public_html/php/inputs/imageRedaction.php?callback=?",
    {oldimage: redaction.oldImage, newimage: redaction.newImage, 
        caption: redaction.caption, type: redaction.type},
    function(res) {
        handleResult(res[0], res[1]);
    });

}

/*
 * Javascript objects for storing information about the image's redaction
 */
function ReplaceRedaction(oldImage, newImage, caption) {
    
    var self = this;
    self.oldImage = oldImage;
    self.newImage = newImage;
    self.caption = caption;
    self.type = "replace";
}