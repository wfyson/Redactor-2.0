
//show the image ready for redaction
function showImage(image){    
    
    var document = $('#main').data('doc');         
    var redaction = getRedaction(image);
    
    //save a reference to the image
    $view.data('image', image); 
    
    //setup the banner
    $banner = clearBanner();
    $backBtn = $('<button></button>');
    $backBtn.addClass('btn btn-default');
    $backBtn.append("Save and Return");
    $backBtn.click(function(){
       saveImageRedaction(); 
    });        
    $overview = $('<h3></h3>');
    $overview.attr('id', 'image-overview');
    $banner.append($backBtn).append($overview);
    
    //update the GUI
    updateGUI(redaction);
    
    //update the sidebar to display redaction options
    $sidebar = clearSidebar();
    $sidebar.addClass("image-sidebar");
    setupSidebar($sidebar, image);            
}

function updateGUI(redaction){                
    var image = $('#view').data('image');

    if (redaction === null){
        //overview
        updateImageOverview("Redacting Image", false);
        
        //view
        displayImage(image);
    }else{
        if(redaction.type === "replace"){
            //overview
            var heading = 'Replace with "' + redaction.newTitle + '"';
            updateImageOverview(heading, true);           
            
            //view
            console.log(redaction);
            displayNewImage(redaction.newimage, redaction.newTitle, redaction.owner, redaction.imageUrl, redaction.licence);            
        }
        
        if(redaction.type === "licence"){
            //overview
            var heading = 'Add new CC licence';
            updateImageOverview(heading, true);           
            
            //view
            displayNewLicence(image, redaction.licence);     
  
        }
    }    
}

//updates the banner with the desired heaing and with cancel button if required
function updateImageOverview(heading, cancel){
    //clear what's already there
    $('#banner .cancel-btn').remove();        
    $heading = $('#image-overview');
    $heading.empty();
    
    //update heading
    $heading.append(heading);

    //add cancel button
    if(cancel){
        $cancelBtn = $('<button></button>');
        $cancelBtn.addClass('btn btn-danger cancel-btn');
        $cancelBtn.append("Cancel");
        $('#banner').append($cancelBtn);     
        $cancelBtn.click(function(){
           cancelRedaction(); 
        });
    }
}

function displayImage(image){
    $view = clearView();
    $view.addClass("img-view");             
    
    $image = $('<img></img>');
    $image.attr('src', image.link);
    
    $view.append($image);    
}

//gui stuff
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

