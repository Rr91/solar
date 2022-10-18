<?php

class shopSolarPlugin extends shopPlugin
{	

	public static function getRef($customer=false){
		if(!$customer){
			$user_id = wa()->getUser()->getId();
			if(!$user_id) wa_dump("ERROR 1");
			$customer = new shopCustomer($user_id);
		}
		$all_bonus = doubleval($customer->affiliateBonus());
		$model = new waModel();
		$contact_id = $customer->getId();
		$query = 'SELECT SUM(amount) AS s FROM `shop_affiliate_transaction` WHERE `contact_id` = "'.$contact_id.'" AND `type` IN ("referral_cancel", "referral_bonus")';
		$ref_summa = round($model->query($query)->fetchField('s'));
		return $all_bonus>$ref_summa?$ref_summa:$all_bonus;
	}

	public function frontendMyAffiliate()
	{
		return <<<HTML
                    <div id="solar" style="margin:20px 0;">
                    	<button id="solarstaff" class="item-c__btn btn btn_shiny btn_main-2 btn_text" style="font-size: 18px;line-height: 24px;">
                    		<span>Вывести деньги</span>
                    	</button>
                    </div>
					<link rel="stylesheet" href="/wa-apps/shop/plugins/solar/js/vendor/arcticmodal/jquery.arcticmodal-0.3.css">
					<link rel="stylesheet" href="/wa-apps/shop/plugins/solar/js/vendor/arcticmodal/themes/simple.css">
                    <script src="/wa-apps/shop/plugins/solar/js/vendor/arcticmodal/jquery.arcticmodal-0.3.min.js"></script>
                    
                    <div style="display: none;">
					    <div class="box-modal" id="solorModal">
					        <div class="box-modal_close arcticmodal-close">закрыть</div>
					        <div class="box-modal-content"></div>
					    </div>
					</div>


                    <script>
                    	$(document).ready(function(){
	                    	if(location.hash == "#solarstaff"){
	                    		$("#solarstaff").trigger("click");
	                    	}
                    	});
                    	$(document).on('click', "#solarstaff", function(e){
                    		$.ajax({
					            type: "POST",
					            url: '/solar/',
					            success: function(data) {
					               	$('#solorModal .box-modal-content').html(data);
					               	$('#solorModal').arcticmodal();

					            }
					        });
                    	});
                    	$(document).on('click', "#card_list_checker .card_delete", function(e){
                    		var that = $(this);
                    		var card_id = parseInt($(this).data('id'));
                    		$.ajax({
					            type: "POST",
					            url: '/solardelete/',
					            dataType: 'json',
					            data:{card_id:card_id},
					            success: function(data) {
					            	if(data.res == 1){
								        that.closest("li").remove();
								    }
								    else if(data.res == 3){
								    	alert(data.text);
								    }
								    else alert("ERROR");
					            }
					        });
                    	});
                    	$(document).on('click', "#card_list_checker .card_check", function(e){
                    		var card_id = parseInt($(this).data('id'));
                    		if(card_id){
                    			$('#solorModal .box-modal-content').find('h1').text("Вывести");
                    			$('#solorModal .box-modal-content').find('#card_list_checker').hide();
                    			$('#solorModal .box-modal-content').find('#amount_checker').show();

                    			$(document).on('click', "#amount_checker button", function(e){
                    				var amount = parseInt($(this).closest("#amount_checker").find("input[name=amount]").val());
                    				if(amount){
	                    				$.ajax({
								            type: "POST",
								            url: '/solarchoise/',
								            dataType: 'json',
								            data:{card_id:card_id,amount:amount},
								            success: function(data) {
								            	if(data.res == 1){
								            		alert("Платёж отправлен!");
								            		location.href = "/my/affiliate/";
								            	}
								            	else if(data.res == 3){
								            		$('#solorModal .box-modal-content').html("<p style='color:red'>"+data.text+"</p>");
								            	}
								            	else alert("UNKNOWN ERROR");
								               	// $('#solorModal .box-modal-content').html(data);
								               	// $('#solorModal').arcticmodal();

								            }
								        });
                    				}
                    			});
                    		}
                    		else{
	                    		$.ajax({
						            type: "POST",
						            url: '/solarchoise/',
						            dataType: 'json',
						            data:{card_id:card_id},
						            success: function(data) {
						            	if(data.res == 2){
						            		location.href = data.href;
						            	}
						            	else if(data.res == 3){
						            		$('#solorModal .box-modal-content').html("<p style='color:red'>"+data.text+"</p>");
						            	}
						            	else alert("UNKNOWN ERROR");
						               	// $('#solorModal .box-modal-content').html(data);
						               	// $('#solorModal').arcticmodal();

						            }
						        });
                    		}
                    	});
                    </script>

                    <style>.cards_block h1{ text-align:center; }.card_list{list-style: none;padding-left: 5px;}.card_list>li>span,.amount_checker{cursor: pointer;    font-size: 18px;
    line-height: 24px;
    display: inline-block;    position: relative;
    padding: 4px;}.card_list>li{margin: 12px 5px;}.card_list>li>span.card_new{background-color: transparent;}.card_list>li>span.card_delete{margin-left: 20px;color: red;
    position: absolute;
    text-decoration: underline;}.govno_span{position: absolute;
    bottom: 25px;
    left: 15px;
    color: #fff;}</style>

HTML;
	}
}
