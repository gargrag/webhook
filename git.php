<?php

define('TH_BRANCH', 'qa');
define('TH_LOG_FILE', '/var/www/qa/logs/git.log');

$repos = array(
	"bunker" => ".",
	"bunker-ng" => "api/v2",
	"bunker-analytics-etl" => "bunker-analytics-etl",
	"bunker-nbs" => "bunker-nbs"
);

$payload = json_decode($_POST['payload']);

if(isset($payload)){

	__log("==== BEGIN Payload from " . $payload->repository->name );
	$chdir = $repos[$payload->repository->name];
	exec("cd ${chdir} &&  git reset --hard HEAD && git pull origin" . TH_BRANCH . "2>&1", $output);
	__log($output);
	__log("==== PHINX");
	exec("php -f ". dirname(__FILE__) ."/vendor/robmorgan/phinx/bin/phinx migrate 2>&1", $output);
	__log($output);
	__log("==== END");

}else{
	__log("Dummy call received from " . $_SERVER['REMOTE_ADDR']);
}

function __log($log){
	$message = date('[m/d/Y h:i:s a] ');
	if(is_array($log)){
		$message .= implode( PHP_EOL, $log);
	}else{
		$message .= $log;
	}
	$message .= PHP_EOL;
	file_put_contents(TH_LOG_FILE, $message, FILE_APPEND);
}
