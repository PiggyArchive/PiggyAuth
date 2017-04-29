<?php

namespace PiggyAuth\Tasks;

use pocketmine\scheduler\AsyncTask;
use pocketmine\Server;

/**
 * Class ValidateEmailTask
 * @package PiggyAuth\Tasks
 */
class ValidateEmailTask extends AsyncTask
{
    private $api;
    private $email;
    private $result;
    private $error;
    private $callback = false;
    private $args;


    /**
     * ValidateEmailTask constructor.
     * @param mixed|null $api
     * @param $email
     * @param $callback
     * @param $args
     */
    public function __construct($api, $email, $callback, $args)
    {
        if ($callback !== null) {
            parent::__construct($callback);
        }
        $this->api = serialize($api);
        $this->email = serialize($email);
        $this->callback = $callback !== null;
        $this->args = $args;
    }

    /**
     * @return bool
     */
    public function onRun()
    {
        if (unserialize($this->email) == "none") {
            $this->result = true;
            return true;
        }
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($ch, CURLOPT_USERPWD, 'api:' . unserialize($this->api));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
        curl_setopt($ch, CURLOPT_URL, 'https://api.mailgun.net/v3/address/validate');
        curl_setopt($ch, CURLOPT_POSTFIELDS, array('address' => unserialize($this->email)));
        $result = json_decode(curl_exec($ch));
        if ($result !== null) {
            $this->result = json_decode(curl_exec($ch))->is_valid;
        } else {
            $this->result = null;
        }
        if (curl_error($ch) == "SSL certificate problem: unable to get local issuer certificate") {
            $this->error = "SSL certificate problem: unable to get local issuer certificate\nPlease make sure you have downloaded the file from https://github.com/MCPEPIG/PiggyAuth-MailGunFiles & edited the php.ini.";
        }
        curl_close($ch);
    }

    /**
     * @param Server $server
     */
    public function onCompletion(Server $server)
    {
        if ($server->getPluginManager()->getPlugin("PiggyAuth")->isEnabled()) {
            if ($this->error !== null) {
                $server->getPluginManager()->getPlugin("PiggyAuth")->getLogger()->error($this->error);
            } else {
                if ($this->result == null) {
                    $this->result = filter_var(unserialize($this->email), FILTER_VALIDATE_EMAIL);
                }
                if ($this->callback && $this->args !== null) {
                    $callback = $this->fetchLocal($server);
                    $callback($this->result, $this->args, $server->getPluginManager()->getPlugin("PiggyAuth"));
                }
            }
        }
    }

}
