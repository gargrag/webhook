<?php
/*
require_once 'WebHook.php';

$webhook = new WebHook();
$webhook->notify();
*/

//$ini = parse_ini_file("config.ini", true);
//print_r($ini);

$var = "asd some thing";
preg_match_all("/merge/i", $var, $matches);

print_r($matches);