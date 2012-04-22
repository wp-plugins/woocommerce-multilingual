<?php 
/*
  Plugin Name: WooCommerce Multilingual
  Plugin URI: http://wpml.org/documentation/related-projects/woocommerce-multilingual/
  Description: Allows running fully multilingual e-Commerce sites with WooCommerce and WPML. <a href="http://wpml.org/documentation/related-projects/woocommerce-multilingual/">Documentation</a>.
  Author: ICanLocalize
  Author URI: http://wpml.org/
  Version: 1.1
*/

define('WCML_VERSION', '1.1');
define('WCML_PLUGIN_PATH', dirname(__FILE__));
define('WCML_PLUGIN_FOLDER', basename(WCML_PLUGIN_PATH));
define('WCML_PLUGIN_URL', plugins_url() . '/' . WCML_PLUGIN_FOLDER);

add_action('plugins_loaded', 'wpml_woocommerce_multilingual_init', 2);

function wpml_woocommerce_multilingual_init(){
	if(!defined('ICL_SITEPRESS_VERSION') || ICL_PLUGIN_INACTIVE){
    	if(!function_exists('is_multisite') || !is_multisite()) {
    		add_action('admin_notices', 'wpml_no_wpml_warning');
    	}
    	return false;            
	} else if(version_compare(ICL_SITEPRESS_VERSION, '2.0.5', '<')){
		add_action('admin_notices', 'wpml_old_wpml_warning');
		return false;
	} else if(!class_exists('woocommerce')){
		add_action('admin_notices', 'wpml_no_woocommerce');
		return false;
	}
	
	if(get_option('icl_is_wcml_installed') !== 'yes'){
		add_action('init', 'wpml_install');
	}
	
	// Filters WPML language switcher
	add_filter('icl_ls_languages', 'wpml_ls_filter');

	add_filter('woocommerce_get_checkout_url', 'wpml_get_checkout_page_id');
	add_filter('woocommerce_get_checkout_page_id', 'wpml_checkout_page_id');
    add_filter('woocommerce_get_cart_page_id', 'wpml_get_cart_page_id');
	add_filter('woocommerce_get_myaccount_page_id', 'wpml_get_myaccount_page_id');
    add_filter('woocommerce_get_edit_address_page_id', 'wpml_get_edit_address_page_id');
    add_filter('woocommerce_get_view_order_page_id', 'wpml_get_view_order_page_id');
    add_filter('woocommerce_get_change_password_page_id', 'wpml_get_change_password_page_id');
	add_filter('woocommerce_get_thanks_page_id', 'wpml_get_thanks_page_id');
    add_filter('woocommerce_get_shop_page_id', 'wpml_shop_page_id');
	add_filter('woocommerce_get_pay_page_id', 'wpml_pay_page_id');
	add_filter('woocommerce_get_checkout_payment_url', 'wpml_get_checkout_payment_url');
	add_filter('woocommerce_get_cancel_order_url', 'wpml_get_cancel_order_url');
	add_filter('woocommerce_get_return_url', 'wpml_get_return_url');
	add_filter('woocommerce_get_remove_url', 'wpml_get_remove_url');
	
	add_filter('woocommerce_in_cart_product_title', 'wpml_in_cart_product_title', 13, 2);
	add_filter('woocommerce_in_cart_product_id', 'wpml_in_cart_product_id', 11, 2);
	
	add_filter('woocommerce_params', 'wpml_ajax_params');
    add_filter('woocommerce_redirect', 'wpml_do_redirect');
    add_filter('woocommerce_attribute_label', 'wpml_translate_attributes', 14, 2);
    add_filter('woocommerce_upsell_crosssell_search_products', 'wpml_woocommerce_upsell_crosssell_search_posts');
    add_filter('icl_post_alternative_languages', 'wpml_post_alternative_languages');
	add_filter('wp_head', 'wpml_redirect_to_the_base_page');

	if(get_option('icl_enable_multi_currency') == 'yes'){
		add_filter('raw_woocommerce_price', 'wpml_woocommerce_price');
		add_filter('woocommerce_currency_symbol', 'wpml_woocommerce_currency_symbol', 2);
	}
	
	add_action('woocommerce_email_header', 'wpml_email_header', 0);
	add_action('woocommerce_email_footer', 'wpml_email_footer', 0);
	add_action('woocommerce_new_order', 'wpml_order_language');
	add_action('updated_post_meta', 'wpml_updated_post_meta_hook', 10, 4);
	
	add_action('admin_head', 'wpml_synchronizate_variations', 15);
	//add_action('save_post', 'wpml_synchronization_of_variations', 16);

	add_action('admin_menu', 'wpml_menu');
	add_action('init', 'wpml_change_permalinks');
	add_action('init', 'wpml_load_css_and_js');
	
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
			add_action('admin_notices', 'wpml_currency_exists_error');
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
	
	if($_GET['page'] == 'wpml-wcml' && isset($_GET['delete']) && $_GET['delete'] == $_GET['delete']){
		global $wpdb;
		
		$remove_id = $_GET['delete'];
		
		$delete = $wpdb->query("DELETE FROM ". $wpdb->prefix ."icl_currencies WHERE id = '$remove_id'");
		
		if(!$delete){
			wp_die(__('<strong>ERROR</strong>: currency can not be deleted. Please try again.'));
		}
		
		wp_safe_redirect(admin_url('admin.php?page=wpml-wcml'));
	}	
}

