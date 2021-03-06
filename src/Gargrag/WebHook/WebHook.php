<?php

namespace Gargrag\WebHook;
class WebHook
{

    private $config;
    private $payload;

    public function __construct()
    {
        $this->config = parse_ini_file('config.ini', true);
        $this->payload = json_decode(file_get_contents('php://input'));
    }

    public function process_payload()
    {
        $this->logfile("=== BEGIN payload from " . $this->payload->repository->name);

        $this->git_pull($this->payload->repository->name);
        $this->post_commands();

        $this->logfile("=== END payload from " . $this->payload->repository->name);

    }
    
    private function slackNotification($message){
        $client = new GuzzleHttp\Client();
        $response = $client->request('POST', 'https://hooks.slack.com/services/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', [
            'form_params' => array('payload', json_encode(array('text' => $message)))
        ]);
    }

    private function git_pull($repo)
    {

        $branch = $this->config['repositories']['branch'];
        $origin = $this->config['repositories']['remote'];
        $dir = $this->config['repositories'][$repo];

        $command = "cd ${dir} && git reset --hard HEAD && git pull ${origin} ${branch} 2>&1";

        exec($command, $output, $return_var);
        $this->logfile($output);

        if ($return_var != 0) {
            $output = implode('<br />', $output);
            $message = "*Can't pull on ${repo}::${branch}*${output}";
        }


    }

    private function post_commands()
    {
        foreach ($this->config['post_commands'] as $key => $post_command) {

            $run_command = false;

            if($this->config['post_commands_triggers'][$key]){
                foreach($this->payload->commits as $commit){
                    foreach($commit->modified as $file){
                        if(strpos($file, $this->config['post_commands_triggers'][$key]) !== false){
                            $run_command = true;
                        }
                    }
                }
            }else{
                $run_command = true;
            }

            if($run_command) {

                $output = '';
                $return_var = 0;

                exec($post_command . " 2>&1", $output, $return_var);

                $exit_status = ($return_var > 0) ? "fails" : "succeed";
                $this->logfile("COMMAND : ${post_command}, ${exit_status} with output: ");
                $this->logfile($output);

                if ($return_var != 0) {
                    $output = implode('<br />', $output);
                    $message = "POST COMMAND: ${post_command} failed, with output: <br />" . $output;
                    $this->slackNotification($message);
                }
            }
        }
    }


private function logfile($message)
    {
        $message_send = '';

        if (is_array($message)) {
            $message_send .= implode(PHP_EOL, $message);
        } else {
            $message_send = $message;
        }

        $date = date(DATE_RFC2822);
        $message_send = "[${date}] ${message_send}" . PHP_EOL;

        file_put_contents($this->config['general']['logfile'], $message_send, FILE_APPEND);
    }

}
