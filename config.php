<?php

$CONFIG=array();

$CONFIG['log']=true;
$CONFIG['logFile']='alarm.log';

$CONFIG['pushover']=true;
$CONFIG['pushoverToken']="Get token from pushover.net";
$CONFIG['pushoverUser']="Get user from pushove.net";

$CONFIG['slack']=true;
$CONFIG['slackURL']="Get URL from Slack";
$CONFIG['slackUsername']="alarm";


$CONFIG['growl']=true;
$CONFIG['growlIP']='127.0.0.1'; //IP of computer to show growl alerts

$CONFIG['delayFile']="/tmp/alarm-delay";



