<?php 
/*
  Plugin Name: WooCommerce Multilingual
  Plugin URI: http://wpml.org/documentation/related-projects/woocommerce-multilingual/
  Description: Allows running fully multilingual e-Commerce sites with WooCommerce and WPML. <a href="http://wpml.org/documentation/related-projects/woocommerce-multilingual/">Documentation</a>.
  Author: ICanLocalize
  Author URI: http://wpml.org/
  Version: 1.0
 */
add_action('plugins_loaded', 'wpml_woocommerce_init', 2);

function wpml_woocommerce_init(){
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
	
	// Filter WPML language switcher
	add_filter('icl_ls_languages', 'wpml_ls_filter');

	add_filter('woocommerce_get_checkout_url', 'wpml_get_checkout_url');
    add_filter('woocommerce_get_cart_page_id', 'wpml_get_cart_url');
    add_filter('woocommerce_get_remove_url', 'wpml_get_remove_url');
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
	add_filter('woocommerce_in_cart_product_title', 'wpml_in_cart_product_title', 13, 2);
	add_filter('woocommerce_in_cart_product_id', 'wpml_in_cart_product_id', 11, 2);
	add_filter('woocommerce_params', 'wpml_params');
    add_filter('woocommerce_redirect', 'wpml_redirect');
	add_filter('wp_head', 'wpml_redirect_to_shop_base_page');
	
	add_action("updated_post_meta", 'wpml_updated_post_meta_hook', 10, 4);
	add_action('woocommerce_email_header', 'wpml_email_header', 0);
	add_action('woocommerce_email_footer', 'wpml_email_footer', 0);
	add_action('woocommerce_new_order', 'wpml_order_language');
	add_action('init', 'wpml_change_permalinks');
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
function wpml_redirect_to_shop_base_page(){
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
function wpml_redirect($link){
	global $sitepress;
    return $sitepress->convert_url($link);
}

/**
 * Filters WooCommerce shop link.
 * 
 * @param type $link
 * @return type 
 */
function wpml_shop_page_id($link){
	$link = icl_object_id(get_option('woocommerce_shop_page_id'), 'page', false);
	
	return $link;
}

/**
 * Filters WooCommerce thanks link.
 */
function wpml_get_thanks_page_id(){
	return icl_object_id(get_option('woocommerce_thanks_page_id'), 'page', true);
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
	return icl_object_id(get_option('woocommerce_view_order_page_id'), 'page', true);
}

/**
 * Filters WooCommerce my account change password link.
 */
function wpml_get_change_password_page_id(){
	return icl_object_id(get_option('woocommerce_change_password_page_id'), 'page', true);
}

/**
 * Filters WooCommerce pay page id
 * @return type 
 */
function wpml_pay_page_id(){
	$is_cart_page = icl_object_id(get_option('woocommerce_cart_page_id'), 'page', true);
	
	if(is_page($is_cart_page)){
		//return true;
	} else {
		return icl_object_id(get_option('woocommerce_pay_page_id'), 'page', true);
	}
}

/**
 * Filters WooCommerce checkout link.
 * 
 * @global type $sitepress
 * @param type $link
 * @return type 
 */
function wpml_get_checkout_url($link){
	global $sitepress;

	return get_permalink(icl_object_id(get_option('woocommerce_checkout_page_id'), 'page', true));
}

/**
 * Filters WooCommerce cart link.
 * 
 * @return type 
 */
function wpml_get_cart_url($link) {
	return icl_object_id(get_option('woocommerce_cart_page_id'), 'page', true);
}

/**
 * Filters WooCommerce product remove link.
 * 
 * @global type $sitepress
 * @param type $link
 * @return type 
 */
function wpml_get_remove_url($link){
	global $sitepress;

    return $link;
}

/**
 * Filters WooCommerce js params
 * 
 * @global type $sitepress
 * @global type $post
 * @param type $value
 * @return type 
 */
function wpml_params($value){
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
	global $sitepress, $order_id, $email_heading;
	
	$order_lang = get_post_custom_values('wpml_language', $order_id);
	$order_lang = trim($order_lang[0]);
	
	$sitepress->switch_lang($order_lang, true);
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

?>
