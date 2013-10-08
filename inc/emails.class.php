<?php
class WCML_Emails{
    
    function __construct(){
        
        add_action('init', array($this, 'init'));
        
        
    }   
    
    function init(){
        //wrappers for email's body
        add_filter('woocommerce_order_status_completed_notification', array($this, 'email_header')); 
        add_filter('woocommerce_order_status_processing_notification', array($this, 'email_header')); 
        add_filter('woocommerce_new_customer_note_notification', array($this, 'email_header')); 
        add_filter('woocommerce_before_resend_order_emails', array($this, 'email_header')); 
        add_filter('woocommerce_after_resend_order_email', array($this, 'email_footer')); 
        
    }    
    
    /**
     * Translate WooCommerce emails.
     *
     * @global type $sitepress
     * @global type $order_id
     * @return type
     */
    function email_header($order) {
        global $sitepress;
        
        if (is_array($order)) {
            $order = $order['order_id'];
        } elseif (is_object($order)) {
            $order = $order->id;
        }

        $lang = get_post_meta($order, 'wpml_language', TRUE);
        if(!empty($lang)){
            $sitepress->switch_lang($lang, true);
        }
    }

    /**
     * After email translation switch language to default.
     *
     * @global type $sitepress
     * @return type
     */
    function email_footer() {
        global $sitepress;

        $sitepress->switch_lang(ICL_LANGUAGE_CODE, true);
    }    
    

}