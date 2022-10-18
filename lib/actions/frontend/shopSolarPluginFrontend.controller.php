<?php

class shopSolarPluginFrontendController extends waController{
    public function execute(){
        // проверяем пользователя id
        $user_id = wa()->getUser()->getId();
        if(!$user_id) wa_dump("ERROR 1");

        $customer = new shopCustomer($user_id);        
        // отправляем пользователя в конструктор класса API
        $api = new shopSolarApi($customer);
        if(!$api->getAmountStatus()){
            echo "<p style='color:red'>Недостаточно средств для перевода на карту</p>";
            exit;
        }
        // возвращаем назад список карт с предложением выбрать одну
        // если карт нет, перенаправляем на заполнения карты
        $cardlist_html = $api->checkCard(4);
        echo $cardlist_html;
        exit;
        // после заполнения, в url для редиректа указываем affilate и post-параметр, если post-параметр есть, делаем тригер нажатия на кнопку и в возвращемся в начало этого скрипта 
        // после клика на карту из списка добавляем на affilate и эту js-обработку
    	// вызываем другой контроллер который будет делать отправку денег 

    }
}