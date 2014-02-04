<?php
  
// Our case:
// Muli-currency can be enabled by an option in wp_options - wcml_multi_currency_enabled
// User currency will be set in the woocommerce session as 'client_currency'
//     
  
class WCML_WC_MultiCurrency{
    
    private $client_currency;
    
    private $exchange_rates = array();
    
    private $currencies_without_cents = array('JPY', 'TWD', 'KRW', 'BIF', 'BYR', 'CLP', 'GNF', 'ISK', 'KMF', 'PYG', 'RWF', 'VUV', 'XAF', 'XOF', 'XPF');
    
    function __construct(){
        
        add_filter('init', array($this, 'init'), 5);
        
    }
    
    function init(){        
        
        add_filter('wcml_price_currency', array($this, 'price_currency_filter'));            
        
        add_filter('wcml_raw_price_amount', array($this, 'raw_price_filter'), 10, 2);
        
        add_filter('wcml_shipping_price_amount', array($this, 'shipping_price_filter'));
        add_filter('wcml_shipping_free_min_amount', array($this, 'shipping_free_min_amount'));
        add_action('woocommerce_product_meta_start', array($this, 'currency_switcher'));            
            
        add_filter('wcml_exchange_rates', array($this, 'get_exchange_rates'));
        
        // exchange rate GUI and logic
        if(is_admin()){

            $this->init_ajax_currencies_actions();
        }
        
        if(defined('W3TC')){
            
            $WCML_WC_MultiCurrency_W3TC = new WCML_WC_MultiCurrency_W3TC;    
            
        }
        
        add_action('woocommerce_email_before_order_table', array($this, 'fix_currency_before_order_email'));
        add_action('woocommerce_email_after_order_table', array($this, 'fix_currency_after_order_email'));
        
        // orders
        if(is_admin()){
            global $wp;
            add_action( 'restrict_manage_posts', array($this, 'filter_orders_by_currency_dropdown'));
            $wp->add_query_var('_order_currency');
            
            add_filter('posts_join', array($this, 'filter_orders_by_currency_join'));
            add_filter('posts_where', array($this, 'filter_orders_by_currency_where'));
            
            // use correct order currency on order detail page
            add_filter('woocommerce_currency_symbol', array($this, '_use_order_currency_symbol'));
            
        }
        
        // reports
        if(is_admin()){
            add_action('woocommerce_reports_tabs', array($this, 'reports_currency_dropdown')); // WC 2.0.x
            add_action('wc_reports_tabs', array($this, 'reports_currency_dropdown')); // WC 2.1.x
            
            add_action('init', array($this, 'reports_init'));
            
            add_action('wp_ajax_wcml_reports_set_currency', array($this,'set_reports_currency'));
        }
        

        //custom prices for different currencies for products/variations [BACKEND]
        add_action('woocommerce_product_options_pricing',array($this,'woocommerce_product_options_custom_pricing'));
        add_action('woocommerce_product_after_variable_attributes',array($this,'woocommerce_product_after_variable_attributes_custom_pricing'),10,3);
    }
    
    
    static function install(){
        global $wpdb;
        
        $sql = "CREATE TABLE IF NOT EXISTS `". $wpdb->prefix ."icl_currencies` (
              `id` int(11) NOT NULL AUTO_INCREMENT,
              `code` varchar(7) COLLATE utf8_unicode_ci NOT NULL,
              `value` varchar(7) COLLATE utf8_unicode_ci DEFAULT NULL,
              `changed` datetime DEFAULT NULL,
              PRIMARY KEY (`id`)
            )";
        $wpdb->query($sql);
        
