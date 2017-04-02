<?php

namespace PiggyAuth\Tasks;

use pocketmine\scheduler\AsyncTask;
use pocketmine\Player;
use pocketmine\Server;

class SendEmailTask extends AsyncTask
{
    private $api;
    private $domain;
    private $to;
    private $from;
    private $subject;
    private $message;
    private $error;
    private $player;


    public function __construct($api, $domain, $to, $from, $subject, $message, $player)
    {
        $this->api = serialize($api);
        $this->domain = serialize($domain);
        $this->to = serialize($to);
        $this->from = serialize($from);
        $this->subject = serialize($subject);
        $this->message = serialize($message);
        $this->player = $player;
    }

    public function onRun()
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($ch, CURLOPT_USERPWD, 'api:' . unserialize($this->api));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($ch, CURLOPT_URL, 'https://api.mailgun.net/v3/' . unserialize($this->domain) . '/messages');
        curl_setopt($ch, CURLOPT_POSTFIELDS, array(
            'from' => unserialize($this->from),
            'to' => unserialize($this->to),
            'subject' => unserialize($this->subject),
            'text' => unserialize($this->message)));
        $result = curl_exec($ch);
        if (curl_error($ch) == "SSL certificate problem: unable to get local issuer certificate") {
            $this->error = "SSL certificate problem: unable to get local issuer certificate\nPlease make sure you have downloaded the file from https://github.com/MCPEPIG/PiggyAuth-MailGunFiles & edited the php.ini.";
        }
        curl_close($ch);
    }

    public function onCompletion(Server $server)
    {
        $player = $server->getPlayerExact($this->player);
        if ($this->error !== null) {
            $server->getPluginManager()->getPlugin("PiggyAuth")->getLogger()->error($this->error);
            if ($player instanceof Player) {
                $player->sendMessage($server->getPluginManager()->getPlugin("PiggyAuth")->getMessage("email-fail"));
            }
        } else {
            if ($player instanceof Player) {
                $player->sendMessage($server->getPluginManager()->getPlugin("PiggyAuth")->getMessage("email-success"));
            }
        }
    }

}
