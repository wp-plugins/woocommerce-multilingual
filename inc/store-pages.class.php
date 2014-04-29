<?php
  
class WCML_Store_Pages{
    
    function __construct(){
        
        add_action('init', array($this, 'init'));
        // Translate shop page ids
        $this->add_filter_to_get_shop_translated_page_id();
    }   
    
    function init(){        
        global $woocommerce_wpml;
        if(!is_admin()){
            add_filter('pre_get_posts', array($this, 'shop_page_query'), 9);
            add_filter('icl_ls_languages', array($this, 'translate_ls_shop_url'));
            add_filter('parse_request', array($this, 'adjust_shop_page'));
        }
        
        if (isset($_POST['create_pages']) && wp_verify_nonce($_POST['wcml_nonce'], 'create_pages')) {
            $this->create_missing_store_pages();
        }
        
        // table rate shipping support        
        if(defined('TABLE_RATE_SHIPPING_VERSION') && $woocommerce_wpml->settings['enable_multi_currency'] == WCML_MULTI_CURRENCIES_INDEPENDENT){
            add_filter('woocommerce_table_rate_query_rates_args', array($this, 'default_shipping_class_id'));
        }
        
        $this->front_page_id = get_option('page_on_front');
        $this->shop_page_id =  woocommerce_get_page_id('shop');
        $this->shop_page = get_post( woocommerce_get_page_id('shop') );
        
        
        $this->localize_flat_rates_shipping_classes();
        
    }   
    
    function add_filter_to_get_shop_translated_page_id(){
        $woo_pages = array(
            'shop_page_id',
            'cart_page_id',
            'checkout_page_id',
            'myaccount_page_id',
            'lost_password_page_id',
            'edit_address_page_id',
            'view_order_page_id',
            'change_password_page_id',
            'logout_page_id',
            'pay_page_id',
            'thanks_page_id',
            'terms_page_id',
            'review_order_page_id'
        );

        foreach($woo_pages as $woo_page){
            add_filter('woocommerce_get_'.$woo_page, array($this, 'translate_pages_in_settings'));
            //I think following filter not needed because "option_woocommerce_..." not used in Woo, but I need ask David to confirm this
            add_filter('option_woocommerce_'.$woo_page, array($this, 'translate_pages_in_settings'));
        }

        add_filter('woocommerce_get_checkout_url', array($this, 'get_checkout_page_url'));
    }
    
    function translate_pages_in_settings($id) {
        return icl_object_id($id, 'page', true);
    }
    
    function default_shipping_class_id($args){
        global $sitepress, $woocommerce_wpml;
        if($sitepress->get_current_language() != $sitepress->get_default_language() && !empty($args['shipping_class_id'])){
            
            $args['shipping_class_id'] = icl_object_id($args['shipping_class_id'], 'product_shipping_class', false, $sitepress->get_default_language());
            
            // use unfiltred cart price to compare against limits of different shipping methods
            $args['price'] = $woocommerce_wpml->multi_currency->unconvert_price_amount($args['price']); 
            
        }
        
        return $args;
    }
    
    /**
     * Filters WooCommerce query for translated shop page
     * 
     */
    function shop_page_query($q) {
        if ( ! $q->is_main_query() )
            return;

        if (!empty($this->shop_page) && $q->get('page_id') !== $this->front_page_id && $this->shop_page_id == $q->get('page_id')) {
            $q->set( 'post_type', 'product' );
            $q->set( 'page_id', '' );
            if ( isset( $q->query['paged'] ) )
                $q->set( 'paged', $q->query['paged'] );

            // Get the actual WP page to avoid errors
            // This is hacky but works. Awaiting http://core.trac.wordpress.org/ticket/21096
            global $wp_post_types;

            $q->is_page = true;

            $wp_post_types['product']->ID             = $this->shop_page->ID;
            $wp_post_types['product']->post_title     = $this->shop_page->post_title;
            $wp_post_types['product']->post_name      = $this->shop_page->post_name;

            // Fix conditional functions
            $q->is_singular = false;
            $q->is_post_type_archive = true;
            $q->is_archive = true;
            $q->queried_object = get_post_type_object('product');
        }
    }    
    