        return;
        
    }
    
    function init_ajax_currencies_actions(){
        add_action('wp_ajax_wcml_update_currency', array($this,'update_currency_exchange_rate'));
        add_action('wp_ajax_wcml_delete_currency', array($this,'delete_currency_exchange_rate'));
        add_action('wp_ajax_wcml_update_currency_lang', array($this,'wcml_update_currency_lang'));
        add_action('wp_ajax_wcml_update_default_currency', array($this,'wcml_update_default_currency'));

        $this->set_default_currencies_languages();
    }
    
    function raw_price_filter($price, $product_id = false) {
        
        $price = $this->convert_price_amount($price, $this->get_client_currency());
        
        return $price;
        
    }
    
    function shipping_price_filter($price) {
        
        $price = $this->convert_price_amount($price, $this->get_client_currency());
        
        return $price;
        
    }    
    
    function shipping_free_min_amount($price) {
        
        $price = $this->convert_price_amount($price, $this->get_client_currency());
        
        return $price;
        
    }        
    
    function convert_price_amount($amount, $currency = false){
        
        if(empty($currency)){
            $currency = $this->get_client_currency();
        }
        
        $exchange_rates = $this->get_exchange_rates();
        
        if(isset($exchange_rates[$currency]) && is_numeric($amount)){
            $amount = $amount * $exchange_rates[$currency];
            if(in_array($currency, $this->currencies_without_cents)){
                $amount = round($amount, 0, PHP_ROUND_HALF_UP);
            }
        }else{
            $amount = 0;
        }
        
        return $amount;        
        
    }   
        
    function price_currency_filter($currency){
        
        if(isset($this->order_currency)){
            $currency = $this->order_currency;
        }else{
            $currency = $this->get_client_currency();    
        }
        
        return $currency;
    }
    
    function get_exchange_rates(){
        
        if(empty($this->exchange_rates)){
            global $wpdb;
            
            $this->exchange_rates = array(get_option('woocommerce_currency') => 1);
            
            $currencies = $wpdb->get_results("SELECT * FROM " . $wpdb->prefix . "icl_currencies ORDER BY `id` DESC", OBJECT);
            
            $woo_currencies = get_woocommerce_currencies(); 
            
            foreach($currencies as $currency){
                if(!empty($woo_currencies[$currency->code])){
                    $this->exchange_rates[$currency->code] = $currency->value;
                }
            }
        }
        
        return $this->exchange_rates;
    }
    
    function update_currency_exchange_rate(){
        if(!wp_verify_nonce($_REQUEST['wcml_nonce'], 'wcml_update_currency')){
            die('Invalid nonce');
        }

        global $wpdb;
        $return = array();
        if($_POST['currency_id'] == 0){
            $wpdb->insert($wpdb->prefix .'icl_currencies', array(
                    'code' => $_POST['currency_code'],
                    'value' => (double) $_POST['currency_value'],
                    'changed' => date('Y-m-d H:i:s')
                )
            );
            $return['id'] = $wpdb->insert_id;
            global $sitepress,$woocommerce_wpml;
            $active_languages = $sitepress->get_active_languages();
            $settings = $woocommerce_wpml->get_settings();
            $return['languages'] ='';
            foreach($active_languages as $language){
                if(!isset($settings['currencies_languages'][$_POST['currency_code']][$language['code']])){
                $settings['currencies_languages'][$_POST['currency_code']][$language['code']] = 1;
                }
            }
            $woocommerce_wpml->update_settings($settings);
        } else {
            $wpdb->update(
                $wpdb->prefix .'icl_currencies',
                array(
                    'code' => $_POST['currency_code'],
                    'value' => (double) $_POST['currency_value'],
                    'changed' => date('Y-m-d H:i:s')
                ),
                array( 'id' => $_POST['currency_id'] )
            );
        }

        $return['symbol'] = sprintf(__(' (%s)','wpml-wcml'),get_woocommerce_currency_symbol($_POST['currency_code']));;

        echo json_encode($return);
        die();
    }

    function delete_currency_exchange_rate(){
        if(!wp_verify_nonce($_REQUEST['wcml_nonce'], 'wcml_delete_currency')){
            die('Invalid nonce');
        }
        global $wpdb,$woocommerce_wpml;
        $wpdb->query($wpdb->prepare("DELETE FROM ". $wpdb->prefix ."icl_currencies WHERE id = %d", $_POST['currency_id']));

        $settings = $woocommerce_wpml->get_settings();
        unset($settings['currencies_languages'][$_POST['code']]);
        $woocommerce_wpml->update_settings($settings);

        die();
    }
    
    function wcml_update_currency_lang(){
        if(!wp_verify_nonce($_REQUEST['wcml_nonce'], 'wcml_update_currency_lang')){
            die('Invalid nonce');
        }
        global $woocommerce_wpml;

        $settings = $woocommerce_wpml->get_settings();
        $settings['currencies_languages'][$_POST['code']][$_POST['lang']] = $_POST['value'];

        $woocommerce_wpml->update_settings($settings);
        die();
    }

    function wcml_update_default_currency(){
        if(!wp_verify_nonce($_REQUEST['wcml_nonce'], 'wcml_update_default_currency')){
            die('Invalid nonce');
        }
        global $woocommerce_wpml;

        $settings = $woocommerce_wpml->get_settings();
        $settings['default_currencies'][$_POST['lang']] = $_POST['code'];

        $woocommerce_wpml->update_settings($settings);
        die();
    }

    function set_default_currencies_languages(){
        global $woocommerce_wpml,$sitepress,$wpdb;

        $settings = $woocommerce_wpml->get_settings();
        $wc_currency = get_option('woocommerce_currency');

            $exists_codes = $wpdb->get_col("SELECT code FROM " . $wpdb->prefix . "icl_currencies ORDER BY `id` DESC");
            $active_languages = $sitepress->get_active_languages();
            foreach ($exists_codes as $code) {
                foreach($active_languages as $language){
                if(!isset($settings['currencies_languages'][$code][$language['code']])){
                    $settings['currencies_languages'][$code][$language['code']] = 1;
                }
            }
        }

            foreach($active_languages as $language){
            if(!isset($settings['default_currencies'][$language['code']])){
                $settings['default_currencies'][$language['code']] = false;
            }

            if(!isset($settings['currencies_languages'][$wc_currency][$language['code']])){
                $settings['currencies_languages'][$wc_currency][$language['code']] = 1;
            }
        }

            $woocommerce_wpml->update_settings($settings);

    }

    function currency_switcher(){
        echo(do_shortcode('[currency_switcher]'));
    }
    
    function get_client_currency(){
        global $woocommerce;
        return $woocommerce->session->get('client_currency');
        
    }
    
    function woocommerce_currency_hijack($currency){
        if(isset($this->order_currency)){
            $currency = $this->order_currency;                
        }
        return $currency;
    }
    
    // handle currency in order emails before handled in woocommerce
    function fix_currency_before_order_email($order){
        
        // backwards comp
        if(!method_exists($order, 'get_order_currency')) return;
        
        $this->order_currency = $order->get_order_currency();
        add_filter('woocommerce_currency', array($this, 'woocommerce_currency_hijack'));
    }
    
    function fix_currency_after_order_email($order){
        unset($this->order_currency);
        remove_filter('woocommerce_currency', array($this, 'woocommerce_currency_hijack'));
    }
    
    function filter_orders_by_currency_join($join){
        global $wp_query, $typenow, $wpdb;
        
        if($typenow == 'shop_order' &&!empty($wp_query->query['_order_currency'])){
            $join .= " JOIN {$wpdb->postmeta} wcml_pm ON {$wpdb->posts}.ID = wcml_pm.post_id AND wcml_pm.meta_key='_order_currency'";
        }
        
        return $join;
    }
    
    function filter_orders_by_currency_where($where){
        global $wp_query, $typenow;
        
        if($typenow == 'shop_order' &&!empty($wp_query->query['_order_currency'])){
            $where .= " AND wcml_pm.meta_value = '" . esc_sql($wp_query->query['_order_currency']) .  "'";
        }
        
        return $where;
    }
    
    function filter_orders_by_currency_dropdown(){
        global $wp_query, $typenow;
        
        if($typenow != 'shop_order') return false;
        
        $order_currencies = $this->get_orders_currencies();
        $currencies = get_woocommerce_currencies(); 
        ?>        
        <select id="dropdown_shop_order_currency" name="_order_currency">
            <option value=""><?php _e( 'Show all currencies', 'wpml-wcml' ) ?></option>
            <?php foreach($order_currencies as $currency => $count): ?>            
            <option value="<?php echo $currency ?>" <?php 
                if ( isset( $wp_query->query['_order_currency'] ) ) selected( $currency, $wp_query->query['_order_currency'] ); 
                ?> ><?php printf("%s (%s) (%d)", $currencies[$currency], get_woocommerce_currency_symbol($currency), $count) ?></option>
            <?php endforeach; ?>
        </select>
        <?php
        wc_enqueue_js( "jQuery('select#dropdown_shop_order_currency, select[name=m]').css('width', '180px').chosen();");
        
    }
    
    function get_orders_currencies(){
        global $wpdb;
        
        $currencies = array();
        
        $results = $wpdb->get_results("
            SELECT m.meta_value AS currency, COUNT(m.post_id) AS c
            FROM {$wpdb->posts} p JOIN {$wpdb->postmeta} m ON p.ID = m.post_id
            WHERE meta_key='_order_currency' AND p.post_type='shop_order'
            GROUP BY meta_value           
        ");
        
        foreach($results as $row){
            $currencies[$row->currency] = $row->c;
        }
        
        return $currencies;
        
        
    }
    
    function _use_order_currency_symbol($currency){
        global $wp_query, $typenow, $woocommerce;
        
        if(!function_exists('get_current_screen')){
            return $currency;
        }

        $current_screen = get_current_screen();
        
        if(!empty($current_screen) && $current_screen->id == 'shop_order'){            
            
            $the_order = new WC_Order( get_the_ID() );                        
            if($the_order && method_exists($the_order, 'get_order_currency')){
                remove_filter('woocommerce_currency_symbol', array($this, '_use_order_currency_symbol'));
                
                $currency = get_woocommerce_currency_symbol($the_order->get_order_currency());    
                
                add_filter('woocommerce_currency_symbol', array($this, '_use_order_currency_symbol'));
            }
            
        }
        
        return $currency;
    }
    
    function reports_init(){
        
        if(isset($_GET['page']) && ($_GET['page'] == 'woocommerce_reports' || $_GET['page'] == 'wc-reports')){ //wc-reports - 2.1.x, woocommerce_reports 2.0.x
            
            add_filter('woocommerce_reports_get_order_report_query', array($this, 'admin_reports_query_filter'));
                        
            wc_enqueue_js( "
                jQuery('#dropdown_shop_report_currency').on('change', function(){ 
                    jQuery('#dropdown_shop_report_currency_chzn').after('&nbsp;' + icl_ajxloaderimg); // WC 2.0
                    jQuery('#dropdown_shop_report_currency_chzn a.chzn-single').css('color', '#aaa'); // WC 2.0
                    jQuery('#dropdown_shop_report_currency_chosen').after('&nbsp;' + icl_ajxloaderimg);
                    jQuery('#dropdown_shop_report_currency_chosen a.chosen-single').css('color', '#aaa');
                    jQuery.ajax({
                        url: ajaxurl,
                        type: 'post',
                        data: {action: 'wcml_reports_set_currency', currency: jQuery('#dropdown_shop_report_currency').val()},
                        success: function(){location.reload();}
                    })
                });
            ");
            
            $this->reports_currency = isset($_COOKIE['_wcml_reports_currency']) ? $_COOKIE['_wcml_reports_currency'] : get_option('woocommerce_currency');
            
            add_filter('woocommerce_currency_symbol', array($this, '_set_reports_currency_symbol'));
            
            /* for WC 2.0.x - start */
            add_filter('woocommerce_reports_sales_overview_order_totals_join', array($this, 'reports_filter_by_currency_join'));
            add_filter('woocommerce_reports_sales_overview_order_totals_where', array($this, 'reports_filter_by_currency_where'));

            add_filter('woocommerce_reports_sales_overview_discount_total_join', array($this, 'reports_filter_by_currency_join'));
            add_filter('woocommerce_reports_sales_overview_discount_total_where', array($this, 'reports_filter_by_currency_where'));

            add_filter('woocommerce_reports_sales_overview_shipping_total_join', array($this, 'reports_filter_by_currency_join'));
            add_filter('woocommerce_reports_sales_overview_shipping_total_where', array($this, 'reports_filter_by_currency_where'));

            add_filter('woocommerce_reports_sales_overview_order_items_join', array($this, 'reports_filter_by_currency_join'));
            add_filter('woocommerce_reports_sales_overview_order_items_where', array($this, 'reports_filter_by_currency_where'));

            add_filter('woocommerce_reports_sales_overview_orders_join', array($this, 'reports_filter_by_currency_join'));
            add_filter('woocommerce_reports_sales_overview_orders_where', array($this, 'reports_filter_by_currency_where'));
            
            add_filter('woocommerce_reports_daily_sales_orders_join', array($this, 'reports_filter_by_currency_join'));
            add_filter('woocommerce_reports_daily_sales_orders_where', array($this, 'reports_filter_by_currency_where'));

            add_filter('woocommerce_reports_monthly_sales_orders_join', array($this, 'reports_filter_by_currency_join'));
            add_filter('woocommerce_reports_monthly_sales_orders_where', array($this, 'reports_filter_by_currency_where'));

            add_filter('woocommerce_reports_monthly_sales_order_items_join', array($this, 'reports_filter_by_currency_join'));
            add_filter('woocommerce_reports_monthly_sales_order_items_where', array($this, 'reports_filter_by_currency_where'));

            add_filter('woocommerce_reports_top_sellers_order_items_join', array($this, 'reports_filter_by_currency_join'));
            add_filter('woocommerce_reports_top_sellers_order_items_where', array($this, 'reports_filter_by_currency_where'));

            add_filter('woocommerce_reports_top_earners_order_items_join', array($this, 'reports_filter_by_currency_join'));
            add_filter('woocommerce_reports_top_earners_order_items_where', array($this, 'reports_filter_by_currency_where'));
            
            add_filter('woocommerce_reports_product_sales_order_items_join', array($this, 'reports_filter_by_currency_join'));
            add_filter('woocommerce_reports_product_sales_order_items_where', array($this, 'reports_filter_by_currency_where'));

            add_filter('woocommerce_reports_coupons_overview_total_order_count_join', array($this, 'reports_filter_by_currency_join'));
            add_filter('woocommerce_reports_coupons_overview_total_order_count_where', array($this, 'reports_filter_by_currency_where'));

            add_filter('woocommerce_reports_coupons_overview_totals_join', array($this, 'reports_filter_by_currency_join'));
            add_filter('woocommerce_reports_coupons_overview_totals_where', array($this, 'reports_filter_by_currency_where'));

            add_filter('woocommerce_reports_coupons_overview_coupons_by_count_join', array($this, 'reports_filter_by_currency_join'));
            add_filter('woocommerce_reports_coupons_overview_coupons_by_count_where', array($this, 'reports_filter_by_currency_where'));
            
            add_filter('woocommerce_reports_coupons_sales_used_coupons_join', array($this, 'reports_filter_by_currency_join'));
            add_filter('woocommerce_reports_coupons_sales_used_coupons_where', array($this, 'reports_filter_by_currency_where'));

            add_filter('woocommerce_reports_coupon_sales_order_totals_join', array($this, 'reports_filter_by_currency_join'));
            add_filter('woocommerce_reports_coupon_sales_order_totals_where', array($this, 'reports_filter_by_currency_where'));

            add_filter('woocommerce_reports_customer_overview_customer_orders_join', array($this, 'reports_filter_by_currency_join'));
            add_filter('woocommerce_reports_customer_overview_customer_orders_where', array($this, 'reports_filter_by_currency_where'));

            add_filter('woocommerce_reports_customer_overview_guest_orders_join', array($this, 'reports_filter_by_currency_join'));
            add_filter('woocommerce_reports_customer_overview_guest_orders_where', array($this, 'reports_filter_by_currency_where'));


            add_filter('woocommerce_reports_monthly_taxes_gross_join', array($this, 'reports_filter_by_currency_join'));
            add_filter('woocommerce_reports_monthly_taxes_gross_where', array($this, 'reports_filter_by_currency_where'));

            add_filter('woocommerce_reports_monthly_taxes_shipping_join', array($this, 'reports_filter_by_currency_join'));
            add_filter('woocommerce_reports_monthly_taxes_shipping_where', array($this, 'reports_filter_by_currency_where'));

            add_filter('woocommerce_reports_monthly_taxes_order_tax_join', array($this, 'reports_filter_by_currency_join'));
            add_filter('woocommerce_reports_monthly_taxes_order_tax_where', array($this, 'reports_filter_by_currency_where'));

            add_filter('woocommerce_reports_monthly_taxes_shipping_tax_join', array($this, 'reports_filter_by_currency_join'));
            add_filter('woocommerce_reports_monthly_taxes_shipping_tax_where', array($this, 'reports_filter_by_currency_where'));

            add_filter('woocommerce_reports_monthly_taxes_tax_rows_join', array($this, 'reports_filter_by_currency_join'));
            add_filter('woocommerce_reports_monthly_taxes_tax_rows_where', array($this, 'reports_filter_by_currency_where'));

            add_filter('woocommerce_reports_category_sales_order_items_join', array($this, 'reports_filter_by_currency_join'));
            add_filter('woocommerce_reports_category_sales_order_items_where', array($this, 'reports_filter_by_currency_where'));
            
            /* for WC 2.0.x - end */
            
        }
    }
    
    function admin_reports_query_filter($query){
        global $wpdb;
        
        $query['join'] .= " LEFT JOIN {$wpdb->postmeta} AS meta_order_currency ON meta_order_currency.post_id = posts.ID ";
        
        $query['where'] .= sprintf(" AND meta_order_currency.meta_key='_order_currency' AND meta_order_currency.meta_value = '%s' ", $this->reports_currency);
        
        return $query;
    }
    
    function set_reports_currency(){
        
        setcookie('_wcml_reports_currency', $_POST['currency'], time() + 86400, COOKIEPATH, COOKIE_DOMAIN);
        
        exit;
        
    }
     
    function reports_currency_dropdown(){
        
        $orders_currencies = $this->get_orders_currencies();
        $currencies = get_woocommerce_currencies(); 
        
        // remove temporary
        remove_filter('woocommerce_currency_symbol', array($this, '_set_reports_currency_symbol'));
        
        ?>
        
        <select id="dropdown_shop_report_currency">
            <?php foreach($orders_currencies as $currency => $count): ?>
            <option value="<?php echo $currency ?>" <?php selected( $currency, $this->reports_currency ); ?>><?php 
                printf("%s (%s)", $currencies[$currency], get_woocommerce_currency_symbol($currency)) ?></option>
            <?php endforeach; ?>
        </select>
        
        <?php
        wc_enqueue_js( "jQuery('select#dropdown_shop_report_currency, select[name=m]').css('width', '180px').chosen();");
        
        // add back
        add_filter('woocommerce_currency_symbol', array($this, '_set_reports_currency_symbol'));
        
    }
    
    function _set_reports_currency_symbol($currency){
        static $no_recur = false;        
        if(!empty($this->reports_currency) && empty($no_recur)){
            $no_recur= true;
            $currency = get_woocommerce_currency_symbol($this->reports_currency);
            $no_recur= false;
        }
        return $currency;
    }
    
    function woocommerce_product_options_custom_pricing(){
        global $pagenow,$sitepress;

        $def_lang = $sitepress->get_default_language();
        if((isset($_GET['lang']) && $_GET['lang'] != $def_lang) || (isset($_GET['post']) && $sitepress->get_language_for_element($_GET['post'],'post_product') != $def_lang) || $sitepress->get_current_language() != $def_lang){
            return;
        }

        $product_id = false;

        if($pagenow == 'post.php' && isset($_GET['post']) && get_post_type($_GET['post']) == 'product'){
            $product_id = $_GET['post'];
        }

        $this->custom_pricing_output($product_id);

        wp_nonce_field('wcml_save_custom_prices','_wcml_custom_prices_nonce');

        $this->load_custom_prices_js_css();
        }

    function custom_pricing_output($post_id = false){
        global $wpdb,$woocommerce;

        $custom_prices = array();
        $is_variation = false;

        if($post_id){
            $custom_prices = get_post_custom($post_id);
            if(get_post_type($post_id) == 'product_variation'){
                $is_variation = true;
                }
            }

        include WCML_PLUGIN_PATH . '/menu/sub/custom-prices.php';
    }

    function load_custom_prices_js_css(){
        wp_register_style('wpml-wcml-prices', WCML_PLUGIN_URL . '/assets/css/wcml-prices.css', null, WCML_VERSION);
        wp_register_script('wcml-tm-scripts-prices', WCML_PLUGIN_URL . '/assets/js/prices.js', array('jquery'), WCML_VERSION);

        wp_enqueue_style('wpml-wcml-prices');
        wp_enqueue_script('wcml-tm-scripts-prices');
    }


    function woocommerce_product_after_variable_attributes_custom_pricing($loop, $variation_data, $variation){
        global $sitepress;

        $def_lang = $sitepress->get_default_language();
        if((isset($_GET['lang']) && $_GET['lang'] != $def_lang) || (isset($_GET['post']) && $sitepress->get_language_for_element($_GET['post'],'post_product') != $def_lang) || $sitepress->get_current_language() != $def_lang){
            return;
        }

        echo '<tr><td>';
            $this->custom_pricing_output($variation->ID);
        echo '</td></tr>';
    }


    /* for WC 2.0.x - start */    
    function reports_filter_by_currency_join($join){
        global $wpdb;
        
        $join .= " LEFT JOIN {$wpdb->postmeta} wcml_rpm ON wcml_rpm.post_id = posts.ID ";
        
        return $join;
    }
    
    function reports_filter_by_currency_where($where){
        
        $where .= " AND wcml_rpm.meta_key = '_order_currency' AND wcml_rpm.meta_value = '" . esc_sql($this->reports_currency) . "'";
        
        return $where;
    }
    /* for WC 2.0.x - end */    

}

//@todo Move to separate file
class WCML_WC_MultiCurrency_W3TC{
    
    function __construct(){
        
        add_filter('init', array($this, 'init'), 15);
        
    }
    
    function init(){
        
        add_action('wcml_switch_currency', array($this, 'flush_page_cache'));
        
    }
    
    function flush_page_cache(){
        w3_require_once(W3TC_LIB_W3_DIR . '/AdminActions/FlushActionsAdmin.php');
        $flush = new W3_AdminActions_FlushActionsAdmin();
        $flush->flush_pgcache(); 
    }
    
}