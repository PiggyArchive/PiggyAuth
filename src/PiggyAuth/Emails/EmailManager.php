<?php

namespace PiggyAuth\Emails;

use PiggyAuth\Tasks\SendEmailTask;
use PiggyAuth\Tasks\ValidateEmailTask;
use pocketmine\Player;

class EmailManager
{
    const SUCCESS = 0;
    const INVALID_CREDENTIALS = 1;
    const NO_EMAIL = 2;

    private $domain;
    private $api;
    private $pubapi;
    private $from;
    private $canSendEmail = false;
    private $canValidateEmail = false;

    public function __construct($plugin, $domain, $api, $pubapi, $from)
    {
        $this->plugin = $plugin;
        $this->domain = $domain;
        $this->api = $api;
        $this->pubapi = $pubapi;
        $this->from = $from;
        $this->checkCredentials();
    }

    public function getDomain()
    {
        return $this->domain;
    }

    public function setDomain($domain)
    {
        $this->domain = $domain;
        $this->checkCredentials();
    }

    public function getAPIKey()
    {
        return $this->api;
    }

    public function setAPIKey($api)
    {
        $this->api = $api;
        $this->checkCredentials();
    }

    public function getPublicAPIKey()
    {
        return $this->pubapi;
    }

    public function setPublicAPIKey($pubapi)
    {
        $this->pubapi = $pubapi;
        $this->checkCredentials();
    }

    public function getFrom()
    {
        return $this->from;
    }

    public function setFrom($from)
    {
        $this->from = $from;
        $this->checkCredentials();
    }

    public function checkCredentials()
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($ch, CURLOPT_USERPWD, 'api:' . $this->api);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($ch, CURLOPT_URL, 'https://api.mailgun.net/v3/' . $this->domain . '/messages');
        curl_setopt($ch, CURLOPT_POSTFIELDS, array(
            'from' => $this->from,
            'to' => "dummy",
            'subject' => "test",
            'text' => "test"));
        $result = json_decode(curl_exec($ch)); //Should be a message saying parameter isn't a a valid address if correct credentials
        $error = null;
        if (isset($result->message) !== true) {
            $this->canSendEmail = false;
            $error = "Invalid API key.";
        } else {
            $this->canSendEmail = $result->message == "'to' parameter is not a valid address. please check documentation";
            if ($this->canSendEmail !== true) {
                switch ($result->message) {
                    case "Domain not found: " . $this->domain:
                        $error = "Invalid domain.";
                        break;
                    case "'from' parameter is not a valid address. please check documentation":
                        $error = "Invalid from.";
                        break;
                }
            }
        }
        if ($error !== null) {
            $this->plugin->getLogger()->error($error);
        }
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($ch, CURLOPT_USERPWD, 'api:' . $this->pubapi);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
        curl_setopt($ch, CURLOPT_URL, 'https://api.mailgun.net/v3/address/validate');
        curl_setopt($ch, CURLOPT_POSTFIELDS, array('address' => "test"));
        $result = json_decode(curl_exec($ch));
        $this->canValidateEmail = $result !== null;
        if ($this->canValidateEmail !== true) {
            $this->plugin->getLogger()->error("Invalid public API key.");
        }
    }

    public function sendEmail($email, $subject, $message, $player = null) //Provide player if you want them to receive fail messages.
    {
        if ($this->canSendEmail !== true) {
            if ($player instanceof Player) {
                $player->sendMessage($this->plugin->getMessage("email-fail"));
            }
            return self::INVALID_CREDENTIALS;
        }
        if ($email == "none") {
            if ($player instanceof Player) {
                $player->sendMessage($this->plugin->getMessage("no-email"));
            }
            return self::NO_EMAIL;
        }
        $task = new SendEmailTask($this->api, $this->domain, $email, $this->from, $subject, $message, $player instanceof Player ? $player->getName() : null);
        $this->plugin->getServer()->getScheduler()->scheduleAsyncTask($task);
        return self::SUCCESS;

    }

    public function validateEmail($email, $callback = null, $args = null)
    {
        if ($email == "none") {
            $callback(true, $args, $this->plugin);
            return true;
        }
        if ($this->canValidateEmail !== true) {
            return self::INVALID_CREDENTIALS;
        }
        $task = new ValidateEmailTask($this->pubapi, $email, $callback, $args);
        $this->plugin->getServer()->getScheduler()->scheduleAsyncTask($task);
        return true;
    }
}