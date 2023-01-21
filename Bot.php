<?php
ini_set('display_errors', 1);
ini_set('error_reporting', E_ALL);

class Bot {
    private $token;
    private $host;
    private $subscribers;
    private $subscribersFile = 'subscribers.log';
    private $updateFile = 'lastUpdate.log';
    public $startMessage = null;
    public $startButton = null;

    public function __construct($token, $startMessage = null, $startButton = null){
        $this->token = $token;
        $this->host = 'https://api.telegram.org/bot';
        $this->subscribers = $this->getAllSubscribers();

        if($startMessage){
            $this->startMessage = $startMessage;

            if($startButton){
                $this->startButton = $startButton;
            }
        }
    }

    public function sendImage($chatId, $imageUrl){
        $addr = $this->host.$this->token."/getUpdates";

        $response = $this->sendRequest($addr, [
            'chat_id' => $chatId,
            'photo' => $imageUrl
        ]);

        return json_decode($response, true);
    }

    public function sendMessageToAll($text){
        if($this->subscribers){
            foreach($this->subscribers as $subscriber){
                $this->sendMessage($text, $subscriber);
            }
        }
    }

    public function getUpdates(){
        $result = [];
        $addr = $this->host.$this->token."/getUpdates";

        $updateId = 0;
        $lastUpdate = file_get_contents($this->updateFile);

        if($lastUpdate){
            $updateId = trim($lastUpdate);
        }

        if($updateId){
            $addr .= '?offset='.$updateId;
        }

        $update = $this->sendRequest($addr);

        if($update){
            $update = json_decode($update, true);

            if(isset($update['result'])){
                foreach ($update['result'] as $key => $r){
                    if($r['update_id'] == $updateId){
                        continue;
                    }

                    if(isset($r['message'])){
                        if(!in_array($r['message']['from']['id'], $this->subscribers) || $r['message']['text'] == '/start'){
                            $this->addSubscriber($r['message']['from']['id']);
                        }
                    }

                    $result[] = $r;
                    file_put_contents($this->updateFile, $r['update_id']);
                }
            }
        }

        return $result;
    }

    public function sendMessage($text, $userId, $keyboard = null, $oneTimeKey = true){
        $addr = $this->host.$this->token."/sendMessage";
        $params = [
            'chat_id' => $userId,
            'text' => $text
        ];

        if($keyboard){
            $keys = [];
            foreach ($keyboard as $key){
                $keys[] = ['text' => $key];
            }

            $params['reply_markup'] = json_encode([
                'one_time_keyboard' => $oneTimeKey,
                'resize_keyboard' => true,
                'keyboard'=>[$keys]
            ]);
        }

        $response = $this->sendRequest($addr, $params);

        $responseArr = json_decode($response, true);

        if($responseArr && isset($responseArr['error_code']) && $responseArr['error_code']){
            switch ($responseArr['error_code']) {
                case 403:
                    $this->removeSubscribers($userId);
                    break;
            }
        }

        return $response;
    }

    public function removeSubscribers($userId){
        $newSubsList = [];

        foreach ($this->subscribers as $subscriber){
            if($subscriber != $userId){
                $newSubsList[] = $subscriber;
            }
        }

        $this->subscribers = $newSubsList;
        $this->saveSubscribers();
    }

    public function getAllSubscribers(){
        $result = [];

        if(file_exists($this->subscribersFile)){
            $subs = file($this->subscribersFile);

            if($subs){
                foreach ($subs as $item) {
                    if($item){
                        $result[] = trim($item);
                    }
                }
            }
        }

        return $result;
    }

    public function addSubscriber($id){
        if($this->startMessage){
            $this->sendMessage($this->startMessage, $id, $this->startButton, false);
        }

        if(!in_array($id, $this->subscribers)){
            $this->subscribers[] = $id;
            $this->saveSubscribers();
        }
    }

    private function saveSubscribers(){
        $subsTxt = '';

        foreach ($this->subscribers as $subscriber) {
            $subsTxt .= $subscriber."\n";
        }

        file_put_contents($this->subscribersFile, $subsTxt);
    }

    private function sendRequest($addr, $param = []){
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => $addr,
            CURLOPT_HEADER => false,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => ["Content-type: application/json"],
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_POSTFIELDS => json_encode($param),
            CURLOPT_POST => true,
            CURLOPT_CONNECTTIMEOUT => 30,
            CURLOPT_TIMEOUT => 100
        ));

        return curl_exec($curl);
    }
}