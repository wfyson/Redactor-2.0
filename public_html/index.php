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

        <!-- Redactor Setup -->
        <script src="js/initialize.js"></script>
    </head>

    <body>

        <div id='file_upload'>
            <div id='file_input'>
                <a class='btn btn-primary' href='javascript:;'>
                    Choose File...
                    <input id='files' type="file" style='position:absolute;z-index:2;top:0;left:0;filter: alpha(opacity=0);-ms-filter:"progid:DXImageTransform.Microsoft.Alpha(Opacity=0)";opacity:0;background-color:transparent;color:transparent;' name="file_source" size="40">
                </a>
            </div>

            <h2>Or</h2>

            <div id="drop_zone"><h3>Drop files here</h3></div>
            <output id="list"></output>
        </div>

        <div id="upload_progress" class="progress">
            <div class="progress-bar" role="progressbar" aria-valuenow="60" aria-valuemin="0" aria-valuemax="100" style="width: 0%;">
            Uploading...
            </div>
        </div>

        <script>

            window.onload = function() {
                // Setup the file input listener
                var input = document.getElementById('files');
                input.addEventListener('change', handleFileSelect, false);

                // Setup the dnd listeners.
                var dropZone = document.getElementById('drop_zone');
                dropZone.addEventListener('dragover', handleDragOver, false);
                dropZone.addEventListener('drop', handleFileDrop, false);
            };

        </script>

    </body>
</html>