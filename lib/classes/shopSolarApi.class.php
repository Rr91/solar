<?php


class shopSolarApi
{
    private $plugin;
    private $salt;
    private $customer_data;
    private $worker_id;
    private $clientId;
    private $max_affiliate_bonus;
    private $min_amount;
    private $amount_status;
    private $specialization;
    private $country;
    private $send_message;
    private $language;
    private $currency;
    private $todo_attributes;
    private $redirect_url;
    private $url_workers;

    public function __construct($customer){
        $data = $customer->load();
        $this->customer_data = array(
            "contact_id" => $data["id"],
            "first_name" => $data["firstname"],
            "last_name" => $data["lastname"],
            "middle_name" => $data["middlename"],
            "email" => $data["email"][0]['value'],
        );
        $this->worker_id = 0;
        $this->max_affiliate_bonus = shopSolarPlugin::getRef($customer);
        $this->amount_status = 1;
        $this->min_amount = 1000;
        if($this->min_amount > $this->max_affiliate_bonus) $this->amount_status = 0;

        $this->plugin = wa('shop')->getPlugin('solar');
        $settings = $this->plugin->getSettings();
        $this->clientId = $settings['client_id'];
        $this->salt = $settings['salt'];

        $this->specialization = 533; // специализация получателя
        $this->country = "RU"; // страна получателя
        $this->send_message = 0; // отправить исполнителю письмо со ссылкой на подтверждение регистрации в сервисе
        $this->language = "ru"; // Язык email
        $this->currency = "RUB"; // Валюта
        $this->todo_attributes = "https://vplaboratory.ru/"; // источник
        $this->redirect_url = "https://vplaboratory.ru/my/affiliate/#solarstaff"; // источник

        $this->url_curl = array(
            "worker_find" => "https://api.solar-staff.com/v1/workers",
            "worker_create" => "https://api.solar-staff.com/v1/workers",
            "worker_remove" => "https://api.solar-staff.com/v1/workers",
            "cards_list" => "https://api.solar-staff.com/v1/workers",
            "card_verify" => "https://api.solar-staff.com/v1/workers",
            "card_remove" => "https://api.solar-staff.com/v1/workers",
            "payout" => "https://api.solar-staff.com/v1/payment",
        );
    }

    public function checkAmount($amount)
    {
        if($amount >= $this->min_amount && $amount <= $this->max_affiliate_bonus) 
            return true;
        return false;
    }

    public function getAmountStatus()
    {
        return $this->amount_status;
    }

    private function getSign($params){
        ksort($params);
        foreach ($params as $key => $value) {
            $signSource .= $key . ':' . $value . ';';
        }
        $signSource .= $this->salt;
        return sha1($signSource);
    }


    private function execCurl($action, $payload)
    {
        // TODO try catch ss
        $url = isset($this->url_curl[$action])?$this->url_curl[$action]:wa_dump("ERROR 87");
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt( $ch, CURLOPT_HTTPHEADER, array('Content-Type:application/json'));
        curl_setopt( $ch, CURLOPT_POSTFIELDS, $payload );
        curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
        $response = curl_exec($ch);
        curl_close($ch);
        return $response;
    }

    private function payload($action, $params)
    {
        $sign = $this->getSign($params);
        $payload = json_encode(array_merge($params, ['signature' => $sign]));
        $response = $this->execCurl($action, $payload);
        return json_decode($response, 1);
    }

    public function deleteWorker(){
        $action = "worker_remove";
        $params = [
            'action' => $action,    
            'email' => $this->customer_data['email'],    
            'client_id' => $this->clientId,
        ];

        $response = $this->payload($action, $params);
    }

    public function createWorker(){
        $action = "worker_create";
        $params = [
            'action' => $action,    
            'email' => $this->customer_data['email'],    
            'password' => md5($this->customer_data['email']),    
            'first_name' => $this->customer_data["first_name"],    
            'last_name' => $this->customer_data["last_name"],    
            'middle_name' => $this->customer_data["middle_name"],    
            'specialization' => $this->specialization,    
            'country' => $this->country,    
            'send_message' => $this->send_message,    
            'language' => $this->language,    
            'client_id' => $this->clientId,
        ];

        $response = $this->payload($action, $params);
        if($response['code'] == 200){
            // var_dump("Ura");
            return $response["response"]['id'];
        }
        else{
            // var_dump("ERROR");
            return 0;
        }

    }

    public function findWorker(){
        $action = "worker_find";
        $params = [
            'action' => $action,    
            'email' => $this->customer_data['email'],    
            'client_id' => $this->clientId,
        ];

        $response = $this->payload($action, $params);
        if($response['code'] == 200){
            // var_dump("Ura");
            return $response["response"]['id'];
        }
        else{
            // var_dump("ERROR");
            return null;
        }
        // var_dump($response);
    }