/**
 * Filters the product price.
 */
function wpml_woocommerce_price($price){
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
 * Filters the currency symbol.
 */
function wpml_woocommerce_currency_symbol($currency_symbol){
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
 * Install the plugin.
 */
function wpml_install(){
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
 * Adds admin notice.
 */
function wpml_no_wpml_warning(){
?>
	<div class="message error"><p><?php printf(__('WooCommerce Multilingual is enabled but not effective. It requires <a href="%s">WPML</a> in order to work.', 'plugin woocommerce'), 
	'http://wpml.org/'); ?></p></div>
<?php
}

/**
 * Adds admin notice.
 */
function wpml_old_wpml_warning(){
?>
	<div class="message error"><p><?php printf(__('WooCommerce Multilingual is enabled but not effective. It is not compatible with  <a href="%s">WPML</a> versions prior 2.0.5.', 'plugin woocommerce'), 
	'http://wpml.org/'); ?></p></div>
<?php
}

/**
 * Adds admin notice.
 */
function wpml_no_woocommerce(){
?>
	<div class="message error"><p><?php printf(__('WooCommerce Multilingual is enabled but not effective. It requires <a href="%s">WooCommerce</a> in order to work.', 'plugin woocommerce'), 
	'http://www.woothemes.com/woocommerce/'); ?></p></div>
<?php
}

/**
 * Adds admin notice.
 */
function wpml_currency_exists_error(){
?>
	<div class="message error"><p><?php echo __('Currency of the selected language already exists.', 'wpml-wcml'); ?></p></div>
<?php
}

/**
 * Filters WooCommerce product link in cart.
 * 
 * @param type $url
 * @param type $product_id
 * @return type 
 */
function wpml_add_to_cart_product_id($url, $product_id) {
    return get_permalink(icl_object_id($product_id, 'product', true));
}

/**
 * Adjusts WooCommerce product ID to be added in cart (original product ID).
 * 
 * @param type $product_id
 * @return type 
 */
function wpml_in_cart_product_id($product_id) {
    return icl_object_id($product_id, 'product', true);
}

/**
 * Synchronizes post meta 'stock' and 'stock_status' betweeen translated posts.
 * 
 * @global type $sitepress
 * @param type $meta_id
 * @param type $object_id
 * @param type $meta_key
 * @param type $_meta_value 
 */
function wpml_updated_post_meta_hook($meta_id, $object_id, $meta_key, $_meta_value) {
	global $sitepress;
	
	$update_meta_keys = array('stock', 'stock_status');

	if (in_array($meta_key, $update_meta_keys)) {
		$translations = $sitepress->get_element_translations($object_id, 'product');
		
		foreach($translations as $t){
			if(!$t->original){
				if($meta_key == 'stock'){
            		update_post_meta($t->translation_id, 'stock', $_meta_value);
            	} else if($meta_key = 'stock_status'){
            		update_post_meta($t->translation_id, 'stock_status', $_meta_value);
            	}
			}
		}
	}
}

/**
 * Filters WooCommerce navigation menu translated shop page link (redirect to store).
 * 
 * @global type $post
 * @global type $sitepress
 */
function wpml_redirect_to_the_base_page(){
	global $post, $sitepress;
	
	$shop_page_id = get_option('woocommerce_shop_page_id');
	$translated_shop_page_id = icl_object_id($shop_page_id, 'page', false);
	
	if(is_page(array($translated_shop_page_id, $shop_page_id))){
		wp_safe_redirect($sitepress->convert_url(get_option('home') . '/?post_type=product'));
	}
}

/**
 * Filters WooCommerce cancel order.
 * 
 * @global type $sitepress
 * @param type $link
 * @return type 
 */
function wpml_get_cancel_order_url($link){
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
function wpml_get_return_url($link){
	global $sitepress;
    return $sitepress->convert_url($link);
}

/**
 * Filters WooCommerce redirect location.
 * 
 * @global type $sitepress
 * @param type $link
 * @return type 
 */
function wpml_do_redirect($link){
	global $sitepress;
    return $sitepress->convert_url($link);
}

/**
 * Filters WooCommerce shop link.
 */
function wpml_shop_page_id(){
	return icl_object_id(get_option('woocommerce_shop_page_id'), 'page', false);
}

/**
 * Filters WooCommerce thanks link.
 */
function wpml_get_thanks_page_id(){
	return icl_object_id(get_option('woocommerce_thanks_page_id'), 'page', true);
}

/**
 * Filters WooCommerce checkout page id.
 */
function wpml_checkout_page_id(){
	return icl_object_id(get_option('woocommerce_checkout_page_id'), 'page', true);
}

/**
 * Filters WooCommerce payment link for unpaid - pending orders.
 * 
 * @global type $sitepress
 * @param type $link
 * @return type 
 */
function wpml_get_checkout_payment_url($link){
	global $sitepress;
    return $sitepress->convert_url($link);
}

/**
 * Filters WooCommerce my account link.
 */
function wpml_get_myaccount_page_id(){
	return icl_object_id(get_option('woocommerce_myaccount_page_id'), 'page', true);
}

/**
 * Filters WooCommerce my account edit address link.
 */
function wpml_get_edit_address_page_id(){
    return icl_object_id(get_option('woocommerce_edit_address_page_id'), 'page', true);
}

/**
 * Filters WooCommerce view order link.
 */
function wpml_get_view_order_page_id(){
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
function wpml_get_change_password_page_id(){
	return icl_object_id(get_option('woocommerce_change_password_page_id'), 'page', true);
}

/**
 * Filters WooCommerce pay page id
 */
function wpml_pay_page_id(){
	$is_cart_page = icl_object_id(get_option('woocommerce_cart_page_id'), 'page', true);
	
	if(is_page($is_cart_page)){
		//return
	} else {
		return icl_object_id(get_option('woocommerce_pay_page_id'), 'page', true);
	}
}

/**
 * Filters WooCommerce checkout link.
 */
function wpml_get_checkout_page_id(){
	return get_permalink(icl_object_id(get_option('woocommerce_checkout_page_id'), 'page', true));
}

/**
 * Filters WooCommerce cart link.
 */
function wpml_get_cart_page_id() {
	return icl_object_id(get_option('woocommerce_cart_page_id'), 'page', true);
}

/**
 * Filters WooCommerce product remove link.
 * 
 * @param type $link
 * @return type 
 */
function wpml_get_remove_url($link){
	// outputs raw
    return $link;
}

/**
 * Filters WooCommerce AJAX params
 * 
 * @global type $sitepress
 * @global type $post
 * @param type $value
 * @return type 
 */
function wpml_ajax_params($value){
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
		
		// Recreates woocommerce.params.locale
		$value['locale'] = '{"AT":{"postcode_before_city":true,"state":{"required":false}},"BE":{"postcode_before_city":true,"state":{"required":false}},
		"CA":{"state":{"label":"Province"}},"CL":{"city":{"required":false},"state":{"label":"Municipalit\u00e9"}},"CN":{"state":{"label":"Province"}},
		"CZ":{"state":{"required":false}},"DE":{"postcode_before_city":true,"state":{"required":false}},
		"DK":{"postcode_before_city":true,"state":{"required":false}},"FI":{"postcode_before_city":true,"state":{"required":false}},
		"FR":{"postcode_before_city":true,"state":{"required":false}},"HK":{"postcode":{"required":false},"city":{"label":"Ville \/ Quartier"},"state":{"label":"R\u00e9gion"}},
		"HU":{"state":{"required":false}},
		"IS":{"postcode_before_city":true,"state":{"required":false}},"IL":{"postcode_before_city":true,"state":{"required":false}},
		"NL":{"postcode_before_city":true,"state":{"required":false}},"NZ":{"state":{"required":false}},"NO":{"postcode_before_city":true,"state":{"required":false}},
		"PL":{"postcode_before_city":true,"state":{"required":false}},"RO":{"state":{"required":false}},"SG":{"state":{"required":false}},
		"SK":{"postcode_before_city":true,"state":{"required":false}},"SI":{"postcode_before_city":true,"state":{"required":false}},
		"ES":{"postcode_before_city":true,"state":{"label":"Province"}},"LK":{"state":{"required":false}},"SE":{"postcode_before_city":true,"state":{"required":false}},
		"TR":{"postcode_before_city":true,"state":{"label":"Province"}},"US":{"postcode":{"label":"Code postal"},"state":{"label":"State"}},
		"GB":{"postcode":{"label":"Code Postal"},"state":{"label":"Comt\u00e9"}},"default":{"postcode":{"label":"Code Postal \/ Zip"},"city":{"label":"Ville"},"state":{"label":"Etat\/Pays"}}}';
		$_SESSION['wpml_globalcart_language'] = $sitepress->get_current_language();
		
	} else if($translated_pay_page_id == $post->ID){
		$value['is_pay_page'] = 1;
	}

	return $value;
}

/**
 * Adds language to order post type.
 * 
 * Language was stored in the session created on checkout page.
 * See params().
 * 
 * @param type $order_id
 */
function wpml_order_language($order_id) { 
	if(!get_post_meta($order_id, 'wpml_language')){
		$language = isset($_SESSION['wpml_globalcart_language']) ? $_SESSION['wpml_globalcart_language'] : ICL_LANGUAGE_CODE;
		update_post_meta($order_id, 'wpml_language', $language);
	}
}

/**
 * Translates WooCommerce emails.
 *
 * @global type $sitepress
 * @global type $order_id
 * @return type
 */
function wpml_email_header() {
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
function wpml_email_footer() {
	global $sitepress;
	
	$sitepress->switch_lang();
}

/**
 * After email translation switch language to default.
 *
 * @param type $title
 * @param $_product
 * @return type
 */
function wpml_in_cart_product_title($title, $_product){
	$product_id = icl_object_id($_product->id, 'product', false, ICL_LANGUAGE_CODE);
	
	if($product_id){
		$title = get_the_title($product_id);
	}
	
	return $title;
}

/**
 * Filters WPML language switcher.
 * 
 * @global type $post
 * @global type $sitepress
 * @param type $languages
 * @return type 
 */
function wpml_ls_filter($languages) {
	global $post, $sitepress;
	
	$translated_checkout_page_id = icl_object_id(get_option('woocommerce_checkout_page_id'), 'page', false);
	$shop_page_id = get_option('woocommerce_shop_page_id');
	
	if(strpos(basename($_SERVER['REQUEST_URI']), 'post_type') !== false || 
		strpos(basename($_SERVER['REQUEST_URI']), 'shop') !== false){
		
			foreach($languages as $lang_code => $language){
				 $languages[$lang_code]['url'] = $sitepress->convert_url(get_option('home') 
				 . '/?post_type=product', $language['language_code']);
			}
	}
	
	return $languages;
}

/**
 * Updates the shop base page permalink in the translated language.
 * Needed for the correct products URLs in the shop base page.
 * 
 * @global type $wpdb
 * @return type 
 */
function wpml_change_permalinks(){
	global $wpdb;
	
	$translated_shop_page_id = icl_object_id(get_option('woocommerce_shop_page_id'), 'page', false);
	$posts_query = $wpdb->get_row("SELECT * FROM $wpdb->posts WHERE `ID` = '$translated_shop_page_id'", ARRAY_A);
	
	if($posts_query['post_name'] !== 'shop'){
		$wpdb->update($wpdb->posts, array('post_name' => 'shop'), array('post_name' => $posts_query['post_name']));
	}
}

/**
 * Creates WCML page.
 */
function wpml_menu(){
	$top_page = apply_filters('icl_menu_main_page', basename(ICL_PLUGIN_PATH) .'/menu/languages.php');
	
	add_submenu_page($top_page, __('WooCommerce Multilingual','wpml-wcml'),
	__('WooCommerce Multilingual', 'wpml-wcml'), 'manage_options', 'wpml-wcml', 'wpml_menu_content');
}

/**
 * Creates WCML page content.
 */
function wpml_menu_content(){
	include WCML_PLUGIN_PATH . '/menu/management.php';
}

/**
 * Adds additional CSS and JS.
 */
function wpml_load_css_and_js() {
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
 * Avoids the post translation links on the product post type.
 * 
 * @global type $post
 * @return type
 */
function wpml_post_alternative_languages($output){
	global $post;
	
	$post_type = get_post_type($post->ID);
	$checkout_page_id = wpml_checkout_page_id();
	
	if($post_type == 'product' || is_page($checkout_page_id)){
		$output = '';
	}
	
	return $output;
}

/**
 * Translates attributes names.
 * 
 * @param type $name
 * @return type
 */
function wpml_translate_attributes($name){
	icl_register_string('woocommerce', $name .'_attribute', $name);

	return icl_t('woocommerce', $name .'_attribute', $name);
}

/**
 * Takes off translated products from the Up-sells/Cross-sells tab.
 * 
 * @global type $sitepress
 * @global type $wpdb
 * @return type
 */
function wpml_woocommerce_upsell_crosssell_search_posts($posts){
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
 * Sync attributes and variations during product duplication.
 * Sync: term relationship, post meta, post variations, post variations meta.
 * 
 * @global type $wpdb
 * @global type $pagenow
 * @global type $post
 * @return type
 */
function wpml_synchronizate_variations() {
	global $wpdb, $pagenow, $post;
	
	$post_id = $post->ID;
	$post_type = get_post_type($post->ID);

	if($pagenow == 'post.php' || $pagenow == 'post-new.php' && $post_type == 'product'){
		$duplicated_post_id = get_post_meta($post_id, '_icl_lang_duplicate_of', TRUE);
		$is_data_already_synced = get_post_meta($post_id, 'wpml_variations_already_synced', TRUE);
		
		// Only on duplication and run once
		if(!empty($duplicated_post_id) && empty($is_data_already_synced)){

			$get_variation_term_name = $wpdb->get_results("SELECT * FROM $wpdb->terms WHERE name = 'variable'");
			$get_variation_term_id = $get_variation_term_name[0]->term_id;
			
			$is_post_has_variations = $wpdb->get_results("SELECT * FROM $wpdb->term_relationships WHERE object_id = '$duplicated_post_id' AND term_taxonomy_id = '$get_variation_term_id'");
			if(!empty($is_post_has_variations)) $is_post_has_variations = TRUE;
			
			// synchronize term data, postmeta and post variations
			if($is_post_has_variations){
				$get_all_term_data = $wpdb->get_results("SELECT * FROM $wpdb->term_relationships WHERE object_id = '$duplicated_post_id'");
					
					// synchronize term data
					foreach($get_all_term_data as $k => $term_relationship){
					
						$wpdb->insert( 
							$wpdb->term_relationships, 
							array( 
								'object_id' => $post_id,
								'term_taxonomy_id' => $term_relationship->term_taxonomy_id,
								'term_order' => $term_relationship->term_order
						));
						
					}
				
				// synchronize post meta
				$get_all_post_meta = $wpdb->get_results("SELECT * FROM $wpdb->postmeta WHERE post_id = '$duplicated_post_id'");
				
					foreach($get_all_post_meta as $k => $post_meta){
						$meta_key = $post_meta->meta_key;
						$meta_value = $post_meta->meta_value;
						
						update_post_meta($post_id, $meta_key, $meta_value);
					}
			
				// synchronize post variations
				$get_all_post_variations = $wpdb->get_results("SELECT * FROM $wpdb->posts 
				WHERE post_status = 'publish' AND post_type = 'product_variation' AND post_parent = '$duplicated_post_id'");
					
					foreach($get_all_post_variations as $k => $post_data){
						$guid = $post_data->guid;
						$replaced_guid = str_replace($duplicated_post_id, $post_id, $guid);
	
						$wpdb->insert( 
							$wpdb->posts, 
							array( 
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
								'post_parent' => $post_id, // current post id
								'guid' => $replaced_guid,
								'menu_order' => $post_data->menu_order,
								'post_type' => $post_data->post_type,
								'post_mime_type' => $post_data->post_mime_type,
								'comment_count' => $post_data->comment_count
						));

					}
				
				foreach($get_all_post_variations as $k => $post_data){
					$duplicated_post_variation_ids[] = $post_data->ID;
				}
				
				$get_current_post_variations = $wpdb->get_results("SELECT * FROM $wpdb->posts 
				WHERE post_status = 'publish' AND post_type = 'product_variation' AND post_parent = '$post_id'");
				
				foreach($get_current_post_variations as $k => $post_data){
					$current_post_variation_ids[] = $post_data->ID;
				}
				
				// synchronize post variations post meta
				foreach($duplicated_post_variation_ids as $dp_key => $duplicated_post_variation_id){
					$get_all_post_meta = $wpdb->get_results("SELECT * FROM $wpdb->postmeta WHERE post_id = '$duplicated_post_variation_id'");
				
					foreach($get_all_post_meta as $k => $post_meta){
						$meta_key = $post_meta->meta_key;
						$meta_value = $post_meta->meta_value;
						
						// update current post variations meta
						update_post_meta($current_post_variation_ids[$dp_key], $meta_key, $meta_value);
					}
				}
				
				// add a record that data is already synced
				update_post_meta($post_id, 'wpml_variations_already_synced', '1');
			}
		}
	}
}

?>
