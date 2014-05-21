<!DOCTYPE html>
<html>
    <head>
        <meta charset="utf-8">

        <title>Redact-O-Matic</title>

        <!-- CSS -->
        <!-- Bootstrap -->
        <link rel="stylesheet" type="text/css" href="css/bootstrap.min.css">        
        
        <!-- Page Styling -->
        <link rel="stylesheet" type="text/css" href="css/style.css">

        <!-- Javascript -->
        <!-- JQuery -->
        <script src="http://ajax.googleapis.com/ajax/libs/jquery/1.11.0/jquery.min.js"></script>

        <!-- Bootstrap -->
        <script src="js/lib/bootstrap.min.js"></script>
        
        <!-- Redactor Setup -->
        <script src="js/initialize.js"></script>
        <script src="js/display.js"></script>
        
        <!-- Redactions -->
        <script src="js/textRedaction.js"></script>
        <script src="js/imageRedaction.js"></script>
        
    </head>

    <body>
        <div id="initial">
            <div id='file-upload'>
                <div id='file-input'>
                    <a class='btn btn-primary' href='javascript:;'>
                        Choose File...
                        <input id='files' type="file" style='position:absolute;z-index:2;top:0;left:0;filter: alpha(opacity=0);-ms-filter:"progid:DXImageTransform.Microsoft.Alpha(Opacity=0)";opacity:0;background-color:transparent;color:transparent;' name="file_source" size="40">
                    </a>
                </div>

                <h2>Or</h2>

                <div id="drop-zone"><h3>Drop files here</h3></div>
                <output id="list"></output>
            </div>

            <div id="upload-progress" class="progress">
                <div class="progress-bar" role="progressbar" aria-valuenow="60" aria-valuemin="0" aria-valuemax="100" style="width: 0%;">
                    Uploading...
                </div>
            </div>
            
            <div id='file-error'>

                <button id='file-btn' class='btn btn-default'>
                    <span class="glyphicon glyphicon-repeat"></span> Try again
                </button>
                
                <div id='file-alert' class="alert alert-danger">
                    <strong>Invalid File!</strong>
                    Unfortunately the redactor doesn't support files of that type. Please select either a .docx or .pptx
                </div>

            </div>
            
            
        </div>
        
        <div id='main' class='row'>
            
            <!-- BAnner controls -->
            <div id='banner' class="col-md-8">
                
            </div>
            
            <!-- The current focus of the user's attention -->
            <div id='view' class='col-md-8'>
                
            </div>
            
            <!--A context sensitive side bar to show controls on the currently
                selected view -->
            <div id='sidebar' class='col-md-3 col-md-offset-7'>

            </div>
            
        </div>

        <script>

            window.onload = function() {                
                
                var session = '<?php session_start();
                    $id = session_id(); echo $id ?>';
                
                var doc = '<?php echo $_GET['doc'] ?>';
                if (doc !== ''){
                    handleUrl(doc);
                }else{               
                    // Setup the file input listener
                    var input = document.getElementById('files');
                    input.addEventListener('change', handleFileSelect, false);

                    // Setup the dnd listeners.
                    var dropZone = document.getElementById('drop-zone');
                    dropZone.addEventListener('dragover', handleDragOver, false);
                    dropZone.addEventListener('drop', handleFileDrop, false);
                }

                $(window).on('beforeunload', function() {
                    var phpUrl = "php/scripts/endsession.php";
                    $.get(phpUrl);                    
                });
                
                $('#file-btn').click(function(){
                    $('#file-error').fadeOut(function(){
                        $('#file-upload').show();
                    });                    
                });
                
            };                       
        </script>

    </body>
</html>