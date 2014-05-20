<?php

session_start();

//$files = glob("../../sessions/*");

//foreach($files as $file){ // iterate files
  //  if(is_file($file))
    //    unlink($file); // delete file
//}

session_destroy();
?>