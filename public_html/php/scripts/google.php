<?php

/*
 * Receive parameters from the user and return back a list of photos with
 * sufficient information that a PHP object can be created in the future for
 * redaction purposes.  
 */

include '../debug/ChromePhp.php';

//work out the licence
$commercial = $_GET['com'];
$derivative = $_GET['derv'];

if ($commercial)
{
    if ($derivative)
    {
        $licence="(cc_publicdomain|cc_attribute|cc_sharealike).-(cc_noncommercial|cc_nonderived)";
    }
    else
    {
        $licence="(cc_publicdomain|cc_attribute|cc_sharealike|cc_nonderived).-(cc_noncommercial)";
    }
}
else //commercial is false
{
    if ($derivative)
    {
        $licence="(cc_publicdomain|cc_attribute|cc_sharealike|cc_noncommercial).-(cc_nonderived)";
    }
    else //both false
    {
        $licence="";
    }
}

//calculate starting position
$start = ($_GET['page'] - 1) * 8; 

$params = array(
	'q'             => $_GET['tags'],
        'as_rights'     => $licence, 
        'rsz'           => '8',
        'start'         => $start
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


$rspData = json_decode($rsp)->responseData;

$page = $rspData->cursor->currentPageIndex + 1;
$total = $rspData->cursor->estimatedResultCount;
if ($page * 8 < $total){            
    $json["next"] = true;
}

$json["page"] = $page;
$json["total"] = $rspData->cursor->resultCount;

$json["results"] = array();
        
$results = json_decode($rsp)->responseData->results;
foreach($results as $result)
{ 
    $jsonSizes = array();
    $jsonSizes["Small"] = $result->tbUrl;
    $jsonSizes["Large"] = $result->url;
    
    //package up everything to do with a photo
    $jsonPhoto = array();
    $jsonPhoto["title"] = $result->titleNoFormatting;
    $jsonPhoto["desc"] = "N/A";
    $jsonPhoto["url"] = $result->originalContextUrl;
    $jsonPhoto["owner"] = $result->visibleUrl;
    $jsonPhoto["ownerUrl"] = $result->visibleUrl;
    $jsonPhoto["sizes"] = $jsonSizes;
    $jsonPhoto["licence"] = "CC Licence";

    array_push($json["results"], $jsonPhoto);
}

echo $_GET['callback'] . '(' . json_encode($json) . ')';
 
?>