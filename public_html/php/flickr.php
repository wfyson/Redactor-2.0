<?php

/*
 * Receive parameters from the user and return back a list of photos with
 * sufficient information that a PHP object can be created in the future for
 * redaction purposes.  
 */

$params = array(
	'api_key'	=> '7493f1b9adc9c0e8e55d5be46f60ddb7',
	'method'	=> 'flickr.photos.getInfo',
	'photo_id'	=> '251875545',
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

#
# display the photo title (or an error if it failed)
#


if ($rsp_obj['stat'] == 'ok'){

	$photo_title = $rsp_obj['photo']['title']['_content'];

	echo "Title is $photo_title!";
}else{

	echo "Call failed!";
}

?>