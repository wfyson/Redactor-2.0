<?php

/*
 * Receive parameters from the user and return back a list of photos with
 * sufficient information that a PHP object can be created in the future for
 * redaction purposes.  
 */

include 'ChromePhp.php';

$params = array(
	'q'             => 'northern lights', //$_POST['tags']
        'as_rights'     => 'cc_publicdomain', //$_POST['licence']
        'rsz'           => '8', //$_POST['perpage'] //8 is the max I believe
        'start'         => '1', //$_POST['page']
);

$encoded_params = array();

foreach ($params as $k => $v){

	$encoded_params[] = urlencode($k).'='.urlencode($v);
}

/*
 * call the API and decode the response
 */
$url = "https://ajax.googleapis.com/ajax/services/search/images?v=1.0&".implode('&', $encoded_params);

$rsp = file_get_contents($url);

//convert google json to my json
$json = array();      
$json["results"] = array();


$results = json_decode($rsp)->responseData->results;
foreach($results as $result)
{ 
    //package up everything to do with a photo
    $jsonPhoto = array();
    $jsonPhoto["title"] = $result->titleNoFormatting;
    $jsonPhoto["desc"] = "n/a";
    $jsonPhoto["owner"] = $result->originalContextUrl;
    
    $jsonSizes = array();
    $jsonSizes["Thumbnail"] = $result->tbUrl;
    $jsonSizes["Medium"] = $result->url;

    $jsonPhoto["sizes"] = $jsonSizes;
           
    array_push($json["results"], $jsonPhoto);            
}
?>