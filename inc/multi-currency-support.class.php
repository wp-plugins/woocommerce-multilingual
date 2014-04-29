<?php
  
class WCML_Multi_Currency_Support{
    
    private $currencies = array();
    private $currency_codes = array();
    
    private $client_currency;
    private $exchange_rates = array();
    
    
    function __construct(){
        
        add_action('init', array($this, 'init'), 5); 
        //add_action('wp_head', array($this, 'set_default_currency')); //@todo - review
        

        if(is_ajax()){        
            add_action('wp_ajax_nopriv_wcml_switch_currency', array($this, 'switch_currency'));
            add_action('wp_ajax_wcml_switch_currency', array($this, 'switch_currency'));
            
            add_action('wp_ajax_legacy_update_custom_rates', array($this, 'legacy_update_custom_rates'));
            add_action('wp_ajax_legacy_remove_custom_rates', array($this, 'legacy_remove_custom_rates'));
            
            add_action('wp_ajax_wcml_new_currency', array($this,'add_currency')); 
            add_action('wp_ajax_wcml_save_currency', array($this,'save_currency'));
            add_action('wp_ajax_wcml_delete_currency', array($this,'delete_currency'));
            add_action('wp_ajax_wcml_currencies_list', array($this,'currencies_list'));
            
            add_action('wp_ajax_wcml_update_currency_lang', array($this,'update_currency_lang'));
            add_action('wp_ajax_wcml_update_default_currency', array($this,'update_default_currency'));
            
            
        }
        
        if(is_admin()){
            add_action('admin_footer', array($this, 'currency_options_wc_integration'));            
            add_action('woocommerce_settings_save_general', array($this, 'currency_options_wc_integration_save_hook'));            
        }
        
        add_action( 'wp_enqueue_scripts', array( $this, 'register_styles' ) );
        add_action( 'init', array( $this, 'register_styles' ) );
    }
    