//gui stuff
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
        imageSearch("flickr", 1);
    });
    
    $google = $('<li></li>');
    $googleLink = $('<a></a>');
    $googleLink.attr('href', '#');
    $googleLink.append("Google");
    $google.append($googleLink);
    $options.append($google);
    
    $googleLink.click(function(){
       imageSearch("google", 1);
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

//gui stuff
function setupLicence(image){
    //add a licence option
    $licence = $('<div></div>');
    $licence.addClass('option-div');
    
    $licenceTitle = $('<div></div>');
    $licenceTitle.addClass("option-title");
    
    $licenceHeading = $('<h4></h4>');
    $licenceHeading.append("Add CC Licence");
    
    $licenceHelp = $('<span></span>');
    $licenceHelp.addClass("glyphicon glyphicon-question-sign");
    $licenceHelp.attr('data-toggle', 'tooltip');
    $licenceHelp.attr('data-placement', 'right');
    $licenceHelp.attr('title', 'Choose a Creative Commons licence to apply to the image.');
    $licenceHelp.tooltip();
    
    $licenceTitle.append($licenceHeading).append($licenceHelp);
    
    $licenceSelect = $('<select></select>');
    $licenceSelect.attr('id', 'licence-select');
    $licenceSelect.addClass('form-control');
    var licences = ["Select a Licence", "CC0", "CC BY", "CC BY-SA", "CC BY-ND", "CC BY-NC", "CC BY-NC-SA", "CC BY-NC-ND"];
    for (var i = 0; i < licences.length; i++){
        $option = $('<option></option>');
        $option.append(licences[i]);
        $licenceSelect.append($option);
    }    
    $licenceSelect.change(function(){
        selectNewLicence($(this).val());
    });
                
    $licence.append($licenceTitle);
    $licence.append($licenceSelect);
    
    return $licence;
}

//gui stuff
function setupObscure(image){
    //obscure the image
    $obscure = $('<div></div>');
    $obscure.addClass('option-div last');
    
    $obscureTitle = $('<div></div>');
    $obscureTitle.addClass("option-title");

    $obscureHeading = $('<h4></h4>');
    $obscureHeading.append("Obscure Image");

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

//perform a search based on form input
function imageSearch(engine, page){
    
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
        $.getJSON(url, {tags: tags, com: commercial, derv: derivative, page: page},
        function(res) {
            displaySearchResults(res.results, res.page, res.total, res.next, engine);
        });
    }
}

//displays results from a replace image search
function displaySearchResults(results, page, total, next, engine){
    
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
            
            $image.click({param1: result}, function(event) {
                selectNewImage(event.data.param1);
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
            
            $image.click({param1: result}, function(event) {
                selectNewImage(event.data.param1);
            });
            
            $image.append($img);
            $secondRow.append($image);                        
        }                        
    }
    
    $view.append($secondRow);
    
    //and now some controls
    $controlRow = $('<div></div>');
    $controlRow.addClass('control-row');
    
    //previous button
    $prevBtn = $('<button></button>');
    $prevBtn.addClass('btn btn-default prev');
    $prevBtn.append("Previous");
    
    if(page === 1){
        $prevBtn.addClass('disabled');
    }
    
    $prevBtn.click(function(){
        page = page - 1;
        imageSearch(engine, page);
    });
    
    //next button
    $nextBtn = $('<button></button>');
    $nextBtn.addClass('btn btn-default next');
    $nextBtn.append("Next");
    
    if(!next){
        $nextBtn.addClass('disabled');
    }
    
    $nextBtn.click(function(){
        page = page + 1;
        imageSearch(engine, page);
    });
    
    //progress through results
    var start = ((page-1) * 8) + 1;
    var end = start + 7;
    var resultsStr = start +  " - " + end + " of " + total + ".";
    $total = $('<div></div>');
    $total.addClass('results-progress');
    $total.append(resultsStr);
    
    $controlRow.append($prevBtn).append($nextBtn).append($total);
    $view.append($controlRow);
}

//a replacement image has been selected
function selectNewImage(image){
    console.log(image);
    
    //get the appropriate link for the new image
    var newLink = getLargestSize(image);
    
    //get old image
    var oldImage = $view.data('image');
    var oldImagePath = oldImage.name + '.' + oldImage.format;
    
    //generate a caption
    var caption = image.title + ", " + image.owner + ", " + image.licence;
    
    //store the required information to make a redaction of this type 
    var replaceRedaction = new ReplaceRedaction(oldImagePath, newLink, image.licence, caption, image.title, image.owner, image.url);    
    $view.data('redaction', replaceRedaction);
    
    //update the GUI
    updateGUI(replaceRedaction);    
}
function displayNewImage(newLink, title, owner, imageUrl, licence){
    
    //setup the view
    $view = clearView();    
    $view.addClass('new-img-view');        
    
    //display the new image    
    $newImage = $('<img></img>');
    $newImage.attr('src', newLink);
    $view.append($newImage);
    
    //and display some metadata about it... (licence, link to the original, author, etc.)
    $metadata = $('<div></div>');
   
    $title = $('<span></span>');
    $title.append(title);
    
    $owner = $('<span></span>');
    $owner.append(owner);
    
    $link = $('<span></span>');
    $link.append(imageUrl);
    
    $licence = $('<span></span>');
    $licence.append(licence);
  
    $metadata.append($title).append($owner).append($link).append($licence);
    $view.append($metadata);
}

//gets a link for an image when a range are available
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

//create a licence redaction
function selectNewLicence(licence){  
    
    if (licence !== "Select a Licence"){
    
        var image = $view.data('image');

        //store the required information to make a redaction of this type 
        var oldImagePath = image.name + '.' + image.format;
        var licenceRedaction = new LicenceRedaction(oldImagePath, licence);
        $view.data('redaction', licenceRedaction);

        //update GUI
        updateGUI(licenceRedaction);    
    }
}
function displayNewLicence(image, licence){
    $view = clearView();
    $view.addClass('new-licence-view');
        
    //show the old image
    $image = $('<img></img>');   
    $image.attr('src', image.link);
    
    //show the new licence
    $licenceDiv = $('<div></div>');
    $licenceDiv.addClass('new-licence');
    $licenceTitle = $('<h4></h4>');
    $licenceTitle.append('Licence added:');
    $licenceVal = $('<h3></h3>');
    $licenceVal.append(licence);
    $licenceDiv.append($licenceTitle).append($licenceVal);    
    $view.append($image).append($licenceDiv);     
}

//remove the redaction
function cancelRedaction(){
    console.log("hello there");
    var redaction = $view.data('redaction');
    
    //if replace redaction and search already been done, go back to search results
    if ((redaction.type === 'replace') && ($('#search-txt').val() !== '')){
        //likely a search is taking place, go back to search results
    }else{
        //reset everything        
        $view.data('redaction', null);
        $('#licence-select').val("Select a Licence");
        updateGUI(null);
    }
    
}

function saveImageRedaction(){
    
    $view = $('#view');

    var redaction = $view.data('redaction');

    //ping redaction off to the server
    if (redaction.type === "replace") {
        $.getJSON("../public_html/php/inputs/imageRedaction.php?callback=?",
                {original: redaction.original, newimage: redaction.newimage, 
                    licence: redaction.licence, caption: redaction.caption, 
                    type: redaction.type, newtitle: redaction.newTitle,
                    owner: redaction.owner, imageurl: redaction.imageUrl},
        function(res) {
            handleResult(res[0], res[1]);
        });
    }
    
    if (redaction.type === "licence") {
        $.getJSON("../public_html/php/inputs/licenceRedaction.php?callback=?",
                {original: redaction.original, licence: redaction.licence, 
                    type: redaction.type},
        function(res) {
            handleResult(res[0], res[1]);
        });
    }  

}

/*
 * Javascript objects for storing information about the image's redaction
 * An object for each equivalent in the PHP
 */
function ReplaceRedaction(original, newImage, licence, caption, newTitle, owner, imageUrl) {    
    var self = this;
    self.original = original;
    self.newimage = newImage;
    self.licence = licence;
    self.caption = caption;
    self.type = "replace";
    
    self.newTitle = newTitle;
    self.owner = owner;
    self.imageUrl = imageUrl;
}

function LicenceRedaction(original, licence) {    
    var self = this;
    self.original = original;
    self.licence = licence;
    self.type = "licence";
}