    public function cardsList(){
        if(!$this->worker_id){
            wa_dump("ERROR 167");
        }
        $action = "cards_list";
        $params = [
            'action' => $action,    
            'worker_id' => $this->worker_id,    
            'client_id' => $this->clientId, 
        ];
        $response = $this->payload($action, $params);
        return $response['code'] == 200?$response['response']:wa_dump("ERROR 176", $response);
    }

    public function cardVerify(){
        $action = "card_verify";
        $params = [
            'action' => $action,    
            'worker_id' => $this->worker_id,      
            'currency' => $this->currency, 
            'language' => $this->language,   
            "redirect_url" => $this->redirect_url,
            'client_id' => $this->clientId, 
        ];
        $response = $this->payload($action, $params);
        if($response['code'] == 200){
            $terminal_url = $response['response']['terminal_url'];
            return $terminal_url;
        }
        else wa_dump("ERROR 194", $response);
    }

    public function newcountAffilate($amount){
        $model = new shopAffiliateTransactionModel();
        $amount = -1*$amount;
        $model->applyBonus($this->customer_data['contact_id'], $amount, null, "Снятие бонусов через Solar Staff", "solarstaff");
    }

    public function payout($amount, $card_id){
        $action = "payout";
        $params = [
            'action' => $action,    
            'worker_id' => $this->worker_id,    
            'merchant_transaction' => md5(time()),
            'card_id' => $card_id, 
            'currency' => $this->currency, 
            'amount' => $amount, 
            'todo_attributes' => $this->todo_attributes,
            'client_id' => $this->clientId, 
        ];
        $response = $this->payload($action, $params);
        if($response['code'] == 200){
            $this->newcountAffilate($amount);
            return true;
        }else{
            wa_dump("ERROR 220", $response);
        }
    }

    public function payOnCart($amount, $card_id)
    {
        if(!$this->worker_id){
            $this->worker_id = $this->findWorker();
        }
        if(!$this->worker_id){
            $this->worker_id = $this->createWorker();
        }
         return $this->payout($amount, $card_id);
    }


    public function createCart(){
        if(!$this->worker_id){
            $this->worker_id = $this->findWorker();
        }
        if(!$this->worker_id){
            $this->worker_id = $this->createWorker();
        }
        return $this->cardVerify();

    }

     public function deleteCard($card_id){
        if(!$this->worker_id){
            $this->worker_id = $this->findWorker();
        }
        if(!$this->worker_id){
            $this->worker_id = $this->createWorker();
        }
        $action = "card_remove";
        $params = [
            'action' => $action,    
            'worker_id' => $this->worker_id,      
            'client_id' => $this->clientId, 
            'card_id' => $card_id, 
        ];
        $response = $this->payload($action, $params);
        if($response['code'] == 200){
            return true;
        }
        else wa_dump("ERROR 265", $response);
    }



    public function checkCard($status = false){
        if(!$this->worker_id){
            $this->worker_id = $this->findWorker();
        }
        if(!$this->worker_id){
            $this->worker_id = $this->createWorker();
        }

        $cards_list = $this->cardsList();
        $true_card_list = array();
        foreach ($cards_list as $card) {
            if($status){
                if($card['verify_status_id'] == $status){
                    $true_card_list[] = $card;
                }
            }
        }
        return $this->getHtmlCardList($true_card_list);
        
    }


    public function getHtmlCardList($card_list){
        $html = "<div class='cards_block'>";
        $html .= "<h1>Выберите карту</h1>";
        $html .= "<ul class='card_list' id='card_list_checker'>";
        foreach ($card_list as $card) {
            $html .= "<li><span class='card_check' data-id='".$card['card_id']."'><span class='govno_span'>".$card['card_number']."</span><img src='/wa-apps/shop/plugins/solar/img/map.svg' style='width: 180px;'></span><span class='card_delete' data-id='".$card['card_id']."'>Удалить</span></li>";
        }
        $html .= "<li><span data-id='0' class='card_new card_check'><img src='/wa-apps/shop/plugins/solar/img/mapnew.svg' style='width: 155px;'></span></li>";
        $html .= "</ul>";
        $html .= "<div id='amount_checker'  style='display:none;'><div style='display:flex;flex-direction:column;'><label><input type='text' placeholder='Введите сумму' name='amount'/> руб.</label> <button class='amount_checker' style='margin-top: 20px;
    width: 200px;'>Оплатить</button></div></div>";
        $html .= "</div>";
        return $html;
    }
}