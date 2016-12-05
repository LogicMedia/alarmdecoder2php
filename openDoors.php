<?php
/* 
Copyright 2015-2016 Logic Media, Inc.  
www.logicmediazone.com    |    info@logicmediazone.com
*/

require_once 'Net/Growl/Autoload.php';

$CONFIG=array();

require "config.php";


$now=time();



$files = scandir($CONFIG['openDoorPath']);



$alert=false;
$message="";
foreach ($files as $file) {
	print $file."<br />";
	if (preg_match("/door-(\d+).open/",$file,$matches)) {
		$door=$matches[1];
		$openTime = file_get_contents($CONFIG['openDoorPath']."/".$file);
		
		$diff = $now - $openTime;
		print "$now - $openTime = $diff <br />";
		if ($diff > 60  && $diff < 122) {
			$alert=true;
			$message="Door $door has been open for $diff seconds";
		}
		if ($diff > 60*5  && $diff < 60*6+2) {
			$alert=true;
			$message="Door $door has been open for $diff seconds";
		}
		if ($diff > 60*10) {
			$alert=true;
			$message="Door $door has been open for $diff seconds";
		}
	}
}



//pushover
if ($alert && $CONFIG['pushover']) {

	curl_setopt_array($ch = curl_init(), array(
	  CURLOPT_URL => "https://api.pushover.net/1/messages.json",
	  CURLOPT_POSTFIELDS => array(
	    "token" => $CONFIG['pushoverToken'],
	    "user" => $CONFIG['pushoverUser'],
	    "message" => $message,
	  ),
	  CURLOPT_SAFE_UPLOAD => true,
	));
	curl_exec($ch);
	curl_close($ch);

}


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
	    $title       = $message;
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


