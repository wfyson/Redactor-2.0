<?php

/*
 * Receive parameters from the user and return back a list of photos with
 * sufficient information that a PHP object can be created in the future for
 * redaction purposes.  
 */

include '../debug/ChromePhp.php';

define("API_CALL", "http://openclipart.org/api/search/?query=water&page=4");

$params = array(
	'query'          => $_GET['tags'],
        'page'          => $_GET['page']
);

$encoded_params = array();

foreach ($params as $k => $v){

	$encoded_params[] = urlencode($k).'='.urlencode($v);
}

/*
 * call the API and decode the response
 */
$url = "http://openclipart.org/api/search/?".implode('&', $encoded_params);

ChromePhp::log($url);

$rsp = file_get_contents($url);

$json = array();

//populate json with information not available from the API call
$json["next"] = true;        
$json["page"] = intval($_GET['page']);    
$json["total"] = null;

//read the return xml and send to client as JSON
$xml = simplexml_load_string($rsp);
$items = $xml->channel->item;
$json["results"] = array();
foreach($items as $item){
    $jsonPhoto = array();

    $url = $item[0]->xpath('enclosure/@url');    
    $jsonSizes = array();
    $jsonSizes["Small"] = (string)$url[0];
    $jsonSizes["Large"] = (string)$url[0];
    $jsonPhoto["sizes"] = $jsonSizes;
    
    //package up everything to do with a photo  
    $jsonPhoto["title"] = (string)$item->title;
    $jsonPhoto["desc"] = (string)$item->description;
    $jsonPhoto["url"] = (string)$item->link;
        
    $creator = $item[0]->xpath('dc:creator');    
    $jsonPhoto["owner"] = (string)$creator[0];

    $licence = $item[0]->xpath('cc:license');
    $jsonPhoto["licence"] = (string)$licence[0];

    array_push($json["results"], $jsonPhoto);    
}

echo $_GET['callback'] . '(' . json_encode($json) . ')';

?>