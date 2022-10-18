<?php

class shopSolarPluginFrontendChoiseController extends waController{
    public function execute(){
        // проверяем пользователя id
        $user_id = wa()->getUser()->getId();
        if(!$user_id){
            echo json_encode(array("res" => 3, "text" => "Пользователь не найден"));
            exit;
        }

        $customer = new shopCustomer($user_id);        
        // отправляем пользователя в конструктор класса API
        $api = new shopSolarApi($customer);

        if(!$api->getAmountStatus()){
            echo json_encode(array("res" => 3, "text" => "Недостаточно средств"));
            exit;
        }

        $card_id = waRequest::post("card_id", 0, 'int');
        if($card_id){
            $amount = waRequest::post("amount", 0, 'int');
            if($amount){
                if($api->checkAmount($amount)){
                    $api->payOnCart($amount, $card_id);
                    echo json_encode(array("res" => 1));
                    exit;
                }else{
                    echo json_encode(array("res" => 3, "text" => "Недостаточно средств для перевода"));
                    exit;
                }
            }
            else{
               echo json_encode(array("res" => 3, "text" => "Ошибка суммы"));
               exit;
            }
        }else{
            $result = $api->createCart();
            echo json_encode(array("res" => 2, "href" => $result));
            exit;
        }

    }
}