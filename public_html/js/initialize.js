/*
 * Get a reference to the file so it can be sent to ther server.
 */

function handleFileSelect(evt) {
    var files = evt.target.files; // FileList object
    for (var i = 0, f; f = files[i]; i++) {
        sendRequest(f, f.name);
    }
  }

function handleFileDrop(evt) {
    evt.stopPropagation();
    evt.preventDefault();
    var files = evt.dataTransfer.files; // FileList object.
    for (var i = 0, f; f = files[i]; i++) {
        sendRequest(f, f.name);
    }
}

function handleDragOver(evt) {
    evt.stopPropagation();
    evt.preventDefault();
    evt.dataTransfer.dropEffect = 'copy'; // Explicitly show this is a copy.
}

function handleUrl(url){    
    //TODO - check the url is something we allow!!!
    $.getJSON("../public_html/php/scripts/writeUrl.php?callback=?", {doc: url},
    function(res) {
        handleResult(res[0], res[1]);
    });
    
    //display the progress bar and remove the upload interface
    $('#file_upload').fadeOut("slow");
    $('#upload_progress').fadeIn("slow");    
}

/*
 * Upload file to the server, slicing a large file into slices if necessary
 */

// Setup variables for uploading the file
BYTES_PER_CHUNK = 1024 * 1024; // 1MB chunk sizes.
var slices;
var slices2;

function sendRequest(blob, fname) {
    
    //TODO: check filetype is appropriate before sending it to server

    //display the progress bar and remove the upload interface
    $('#file_upload').fadeOut("slow");
    $('#upload_progress').fadeIn("slow");

    var start = 0;
    var end;
    var index = 0;

    // calculate the number of slices required
    slices = Math.ceil(blob.size / BYTES_PER_CHUNK);
    slices2 = slices;

    while (start < blob.size) {
        end = start + BYTES_PER_CHUNK;
        if (end > blob.size) {
            end = blob.size;
        }

        uploadFile(blob, index, start, end, fname);

        start = end;
        index++;
    }
}

function uploadFile(blob, index, start, end, fname) {
    var xhr;
    var end;
    var fd;
    var chunk;
    var url;

    xhr = new XMLHttpRequest();

    xhr.onreadystatechange = function() {
        if (xhr.readyState == 4) {
            if (xhr.responseText) {
                console.log(xhr.responseText);
            }

            slices--;

            updateProgress(slices, slices2);

            // if we have finished all slices
            if (slices == 0) {
                mergeFile(fname);
            }
        }
    };

    chunk = blob.slice(start, end);

    fd = new FormData();
    fd.append("file", chunk);
    fd.append("name", fname);
    fd.append("index", index);

    xhr.open("POST", "../public_html/php/scripts/writer.php", true);
    xhr.send(fd);
}

//update progress bar as file uploads
function updateProgress(slices, totalSlices) {

    var percentage = ((totalSlices - slices) / totalSlices) * 100;

    var cssPercentage = percentage + "%";

    $('#upload_progress .progress-bar').css('width', cssPercentage);
}

//reconstruct slices into original file
function mergeFile(fname) {

    $.getJSON("../public_html/php/scripts/merge.php?callback=?", {name: fname, index: slices2},
    function(res) {        
        handleResult(res[0], res[1]);
    });

}

//deals with the result of any of the php uploading processes
function handleResult(document, redactions){
    $('#main').data("doc", document); 
    $('#main').data("redactions", redactions);
    initDisplay();
}