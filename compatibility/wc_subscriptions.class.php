<?php

class WCML_WC_Subscriptions{

    function __construct(){

        add_action('init', array($this, 'init'),9);
    }

    function init(){
        if(!is_admin()){
            add_filter('woocommerce_subscriptions_product_sign_up_fee', array($this, 'product_price_filter'), 10, 2);                
        }
    }
    
    function product_price_filter($subscription_sign_up_fee, $product){
        
        $subscription_sign_up_fee = apply_filters('wcml_raw_price_amount', $subscription_sign_up_fee, $product->ID);    
        
        return $subscription_sign_up_fee;
    }

}
