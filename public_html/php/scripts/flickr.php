<?php

/*
 * Receive parameters from the user and return back a list of photos with
 * sufficient information that a PHP object can be created in the future for
 * redaction purposes.  
 */

include '../debug/ChromePhp.php';

define("API_KEY", "7493f1b9adc9c0e8e55d5be46f60ddb7");
define("INFO_CALL", "http://api.flickr.com/services/rest/?method=flickr.photos.getInfo&api_key=7493f1b9adc9c0e8e55d5be46f60ddb7&format=php_serial&photo_id=");
define("SIZE_CALL", "http://api.flickr.com/services/rest/?method=flickr.photos.getSizes&api_key=7493f1b9adc9c0e8e55d5be46f60ddb7&format=php_serial&photo_id=");

//work out the licence
$commercial = $_GET['com'];
$derivative = $_GET['derv'];
        
if ($commercial)
{
    if ($derivative)
    {
        $licence="4,5,7";
    }
    else
    {
        $licence="4,5,6,7";
    }
}
else //commercial is false
{
    if ($derivative)
    {
        $licence="1,2,4,5,7";
    }
    else //both false
    {
        $licence="1,2,3,4,5,6,7";
    }
}

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
$json = array();
if ($rsp_obj['stat'] == 'ok'){                   
    
        $page = $rsp_obj[photos][page];
        $total = intval($rsp_obj[photos][total]);
        if ($page * 8 < $total){            
            $json["next"] = true;
        }
        
        $json["page"] = $page;
        $json["total"] = $total;
    
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
                $label = str_replace(' ', '_', $size[label]);
                $jsonSizes[$label] = $size[source];
            }
            
            //get the photos licence
            $licenceNo = $photoInfo[license];
            switch ($licenceNo){
                case 0:
                    $licenceStr = "All Rights Reserved";
                    break;
                case 1:
                    $licenceStr = "Attribution-NonCommercial-ShareAlike License";
                    break;
                case 2:
                    $licenceStr = "Attribution-NonCommercial License";
                    break;
                case 3:
                    $licenceStr = "Attribution-NonCommercial-NoDerivs License";
                    break;
                case 4:
                    $licenceStr = "Attribution License";
                    break;
                case 5:
                    $licenceStr = "Attribution-ShareAlike License";
                    break;
                case 6:
                    $licenceStr = "Attribution-NoDerivs License";
                    break;
                case 7:
                    $licenceStr = "No known copyright restrictions";
                    break;
                case 8:
                    $licenceStr = "United States Government Work";
                    break;                
            }
                        
            //package up everything to do with a photo
            $jsonPhoto = array();
            $jsonPhoto["title"] = $photoInfo[title][_content];
            $jsonPhoto["desc"] = $photoInfo[description][_content];
            $jsonPhoto["url"] = $photoInfo[urls][url][0][_content];
            $jsonPhoto["owner"] = $photoInfo[owner][username];
            $jsonPhoto["sizes"] = $jsonSizes;
            $jsonPhoto["licence"] = $licenceStr;
            
            array_push($json["results"], $jsonPhoto);            
        }

}else{
	array_push($json["fail"], true);
}

ChromePhp::log($json);

echo $_GET['callback'] . '(' . json_encode($json) . ')';

?>