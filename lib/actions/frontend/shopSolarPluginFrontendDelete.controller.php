<?php

class shopSolarPluginFrontendDeleteController extends waController{
    public function execute(){
        $user_id = wa()->getUser()->getId();
        if(!$user_id){
            echo json_encode(array("res" => 3, "text" => "Пользователь не найден"));
            exit;
        }

        $customer = new shopCustomer($user_id);        
        // отправляем пользователя в конструктор класса API
        $api = new shopSolarApi($customer);

        $card_id = waRequest::post("card_id", 0, 'int');
        if($card_id){
            $del = $api->deleteCard($card_id);
            if($del){
                echo json_encode(array("res" => 1));
                exit;
            }
        }
    }
}