    /**
     * Translate shop url
     */
    function translate_ls_shop_url($languages) {
        global $sitepress;
        $shop_id = $this->shop_page_id;
        $front_id = icl_object_id($this->front_page_id, 'page');
        foreach ($languages as &$language) {
            // shop page
            // obsolete?
            if (is_post_type_archive('product')) {
                if ($front_id == $shop_id) {
                    $url = $sitepress->language_url($language['language_code']);
                } else {
                    $url = get_permalink(icl_object_id($shop_id, 'page', true, $language['language_code']));
                }
                $language['url'] = $url;
            }
        }
        return $languages;
    }

    function adjust_shop_page($q) {
        global $sitepress;
        if ($sitepress->get_default_language() != $sitepress->get_current_language()) {
            if (!empty($q->query_vars['pagename'])) {
                $shop_page = get_post( woocommerce_get_page_id('shop') );
                // we should explode by / for children page
                $query_var_page = explode('/',$q->query_vars['pagename']);
                if ($shop_page->post_name == $query_var_page[count($query_var_page)-1]) {
                    unset($q->query_vars['page']);
                    unset($q->query_vars['pagename']);
                    $q->query_vars['post_type'] = 'product';
                }
            }
        }
    }
    
    /**
     * create missing pages
     */
    function create_missing_store_pages() {
        global $sitepress,$wp_rewrite,$woocommerce_wpml;
        $miss_lang = $this->get_missing_store_pages();

        //dummy array for names
        $names = array( __('Cart','wpml-wcml'),
                        __('Checkout','wpml-wcml'),
                        __('Checkout &rarr; Pay','wpml-wcml'),
                        __('Order Received','wpml-wcml'),
                        __('My Account','wpml-wcml'),
                        __('Change Password','wpml-wcml'),
                        __('Edit My Address','wpml-wcml'),
                        __('Logout','wpml-wcml'),
                        __('Lost Password','wpml-wcml'),
                        __('View Order','wpml-wcml'),
                        __('Shop','wpml-wcml'));

        if ($miss_lang) {            
            $wp_rewrite = new WP_Rewrite();
            $current_language = $sitepress->get_current_language();
            
            $check_pages = apply_filters('wcml_wc_installed_pages', array(
                'woocommerce_shop_page_id',
                'woocommerce_cart_page_id',
                'woocommerce_checkout_page_id',
                'woocommerce_myaccount_page_id'
            ));
            
            if(in_array($sitepress->get_default_language(), $miss_lang['codes'])){
                $miss_lang['codes'] = array_merge(array($sitepress->get_default_language()), array_diff($miss_lang['codes'], array($sitepress->get_default_language())));   
            }                               
            
            foreach ($miss_lang['codes'] as $mis_lang) {
                $args = array();

                foreach ($check_pages as $page) {
                    
                    
                    $orig_id = get_option($page);
                    $trnsl_id = icl_object_id($orig_id, 'page', false, $mis_lang);

                    if ($orig_id && (is_null($trnsl_id) || get_post_status($trnsl_id) != 'publish')) {
                        $orig_page = get_post($orig_id);
                        unload_textdomain('wpml-wcml');
                        $sitepress->switch_lang($mis_lang);
                        $woocommerce_wpml->load_locale();
                        $args['post_title'] = __($orig_page->post_title, 'wpml-wcml');
                        $args['post_type'] = $orig_page->post_type;
                        $args['post_content'] = $orig_page->post_content;
                        $args['post_excerpt'] = $orig_page->post_excerpt;
                        $args['post_status'] = get_post_status($trnsl_id) != 'publish' ? 'publish' : $orig_page->post_status;
                        $args['menu_order'] = $orig_page->menu_order;
                        $args['ping_status'] = $orig_page->ping_status;
                        $args['comment_status'] = $orig_page->comment_status;
                        $post_parent = icl_object_id($orig_page->post_parent, 'page', false, $mis_lang);
                        $args['post_parent'] = is_null($post_parent)?0:$post_parent;
                        $new_page_id = wp_insert_post($args);

                        if(get_post_status($trnsl_id) == 'trash' && $mis_lang == $sitepress->get_default_language()){
                            update_option($page, $new_page_id);
                        }
                        
                        $trid = $sitepress->get_element_trid($orig_id, 'post_' . $orig_page->post_type);
                        $sitepress->set_element_language_details($new_page_id, 'post_' . $orig_page->post_type, $trid, $mis_lang);
                        if(!is_null($trnsl_id)){
                            $sitepress->set_element_language_details($trnsl_id, 'post_' . $orig_page->post_type, false, $mis_lang);
                        }
                        $sitepress->switch_lang($current_language);
                    }
                }
            }
            
            unload_textdomain('wpml-wcml');
            $sitepress->switch_lang($current_language);
            $woocommerce_wpml->load_locale();
            wp_redirect(admin_url('admin.php?page=wpml-wcml')); exit;
        }
    }
    
