<?php
class woocommerce_wpml {
    
    function __construct(){
        add_action('plugins_loaded', array($this, 'init'), 2);
    }
    
    function init(){
        if(!defined('ICL_SITEPRESS_VERSION') || ICL_PLUGIN_INACTIVE){
            if(!function_exists('is_multisite') || !is_multisite()) {
                add_action('admin_notices', array($this, 'no_wpml_warning'));
            }
            return false;            
        } else if(version_compare(ICL_SITEPRESS_VERSION, '2.0.5', '<')){
            add_action('admin_notices', array($this, 'old_wpml_warning'));
            return false;
        } else if(!class_exists('woocommerce')){
            add_action('admin_notices', array($this, 'no_woocommerce'));
            return false;
        }

        if(get_option('icl_is_wcml_installed') !== 'yes'){
            add_action('init', array($this, 'wcml_install'));
        }

        add_action('admin_notices', array($this, 'check_for_incompatible_permalinks'));

        add_filter('woocommerce_get_checkout_url', array($this, 'get_checkout_page_id'));
        add_filter('woocommerce_get_checkout_page_id', array($this, 'checkout_page_id'));
        add_filter('woocommerce_get_cart_page_id', array($this, 'get_cart_page_id'));
        add_filter('woocommerce_get_myaccount_page_id', array($this, 'get_myaccount_page_id'));
        add_filter('woocommerce_get_edit_address_page_id', array($this, 'get_edit_address_page_id'));
        add_filter('woocommerce_get_view_order_page_id', array($this, 'get_view_order_page_id'));
        add_filter('woocommerce_get_change_password_page_id', array($this, 'get_change_password_page_id'));
        add_filter('woocommerce_get_thanks_page_id', array($this, 'get_thanks_page_id'));
        add_filter('woocommerce_get_shop_page_id', array($this, 'shop_page_id'));
        add_filter('woocommerce_get_pay_page_id', array($this, 'pay_page_id'));
        add_filter('woocommerce_get_checkout_payment_url', array($this, 'get_checkout_payment_url'));
        add_filter('woocommerce_get_cancel_order_url', array($this, 'get_cancel_order_url'));
        add_filter('woocommerce_get_return_url', array($this, 'get_return_url'));
        add_filter('woocommerce_get_remove_url', array($this, 'get_remove_url'));
        add_filter('woocommerce_in_cart_product_title', array($this, 'in_cart_product_title'), 13, 2);
        add_filter('woocommerce_in_cart_product_id', array($this, 'in_cart_product_id'), 11, 2);
        add_filter('woocommerce_params', array($this, 'ajax_params'));
        add_filter('woocommerce_redirect', array($this, 'do_redirect'));
        add_filter('woocommerce_attribute_label', array($this, 'translate_attributes'), 14, 2);
        add_filter('woocommerce_upsell_crosssell_search_products', array($this, 'woocommerce_upsell_crosssell_search_posts'));
        add_filter('icl_post_alternative_languages', array($this, 'post_alternative_languages'));
        add_filter('woocommerce_variation_term_name', array($this, 'variation_term_name'));
        add_filter('woocommerce_gateway_title', array($this, 'gateway_title'), 10);
        add_filter('woocommerce_gateway_raw_description', array($this, 'gateway_description'), 10, 3);
        add_filter('pre_get_posts', array($this, 'shop_page_query'), 10);
		add_filter('woocommerce_json_search_found_products', array($this, 'search_products'));

        if(get_option('icl_enable_multi_currency') == 'yes'){
            add_filter('raw_woocommerce_price', array($this, 'woocommerce_price'));
            add_filter('woocommerce_currency_symbol', array($this, 'woocommerce_currency_symbol'), 2);
        }

        add_action('woocommerce_email_header', array($this, 'email_header'), 0);
        add_action('woocommerce_email_footer', array($this, 'email_footer'), 0);
		add_action('localize_woocommerce_on_ajax', array($this, 'localize_on_ajax'));

        add_action('woocommerce_reduce_order_stock', array($this, 'sync_product_stocks'));

        add_action('woocommerce_checkout_update_order_meta', array($this, 'order_language'));

        add_filter('woocommerce_price_filter_min_price', array($this, 'price_filter_min_price'));
        add_filter('woocommerce_price_filter_max_price', array($this, 'price_filter_max_price'));

        add_action('icl_make_duplicate', array($this, 'synchronize_variations'), 10, 4);
      
        add_action('admin_menu', array($this, 'menu'));
        add_action('init', array($this, 'load_css_and_js'));

        if(is_admin()){
            add_action('admin_init', array($this, 'make_new_attributes_translatable'));
            add_action('admin_init', array($this, 'translate_custom_attributes'));
        }

        if(isset($_POST['general_options']) && check_admin_referer('general_options', 'general_options_nonce')){
            $enable_multi_currency = (isset($_POST['multi_currency'])) ? trim($_POST['multi_currency']) : null;

            if($enable_multi_currency == 'yes'){
                add_option('icl_enable_multi_currency', 'yes');
            } else {
                delete_option('icl_enable_multi_currency');
            }
        }

        if(isset($_POST['add_currency']) && check_admin_referer('add_currency', 'add_currency_nonce')){
            global $wpdb, $pagenow;

            $language_code = (isset($_POST['language'])) ? trim($_POST['language']) : null;
            $currency_code = (isset($_POST['currency_code'])) ? mb_convert_case(trim($_POST['currency_code']), MB_CASE_UPPER, "UTF-8") : null;
            $exchange_rate = (isset($_POST['exchange_rate'])) ? trim($_POST['exchange_rate']) : null;
            $currency_id = (isset($_POST['currency_id'])) ? trim($_POST['currency_id']) : null;
            $date = date('Y-m-d H:i:s');

            if($currency_code == ''){
                wp_die(__('<strong>ERROR</strong>: please fill the currency code field.'));
            }

            if($exchange_rate == ''){
                wp_die(__('<strong>ERROR</strong>: please fill the exchange rate field.'));
            } else if(!is_numeric($exchange_rate)){
                wp_die(__('<strong>ERROR</strong>: please enter the correct exchange rate.'));
            }

            $result = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) 
                FROM ". $wpdb->prefix ."icl_currencies WHERE language_code = '$language_code'"));

            if($result && !$currency_id){
                add_action('admin_notices', array($this, 'currency_exists_error'));
            } else {
                // Add
                if(!$currency_id){
                    $wpdb->insert($wpdb->prefix .'icl_currencies', array( 
                        'language_code' => $language_code, 
                        'code' => $currency_code,
                        'value' => (double) $exchange_rate,
                        'changed' => $date
                        )
                    );

                // Update
                } else {
                    $wpdb->update( 
                        $wpdb->prefix .'icl_currencies', 
                            array( 
                                'code' => $currency_code,
                                'value' => (double) $exchange_rate,
                                'changed' => $date
                            ), 
                            array( 'id' => $currency_id ) 
                    );

                    wp_safe_redirect(admin_url('admin.php?page=wpml-wcml'));
                }
            }
        }

