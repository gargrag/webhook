<?php
ini_set('date.timezone', 'America/Montevideo');
ini_set('date.timezone', 'America/Montevideo');
ini_set('date.timezone', 'America/Montevideo');
require_once 'vendor/autoload.php';

use HipChat\HipChat;

class WebHook
{

    private $config;

    public function __construct()
    {
        $this->config = parse_ini_file('config.ini', true);
    }

    public function process_payload()
    {

        if (isset($_POST['payload'])) {

            $payload = json_decode($_POST['payload']);

            print_r($payload);

            $this->logfile("=== BEGIN payload from " . $payload->repository->slug);

            foreach ($payload->commits as $commit) {
                preg_match_all("/merge/", $commit->message, $matches);

                if (count($matches) > 1) {
                    $message = "It seems that " . $commit->author . "<br/>"  .
                        "has <b>merged</b> smth into " . $commit->branch . " on the <b>repo:" .
                        $payload->repository->name . "</b>";

                    $this->hipchat_notify($message);
                }
            }

            $this->git_pull($payload->repository->slug);
            $this->post_commands();

            $this->logfile("=== END payload from " . $payload->repository->slug);

        } else {

            $this->logfile("=== EMPTY Call from " . $_SERVER['HTTP_REMOTE_ADDR']);
        }
    }

    private function git_pull($repo)
    {

        $branch = $this->config['repositories']['branch'];
        $dir = $this->config['repositories'][$repo];

        $command = "cd ${dir} && git pull origin $branch 2>&1";
        exec($command, $output, $return_var);

        $this->logfile($output);

        if ($return_var != 0) {
            $output = implode('<br />', $output);
            $message = "Can\'t pull on ${repo}::${branch} <br/> ${output}";
            $this->hipchat_notify("Can\'t pull on ${repo}::${branch} <br/>");
        }


    }

    private function post_commands(){
        if(count($this->config['post_commands']) > 0){
            foreach($this->config['post_commands'] as $post_command){
                $output = '';
                $return_var = 0;
                exec($post_command . " 2>&1", $output, $return_var);
                $exit_status = ( $return_var == 0 ) ? "succeed" : "fails";
                $this->logfile("POST COMMAND : ${post_command}, ${exit_status} with output: ");
                $this->logfile($output);

                if($return_var != 0){
                    $output = implode('<br />', $output);
                    $message = "POST COMMAND: ${post_command} failed, with output: <br />" . $output;

                    $this->hipchat_notify($message, "warning");
                }
            }
        }
    }


    private function hipchat_notify($message, $type = null)
    {

        $hc_config = $this->config['hipchat'];

        switch ($type) {
            case 'warning' :
                $color = HipChat::COLOR_YELLOW;
                break;

            case 'error' :
                $color = HipChat::COLOR_RED;
                break;

            default :
                $color = HipChat::COLOR_GRAY;
                break;
        }

        $hc = new HipChat($hc_config['token']);
        $hc->message_room($hc_config['room'] , $hc_config['from'],
            $message, false, $color, HipChat::FORMAT_HTML);

    }

    private function logfile($message)
    {
        $message_send = '';

        if (is_array($message)) {
            $message_send .= implode(PHP_EOL, $message);
        }else{
            $message_send = $message;
        }

        $date = date(DATE_RFC2822);
        $message_send = "[${date}] ${message_send}" . PHP_EOL;

        file_put_contents($this->config['general']['logfile'], $message_send, FILE_APPEND);
    }

}   
