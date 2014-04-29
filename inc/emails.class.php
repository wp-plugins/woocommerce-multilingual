<?php
class WCML_Emails{
    
    function __construct(){
        
        add_action('init', array($this, 'init'));
        
        
    }   
    
    function init(){
        //wrappers for email's header
        if(is_admin() && !defined( 'DOING_AJAX' )){
            add_action('woocommerce_order_status_completed_notification', array($this, 'email_heading_completed'),9);
            add_action( 'woocommerce_order_status_pending_to_processing_notification', array( $this, 'email_heading_processing' ) );
            add_action( 'woocommerce_order_status_pending_to_on-hold_notification', array( $this, 'email_heading_processing' ) );
            add_action('woocommerce_new_customer_note_notification', array($this, 'email_heading_note'),9);
            add_action('woocommerce_order_status_changed', array($this, 'comments_language'),10);
        }

        //wrappers for email's body
        add_action('woocommerce_before_resend_order_emails', array($this, 'email_header'));
        add_action('woocommerce_after_resend_order_email', array($this, 'email_footer'));
        add_action( 'woocommerce_email_before_order_table', array( $this, 'email_instructions' ), 9, 3 );
        
        //WPML job link
        add_filter('icl_job_edit_url',array($this,'icl_job_edit_url'),10 ,2);

        //change order status
        add_action('woocommerce_order_status_completed',array($this,'refresh_email_lang'),9);
        add_action('woocommerce_order_status_pending_to_processing_notification',array($this,'refresh_email_lang'),9);
        add_action('woocommerce_order_status_pending_to_on-hold_notification',array($this,'refresh_email_lang'),9);
        add_action('woocommerce_new_customer_note',array($this,'refresh_email_lang'),9);


        //admin emails
        add_action( 'woocommerce_order_status_pending_to_processing_notification', array( $this, 'admin_email' ), 9 );
        add_action( 'woocommerce_order_status_pending_to_completed_notification', array( $this, 'admin_email' ), 9 );
        add_action( 'woocommerce_order_status_pending_to_on-hold_notification', array( $this, 'admin_email' ), 9 );
        add_action( 'woocommerce_order_status_failed_to_processing_notification', array( $this, 'admin_email' ), 9 );
        add_action( 'woocommerce_order_status_failed_to_completed_notification', array( $this, 'admin_email' ), 9 );
        add_action( 'woocommerce_order_status_failed_to_on-hold_notification', array( $this, 'admin_email' ), 9 );
    }    
    
    /**
     * Translate WooCommerce emails.
     *
     * @global type $sitepress
     * @global type $order_id
     * @return type
     */
    function email_header($order) {

        
        if (is_array($order)) {
            $order = $order['order_id'];
        } elseif (is_object($order)) {
            $order = $order->id;
        }

        $this->refresh_email_lang($order);

    }


