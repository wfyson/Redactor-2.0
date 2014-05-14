
//show the image ready for redaction
function showImage(image){    
    
    var document = $('#main').data('doc');
    
    console.log(image);
    
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
    
    $searchHeading = $('<h4></h4>');
    $searchHeading.append("Image Search");
    
    //search
    $searchText = $('<input>');
    $searchText.attr('id', 'search-txt');
    $searchText.addClass('form-control');
    $searchText.attr('type', 'text');
    
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

    $search.append($searchHeading);
    $search.append($searchText);
    $search.append($commercial);
    $search.append($derivative);
    $search.append($group);
    
    return $search;
}

function setupLicence(image){
    //add a licence option
    $licence = $('<div></div>');
    $licence.addClass('option-div');
    $licenceHeading = $('<h4></h4>');
    $licenceHeading.append("CC Licence");
    
    $licenceSelect = $('<select></select>');
    $licenceSelect.addClass('form-control');
    var licences = ["CC0", "CC BY", "CC BY-SA", "CC BY-ND", "CC BY-NC", "CC BY-NC-SA", "CC BY-NC-ND"];
    for (var i = 0; i < licences.length; i++){
        $option = $('<option></option>');
        $option.append(licences[i]);
        $licenceSelect.append($option);
    }    
    $licence.append($licenceHeading);
    $licence.append($licenceSelect);
    
    return $licence;
}

function setupObscure(image){
    //obscure the image
    $obscure = $('<div></div>');
    $obscure.addClass('option-div last');
    $obscureHeading = $('<h4></h4>');
    $obscureHeading.append("Obscure Image");
    
    $obscureBtn = $('<button></button>');
    $obscureBtn.addClass('btn btn-default');
    $obscureBtn.attr('type', 'button');
    $obscureBtn.append("Obscure");
    
    $obscure.append($obscureHeading);
    $obscure.append($obscureBtn);
    
    return $obscure;
}

function imageSearch(engine){
    
    //first check there are search terms entered!!
    
    //get image search engine to use
    var url;
    switch (engine){
        case "flickr":
            url = '../public_html/php/inputs/flickr.php?callback=?'
        break;
        case "google":
            url = '../public_html/php/inputs/google.php?callback=?';
        break;
    }
    
    var tags = $('#search-txt').val();
    var commercial = $('#commercial-check').is(':checked');
    var derivative = $('#derivative-check').is(':checked');
    
    console.log(tags);
    console.log(commercial);
    console.log(derivative);
    
    //ping search request off to the server
    //$.getJSON(url, {tags: tags, com: commercial, derv: derivative, page: 1},
    //function(res) {
    //    handleResult(res[0], res[1]);
    //});
    
}