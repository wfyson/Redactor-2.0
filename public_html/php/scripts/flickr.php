<?php

/*
 * Receive parameters from the user and return back a list of photos with
 * sufficient information that a PHP object can be created in the future for
 * redaction purposes.  
 */

include 'ChromePhp.php';

define("API_KEY", "7493f1b9adc9c0e8e55d5be46f60ddb7");
define("INFO_CALL", "http://api.flickr.com/services/rest/?method=flickr.photos.getInfo&api_key=7493f1b9adc9c0e8e55d5be46f60ddb7&format=php_serial&photo_id=");
define("SIZE_CALL", "http://api.flickr.com/services/rest/?method=flickr.photos.getSizes&api_key=7493f1b9adc9c0e8e55d5be46f60ddb7&format=php_serial&photo_id=");

//work out the licence
$commercial = $_GET['com'];
$derivative = $_GET['derv'];

$licence = "test";

$params = array(
	'api_key'	=> constant("API_KEY"),
	'method'	=> 'flickr.photos.search',
	'tags'          => $_GET['tags'],
        'tag_mode'      => 'all',
        'license'       => $licence,
        'sort'          => 'date-posted-asc',
        'per_page'      => '8', //$_POST['perpage']
        'page'          => $_GET['page'],
	'format'	=> 'php_serial',
);

$encoded_params = array();

foreach ($params as $k => $v){

	$encoded_params[] = urlencode($k).'='.urlencode($v);
}

/*
 * call the API and decode the response
 */
$url = "https://api.flickr.com/services/rest/?".implode('&', $encoded_params);

$rsp = file_get_contents($url);

$rsp_obj = unserialize($rsp);

/*
 * generate some json to send back to the website (or an error if it failed)
 * (will also need to send back the information as to whether or not there is another page to search for (forward or back))
 */
if ($rsp_obj['stat'] == 'ok'){          
    
        $json = array();      
        $json["results"] = array();
        
        //cycle through the photos, getting some information for each one
        $photos = $rsp_obj[photos][photo];                            
        foreach($photos as $photo)
        {
            //get more information about the photo
            $infoUrl = constant("INFO_CALL") . $photo[id];
            $infoResponse = file_get_contents($infoUrl);
            $infoObj = unserialize($infoResponse);
            $photoInfo = $infoObj[photo];            
            
            //get urls to the photo
            $sizeUrl = constant("SIZE_CALL") . $photo[id];
            $sizeResponse = file_get_contents($sizeUrl);
            $sizeObj = unserialize($sizeResponse);
            $photoSize = $sizeObj[sizes];
            
            $jsonSizes = array();
            foreach($photoSize[size] as $size)
            {
                $jsonSizes[$size[label]] = $size[source];
            }

            //package up everything to do with a photo
            $jsonPhoto = array();
            $jsonPhoto["title"] = $photoInfo[title][_content];
            $jsonPhoto["desc"] = $photoInfo[description][_content];
            $jsonPhoto["owner"] = $photoInfo[owner][username];
            $jsonPhoto["sizes"] = $jsonSizes;
            
            array_push($json["results"], $jsonPhoto);            
        }

}else{
	echo "Call failed!";
}

echo json_encode($json)

?>