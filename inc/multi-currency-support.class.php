<?php
  
class WCML_Multi_Currency_Support{

    private $client_currency;
    private $exchange_rates = array();
    
    
    function __construct(){
        
        add_action('init', array($this, 'init'), 5); 
        
        if(is_ajax()){        
            add_action('wp_ajax_nopriv_wcml_switch_currency', array($this, 'switch_currency'));
            add_action('wp_ajax_wcml_switch_currency', array($this, 'switch_currency'));
            
            add_action('wp_ajax_legacy_update_custom_rates', array($this, 'legacy_update_custom_rates'));
            add_action('wp_ajax_legacy_remove_custom_rates', array($this, 'legacy_remove_custom_rates'));
        }
        
        
        
    }
    
    function _load_filters(){
        $load = false;
        
        if(!is_admin() && $this->get_client_currency() != get_option('woocommerce_currency')){
            $load = true;
        }else{
            if(is_ajax() && $this->get_client_currency() != get_option('woocommerce_currency')){
                if(isset($_REQUEST['action'])){
                    if(in_array($_REQUEST['action'], array('woocommerce_get_refreshed_fragments', 'woocommerce_update_order_review', 'woocommerce_update_shipping_method', 'woocommerce-checkout'))){
                        $load = true;
                    }
                }
            }
        }
        
        return $load;
    }
    
    function init(){
       
        if($this->_load_filters()){    
            
            add_filter('woocommerce_currency', array($this, 'currency_filter'));
            //add_filter('option_woocommerce_currency', array($this, 'currency_filter'));
            
            add_filter('get_post_metadata', array($this, 'product_price_filter'), 10, 4);            
            add_filter('get_post_metadata', array($this, 'variation_prices_filter'), 12, 4); // second
            
            add_filter('woocommerce_available_shipping_methods', array($this, 'shipping_taxes_filter'));
            
            add_action('woocommerce_coupon_loaded', array($this, 'filter_coupon_data'));
            
            add_filter('option_woocommerce_free_shipping_settings', array($this, 'adjust_min_amount_required'));
            
            // table rate shipping support
            if(defined('TABLE_RATE_SHIPPING_VERSION')){
                add_filter('woocommerce_table_rate_query_rates', array($this, 'table_rate_shipping_rates'));
                add_filter('woocommerce_table_rate_instance_settings', array($this, 'table_rate_instance_settings'));
            }
            
        }
        
        add_shortcode('currency_switcher', array($this, 'currency_switcher'));
        
        $this->load_inline_js();
        
    }    
    