    function _load_filters(){
        $load = false;
        
        if(!is_admin() && $this->get_client_currency() != get_option('woocommerce_currency')){
            $load = true;
        }else{
            if(is_ajax() && $this->get_client_currency() != get_option('woocommerce_currency')){
                if(isset($_REQUEST['action'])){
                    $ajax_actions = array('woocommerce_get_refreshed_fragments', 'woocommerce_update_order_review', 'woocommerce-checkout', 'woocommerce_checkout', 'woocommerce_add_to_cart');
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
        
        $this->init_currencies();
        
        if($this->_load_filters()){    
            
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
            
            add_filter('option_woocommerce_currency_pos', array($this, 'filter_currency_position_option'));
            add_filter('option_woocommerce_price_thousand_sep', array($this, 'filter_currency_thousand_sep_option'));
            add_filter('option_woocommerce_price_decimal_sep', array($this, 'filter_currency_decimal_sep_option'));
            add_filter('option_woocommerce_price_num_decimals', array($this, 'filter_currency_num_decimals_option'));
            
        }
        
        add_action('currency_switcher', array($this, 'currency_switcher'));        
        add_shortcode('currency_switcher', array($this, 'currency_switcher_shortcode'));
        
        if(!is_admin()) $this->load_inline_js();
        
    }    
    
    function init_currencies(){
        global $woocommerce_wpml, $sitepress;
        $this->currencies =& $woocommerce_wpml->settings['currency_options'];  // ref
        
        $save_to_db = false;
        
        $active_languages = $sitepress->get_active_languages();
        
        $currency_defaults = array(
                                'rate'                  => 0,
                                'position'              => 'left',
                                'thousand_sep'          => ',',
                                'decimal_sep'           => '.',
                                'num_decimals'          => 2,
                                'rounding'              => 'disabled',
                                'rounding_increment'    => 1,
                                'auto_subtract'         => 0
        );
        
        foreach($this->currencies as $code => $currency){
            foreach($currency_defaults as $key => $val){
                if(!isset($currency[$key])){
                    $this->currencies[$code][$key] = $val;
                    $save_to_db = true;
                }
            }
            
            foreach($active_languages as $language){
                if(!isset($currency['languages'][$language['code']])){
                    $this->currencies[$code]['languages'][$language['code']] = 1;
                    $save_to_db = true;
                }
            }
        }
        
        $this->currency_codes = array_keys($this->currencies); 
        
        // default language currencies
        foreach($active_languages as $language){
            if(!isset($woocommerce_wpml->settings['default_currencies'][$language['code']])){
                $woocommerce_wpml->settings['default_currencies'][$language['code']] = 0;
                $save_to_db = true;
            }
        }
        
        // sanity check
        if(isset($woocommerce_wpml->settings['default_currencies'])){
            foreach($woocommerce_wpml->settings['default_currencies'] as $language => $value){
                if(!isset($active_languages[$language])){
                    unset($woocommerce_wpml->settings['default_currencies'][$language]);
                    $save_to_db = true;
                }
                if(!empty($value) && !in_array($value, $this->currency_codes)){
                    $woocommerce_wpml->settings['default_currencies'][$language] = 0;
                    $save_to_db = true;
                }
            }
        }
        
        if($save_to_db){
            $woocommerce_wpml->update_settings();                
        }
        
    }
    
    function get_currencies(){
        
        // by default, exclude default currency
        $currencies = array();
        foreach($this->currencies as $key => $value){
            if(get_option('woocommerce_currency') != $key){
                $currencies[$key] = $value;
            }
        }
         
        return $currencies;
    }
    
    function get_currency_codes(){
        return $this->currency_codes;
    }
    
    function add_currency(){
        if(!wp_verify_nonce($_REQUEST['wcml_nonce'], 'wcml_new_currency')){
            die('Invalid nonce');
        }

        global $sitepress, $woocommerce_wpml;;
        $settings = $woocommerce_wpml->get_settings();
        
        $return = array();
        
        if(!empty($_POST['currency_code'])){
            
            $currency_code = $_POST['currency_code'];
            
            $active_languages = $sitepress->get_active_languages();
            $return['languages'] ='';
            foreach($active_languages as $language){
                if(!isset($settings['currency_options'][$currency_code]['languages'][$language['code']])){
                    $settings['currency_options'][$currency_code]['languages'][$language['code']] = 1;
                }
            }
            $settings['currency_options'][$currency_code]['rate'] = (double) $_POST['currency_value'];
            $settings['currency_options'][$currency_code]['updated'] = date('Y-m-d H:i:s');        

            $wc_currency = get_option('woocommerce_currency'); 
            if(!isset($settings['currencies_order']))
                $settings['currencies_order'][] = $wc_currency;

            $settings['currencies_order'][] = $currency_code;

            $woocommerce_wpml->update_settings($settings);

            $wc_currencies = get_woocommerce_currencies();
            $return['currency_name_formatted'] = sprintf('%s (%s)', $wc_currencies[$currency_code], sprintf('%s 99.99', get_woocommerce_currency_symbol($currency_code)));
            $return['currency_name_formatted_without_rate'] = sprintf('%s (%s)', $wc_currencies[$currency_code], get_woocommerce_currency_symbol($currency_code));
            $return['currency_meta_info'] = sprintf('1 %s = %s %s', $wc_currency, $settings['currency_options'][$currency_code]['rate'], $currency_code);
            
            ob_start();
            $code = $currency_code;
            $this->init_currencies();
            $currency = $this->currencies[$currency_code];
            include WCML_PLUGIN_PATH . '/menu/sub/custom-currency-options.php'; 
            $return['currency_options'] = ob_get_contents();
            ob_end_clean();
            
            
            
        }
        
        echo json_encode($return);
        die();
    }    
    
    function save_currency(){
        global $woocommerce_wpml;
        
        $currency_code = $_POST['currency'];
        $options = $_POST['currency_options'][$currency_code];
        
        $changed = false;
        $rate_changed = false;
        foreach($this->currencies[$currency_code] as $key => $value){
            
            if(isset($options[$key]) && $options[$key] != $value){
                $this->currencies[$currency_code][$key] = $options[$key];
                $changed = true;
                if($key == 'rate'){
                    $rate_changed = true;
                }
            }
            
        }

        if($changed){
            if($rate_changed){
                $this->currencies[$currency_code]['updated'] = date('Y-m-d H:i:s');
            }
            $woocommerce_wpml->settings['currency_options'] = $this->currencies;
            $woocommerce_wpml->update_settings();
        }
        
        
        $wc_currency = get_option('woocommerce_currency'); 
        $wc_currencies = get_woocommerce_currencies();
        
        switch($this->currencies[$currency_code]['position']){
            case 'left': $price = sprintf('%s99.99', get_woocommerce_currency_symbol($currency_code)); break;
            case 'right': $price = sprintf('99.99%s', get_woocommerce_currency_symbol($currency_code)); break;
            case 'left_space': $price = sprintf('%s 99.99', get_woocommerce_currency_symbol($currency_code)); break;
            case 'right_space': $price = sprintf('99.99 %s', get_woocommerce_currency_symbol($currency_code)); break;
        }
        $return['currency_name_formatted'] = sprintf('%s (%s)', $wc_currencies[$currency_code], $price);
        
        $return['currency_meta_info'] = sprintf('1 %s = %s %s', $wc_currency, $this->currencies[$currency_code]['rate'], $currency_code);
        
        echo json_encode($return);
        exit;
    }
    
    function delete_currency(){
        if(!wp_verify_nonce($_REQUEST['wcml_nonce'], 'wcml_delete_currency')){
            die('Invalid nonce');
        }
        global $woocommerce_wpml;

        $settings = $woocommerce_wpml->get_settings();
        unset($settings['currency_options'][$_POST['code']]);
        
        if(isset($settings['currencies_order'])){
            foreach($settings['currencies_order'] as $key=>$cur_code){
                if($cur_code == $_POST['code']) unset($settings['currencies_order'][$key]);
            }
        }

        $woocommerce_wpml->update_settings($settings);
        
        exit;
    }
    
    function currencies_list(){
        if(!wp_verify_nonce($_REQUEST['wcml_nonce'], 'wcml_currencies_list')){
            die('Invalid nonce');
        }
        global $woocommerce_wpml;
        $wc_currencies = get_woocommerce_currencies();
        $wc_currency = get_option('woocommerce_currency');
        unset($wc_currencies[$wc_currency]);
        $currencies = $woocommerce_wpml->multi_currency_support->get_currencies();
        $html = '<select name="code">';
        foreach($wc_currencies as $wc_code=>$currency_name){
            if(empty($currencies[$wc_code])){
                $html .= '<option value="'.$wc_code.'">'.$currency_name.'</option>';
            }
        }
        $html .= '</select>';
        ob_clean();
        echo $html;

        die();
    }
    
    function update_currency_lang(){
        if(!wp_verify_nonce($_REQUEST['wcml_nonce'], 'wcml_update_currency_lang')){
            die('Invalid nonce');
        }
        global $woocommerce_wpml;

        $settings = $woocommerce_wpml->get_settings();
        $settings['currency_options'][$_POST['code']]['languages'][$_POST['lang']] = $_POST['value'];

        $woocommerce_wpml->update_settings($settings);
        exit;
    }

    function update_default_currency(){
        if(!wp_verify_nonce($_REQUEST['wcml_nonce'], 'wcml_update_default_currency')){
            die('Invalid nonce');
        }
        global $woocommerce_wpml;
        $woocommerce_wpml->settings['default_currencies'][$_POST['lang']] = $_POST['code'];
        $woocommerce_wpml->update_settings();
        
        
        exit;
    }
    
    function currency_options_wc_integration(){
        global $woocommerce_wpml;
        
        if($woocommerce_wpml->settings['enable_multi_currency'] == WCML_MULTI_CURRENCIES_INDEPENDENT && isset($_GET['page']) && $_GET['page'] == 'wc-settings' && empty($_GET['tab'])){
            
            wp_enqueue_style('wcml_wc', WCML_PLUGIN_URL . '/assets/css/wcml-wc-integration.css', array(), WCML_VERSION);
            
            $wc_currencies = get_woocommerce_currencies();
            $wc_currency = get_option('woocommerce_currency');
                                     
            foreach($this->currencies as $code => $currency){
                $selected = $code == $wc_currency ? ' selected' : '';
                $menu[] = '<a class="wcml_currency_options_menu_item' . $selected . '" href="#" data-currency="' . $code . '">' . 
                    sprintf('%s (%s)', $wc_currencies[$code], get_woocommerce_currency_symbol($code)) . '</a>';
                
                if($code != $wc_currency){
                    $symbols[] = get_woocommerce_currency_symbol($code);
                    
                    $options_currency_pos[] = $currency['position'];
                    $options_thousand_sep[] = $currency['thousand_sep'];
                    $options_decimal_sep[] = $currency['decimal_sep'];
                    $options_num_decimals[] = $currency['num_decimals'];
                }
                
            }
            
            $menu = '<p>' . esc_js(__('Select the currency you want to set the options for:', 'wpml-wcml')) . '</p><br />' . join (' | ', $menu);
            
            $codes = "['" . join("', '", array_keys($this->get_currencies())) . "']";            
            $symbols = "['" . join("', '", $symbols) . "']";            
            $symbol_default =  get_woocommerce_currency_symbol($wc_currency);
            $symbol_default = html_entity_decode($symbol_default);
            
            $options_currency_pos = "['" . join("', '", $options_currency_pos) . "']";            
            $options_thousand_sep = "['" . join("', '", $options_thousand_sep) . "']";            
            $options_decimal_sep = "['" . join("', '", $options_decimal_sep) . "']";            
            $options_num_decimals = "['" . join("', '", $options_num_decimals) . "']";            
            
            wc_enqueue_js( "
                var wcml_wc_currency_options_integration = {
                    
                    init: function(){  
                        
                        var table = jQuery('.form-table').eq(1);                         
                        var currencies = {$codes};
                        var symbols = {$symbols};
                        var symbol_default = '{$symbol_default}';
                        
                        var options_currency_pos = {$options_currency_pos};
                        var options_thousand_sep = {$options_thousand_sep};
                        var options_decimal_sep = {$options_decimal_sep};
                        var options_num_decimals = {$options_num_decimals};
                        
                        table.find('tr').each(function( index ){
                            if(index > 0){
                                jQuery(this).addClass('wcml_co_row');
                                jQuery(this).addClass('wcml_co_row_{$wc_currency}');
                            }
                        });
                                                
                        table.find('tr').each(function( index ){
                            if(index > 0){
                                for(var i in currencies){
                                    var currency_option_row = jQuery(this).clone();    
                                    currency_option_row.removeClass('wcml_co_row_{$wc_currency}');
                                    currency_option_row.addClass('wcml_co_row_' + currencies[i]);
                                    currency_option_row.addClass('hidden');
                                    
                                    var html = currency_option_row.html();
                                    
                                    html = html.replace(/woocommerce_currency_pos/g, 'woocommerce_currency_pos_' + currencies[i]);
                                    html = html.replace(/woocommerce_price_thousand_sep/g, 'woocommerce_price_thousand_sep_' + currencies[i]);
                                    html = html.replace(/woocommerce_price_decimal_sep/g, 'woocommerce_price_decimal_sep_' + currencies[i]);
                                    html = html.replace(/woocommerce_price_num_decimals/g, 'woocommerce_price_num_decimals_' + currencies[i]);
                                    
                                    html = html.replace(new RegExp(symbol_default, 'g'), symbols[i]);
                                    
                                    currency_option_row.html(html);
                                    
                                    currency_option_row.find('select[name=woocommerce_currency_pos_' + currencies[i] + ']').val(options_currency_pos[i]);
                                    currency_option_row.find('input[name=woocommerce_price_thousand_sep_' + currencies[i] + ']').val(options_thousand_sep[i]);
                                    currency_option_row.find('input[name=woocommerce_price_decimal_sep_' + currencies[i] + ']').val(options_decimal_sep[i]);
                                    currency_option_row.find('input[name=woocommerce_price_num_decimals_' + currencies[i] + ']').val(options_num_decimals[i]);
                                    
                                    jQuery(this).after(currency_option_row);
                                }
                            }
                        });

                        table.find('tr').eq(0).after('<tr valign=\"top\"><td>&nbsp;</td><td>{$menu}</td></tr>');                
                        jQuery(document).on('click', '.wcml_currency_options_menu_item', function(){
                            jQuery('.wcml_currency_options_menu_item').removeClass('selected');
                            jQuery(this).addClass('selected');
                            
                            jQuery('.wcml_co_row').hide();
                            jQuery('.wcml_co_row_' + jQuery(this).data('currency')).show();
                            
                            return false;
                        });
                        
                        
                    }                        
                    
                }
                
                wcml_wc_currency_options_integration.init();
                
                
            " );                     

        }
    }
    
    function currency_options_wc_integration_save_hook(){
        global $woocommerce_wpml;
        
        if( $woocommerce_wpml->settings['enable_multi_currency'] == WCML_MULTI_CURRENCIES_INDEPENDENT ){
            
            $save = false;
            
            $options = array(
                'woocommerce_currency_pos_' => 'position',
                'woocommerce_price_thousand_sep_' => 'thousand_sep',
                'woocommerce_price_decimal_sep_' => 'decimal_sep',
                'woocommerce_price_num_decimals_' => 'num_decimals'
            );
            
            foreach($this->get_currencies() as $code => $currency){
                foreach($options as $wc_key => $key){
                    if(isset($_POST[$wc_key . $code]) && $_POST[$wc_key . $code] != $this->currencies[$code][$key]){
                        $save = true;
                        $this->currencies[$code][$key] = $_POST[$wc_key . $code];
                    }
                }
            }
            
            if($save){
                $woocommerce_wpml->settings['currency_options'] = $this->currencies;
                $woocommerce_wpml->update_settings();
                
                $this->init_currencies();
            }
            
        }
        
    }
    
    function filter_currency_position_option($value){
        if(isset($this->currencies[$this->client_currency]['position']) && 
            in_array($this->currencies[$this->client_currency]['position'], array('left', 'right', 'left_space', 'right_space'))){
            $value = $this->currencies[$this->client_currency]['position'];
        }
        return $value;
    }
    
    function filter_currency_thousand_sep_option($value){
        if(isset($this->currencies[$this->client_currency]['thousand_sep']) ){
            $value = $this->currencies[$this->client_currency]['thousand_sep'];
        }
        return $value;
    }
    
    function filter_currency_decimal_sep_option($value){
        if(isset($this->currencies[$this->client_currency]['decimal_sep']) ){
            $value = $this->currencies[$this->client_currency]['decimal_sep'];
        }
        return $value;
    }

    function filter_currency_num_decimals_option($value){
        if(isset($this->currencies[$this->client_currency]['num_decimals']) ){
            $value = $this->currencies[$this->client_currency]['num_decimals'];
        }
        return $value;
    }
    
    function load_inline_js(){
        
        wc_enqueue_js( "
            jQuery('.wcml_currency_switcher').on('change', function(){                   
                    var currency = jQuery(this).val(); 
                load_currency(currency);
            });
            jQuery('.wcml_currency_switcher li').on('click', function(){
                var currency = jQuery(this).attr('rel');
                load_currency(currency);
            });


            function load_currency(currency){
                var ajax_loader = jQuery('<img style=\"margin-left:10px;\" width=\"16\" heigth=\"16\" src=\"" . WCML_PLUGIN_URL . "/assets/images/ajax-loader.gif\" />')
                    jQuery('.wcml_currency_switcher').attr('disabled', 'disabled');
                    jQuery('.wcml_currency_switcher').after()
                    ajax_loader.insertAfter(jQuery('.wcml_currency_switcher'));
                    var data = {action: 'wcml_switch_currency', currency: currency}
                    jQuery.post(woocommerce_params.ajax_url, data, function(){ 
                        jQuery('.wcml_currency_switcher').removeAttr('disabled');                                        
                        ajax_loader.remove();
                        location.reload();
                    });
            }
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
                if(in_array($meta_key, array('_price', '_regular_price', '_sale_price')) && !empty($ccr) && isset($ccr[$meta_key][$this->get_client_currency()])){                    
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
            if($translation->language_code == $sitepress->get_default_language()){
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
        global $woocommerce, $woocommerce_wpml, $sitepress;
        
        $default_currencies   = $woocommerce_wpml->settings['default_currencies'];
        $current_language     = $sitepress->get_current_language();
        $active_languages     = $sitepress->get_active_languages();
        
        if(isset($_POST['action']) && $_POST['action'] == 'wcml_switch_currency' && !empty($_POST['currency'])){
            return $this->client_currency = $_POST['currency'];
        }
        
        
        if(empty($this->client_currency) && !empty($woocommerce->session)){
            $session_currency = $woocommerce->session->get('client_currency_' . $current_language);            
            if(isset($this->currencies[$session_currency]) && !empty($this->currencies[$session_currency]['languages'][$current_language])){
                $this->client_currency = $session_currency;
            }
        }
                                                                    
        if(!$this->client_currency && $default_currencies[$current_language]){
            $this->client_currency = $default_currencies[$current_language];
            if(!empty($woocommerce->session)){
                $woocommerce->session->set('client_currency_' . $current_language, $this->client_currency);
            }
        }
        
        //reset other languages
        if(!$this->client_currency && !empty($woocommerce->session)){
            foreach($active_languages as $language){
                if($language['code'] != $current_language){
                    $woocommerce->session->__unset('client_currency_' . $current_language);
                }
            }
        }
        
        if(empty($this->client_currency)){
            
            // client currency in general / if enabled for this language
            if( !empty($woocommerce->session) ){
                $session_currency = $woocommerce->session->get('client_currency');
                if($session_currency && !empty($this->currencies[$session_currency]['languages'][$current_language])){
                    $this->client_currency = $woocommerce->session->get('client_currency');    
                }
                
            }
            
            if(is_null($this->client_currency)){
                $woocommerce_currency = get_option('woocommerce_currency');
                
                // fall on WC currency if enabled for this language
                if(!empty($this->currencies[$woocommerce_currency]['languages'][$current_language])){
                    $this->client_currency = $woocommerce_currency;
                }else{
                    // first currency enabled for this language
                    foreach($this->currencies as $code => $data){
                        if(!empty($data['languages'][$current_language])){
                            $this->client_currency = $code;
                            break;          
                        }                        
                    }
                }
                
                if(!empty($woocommerce->session)){
                    $woocommerce->session->set('client_currency', $this->client_currency);    
                }
            }
        }
            
        return apply_filters('wcml_client_currency', $this->client_currency);
    }
    
    function set_client_currency($currency){
        
        global $woocommerce, $sitepress;
        $this->client_currency = $currency;
        
        $woocommerce->session->set('client_currency', $currency);    
        $woocommerce->session->set('client_currency_' . $sitepress->get_current_language(), $currency);
        
                
        do_action('wcml_set_client_currency', $currency);
        
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
    
    function currency_switcher_shortcode($atts){
        extract( shortcode_atts( array(), $atts ) );
    
        $this->currency_switcher($atts);
    }
    
    function currency_switcher($args = array()){
        global $sitepress, $woocommerce_wpml;
        
        $settings = $woocommerce_wpml->get_settings();
        if(!isset($args['switcher_style'])){
            $args['switcher_style'] = isset($settings['currency_switcher_style'])?$settings['currency_switcher_style']:'dropdown';
        }

        if(!isset($args['orientation'])){
            $args['orientation'] = isset($settings['wcml_curr_sel_orientation'])?$settings['wcml_curr_sel_orientation']:'vertical';
        }

        if(!isset($args['format'])){
            $args['format'] = isset($settings['wcml_curr_template']) && $settings['wcml_curr_template'] != '' ? $settings['wcml_curr_template']:'%name% (%symbol%) - %code%';
        }

        
        $wc_currencies = get_woocommerce_currencies();
                
        if(!isset($settings['currencies_order'])){
            $currencies = $this->get_currency_codes();
        }else{
            $currencies = $settings['currencies_order'];
        }
        
        if($args['switcher_style'] == 'dropdown'){
        echo '<select class="wcml_currency_switcher">';
        }else{
            $args['orientation'] = $args['orientation'] == 'horizontal'?'curr_list_horizontal':'curr_list_vertical';
            echo '<ul class="wcml_currency_switcher '.$args['orientation'].'">';
        }
        foreach($currencies as $currency){
            if($woocommerce_wpml->settings['currency_options'][$currency]['languages'][$sitepress->get_current_language()] == 1 ){
                $selected = $currency == $this->get_client_currency() ? ' selected="selcted"' : '';
                
                $currency_format = preg_replace(array('#%name%#', '#%symbol%#', '#%code%#'),
                    array($wc_currencies[$currency], get_woocommerce_currency_symbol($currency), $currency), $args['format']);
                if($args['switcher_style'] == 'dropdown'){
                echo '<option value="' . $currency . '"' . $selected . '>' . $currency_format . '</option>';            
                }else{
                    echo '<li rel="' . $currency . '" >' . $currency_format . '</li>';
            }
        }
        }
        if($args['switcher_style'] == 'dropdown'){
        echo '</select>';
        }else{
            echo '</ul>';
        }
    }        
    
    function register_styles(){
        wp_register_style('currency-switcher', WCML_PLUGIN_URL . '/assets/css/currency-switcher.css', null, WCML_VERSION);
        wp_enqueue_style('currency-switcher');
    }    
    
}

