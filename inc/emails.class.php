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
        
        //WPML job link
        add_filter('icl_job_edit_url',array($this,'icl_job_edit_url'),10 ,2);
    }    
    
    /**
     * Translate WooCommerce emails.
     *
     * @global type $sitepress
     * @global type $order_id
     * @return type
     */
    function email_header($order) {
        global $sitepress,$woocommerce;
        
        if (is_array($order)) {
            $order = $order['order_id'];
        } elseif (is_object($order)) {
            $order = $order->id;
        }

        $lang = get_post_meta($order, 'wpml_language', TRUE);
        if(!empty($lang)){
            $sitepress->switch_lang($lang, true);
            $domain = 'woocommerce';
            unload_textdomain($domain);
            $woocommerce->load_plugin_textdomain();
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
    

    function icl_job_edit_url($link,$job_id){
        global $wpdb,$sitepress;

        $trid = $wpdb->get_var($wpdb->prepare("
                    SELECT t.trid
                        FROM {$wpdb->prefix}icl_translate_job j
                        JOIN {$wpdb->prefix}icl_translation_status s ON j.rid = s.rid
                        JOIN {$wpdb->prefix}icl_translations t ON s.translation_id = t.translation_id
                    WHERE j.job_id = %d
                ", $job_id));

        if($trid){
            $original_product_id = $wpdb->get_var($wpdb->prepare("
                    SELECT element_id
                        FROM {$wpdb->prefix}icl_translations
                    WHERE trid = %d AND element_type = 'post_product' AND language_code = '%s'
                ", $trid,$sitepress->get_default_language()));

            if($original_product_id){
                $link = admin_url('admin.php?page=wpml-wcml&tab=products&prid='.$original_product_id);
            }
        }

        return $link;
    }

}