    function load_inline_js(){
        global $woocommerce;
        
        $woocommerce->add_inline_js( "
            jQuery('.wcml_currency_switcher').on('change', function(){            
                    var currency = jQuery(this).val(); 
                    jQuery('.wcml_currency_switcher').attr('disabled', 'disabled');
                    jQuery('.wcml_currency_switcher').val(currency);
                    var data = {action: 'wcml_switch_currency', currency: currency}
                    jQuery.post(woocommerce_params.ajax_url, data, function(){ 
                        jQuery('.wcml_currency_switcher').removeAttr('disabled');                                        
                        location.reload();
                    });
            });
        " );                
    }
    
    function product_price_filter($null, $object_id, $meta_key, $single){
        global $sitepress;
        
        static $no_filter = false;
                
        if(empty($no_filter) && in_array(get_post_type($object_id), array('product', 'product_variation'))){
            
            $price_keys = array(
                '_price', '_regular_price', '_sale_price', 
                '_min_variation_price', '_max_variation_price',                
                '_min_variation_regular_price', '_max_variation_regular_price',
                '_min_variation_sale_price', '_max_variation_sale_price');
            
            if(in_array($meta_key, $price_keys)){
                $no_filter = true;
                
                // exception for products migrated from before WCML 3.1 with independent prices
                // legacy prior 3.1
                $original_object_id = icl_object_id($object_id, get_post_type($object_id), false, $sitepress->get_default_language());                    
                $ccr = get_post_meta($original_object_id, '_custom_conversion_rate', true);
                if(in_array($meta_key, array('_price', '_regular_price', '_sale_price')) && isset($ccr[$meta_key][$this->get_client_currency()])){                    
                    $price_original = get_post_meta($original_object_id, $meta_key, $single);
                    $price = $price_original * $ccr[$meta_key][$this->get_client_currency()];
                    
                }else{
                        
                    // normal filtering                    
                    $price = get_post_meta($object_id, $meta_key, $single);
                    $price = apply_filters('wcml_raw_price_amount', $price, $object_id);    
                    
                }
                
                
                $no_filter = false;
            }
            
        }
        
        return !empty($price) ? $price : null;
    }
    
    function variation_prices_filter($null, $object_id, $meta_key, $single){        
        
        if(empty($meta_key) && get_post_type($object_id) == 'product_variation'){
            static $no_filter = false;
            
            if(empty($no_filter)){
                $no_filter = true;
                
                $variation_fields = get_post_meta($object_id);
                
                foreach($variation_fields as $k => $v){
                    
                    if(in_array($k, array('_price', '_regular_price', '_sale_price'))){
                        
                        foreach($v as $j => $amount){
                            
                            $variation_fields[$k][$j] = apply_filters('wcml_raw_price_amount', $amount, $object_id);    
                            
                        }
                        
                    }
                    
                }
                
                $no_filter = false;
            }
            
        }
        
        return !empty($variation_fields) ? $variation_fields : null;
        
    }

    function currency_filter($currency){
        
        $currency = apply_filters('wcml_price_currency', $currency);
        
        return $currency;
    }
    
    function shipping_taxes_filter($methods){
        static $filtered_once = false;
        
        if(empty($filtered_once)){
            
            global $woocommerce;                
            $woocommerce->shipping->load_shipping_methods();
            $shipping_methods = $woocommerce->shipping->get_shipping_methods();
            
            foreach($methods as $k => $method){
                
                // exceptions
                if(
                    isset($shipping_methods[$method->id]) && isset($shipping_methods[$method->id]->settings['type']) && $shipping_methods[$method->id]->settings['type'] == 'percent' 
                     || preg_match('/^table_rate-[0-9]+ : [0-9]$/', $k)
                ){
                    continue;
                } 
                    
                
                foreach($method->taxes as $j => $tax){
                    
                    $methods[$k]->taxes[$j] = apply_filters('wcml_shipping_price_amount', $methods[$k]->taxes[$j]);
                    
                }
                
                if($methods[$k]->cost){
                    $methods[$k]->cost = apply_filters('wcml_shipping_price_amount', $methods[$k]->cost);
                }
                
            }
            
            $filtered_once = true;
        }
        
        return $methods;
    }
    
    function table_rate_shipping_rates($rates){
        
        foreach($rates as $k => $rate){
            
            $rates[$k]->rate_cost                   = apply_filters('wcml_shipping_price_amount', $rates[$k]->rate_cost);
            $rates[$k]->rate_cost_per_item          = apply_filters('wcml_shipping_price_amount', $rates[$k]->rate_cost_per_item);
            $rates[$k]->rate_cost_per_weight_unit   = apply_filters('wcml_shipping_price_amount', $rates[$k]->rate_cost_per_weight_unit);
            
        }
        
        return $rates;
    }
    
    function table_rate_instance_settings($settings){
        
        if(is_numeric($settings['handling_fee'])){
            $settings['handling_fee'] = apply_filters('wcml_shipping_price_amount', $settings['handling_fee']);            
        }
        $settings['min_cost'] = apply_filters('wcml_shipping_price_amount', $settings['min_cost']);
        
        return $settings;
    }
    
    function adjust_min_amount_required($options){
        
        if(!empty($options['min_amount'])){
            
            $options['min_amount'] = apply_filters('wcml_shipping_free_min_amount', $options['min_amount']);
            
        }
        
        return $options;
    }    
        
    function filter_coupon_data($coupon){
        
        if($coupon->type == 'fixed_cart' || $coupon->type == 'fixed_product'){
            $coupon->amount = apply_filters('wcml_raw_price_amount', $coupon->amount);        
        }
        
        
    }
    
    function get_client_currency(){
        global $woocommerce;
        
        if(!empty($woocommerce->session)){
            $this->client_currency = $woocommerce->session->get('client_currency');
            if(is_null($this->client_currency)){
                $this->client_currency = get_option('woocommerce_currency');
                $woocommerce->session->set('client_currency', $this->client_currency);
            }
        }else{
            $this->client_currency = get_option('woocommerce_currency');
        }
        
        return apply_filters('wcml_client_currency', $this->client_currency);
    }
    
    function set_client_currency($currency){
        global $woocommerce;
        $woocommerce->session->set('client_currency', $currency);    
        do_action('wcml_set_client_currency', $currency);
        
    }
    
    function get_exchange_rates(){
            
        $exchange_rates = apply_filters('wcml_exchange_rates', $this->exchange_rates);
        
        return $exchange_rates;
        
    }

    function legacy_update_custom_rates(){
        
        foreach($_POST['posts'] as $post_id => $rates){
            
            update_post_meta($post_id, '_custom_conversion_rate', $rates);
            
        }
        
        echo json_encode(array());
        
        exit;
    }
    
    function legacy_remove_custom_rates(){
        
        delete_post_meta($_POST['post_id'], '_custom_conversion_rate');
        echo json_encode(array());
        
        exit;
    }
    
    
    function currency_switcher( $atts ){
        
        // format tags
        // Name     - %name
        // Symbol   - %symbol
        // Code     - $code
        
        extract( shortcode_atts( array(
                'format' => '%name (%symbol)',
            ), $atts ) );
        
        $wc_currencies = get_woocommerce_currencies();
                
        $exchange_rates = $this->get_exchange_rates();
        
        echo '<select class="wcml_currency_switcher">';
        foreach($exchange_rates as $currency => $rate){
            $selected = $currency == $this->get_client_currency() ? ' selected="selcted"' : '';
            
            $currency_format = preg_replace(array('#%name#', '#%symbol#', '#%code#'), 
                array($wc_currencies[$currency], get_woocommerce_currency_symbol($currency), $currency), $format);
            
            echo '<option value="' . $currency . '"' . $selected . '>' . $currency_format . '</option>';            
        }
        echo '</select>';
        
    }
    
    function switch_currency(){
        
        $this->set_client_currency($_POST['currency']);
        
        do_action('wcml_switch_currency', $_POST['currency']);
        
        exit;
        
    }
    
    
    
}