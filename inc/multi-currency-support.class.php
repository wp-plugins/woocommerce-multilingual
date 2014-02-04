<?php
  
class WCML_Multi_Currency_Support{

    private $client_currency;
    private $exchange_rates = array();
    
    
    function __construct(){
        
        add_action('init', array($this, 'init'), 5); 
        add_action('wp_head', array($this, 'set_default_currency')); //@todo - review
        
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
                    $ajax_actions = array('woocommerce_get_refreshed_fragments', 'woocommerce_update_order_review', 'woocommerce-checkout', 'woocommerce_checkout');
                    if(version_compare($GLOBALS['woocommerce']->version, '2.1', '>=')){
                        $ajax_actions[] = 'woocommerce_update_shipping_method';
                    }
                    if(in_array($_REQUEST['action'], $ajax_actions)){
                        $load = true;
                    }
                }
            }
        }
        
        return $load;
    }
    
    function init(){
        
        if($this->_load_filters()){    

            $this->set_default_currency();
            
            add_filter('woocommerce_currency', array($this, 'currency_filter'));
            //add_filter('option_woocommerce_currency', array($this, 'currency_filter'));
            
            add_filter('get_post_metadata', array($this, 'product_price_filter'), 10, 4);            
            add_filter('get_post_metadata', array($this, 'variation_prices_filter'), 12, 4); // second
            
            add_filter('woocommerce_package_rates', array($this, 'shipping_taxes_filter'));
            
            add_action('woocommerce_coupon_loaded', array($this, 'filter_coupon_data'));
            
            add_filter('option_woocommerce_free_shipping_settings', array($this, 'adjust_min_amount_required'));
            
            // table rate shipping support
            if(defined('TABLE_RATE_SHIPPING_VERSION')){
                add_filter('woocommerce_table_rate_query_rates', array($this, 'table_rate_shipping_rates'));
                add_filter('woocommerce_table_rate_instance_settings', array($this, 'table_rate_instance_settings'));
            }
            
            add_action('currency_switcher', array($this, 'currency_switcher'));
            
        }
        
        add_shortcode('currency_switcher', array($this, 'currency_switcher'));
        
        $this->load_inline_js();
        
    }    
    
    function set_default_currency(){

        if(!is_admin()){
            global $sitepress,$woocommerce_wpml,$post;
            $current_language = $sitepress->get_current_language();
            $currency_code = $woocommerce_wpml->settings['default_currencies'][$current_language];

            if($currency_code && (isset($post->ID) && get_post_type($post->ID) == 'product' && isset($_COOKIE['_wcml_product_id']) && $post->ID != $_COOKIE['_wcml_product_id'])){
                $this->set_client_currency($currency_code);

            }elseif(!$woocommerce_wpml->settings['currencies_languages'][$this->get_client_currency()][$current_language]){
                foreach($woocommerce_wpml->settings['currencies_languages'] as $code=>$langs){
                    if($langs[$current_language]){
                        $this->set_client_currency($code);
                        break;
                    }
                }
            }

            $this->set_current_product_id();

        }
    }

    function set_current_product_id(){
        global $post;

        if(isset($post->ID) && !headers_sent()){
            setcookie( '_wcml_product_id', $post->ID, time() + 86400, '/', $_SERVER[ 'HTTP_HOST' ] );
        }
    }
    
    function load_inline_js(){
        
        wc_enqueue_js( "
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
                    // 1. manual prices
                    $manual_prices = $this->get_product_custom_prices($object_id, $this->get_client_currency());
                    
                    if($manual_prices && !empty($manual_prices[$meta_key])){
                        
                        $price = $manual_prices[$meta_key];
                        
                    }else{
                    // 2. automatic conversion
                        $price = get_post_meta($object_id, $meta_key, $single);
                        $price = apply_filters('wcml_raw_price_amount', $price, $object_id);    
                        
                    }
                    
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
                
                $manual_prices = $this->get_product_custom_prices($object_id, $this->get_client_currency());
                
                foreach($variation_fields as $k => $v){
                    
                    if(in_array($k, array('_price', '_regular_price', '_sale_price'))){
                        
                        foreach($v as $j => $amount){
                            
                            if(isset($manual_prices[$k])){
                                $variation_fields[$k][$j] = $manual_prices[$k];     // manual price
                                
                            }else{
                                $variation_fields[$k][$j] = apply_filters('wcml_raw_price_amount', $amount, $object_id);   // automatic conversion     
                            }
                            
                            
                        }
                        
                    }
                    
                }
                
                $no_filter = false;
            }
            
        }
        
        return !empty($variation_fields) ? $variation_fields : null;
        
    }

    function get_product_custom_prices($product_id, $currency = false){
        global $wpdb, $sitepress;
        
        $distinct_prices = false;
        
        if(empty($currency)){
            $currency = $this->get_client_currency();
        }
        
        $original_product_id = $product_id;
        $post_type = get_post_type($product_id);
        $product_translations = $sitepress->get_element_translations($sitepress->get_element_trid($product_id, 'post_'.$post_type), 'post_'.$post_type);
        foreach($product_translations as $translation){
            if($translation->original){
                $original_product_id = $translation->element_id;
                break;
            }
        }
        
        $product_meta = get_post_custom($original_product_id);
        
        $custom_prices = false;
        
        if(!empty($product_meta['_wcml_custom_prices_status'][0])){
        
            $prices_keys = array(
                '_price', '_regular_price', '_sale_price', 
                '_min_variation_price', '_max_variation_price',                
                '_min_variation_regular_price', '_max_variation_regular_price',
                '_min_variation_sale_price', '_max_variation_sale_price');
            
            foreach($prices_keys as $key){
                
                if(!empty($product_meta[$key . '_' . $currency][0])){
                    $custom_prices[$key] = $product_meta[$key . '_' . $currency][0];
                }
                
            }
        
        }
        
        if(!isset($custom_prices['_price'])) return false;
        
        $current__price_value = $custom_prices['_price'];
        
        // update sale price
        if(!empty($custom_prices['_sale_price'])){
            
            if(!empty($product_meta['_wcml_schedule_' . $currency][0])){
                // custom dates
                if(!empty($product_meta['_sale_price_dates_from_' . $currency][0]) && !empty($product_meta['_sale_price_dates_to_' . $currency][0])){
                    if(current_time('timestamp') > $product_meta['_sale_price_dates_from_' . $currency][0] && current_time('timestamp') < $product_meta['_sale_price_dates_to_' . $currency][0]){
                        $custom_prices['_price'] = $custom_prices['_sale_price'];
                    }else{
                        $custom_prices['_price'] = $custom_prices['_regular_price'];
                    }
                }else{
                    $custom_prices['_price'] = $custom_prices['_sale_price'];
                }
                
            }else{
                // inherit
                if(!empty($product_meta['_sale_price_dates_from'][0]) && !empty($product_meta['_sale_price_dates_to'][0])){
                    if(current_time('timestamp') > $product_meta['_sale_price_dates_from'][0] && current_time('timestamp') < $product_meta['_sale_price_dates_to'][0]){
                        $custom_prices['_price'] = $custom_prices['_sale_price'];
                    }else{
                        $custom_prices['_price'] = $custom_prices['_regular_price'];
                    }
                }else{
                    $custom_prices['_price'] = $custom_prices['_sale_price'];
                }
            }
            
        }
        
        if($custom_prices['_price'] != $current__price_value){
            update_post_meta($product_id, '_price_' . $currency, $custom_prices['_price']);
        }
        
        // detemine min/max variation prices        
        if(!empty($product_meta['_min_variation_price'])){
            
            static $product_min_max_prices = array();
            
            if(empty($product_min_max_prices[$product_id])){
                
                // get variation ids
                $variation_ids = $wpdb->get_col($wpdb->prepare("SELECT ID FROM {$wpdb->posts} WHERE post_parent = %d", $product_id));
                
                // variations with custom prices
                $res = $wpdb->get_results("SELECT post_id, meta_value FROM {$wpdb->postmeta} WHERE post_id IN(" . join(',', $variation_ids) . ") AND meta_key='_wcml_custom_prices_status'");
                foreach($res as $row){
                    $custom_prices_enabled[$row->post_id] = $row->meta_value;
                }
                
                // REGULAR PRICES
                // get custom prices
                $res = $wpdb->get_results("SELECT post_id, meta_value FROM {$wpdb->postmeta} WHERE post_id IN(" . join(',', $variation_ids) . ") AND meta_key='_regular_price_" . $currency . "'");
                foreach($res as $row){
                    $regular_prices[$row->post_id] = $row->meta_value;
                }
                
                // get default prices (default currency)
                $res = $wpdb->get_results("SELECT post_id, meta_value FROM {$wpdb->postmeta} WHERE post_id IN(" . join(',', $variation_ids) . ") AND meta_key='_regular_price'");
                foreach($res as $row){
                    $default_regular_prices[$row->post_id] = $row->meta_value;
                }
                
                // include the dynamic prices
                foreach($variation_ids as $vid){
                    if(empty($regular_prices[$vid]) && isset($default_regular_prices[$vid])){
                        $regular_prices[$vid] = apply_filters('wcml_raw_price_amount', $default_regular_prices[$vid], $vid);    
                    }
                }
                
                // SALE PRICES
                // get custom prices
                $res = $wpdb->get_results("SELECT post_id, meta_value FROM {$wpdb->postmeta} WHERE post_id IN(" . join(',', $variation_ids) . ") AND meta_key='_sale_price_" . $currency . "'");
                foreach($res as $row){
                    $custom_sale_prices[$row->post_id] = $row->meta_value;
                }
                
                // get default prices (default currency)
                $res = $wpdb->get_results("SELECT post_id, meta_value FROM {$wpdb->postmeta} WHERE post_id IN(" . join(',', $variation_ids) . ") AND meta_key='_sale_price' AND meta_value <> ''");
                foreach($res as $row){
                    $default_sale_prices[$row->post_id] = $row->meta_value;
                }
                
                // include the dynamic prices
                foreach($variation_ids as $vid){
                    if(empty($sale_prices[$vid]) && isset($default_sale_prices[$vid])){
                        $sale_prices[$vid] = apply_filters('wcml_raw_price_amount', $default_sale_prices[$vid], $vid);    
                    }
                }
                
                
                // PRICES
                // get custom prices
                $res = $wpdb->get_results("SELECT post_id, meta_value FROM {$wpdb->postmeta} WHERE post_id IN(" . join(',', $variation_ids) . ") AND meta_key='_price_" . $currency . "'");
                foreach($res as $row){
                    $custom_prices_prices[$row->post_id] = $row->meta_value;
                }
                
                // get default prices (default currency)
                $res = $wpdb->get_results("SELECT post_id, meta_value FROM {$wpdb->postmeta} WHERE post_id IN(" . join(',', $variation_ids) . ") AND meta_key='_price'");
                foreach($res as $row){
                    $default_prices[$row->post_id] = $row->meta_value;
                }
                
                // include the dynamic prices
                foreach($variation_ids as $vid){
                    if(empty($custom_prices_prices[$vid]) && isset($default_prices[$vid])){
                        $prices[$vid] = apply_filters('wcml_raw_price_amount', $default_prices[$vid], $vid);    
                    }
                }
                
                if(!empty($regular_prices)){
                    $product_min_max_prices[$product_id]['_min_variation_regular_price'] = min($regular_prices);
                    $product_min_max_prices[$product_id]['_max_variation_regular_price'] = max($regular_prices);
                }
                
                if(!empty($sale_prices)){
                    $product_min_max_prices[$product_id]['_min_variation_sale_price'] = min($sale_prices);
                    $product_min_max_prices[$product_id]['_max_variation_sale_price'] = max($sale_prices);
                }
                
                if(!empty($prices)){
                    $product_min_max_prices[$product_id]['_min_variation_price'] = min($prices);
                    $product_min_max_prices[$product_id]['_max_variation_price'] = max($prices);
                }
                
                
            }
            
            if(isset($product_min_max_prices[$product_id]['_min_variation_regular_price'])){
                $custom_prices['_min_variation_regular_price'] = $product_min_max_prices[$product_id]['_min_variation_regular_price'];                    
            }
            if(isset($product_min_max_prices[$product_id]['_max_variation_regular_price'])){
                $custom_prices['_max_variation_regular_price'] = $product_min_max_prices[$product_id]['_max_variation_regular_price'];                    
            }

            if(isset($product_min_max_prices[$product_id]['_min_variation_sale_price'])){
                $custom_prices['_min_variation_sale_price'] = $product_min_max_prices[$product_id]['_min_variation_sale_price'];                    
            }
            if(isset($product_min_max_prices[$product_id]['_max_variation_sale_price'])){
                $custom_prices['_max_variation_sale_price'] = $product_min_max_prices[$product_id]['_max_variation_sale_price'];                    
            }

            if(isset($product_min_max_prices[$product_id]['_min_variation_price'])){
                $custom_prices['_min_variation_price'] = $product_min_max_prices[$product_id]['_min_variation_price'];                    
            }
            if(isset($product_min_max_prices[$product_id]['_max_variation_price'])){
                $custom_prices['_max_variation_price'] = $product_min_max_prices[$product_id]['_max_variation_price'];                    
            }
            
            
            
            
            
        }
        
        return $custom_prices; 
        
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
                     || preg_match('/^table_rate-[0-9]+ : [0-9]+$/', $k)
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
        global $sitepress,$woocommerce_wpml;
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
            if($woocommerce_wpml->settings['currencies_languages'][$currency][$sitepress->get_current_language()] == 1 ){
            $selected = $currency == $this->get_client_currency() ? ' selected="selcted"' : '';
            
            $currency_format = preg_replace(array('#%name#', '#%symbol#', '#%code#'), 
                array($wc_currencies[$currency], get_woocommerce_currency_symbol($currency), $currency), $format);
            
            echo '<option value="' . $currency . '"' . $selected . '>' . $currency_format . '</option>';            
        }
        }
        echo '</select>';
        
    }
    
    function switch_currency(){
        $this->set_client_currency($_POST['currency']);
        
        // force set user cookie when user is not logged in        
        global $woocommerce, $current_user;
        if(empty($woocommerce->session->data) && empty($current_user->ID)){
            $woocommerce->session->set_customer_session_cookie(true);    
        }
        
        do_action('wcml_switch_currency', $_POST['currency']);
        
        exit;
        
    }
    
    
    
}