<?php
if(file_exists('./disable.webhook')){
  return true;
}

require_once 'WebHook.php';

ignore_user_abort(true);
set_time_limit(0);
ob_start();
header('Connection: close');
header('Content-Length: '.ob_get_length());
ob_end_flush();
ob_flush();
flush();

$wh = new WebHook();
$wh->process_payload();
