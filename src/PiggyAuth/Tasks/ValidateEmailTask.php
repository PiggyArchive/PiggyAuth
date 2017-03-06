<?php

namespace PiggyAuth\Tasks;

use pocketmine\scheduler\AsyncTask;
use pocketmine\Server;

class ValidateEmailTask extends AsyncTask {
    private $api;
    private $email;
    private $result;
    private $error;
    private $callback;
    private $args;


    public function __construct($api, $email, $callback, $args) {
        $this->api = serialize($api);
        $this->email = serialize($email);
        $this->callback = $callback;
        $this->args = $args;
    }

    public function onRun() {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($ch, CURLOPT_USERPWD, 'api:' . unserialize($this->api));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
        curl_setopt($ch, CURLOPT_URL, 'https://api.mailgun.net/v3/address/validate');
        curl_setopt($ch, CURLOPT_POSTFIELDS, array('address' => unserialize($this->email)));
        $this->result = json_decode(curl_exec($ch))->is_valid;
        if (curl_error($ch) == "SSL certificate problem: unable to get local issuer certificate") {
            $this->error = "SSL certificate problem: unable to get local issuer certificate\nPlease make sure you have downloaded the file from https://github.com/MCPEPIG/PiggyAuth-MailGunFiles & edited the php.ini.";
        }
        curl_close($ch);
    }

    public function onCompletion(Server $server) {
        if ($this->error !== null) {
            $server->getPluginManager()->getPlugin("PiggyAuth")->getLogger()->error($this->error);
        } else {
            if ($this->result == null) {
                //$this->result = filter_var($this->email, FILTER_VALIDATE_EMAIL);
            }
            $callback = $this->callback;
            $callback($this->result, $this->args, $server->getPluginManager()->getPlugin("PiggyAuth"));
        }
    }

}
