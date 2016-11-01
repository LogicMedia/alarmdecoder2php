<?php
/* 
Copyright 2015-2016 Elliott Bennett at Logic Media
www.logicmediazone.com    |    info@logicmediazone.com
*/

require_once 'Net/Growl/Autoload.php';

$CONFIG=array();

require "config.php";


if (isset($_GET["delay"])) {
	$d = $_GET["delay"];
	$now=time();
	if (!is_numeric($d) || $d>86401)
		$now=-1;
	else if ($d>0)
		$now += $d;
	else	
		$now=-1;


	file_put_contents($CONFIG['delayFile'],$now);

	print "Delaying until [$now]";
	exit;
}


$exp = -1;
if (file_exists($CONFIG['delayFile']))
	$exp = file_get_contents($CONFIG['delayFile']);

$now=time();


// Retrieve the request's body and parse it as JSON
$input = @file_get_contents("php://input");
$event_json = json_decode($input);

$zone = $event_json->{'message'};




//Zones to skip alert
$alert=true;
if ($zone == "Zone Foyer Motion (8) has been faulted.") {
	$alert=false;
}
if ($zone == "Zone Basement motion (10) has been faulted.") {
	$alert=false;
}


//Log entry to file
if ($CONFIG['log']) {
	file_put_contents($CONFIG['logFile'],date(DATE_ATOM)." ".$zone."\n",FILE_APPEND);
}


//Clean up text
$zone = preg_replace("/^Zone /","",$zone);
$zone = preg_replace("/ \(\d+\) has been faulted.$/","",$zone);
	


//If delay active, skip alert
if ($now < $exp) {
	$alert=false;
}


//pushover
if ($alert && $CONFIG['pushover']) {

	curl_setopt_array($ch = curl_init(), array(
	  CURLOPT_URL => "https://api.pushover.net/1/messages.json",
	  CURLOPT_POSTFIELDS => array(
	    "token" => $CONFIG['pushoverToken'],
	    "user" => $CONFIG['pushoverUser'],
	    "message" => $zone,
	  ),
	  CURLOPT_SAFE_UPLOAD => true,
	));
	curl_exec($ch);
	curl_close($ch);

}


//slack
if ($CONFIG['slack']) {
	slack($zone,"alarm");
}





//growl
if ($CONFIG['growl']) {
	$notifications = array(
	    'GROWL_NOTIFY_STATUS' => array(
		'display' => 'Status',
	    ),
	    'GROWL_NOTIFY_PHPERROR' => array(
		'display' => 'Error-Log'
	    )
	);
	$appName  = 'Home Security';
	$password = '';
	$options  = array(
		'protocol' => 'gntp',
		'timeout'  => 15,
		'host' => $CONFIG['growlIP']
	);

	try {
	    $growl = Net_Growl::singleton($appName, $notifications, $password, $options);

	    $name        = 'GROWL_NOTIFY_STATUS';
	    $title       = $zone;
	    $description = '';
	    $growl->publish($name, $title, $description);

	} catch (Net_Growl_Exception $e) {
	    echo 'Caught Growl exception: ' . $e->getMessage() . PHP_EOL;
	}
}


http_response_code(200); // PHP 5.4 or greater









//thanks to https://gist.github.com/alexstone/9319715
function slack($message, $room = "engineering", $icon = ":heavy_exclamation_mark:") {
	global $CONFIG;
        $room = ($room) ? $room : "engineering";
        $data = "payload=" . json_encode(array(
                "channel"       =>  "#{$room}",
                "text"          =>  $message,
                "icon_emoji"    =>  $icon,
		"username"	=> $CONFIG['slackUsername']
            ));
	
        $ch = curl_init($CONFIG['slackURL']);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $result = curl_exec($ch);
        curl_close($ch);
	
        return $result;
    }