    function refresh_email_lang($order_id){

        if(is_array($order_id)){
           if(isset($order_id['order_id'])){
               $order_id = $order_id['order_id'];
           }else{
           return;
        }

        }

        $lang = get_post_meta($order_id, 'wpml_language', TRUE);
        if(!empty($lang)){
            $this->change_email_language($lang);
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
        $sitepress->switch_lang($sitepress->get_default_language());

    }    

    function comments_language(){
        global $sitepress_settings;
        $this->change_email_language($sitepress_settings['st']['strings_language']);
    }

    function email_heading_completed($order_id){
        global $woocommerce,$wpdb,$sitepress_settings;
        if(class_exists('WC_Email_Customer_Completed_Order')){
            $heading = $wpdb->get_results($wpdb->prepare("SELECT value,context FROM {$wpdb->prefix}icl_strings WHERE language = %s AND name = %s ", $sitepress_settings['st']['strings_language'],'[woocommerce_customer_completed_order_settings]heading'));
            if($heading)
                $woocommerce->mailer()->emails['WC_Email_Customer_Completed_Order']->heading = icl_t($heading[0]->context,'[woocommerce_customer_completed_order_settings]heading',$heading[0]->value);
            $subject = $wpdb->get_results($wpdb->prepare("SELECT value,context FROM {$wpdb->prefix}icl_strings WHERE language = %s AND name = %s ", $sitepress_settings['st']['strings_language'], '[woocommerce_customer_completed_order_settings]subject'));
            if($subject)
                $woocommerce->mailer()->emails['WC_Email_Customer_Completed_Order']->subject = icl_t($subject[0]->context,'[woocommerce_customer_completed_order_settings]subject',$subject[0]->value);
            $heading_downloadable = $wpdb->get_results($wpdb->prepare("SELECT value,context FROM {$wpdb->prefix}icl_strings WHERE language = %s AND name = %s ", $sitepress_settings['st']['strings_language'],'[woocommerce_customer_completed_order_settings]heading_downloadable'));
            if($heading_downloadable)
                $woocommerce->mailer()->emails['WC_Email_Customer_Completed_Order']->heading_downloadable = icl_t($heading_downloadable[0]->context,'[woocommerce_customer_completed_order_settings]heading_downloadable',$heading_downloadable[0]->value);
            $subject_downloadable = $wpdb->get_results($wpdb->prepare("SELECT value,context FROM {$wpdb->prefix}icl_strings WHERE language = %s AND name = %s ", $sitepress_settings['st']['strings_language'], '[woocommerce_customer_completed_order_settings]subject_downloadable'));
            if($subject_downloadable)
                $woocommerce->mailer()->emails['WC_Email_Customer_Completed_Order']->subject_downloadable = icl_t($subject_downloadable[0]->context,'[woocommerce_customer_completed_order_settings]subject_downloadable',$subject_downloadable[0]->value);

            $enabled = $woocommerce->mailer()->emails['WC_Email_Customer_Completed_Order']->enabled;
            $woocommerce->mailer()->emails['WC_Email_Customer_Completed_Order']->enabled = false;
            $woocommerce->mailer()->emails['WC_Email_Customer_Completed_Order']->trigger($order_id);
            $woocommerce->mailer()->emails['WC_Email_Customer_Completed_Order']->enabled = $enabled;
        }
    }

    function email_heading_processing($order_id){
        global $woocommerce,$wpdb,$sitepress_settings;
        if(class_exists('WC_Email_Customer_Processing_Order')){
            $heading = $wpdb->get_results($wpdb->prepare("SELECT value,context FROM {$wpdb->prefix}icl_strings WHERE language = %s AND name = %s ", $sitepress_settings['st']['strings_language'], '[woocommerce_customer_processing_order_settings]heading'));
            if($heading)
                $woocommerce->mailer()->emails['WC_Email_Customer_Processing_Order']->heading = icl_t($heading[0]->context,'[woocommerce_customer_processing_order_settings]heading',$heading[0]->value);
            $subject = $wpdb->get_results($wpdb->prepare("SELECT value,context FROM {$wpdb->prefix}icl_strings WHERE language = %s AND name = %s ", $sitepress_settings['st']['strings_language'], '[woocommerce_customer_processing_order_settings]subject'));
            if($subject)
                $woocommerce->mailer()->emails['WC_Email_Customer_Processing_Order']->subject = icl_t($subject[0]->context,'[woocommerce_customer_processing_order_settings]subject',$subject[0]->value);

            $enabled = $woocommerce->mailer()->emails['WC_Email_Customer_Processing_Order']->enabled;
            $woocommerce->mailer()->emails['WC_Email_Customer_Processing_Order']->enabled = false;
            $woocommerce->mailer()->emails['WC_Email_Customer_Processing_Order']->trigger($order_id);
            $woocommerce->mailer()->emails['WC_Email_Customer_Processing_Order']->enabled = $enabled;
        }
    }

    function email_heading_note($args){
        global $woocommerce,$wpdb,$sitepress_settings,$sitepress;
        if(class_exists('WC_Email_Customer_Note')){
            $heading = $wpdb->get_results($wpdb->prepare("SELECT value,context FROM {$wpdb->prefix}icl_strings WHERE language = %s AND name = %s ", $sitepress_settings['st']['strings_language'], '[woocommerce_customer_note_settings]heading'));
            if($heading)
                $woocommerce->mailer()->emails['WC_Email_Customer_Note']->heading = icl_t($heading[0]->context,'[woocommerce_customer_note_settings]heading',$heading[0]->value);
            $subject = $wpdb->get_results($wpdb->prepare("SELECT value,context FROM {$wpdb->prefix}icl_strings WHERE language = %s AND name = %s ", $sitepress_settings['st']['strings_language'],'[woocommerce_customer_note_settings]subject'));
            if($subject)
                $woocommerce->mailer()->emails['WC_Email_Customer_Note']->subject = icl_t($subject[0]->context,'[woocommerce_customer_note_settings]subject',$subject[0]->value);

            $enabled = $woocommerce->mailer()->emails['WC_Email_Customer_Note']->enabled;
            $woocommerce->mailer()->emails['WC_Email_Customer_Note']->enabled = false;
            $woocommerce->mailer()->emails['WC_Email_Customer_Note']->trigger($args);
            $woocommerce->mailer()->emails['WC_Email_Customer_Note']->enabled = $enabled;
        }
    }


    function admin_email($order_id){
        global $woocommerce,$sitepress,$wpdb,$sitepress_settings;
        if(class_exists('WC_Email_New_Order')){
            $recipients = explode(',',$woocommerce->mailer()->emails['WC_Email_New_Order']->get_recipient());
            foreach($recipients as $recipient){
                $user = get_user_by('email',$recipient);
                if($user){
                    $user_lang = $sitepress->get_user_admin_language($user->ID);
                }else{
                    $user_lang = get_post_meta($order_id, 'wpml_language', TRUE);
                }
                $this->change_email_language($user_lang);
                $heading = $wpdb->get_results($wpdb->prepare("SELECT value,context FROM {$wpdb->prefix}icl_strings WHERE language = %s AND name = %s ", $sitepress_settings['st']['strings_language'], '[woocommerce_new_order_settings]heading'));
                if($heading)
                    $woocommerce->mailer()->emails['WC_Email_New_Order']->heading = icl_t($heading[0]->context,'[woocommerce_new_order_settings]heading',$heading[0]->value);
                $subject = $wpdb->get_results($wpdb->prepare("SELECT value,context FROM {$wpdb->prefix}icl_strings WHERE language = %s AND name = %s ", $sitepress_settings['st']['strings_language'], '[woocommerce_new_order_settings]subject'));
                if($subject)
                    $woocommerce->mailer()->emails['WC_Email_New_Order']->subject = icl_t($subject[0]->context,'[woocommerce_new_order_settings]subject',$subject[0]->value);



                $woocommerce->mailer()->emails['WC_Email_New_Order']->recipient = $recipient;
                $woocommerce->mailer()->emails['WC_Email_New_Order']->trigger($order_id);
            }
            $woocommerce->mailer()->emails['WC_Email_New_Order']->enabled = false;
            $this->refresh_email_lang($order_id);
        }
    }

    function change_email_language($lang){
        global $sitepress,$woocommerce;
        $sitepress->switch_lang($lang,true);
        unload_textdomain('woocommerce');
        unload_textdomain('default');
        $woocommerce->load_plugin_textdomain();
        load_default_textdomain();
        global $wp_locale;
        $wp_locale = new WP_Locale();
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

    function email_instructions($order, $sent_to_admin, $plain_text = false){
        global $woocommerce_wpml;
        $this->refresh_email_lang($order->id);
        $woocommerce_wpml->strings->translate_payment_instructions($order->payment_method);
    }

}