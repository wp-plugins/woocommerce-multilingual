<?php
class WCML_Orders{
    
    function __construct(){
        
        add_action('init', array($this, 'init'));
        
        
    }
    
    function init(){
        
        add_action('woocommerce_shipping_update_ajax', array($this, 'fix_shipping_update'));
        add_action('woocommerce_checkout_update_order_meta', array($this, 'set_order_language'));

    }
    
    // Fix for shipping update on the checkout page.
    function fix_shipping_update($amount){
        global $sitepress, $post;
        
        if($sitepress->get_current_language() !== $sitepress->get_default_language() && $post->ID == $this->checkout_page_id()){
        
            $_SESSION['icl_checkout_shipping_amount'] = $amount;
            
            $amount = $_SESSION['icl_checkout_shipping_amount'];
        
        }
    
        return $amount;
    }


    /**
     * Adds language to order post type.
     * 
     * Language was stored in the session created on checkout page.
     * See params().
     * 
     * @param type $order_id
     */ 
    function set_order_language($order_id) { 
        if(!get_post_meta($order_id, 'wpml_language')){
            $language = isset($_SESSION['wpml_globalcart_language']) ? $_SESSION['wpml_globalcart_language'] : ICL_LANGUAGE_CODE;
            update_post_meta($order_id, 'wpml_language', $language);
        }
    }

}
