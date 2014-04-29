<?php
  
global $woocommerce, $woocommerce_wpml;

if(version_compare(preg_replace('#-(.+)$#', '', $woocommerce->version), '2.1', '<')){
    
    //wc_enqueue_js
    if(!function_exists('wc_enqueue_js')){
        function wc_enqueue_js($code){
            global $woocommerce;
            return $woocommerce->add_inline_js($code) ;
        }
        
    }
    
    //wc_get_attribute_taxonomies
    if(!function_exists('wc_get_attribute_taxonomies')){
        
        function wc_get_attribute_taxonomies(){
            global $woocommerce;
            return $woocommerce->get_attribute_taxonomies();
        }
        
    }
    
    // 
    add_filter('wcml_wc_installed_pages', 'wcml_wc_2_0_backward_compatibility_pages');
    function wcml_wc_2_0_backward_compatibility_pages($pages){
        
        $pages = array(
            'woocommerce_shop_page_id',
            'woocommerce_cart_page_id',
            'woocommerce_checkout_page_id',
            'woocommerce_myaccount_page_id',
            'woocommerce_lost_password_page_id',
            'woocommerce_edit_address_page_id',
            'woocommerce_view_order_page_id',
            'woocommerce_change_password_page_id',
            'woocommerce_logout_page_id',
            'woocommerce_pay_page_id',
            'woocommerce_thanks_page_id'
        );
        
        return $pages;
        
    }
    
    //    
    function wcml_wc_2_0_backward_compatibility_register_shipping_methods($available_methods){
        foreach($available_methods as $method){
            $method->label = icl_translate('woocommerce', $method->label .'_shipping_method_title', $method->label);
        }
        return $available_methods;
    }
    add_filter('woocommerce_available_shipping_methods', 'wcml_wc_2_0_backward_compatibility_register_shipping_methods');
    
    if(isset($woocommerce_wpml->multi_currency_support)){
        add_filter('woocommerce_available_shipping_methods', array($woocommerce_wpml->multi_currency_support, 'shipping_taxes_filter'));    
    }

    add_filter('woocommerce_in_cart_product_title',array($this->strings, 'translated_cart_item_name'), 10, 3);
      
      
}
  
  
