<?php
  
  
class WCML_Ajax_Setup{
    
    function __construct(){
        
        add_action('init', array($this, 'init'));
        add_action('localize_woocommerce_on_ajax', array($this, 'localize_woocommerce_on_ajax'));
        
    }
    
    function init(){
        if (wpml_is_ajax()){
           do_action('localize_woocommerce_on_ajax');
        }
        
        add_filter('woocommerce_params', array($this, 'filter_woocommerce_ajax_params'));
        add_action( 'woocommerce_checkout_order_review', array($this,'filter_woocommerce_order_review'), 9 );
        add_action( 'woocommerce_checkout_update_order_review', array($this,'filter_woocommerce_order_review'), 9 );
        
    }
    
    function filter_woocommerce_order_review(){
        global $woocommerce;
        unload_textdomain('woocommerce');
        $woocommerce->load_plugin_textdomain();
        
    }

    function filter_woocommerce_ajax_params($woocommerce_params){
        global $sitepress, $post;
        $value = array();
        $value = $woocommerce_params;

        if($sitepress->get_current_language() !== $sitepress->get_default_language()){
            $value['ajax_url'] = add_query_arg('lang', ICL_LANGUAGE_CODE, $woocommerce_params['ajax_url']);
            $value['checkout_url'] = add_query_arg('action', 'woocommerce-checkout', $value['ajax_url']);
        }

        if(!isset($post->ID)){
            return $value; 
        }

        $checkout_page_id = get_option('woocommerce_checkout_page_id');
        $pay_page_id = get_option('woocommerce_pay_page_id');
        $cart_page_id = get_option('woocommerce_cart_page_id');

        $translated_checkout_page_id = icl_object_id($checkout_page_id, 'page', false);
        $translated_pay_page_id = icl_object_id($pay_page_id, 'page', false);
        $translated_cart_page_id = icl_object_id($cart_page_id, 'page', false);
        

        if($translated_cart_page_id == $post->ID){
            $value['is_cart'] = 1;
            $value['cart_url'] = get_permalink($translated_cart_page_id);
        } else if($translated_checkout_page_id == $post->ID || $checkout_page_id == $post->ID){
            $value['is_checkout'] = 1;

            $_SESSION['wpml_globalcart_language'] = $sitepress->get_current_language();

        } else if($translated_pay_page_id == $post->ID){
            $value['is_pay_page'] = 1;
        }

        return $value; 
    }
    
    function localize_woocommerce_on_ajax(){
        global $sitepress;
        
        $current_language = $sitepress->get_current_language();
        
        $sitepress->switch_lang($current_language, true);
    }
    
    
} 