     /**
     * get missing pages
     * return array;
     */
     function get_missing_store_pages() {

        $check_pages = apply_filters('wcml_wc_installed_pages', array(
            'woocommerce_shop_page_id',
            'woocommerce_cart_page_id',
            'woocommerce_checkout_page_id',
            'woocommerce_myaccount_page_id'
        ));

        $missing_lang = '';
        
        foreach ($check_pages as $page) {
            $page_id = get_option($page);
            
                if(!$page_id || !get_page($page_id)){
                    return 'non_exist';
                }
        }
        
        global $sitepress;
        $languages = $sitepress->get_active_languages();
        $default_language = $sitepress->get_default_language();

        $missing_lang_codes = array();
        foreach ($languages as $language) {
            foreach ($check_pages as $page) {
                $store_page_id = get_option($page);
                $trnsl_page_id = icl_object_id($store_page_id, 'page', false, $language['code']);
                if ($store_page_id && (is_null($trnsl_page_id) || get_post_status($trnsl_page_id)!='publish')) {
                    if (!empty($missing_lang)) {
                        $missing_lang .= ', ' . $language['display_name'];
                    } else {
                        $missing_lang .= $language['display_name'];
                    }
                    $missing_lang_codes[] = $language['code'];
                    break;
                }
            }
        }


        if (!empty($missing_lang)) {
            $array = array();
            $array['lang'] = $missing_lang;
            $array['codes'] = $missing_lang_codes;
            return $array;
        } else {
            return false;
        }
    }
    
    /**
     * Filters WooCommerce checkout link.
     */
    function get_checkout_page_url(){
        return get_permalink(icl_object_id(get_option('woocommerce_checkout_page_id'), 'page', true));
    }
    
    function localize_flat_rates_shipping_classes(){
        global $woocommerce;
        
        if(is_ajax() && isset($_POST['action']) && $_POST['action'] == 'woocommerce_update_order_review'){
            $woocommerce->shipping->load_shipping_methods();
            $shipping_methods = $woocommerce->shipping->get_shipping_methods();
            foreach($shipping_methods as $method){
                if(isset($method->flat_rate_option)){
                    add_filter('option_' . $method->flat_rate_option, array($this, 'translate_shipping_classs'));
                }
            }
            
        }
        
        
    }
    
    function translate_shipping_classs($rates){
        
        if(is_array($rates)){
            foreach($rates as $shipping_class => $value){
                $term = get_term_by('slug', $shipping_class, 'product_shipping_class');
                if($term && !is_wp_error($term)){
                    $translated_term_id = icl_object_id($term->term_id, 'product_shipping_class', true);
                    if($translated_term_id != $term->term_id){
                        $term = get_term_by('id', $translated_term_id, 'product_shipping_class');
                        unset($rates[$shipping_class]);
                        $rates[$term->slug] = $value;
                        
                    }
                }
            }
        }   
        return $rates;    
    }
        
    
}