        if(isset($_GET['page']) && $_GET['page'] == 'wpml-wcml' && isset($_GET['delete']) && $_GET['delete'] == $_GET['delete']){
            global $wpdb;

            $remove_id = $_GET['delete'];

            $delete = $wpdb->query("DELETE FROM ". $wpdb->prefix ."icl_currencies WHERE id = '$remove_id'");

            if(!$delete){
                wp_die(__('<strong>ERROR</strong>: currency can not be deleted. Please try again.'));
            }

            wp_safe_redirect(admin_url('admin.php?page=wpml-wcml'));
        }	

        add_action('admin_footer', array($this, 'documentation_links'));
        add_action('admin_notices', array($this, 'admin_notice_after_install'));

        if(isset($_GET['wcml_action']) && $_GET['wcml_action'] = 'dismiss'){
            update_option('wpml_dismiss_doc_main', 'yes');
        }
		
		if (defined('DOING_AJAX') && DOING_AJAX){
			do_action('localize_woocommerce_on_ajax');
		}

        register_deactivation_hook(__FILE__, array($this, 'wcml_deactivate'));
    }

    /**
     * Adds admin notice.
     */
    function no_wpml_warning(){
    ?>
        <div class="message error"><p><?php printf(__('WooCommerce Multilingual is enabled but not effective. It requires <a href="%s">WPML</a> in order to work.', 'plugin woocommerce'), 
        'http://wpml.org/'); ?></p></div>
    <?php
    }
    
    /**
     * Adds admin notice.
     */
    function old_wpml_warning(){
    ?>
        <div class="message error"><p><?php printf(__('WooCommerce Multilingual is enabled but not effective. It is not compatible with  <a href="%s">WPML</a> versions prior 2.0.5.', 'plugin woocommerce'), 
        'http://wpml.org/'); ?></p></div>
    <?php
    }

    /**
     * Adds admin notice.
     */
    function no_woocommerce(){
    ?>
        <div class="message error"><p><?php printf(__('WooCommerce Multilingual is enabled but not effective. It requires <a href="%s">WooCommerce</a> in order to work.', 'plugin woocommerce'), 
        'http://www.woothemes.com/woocommerce/'); ?></p></div>
    <?php
    }

    /**
     * Install the plugin.
     */
    function wcml_install(){
        global $wpdb;

        add_option('icl_is_wcml_installed', 'yes'); 

        $sql = "CREATE TABLE IF NOT EXISTS `". $wpdb->prefix ."icl_currencies` (
          `id` int(11) NOT NULL AUTO_INCREMENT,
          `language_code` varchar(7) COLLATE utf8_unicode_ci NOT NULL,
          `code` varchar(7) COLLATE utf8_unicode_ci NOT NULL,
          `value` varchar(7) COLLATE utf8_unicode_ci DEFAULT NULL,
          `changed` datetime DEFAULT NULL,
          PRIMARY KEY (`id`)
        )";

        $install = $wpdb->query($sql);
    }
    
    /**
     * For all the urls to work we need either:
     * 1) the shop page slug must be the same in all languages
     * 2) or the shop prefix disabled in woocommerce settings
     * one of these must be true for product urls to work
     * if none of these are true, display a warning message
     */
    function check_for_incompatible_permalinks() {
        global $sitepress;
        // Check if the shop prefix is disabled, if so we are ok
        if (get_option('woocommerce_prepend_shop_page_to_products') != "yes") {
            return;
        }
        // Check if translated shop pages have the same slug
        $shop_page_id = get_option('woocommerce_shop_page_id');
        $slug = get_page($shop_page_id)->post_name;
        $languages = icl_get_languages('skip_missing=0');
        $allsame = true;
        foreach ($languages as $language) {
            if ($language['language_code'] != $sitepress->get_default_language()) {
                $translated_slug = get_page(icl_object_id($shop_page_id, 'page', false, $language['language_code']))->post_name;
                if ($translated_slug != $slug) {
                    $allsame = false;
                    break;
                }
            }
        }
        if (!$allsame) {
    ?>
        <div class="message error"><p><?php printf(__('If you want different slugs for shop pages, you need to disable the shop prefix for products in <a href="%s">WooCommerce Settings</a>', 'plugin woocommerce'),
        'admin.php?page=woocommerce_settings&tab=pages'); ?></p></div>
    <?php
        }
    }
	
	/**
     * Switch the language on AJAX action, because in some ways the correct language is missing.
     * Fix is for the checkout page review order table texts and the payment gateways table texts, they're loaded on AJAX.
	 */
	function localize_on_ajax(){
		global $sitepress;
		
		$current_language = $sitepress->get_current_language();
		
		$sitepress->switch_lang($current_language, true);
	}
	
	/**
     * Filters upsell/crosell products in the correct language.
     */
	function search_products($found_products){
		global $wpdb, $sitepress;
		
		$current_page_language = $sitepress->get_current_language();
		
        foreach($found_products as $product_id => $output_v){
            $post_data = $wpdb->get_row("SELECT * FROM ". $wpdb->prefix ."icl_translations WHERE element_id = '$product_id'");
			$product_language = $post_data->language_code;
			
			if($product_language !== $current_page_language){
				unset($found_products[$product_id]);
            }
        }
	
		return $found_products;
	}
	
    /**
     * Filters WooCommerce checkout link.
     */
    function get_checkout_page_id(){
        return get_permalink(icl_object_id(get_option('woocommerce_checkout_page_id'), 'page', true));
    }
    
    /**
     * Filters WooCommerce checkout page id.
     */
    function checkout_page_id(){
        return icl_object_id(get_option('woocommerce_checkout_page_id'), 'page', true);
    }

    /**
     * Filters WooCommerce cart link.
     */
    function get_cart_page_id() {
        return icl_object_id(get_option('woocommerce_cart_page_id'), 'page', true);
    }

    /**
     * Filters WooCommerce my account link.
     */
    function get_myaccount_page_id(){
        return icl_object_id(get_option('woocommerce_myaccount_page_id'), 'page', true);
    }

    /**
     * Filters WooCommerce my account edit address link.
     */
    function get_edit_address_page_id(){
        return icl_object_id(get_option('woocommerce_edit_address_page_id'), 'page', true);
    }

    /**
     * Filters WooCommerce view order link.
     */
    function get_view_order_page_id(){
        // avoid redirect to the my account page
        if(get_option('woocommerce_view_order_page_id') !== get_option('woocommerce_checkout_page_id')){
            $return = icl_object_id(get_option('woocommerce_view_order_page_id'), 'page', true);
        } else {
            $return = false;
        }

        return $return;
    }

    /**
     * Filters WooCommerce my account change password link.
     */
    function get_change_password_page_id(){
        return icl_object_id(get_option('woocommerce_change_password_page_id'), 'page', true);
    }

    /**
     * Filters WooCommerce thanks link.
     */
    function get_thanks_page_id(){
        return icl_object_id(get_option('woocommerce_thanks_page_id'), 'page', true);
    }

    /**
     * Filters WooCommerce shop link.
     */
    function shop_page_id(){
        return icl_object_id(get_option('woocommerce_shop_page_id'), 'page', false);
    }

    /**
     * Filters WooCommerce pay page id
     */
    function pay_page_id(){
        $is_cart_page = icl_object_id(get_option('woocommerce_cart_page_id'), 'page', true);

        if(is_page($is_cart_page)){
            //return
        } else {
            return icl_object_id(get_option('woocommerce_pay_page_id'), 'page', true);
        }
    }

    /**
     * Filters WooCommerce payment link for unpaid - pending orders.
     * 
     * @global type $sitepress
     * @param type $link
     * @return type 
     */
    function get_checkout_payment_url($link){
        global $sitepress;
        return $sitepress->convert_url($link);
    }

    /**
     * Filters WooCommerce cancel order.
     * 
     * @global type $sitepress
     * @param type $link
     * @return type 
     */
    function get_cancel_order_url($link){
        global $sitepress;
        return $sitepress->convert_url($link);
    }

    /**
     * Filters WooCommerce return URL after payment.
     * 
     * @global type $sitepress
     * @param type $link
     * @return type 
     */
    function get_return_url($link){
        global $sitepress;
        return $sitepress->convert_url($link);
    }

    /**
     * Filters WooCommerce product remove link.
     * 
     * @param type $link
     * @return type 
     */
    function get_remove_url($link){
        // outputs raw
        return $link;
    }

    /**
     * After email translation switch language to default.
     *
     * @param type $title
     * @param $_product
     * @return type
     */
    function in_cart_product_title($title, $_product){
        $product_id = icl_object_id($_product['product_id'], 'product', false, ICL_LANGUAGE_CODE);

        if($product_id){
            $title = get_the_title($product_id);
        }

        return $title;
    }

    /**
     * Adjusts WooCommerce product ID to be added in cart (original product ID).
     * 
     * @param type $product_id
     * @return type 
     */
    function in_cart_product_id($product_id) {
        return icl_object_id($product_id, 'product', true);
    }

    /**
     * Filters WooCommerce AJAX params
     * 
     * @global type $sitepress
     * @global type $post
     * @param type $value
     * @return type 
     */
    function ajax_params($value){
        global $sitepress, $post;

        if(!isset($post->ID)){
            $post->ID = null;
        }

        if($sitepress->get_current_language() !== $sitepress->get_default_language()){
            $value['checkout_url'] = admin_url('admin-ajax.php?action=woocommerce-checkout&lang=' . ICL_LANGUAGE_CODE);
            $value['ajax_url'] = admin_url('admin-ajax.php?lang=' . ICL_LANGUAGE_CODE);
        }

        $checkout_page_id = get_option('woocommerce_checkout_page_id');
        $pay_page_id = get_option('woocommerce_pay_page_id');
        $cart_page_id = get_option('woocommerce_cart_page_id');

        $translated_checkout_page_id = icl_object_id($checkout_page_id, 'page', false);
        $translated_pay_page_id = icl_object_id($pay_page_id, 'page', false);
        $translated_cart_page_id = icl_object_id($cart_page_id, 'page', false);

        if($translated_cart_page_id == $post->ID){
            $value['is_cart'] = 1;
        } else if($translated_checkout_page_id == $post->ID || $checkout_page_id == $post->ID){
            $value['is_checkout'] = 1;

            $value['locale'] = '';

            $_SESSION['wpml_globalcart_language'] = $sitepress->get_current_language();

        } else if($translated_pay_page_id == $post->ID){
            $value['is_pay_page'] = 1;
        }

        return $value;
    }

    /**
     * Filters WooCommerce redirect location.
     * 
     * @global type $sitepress
     * @param type $link
     * @return type 
     */
    function do_redirect($link){
        global $sitepress;
        return $sitepress->convert_url($link);
    }

    /**
     * Translates attributes names.
     * 
     * @param type $name
     * @return type
     */
    function translate_attributes($name){
        if(function_exists('icl_register_string')){
            icl_register_string('woocommerce', $name .'_attribute', $name);

            $name = icl_t('woocommerce', $name .'_attribute', $name);
        }

        return $name;
    }

    /**
     * Takes off translated products from the Up-sells/Cross-sells tab.
     * 
     * @global type $sitepress
     * @global type $wpdb
     * @return type
     */
    function woocommerce_upsell_crosssell_search_posts($posts){
        global $sitepress, $wpdb;

        foreach($posts as $key => $post){
            $post_id = $posts[$key]->ID;
            $post_data = $wpdb->get_row("SELECT * FROM ". $wpdb->prefix ."icl_translations WHERE element_id = '$post_id'", ARRAY_A);

            if($post_data['language_code'] !== $sitepress->get_current_language()){
                unset($posts[$key]);
            }
        }

        return $posts;
    }

    /**
     * Avoids the post translation links on the product post type.
     * 
     * @global type $post
     * @return type
     */
    function post_alternative_languages($output){
        global $post;

        $post_type = get_post_type($post->ID);
        $checkout_page_id = wpml_checkout_page_id();

        if($post_type == 'product' || is_page($checkout_page_id)){
            $output = '';
        }

        return $output;
    }

    /**
     * Translates custom attribute/variation title.
     * 
     * @return type
     */
    function variation_term_name($term_name){
        if(function_exists('icl_t')){
            $term_name = icl_t('woocommerce', $term_name .'_attribute_name', $term_name);
        }

        return $term_name;
    }

    /**
     * Translates the payment gateway title text.
     * 
     * @return type
     */
    function gateway_title($title){

        if(function_exists('icl_t')){
            $translated_title = icl_t('woocommerce', $title .'_gateway_title', $title);
        }

        if(!$translated_title){

            if(function_exists('icl_register_string')){
                icl_register_string('woocommerce', $title .'_gateway_title', $title);
            }

        } else {

            return $translated_title;

        }

        return $title;
    }

    /**
     * Translates the payment gateway description text.
     * 
     * @return type
     */
    function gateway_description($description, $description_raw, $gateway_title){

        if(function_exists('icl_t')){
            $translated_description = icl_t('woocommerce', $gateway_title .'_gateway_description', $description_raw);
        }

        if(!$translated_description){

            if(function_exists('icl_register_string')){
                icl_register_string('woocommerce', $gateway_title .'_gateway_description', $description_raw);
            }

        } else {

            return $translated_description;
        }

        return $description;
    }

    /**
     * Filters WooCommerce query for translated shop page
     * 
     */
    function shop_page_query($q) {
        if ( ! $q->is_main_query() )
            return;

        $shop_page_id = get_option('woocommerce_shop_page_id');
        $translated_shop_page_id = icl_object_id($shop_page_id, 'page', false);
        if (!empty($translated_shop_page_id) && $translated_shop_page_id == $q->get('page_id')) {
            $q->set( 'post_type', 'product' );
            $q->set( 'page_id', '' );
            if ( isset( $q->query['paged'] ) )
                $q->set( 'paged', $q->query['paged'] );

            // Fix conditional functions
            $q->is_singular = false;
            $q->is_archive = true;
        }
        return $q;
    }

    /**
     * Filters the currency symbol.
     */
    function woocommerce_currency_symbol($currency_symbol){
        global $sitepress, $wpdb;

        $sql = "SELECT (code) FROM ". $wpdb->prefix ."icl_currencies WHERE language_code = '". $sitepress->get_current_language() ."'";
        $db_currency = $wpdb->get_results($sql, OBJECT);

        if($db_currency){
            $currency = $db_currency[0]->code;
            $currencies = array('BRL', 'USD', 'EUR', 'JPY', 'TRY', 'NOK', 'ZAR', 'CZK', 'THB', 'GBP');

            if(in_array($currency, $currencies)){
                switch ($currency) :
                    case 'BRL' : $currency_symbol = 'R&#36;'; break;
                    case 'USD' : $currency_symbol = '&#36;'; break;
                    case 'EUR' : $currency_symbol = '&euro;'; break;
                    case 'JPY' : $currency_symbol = '&yen;'; break;
                    case 'TRY' : $currency_symbol = 'TL'; break;
                    case 'NOK' : $currency_symbol = 'kr'; break;
                    case 'ZAR' : $currency_symbol = 'R'; break;
                    case 'CZK' : $currency_symbol = '&#75;&#269;'; break;
                    case 'GBP' : $currency_symbol = '&pound;'; break;
                endswitch;
            } else {
                $currency_symbol = $currency;
            }
        }

        return $currency_symbol;
    }

    /**
     * Filters the product price.
     */
    function woocommerce_price($price){
        global $sitepress, $wpdb;

        $sql = "SELECT (value) FROM ". $wpdb->prefix ."icl_currencies WHERE language_code = '". $sitepress->get_current_language() ."'";
        $currency = $wpdb->get_results($sql, OBJECT);

        if($currency){
            $exchange_rate = $currency[0]->value;
            $price = $price * $exchange_rate;

            $price = apply_filters('woocommerce_multilingual_price', $price);
        }

        return $price;
    }

    /**
     * Translates WooCommerce emails.
     *
     * @global type $sitepress
     * @global type $order_id
     * @return type
     */
    function email_header() {
        global $sitepress, $order_id;

        $lang = get_post_meta($order_id, 'wpml_language', TRUE);

        if(empty($lang)){
            if(isset($_SESSION['wpml_globalcart_language'])){
                $lang = $_SESSION['wpml_globalcart_language'];
            } else {
                $lang = $sitepress->get_current_language();
            }
        }

        $sitepress->switch_lang($lang, true);
    }

    /**
     * After email translation switch language to default.
     *
     * @global type $sitepress
     * @return type
     */
    function email_footer() {
        global $sitepress;

        $sitepress->switch_lang();
    }

    /* 
      Only when translated products are ordered, force adjusting stuck information for all translations
      When a product in the default language is ordered stocks are adjusted automatically
    */
    function sync_product_stocks($order){
        global $sitepress;
        $order_id = $order->id;
        $order_language = get_post_meta($order_id, 'wpml_language', true);

        if($sitepress->get_default_language() != $order_language){
            $items = $order->items;    
            foreach($items as $item){

                if (isset($item['variation_id']) && $item['variation_id']>0){
                    $_product = new WC_Product_Variation( $item['variation_id'] );
                }else{
                    $_product = new WC_Product( $item['id'] );
                }

                // Out of stock attribute
                if ($_product->managing_stock() && !$_product->backorders_allowed() && $_product->get_total_stock()<=0){
                    $outofstock = 'outofstock';
                }else{
                    $outofstock = false;
                }


                if ( $_product && $_product->exists() && $_product->managing_stock() ) {

                    $trid = $sitepress->get_element_trid($_product->id, 'post_product');
                    $translations = $sitepress->get_element_translations($trid, 'post_product', true);

                    $stock          = get_post_meta($_product->id, '_stock', true);
                    $total_sales    = get_post_meta($_product->id, 'total_sales', true);

                    foreach($translations as $translation){
                        if($translation->element_id != $_product->id){
                            update_post_meta($translation->element_id, '_stock', $stock);    
                            update_post_meta($translation->element_id, 'total_sales', $total_sales);    
                            if($outofstock){
                                update_post_meta($translation->element_id, '_stock_status', 'outofstock');    
                            }
                        }
                    }

                }

            }
        }

    }

    /**
     * Adds language to order post type.
     * 
     * Language was stored in the session created on checkout page.
     * See params().
     * 
     * @param type $order_id
     */ 
    function order_language($order_id) { 
        if(!get_post_meta($order_id, 'wpml_language')){
            $language = isset($_SESSION['wpml_globalcart_language']) ? $_SESSION['wpml_globalcart_language'] : ICL_LANGUAGE_CODE;
            update_post_meta($order_id, 'wpml_language', $language);
        }
    }

    /**
     * Filters the minimum price of  price filter widget, when the multi-currency feature is enabled.
     * 
     * @param type $min_price
     * @global type $sitepress
     * @global type $wpdb
     * @return type
     */
    function price_filter_min_price($min_price){
        global $sitepress, $wpdb;

        if(get_option('icl_enable_multi_currency') == 'yes'){
            $sql = "SELECT (value) FROM ". $wpdb->prefix ."icl_currencies WHERE language_code = '". $sitepress->get_current_language() ."'";
            $currency = $wpdb->get_results($sql, OBJECT);

            if($currency){
                $exchange_rate = $currency[0]->value;
                $min_price = $min_price / $exchange_rate;
                $min_price = round($min_price);
            }
        }

        return $min_price;
    }

    /**
     * Filters the maximum price of price filter widget, when the multi-currency feature is enabled.
     * 
     * @param type $min_price
     * @global type $sitepress
     * @global type $wpdb
     * @return type
     */
    function price_filter_max_price($max_price){
        global $sitepress, $wpdb;

        if(get_option('icl_enable_multi_currency') == 'yes'){
            $sql = "SELECT (value) FROM ". $wpdb->prefix ."icl_currencies WHERE language_code = '". $sitepress->get_current_language() ."'";
            $currency = $wpdb->get_results($sql, OBJECT);

            if($currency){
                $exchange_rate = $currency[0]->value;
                $max_price = $max_price / $exchange_rate;
                $max_price = round($max_price);
            }
        }

        return $max_price;
    }

    /**
	 * If upsell and crosell products exists for the product, filter them for translations.
     * Sync attributes and variations during product duplication.
     * Sync: term relationship, post meta, post variations, post variations meta.
     * 
     * @global type $wpdb
     * @global type $pagenow
     * @global type $post
     * @return type
     */
    function synchronize_variations($duplicated_post_id, $lang, $postarr, $post_id) {
        global $wpdb, $pagenow, $sitepress;

        $post_type = @get_post_type($post_id);

        $ajax_call = (!empty($_POST['icl_ajx_action']) && $_POST['icl_ajx_action'] == 'make_duplicates');
		
		// Filter upsell and crosell products for translations
		if(($pagenow == 'post.php' || $ajax_call ) && $post_type == 'product'){
		
			$original_product_upsell_ids = get_post_meta($duplicated_post_id, '_upsell_ids', TRUE);
			$original_product_crosssell_ids = get_post_meta($duplicated_post_id, '_crosssell_ids', TRUE);
			
			if(!empty($original_product_upsell_ids)){
				$unserialized_upsell_ids = maybe_unserialize($original_product_upsell_ids);
				
				foreach($unserialized_upsell_ids as $k => $product_id){
					// get the correct language
					$upsell_product_translation_id = icl_object_id($product_id, 'product', false, $lang);
					
					if($upsell_product_translation_id){
						$unserialized_upsell_ids[$k] = $upsell_product_translation_id;
					// if it isn't translated - unset it
					} else {
						unset($unserialized_upsell_ids[$k]);
					}
				}
				
				update_post_meta($post_id, '_upsell_ids', $unserialized_upsell_ids);
			}
			
			if(!empty($original_product_crosssell_ids)){
				$unserialized_crosssell_ids = maybe_unserialize($original_product_crosssell_ids);
				
				foreach($unserialized_crosssell_ids as $k => $product_id){
					// get the correct language
					$crosssell_product_translation_id = icl_object_id($product_id, 'product', false, $lang);
					
					if($crosssell_product_translation_id){
						$unserialized_crosssell_ids[$k] = $crosssell_product_translation_id;
					// if it isn't translated - unset it
					} else {
						unset($unserialized_crosssell_ids[$k]);
					}
				}
				
				update_post_meta($post_id, '_crosssell_ids', $unserialized_crosssell_ids);
			}
			
		}
		
        if( ($pagenow == 'post.php' || $ajax_call ) && $post_type == 'product' && !empty($duplicated_post_id)){

            $get_variation_term_name = $wpdb->get_results("SELECT * FROM $wpdb->terms WHERE name = 'variable'");
            $get_variation_term_id = $get_variation_term_name[0]->term_id;

            $is_post_has_variations = $wpdb->get_results("SELECT * FROM $wpdb->term_relationships WHERE object_id = '$duplicated_post_id' AND term_taxonomy_id = '$get_variation_term_id'");
            if(!empty($is_post_has_variations)) $is_post_has_variations = TRUE;

            // synchronize term data, postmeta and post variations
            if($is_post_has_variations){
                // synchronize post variations
                $get_all_post_variations = $wpdb->get_results("SELECT * FROM $wpdb->posts 
                WHERE post_status = 'publish' AND post_type = 'product_variation' AND post_parent = '$duplicated_post_id'");

                foreach($get_all_post_variations as $k => $post_data){
                    $duplicated_post_variation_ids[] = $post_data->ID;
                }

                foreach($get_all_post_variations as $k => $post_data){
                    // Find if this has already been duplicated
                    $variation_id = $wpdb->get_var("SELECT post_id FROM $wpdb->postmeta WHERE meta_key = '_wcml_duplicate_of_variation' AND meta_value = '$post_data->ID'");

                    if (!empty($variation_id)) {
                        // Update variation
                        $wpdb->update( $wpdb->posts, array( 
                            'post_author' => $post_data->post_author,
                            'post_date_gmt' => $post_data->post_date_gmt,
                            'post_content' => $post_data->post_content,
                            'post_title' => $post_data->post_title,
                            'post_excerpt' => $post_data->post_excerpt,
                            'post_status' => $post_data->post_status,
                            'comment_status' => $post_data->comment_status,
                            'ping_status' => $post_data->ping_status,
                            'post_password' => $post_data->post_password,
                            'post_name' => $post_data->post_name,
                            'to_ping' => $post_data->to_ping,
                            'pinged' => $post_data->pinged,
                            'post_modified' => $post_data->post_modified,
                            'post_modified_gmt' => $post_data->post_modified_gmt,
                            'post_content_filtered' => $post_data->post_content_filtered,
                            'post_parent' => $post_id, // current post ID
                            'menu_order' => $post_data->menu_order,
                            'post_type' => $post_data->post_type,
                            'post_mime_type' => $post_data->post_mime_type,
                            'comment_count' => $post_data->comment_count
                        ), array('ID' => $variation_id));
                    } else {
                        // Add new variation
                        $guid = $post_data->guid;
                        $replaced_guid = str_replace($duplicated_post_id, $post_id, $guid);
                        $wpdb->insert( $wpdb->posts, array( 
                            'post_author' => $post_data->post_author,
                            'post_date_gmt' => $post_data->post_date_gmt,
                            'post_content' => $post_data->post_content,
                            'post_title' => $post_data->post_title,
                            'post_excerpt' => $post_data->post_excerpt,
                            'post_status' => $post_data->post_status,
                            'comment_status' => $post_data->comment_status,
                            'ping_status' => $post_data->ping_status,
                            'post_password' => $post_data->post_password,
                            'post_name' => $post_data->post_name,
                            'to_ping' => $post_data->to_ping,
                            'pinged' => $post_data->pinged,
                            'post_modified' => $post_data->post_modified,
                            'post_modified_gmt' => $post_data->post_modified_gmt,
                            'post_content_filtered' => $post_data->post_content_filtered,
                            'post_parent' => $post_id, // current post ID
                            'guid' => $replaced_guid,
                            'menu_order' => $post_data->menu_order,
                            'post_type' => $post_data->post_type,
                            'post_mime_type' => $post_data->post_mime_type,
                            'comment_count' => $post_data->comment_count
                        ));
                        update_post_meta($wpdb->insert_id, '_wcml_duplicate_of_variation', $post_data->ID);
                    }
                }

                $get_current_post_variations = $wpdb->get_results("SELECT * FROM $wpdb->posts 
                WHERE post_status = 'publish' AND post_type = 'product_variation' AND post_parent = '$post_id'");

                // Delete variations that no longer exist
                foreach ($get_current_post_variations as $post_data) {
                    $variation_id = get_post_meta($post_data->ID, '_wcml_duplicate_of_variation', true);
                    if (!in_array($variation_id, $duplicated_post_variation_ids)) {
                        wp_delete_post($id, true);
                    }
                }

                // synchronize post variations post meta
                foreach($get_current_post_variations as $k => $post_data){
                    $current_post_variation_ids[] = $post_data->ID;
                }

                foreach($duplicated_post_variation_ids as $dp_key => $duplicated_post_variation_id){
                    $get_all_post_meta = $wpdb->get_results("SELECT * FROM $wpdb->postmeta WHERE post_id = '$duplicated_post_variation_id'");

                    foreach($get_all_post_meta as $k => $post_meta){
                        $meta_key = $post_meta->meta_key;
                        $meta_value = $post_meta->meta_value;

                        // adjust the global attribute slug in the custom field
                        if (substr($meta_key, 0, 10) == 'attribute_') {
                            $tax = substr($meta_key, 10);
                            $attid = get_term_by('slug', $meta_value, $tax)->term_id;
                            $attid = icl_object_id($attid, $tax, true, $lang);
                            $meta_value = get_term_by('id', $attid, $tax)->slug;
                        }
                        
                        // update current post variations meta
                        update_post_meta($current_post_variation_ids[$dp_key], $meta_key, $meta_value);
                    }
                }
            }
        }
    }

    /**
     * Creates WCML page.
     */
    function menu(){
        $top_page = apply_filters('icl_menu_main_page', basename(ICL_PLUGIN_PATH) .'/menu/languages.php');

        add_submenu_page($top_page, __('WooCommerce Multilingual','wpml-wcml'),
        __('WooCommerce Multilingual', 'wpml-wcml'), 'manage_options', 'wpml-wcml', array($this, 'menu_content'));
    }

    /**
     * Creates WCML page content.
     */
    function menu_content(){
        include WCML_PLUGIN_PATH . '/menu/management.php';
    }

    /**
     * Adds additional CSS and JS.
     */
    function load_css_and_js() {
        wp_enqueue_style('wpml-wcml', WCML_PLUGIN_URL . '/assets/css/management.css', array(), WCML_VERSION);

        wp_enqueue_script(
            'jquery-validate',
            plugin_dir_url(__FILE__) . '/assets/js/jquery.validate.min.js',
            array('jquery'),
            '1.8.1',
            true
        );
    }

    /**
     * Makes all new attributes translatable.
     */
    function make_new_attributes_translatable(){
        if(isset($_GET['page']) && $_GET['page'] == 'woocommerce_attributes'){

            $wpml_settings = get_option('icl_sitepress_settings');

            $get_all_taxonomies = get_taxonomies();

            foreach($get_all_taxonomies as $tax_key => $taxonomy){
                $pos = strpos($taxonomy, 'pa_');

                // get only product attribute taxonomy name
                if($pos !== false){	
                    $wpml_settings['taxonomies_sync_option'][$taxonomy] = 1;
                }
            }

            update_option('icl_sitepress_settings', $wpml_settings);
        }
    }

    /**
     * Registers custom attribute/variation title for translation.
     * 
     * @global type $wpdb
     * @return type
     */
    function translate_custom_attributes(){
        global $wpdb;

        $all_variations = $wpdb->get_results("SELECT ID FROM $wpdb->posts WHERE post_status = 'publish' AND post_type = 'product_variation'");

        $attribute_meta_keys = $wpdb->get_results("SELECT DISTINCT(meta_value) FROM $wpdb->postmeta WHERE meta_key LIKE 'attribute_%'");

        foreach($attribute_meta_keys as $k => $meta){
            $variation_name = $meta->meta_value;

            if(function_exists('icl_register_string')){
                icl_register_string('woocommerce', $variation_name .'_attribute_name', $variation_name);
            }
        }
    }

    /**
     * Adds admin notice.
     */
    function currency_exists_error(){
    ?>
        <div class="message error"><p><?php echo __('Currency of the selected language already exists.', 'wpml-wcml'); ?></p></div>
    <?php
    }

    /**
     * Outputs documentation links.
     */
    function documentation_links(){
        global $post, $pagenow;

        $get_post_type = get_post_type(@$post->ID);

        if($get_post_type == 'product' && $pagenow == 'edit.php'){
            $prot_link = '<span class="button" style="padding:4px;margin-top:10px;"><img align="baseline" src="' . ICL_PLUGIN_URL .'/res/img/icon16.png" width="16" height="16" style="margin-bottom:-4px" /> <a href="http://wpml.org/documentation/related-projects/woocommerce-multilingual/#translating_products" target="_blank">' .
                    __('How to translate products', 'sitepress') . '<\/a>' . '<\/span>'
        ?>
                <script type="text/javascript">
                    jQuery(".subsubsub").append('<?php echo $prot_link ?>');
                </script>
        <?php
        }

        if(isset($_GET['taxonomy'])){
            $pos = strpos($_GET['taxonomy'], 'pa_');

            if($pos !== false && $pagenow == 'edit-tags.php'){
                $prot_link = '<span class="button" style="padding:4px;margin-top:0px; float: left;"><img align="baseline" src="' . ICL_PLUGIN_URL .'/res/img/icon16.png" width="16" height="16" style="margin-bottom:-4px" /> <a href="http://wpml.org/documentation/related-projects/woocommerce-multilingual/#translating_attributes" target="_blank" style="text-decoration: none;">' .
                            __('How to translate attributes', 'sitepress') . '<\/a>' . '<\/span><br \/><br \/>'
                ?>
                        <script type="text/javascript">
                            jQuery("table.widefat").before('<?php echo $prot_link ?>');
                        </script>
                <?php
            }
        }

        if(isset($_GET['taxonomy']) && $_GET['taxonomy'] == 'product_cat'){

                $prot_link = '<span class="button" style="padding:4px;margin-top:0px; float: left;"><img align="baseline" src="' . ICL_PLUGIN_URL .'/res/img/icon16.png" width="16" height="16" style="margin-bottom:-4px" /> <a href="http://wpml.org/documentation/related-projects/woocommerce-multilingual/#translating_product_categories" target="_blank" style="text-decoration: none;">' .
                            __('How to translate product categories', 'sitepress') . '<\/a>' . '<\/span><br \/><br \/>'
                ?>
                        <script type="text/javascript">
                            jQuery("table.widefat").before('<?php echo $prot_link ?>');
                        </script>
                <?php
        }
    }

    /**
     * Admin notice after plugin install.
     */
    function admin_notice_after_install(){
        if(get_option('wpml_dismiss_doc_main') != 'yes'){

            $url = $_SERVER['REQUEST_URI'];
            $pos = strpos($url, '?');

            if($pos !== false){
                $url .= '&wcml_action=dismiss';
            } else {
                $url .= '?wcml_action=dismiss';
            }
    ?>
            <div id="message" class="updated message fade" style="clear:both;margin-top:5px;"><p>
                <?php _e('Would you like to see a quick overview?', 'sitepress'); ?>
                </p>
                <p>
                <a class="button-primary" href="http://wpml.org/documentation/related-projects/woocommerce-multilingual/" target="_blank">Learn how to turn your e-commerce site multilingual</a>
                <a class="button-secondary" href="<?php echo $url; ?>">Dismiss</a>
                </p>
            </div>
    <?php
        }
    }

    /**
     * WooCommerce Multilingual deactivation hook.
     */
    function wcml_deactivate(){
        delete_option('wpml_dismiss_doc_main');
    }

}
