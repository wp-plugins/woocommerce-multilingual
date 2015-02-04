<?php
class WCML_Emails{

    private $order_id = false;

    private $locale = false;

    function __construct(){
        
        add_action('init', array($this, 'init'));
        
    }   
    
    function init(){
        //wrappers for email's header
        if(is_admin() && !defined( 'DOING_AJAX' )){
            add_action('woocommerce_order_status_completed_notification', array($this, 'email_heading_completed'),9);
            add_action('woocommerce_order_status_changed', array($this, 'comments_language'),10);
        }

        add_action('woocommerce_new_customer_note_notification', array($this, 'email_heading_note'),9);
        add_action('wp_ajax_woocommerce_mark_order_complete',array($this,'email_refresh_in_ajax'),9);

        add_action( 'woocommerce_order_status_pending_to_processing_notification', array( $this, 'email_heading_processing' ) );
        add_action( 'woocommerce_order_status_pending_to_on-hold_notification', array( $this, 'email_heading_processing' ) );

        //wrappers for email's body
        add_action('woocommerce_before_resend_order_emails', array($this, 'email_header'));
        add_action('woocommerce_after_resend_order_email', array($this, 'email_footer'));
        add_action( 'woocommerce_email_before_order_table', array( $this, 'email_instructions' ), 9, 3 );

        //WPML job link
        add_filter('icl_job_edit_url',array($this,'icl_job_edit_url'),10 ,2);
        //filter string language before for emails
        add_filter('icl_current_string_language',array($this,'icl_current_string_language'),10 ,2);

        //change order status
        add_action('woocommerce_order_status_completed',array($this,'refresh_email_lang_complete'),9);
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

        add_filter( 'icl_st_admin_string_return_cached', array( $this, 'admin_string_return_cached' ), 10, 2 );

        add_filter( 'plugin_locale', array( $this, 'set_locale_for_emails' ), 10, 2 );
    }    
    function email_refresh_in_ajax(){
        if(isset($_GET['order_id'])){
            $this->refresh_email_lang($_GET['order_id']);
            $this->email_heading_completed($_GET['order_id'],true);
        }
    }

    function refresh_email_lang_complete( $order_id ){

        $this->order_id = $order_id;
        $this->refresh_email_lang($order_id);
        $this->email_heading_completed($order_id,true);

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

        if ( WPML_SUPPORT_STRINGS_IN_DIFF_LANG ) {
            $context_ob = icl_st_get_context( 'woocommerce' );
            if($context_ob){
                $this->change_email_language($context_ob->language);
            }
        }else{
            $this->change_email_language($sitepress_settings['st']['strings_language']);
        }

    }

    function email_heading_completed( $order_id, $no_checking = false ){
        global $woocommerce;
        if(class_exists('WC_Email_Customer_Completed_Order') || $no_checking){
            $heading = $this->wcml_get_email_string_info( '[woocommerce_customer_completed_order_settings]heading' );
            if($heading)
                $woocommerce->mailer()->emails['WC_Email_Customer_Completed_Order']->heading = icl_t($heading[0]->context,'[woocommerce_customer_completed_order_settings]heading',$heading[0]->value);
            $subject = $this->wcml_get_email_string_info( '[woocommerce_customer_completed_order_settings]subject' );
            if($subject)
                $woocommerce->mailer()->emails['WC_Email_Customer_Completed_Order']->subject = icl_t($subject[0]->context,'[woocommerce_customer_completed_order_settings]subject',$subject[0]->value);
            $heading_downloadable = $this->wcml_get_email_string_info( '[woocommerce_customer_completed_order_settings]heading_downloadable' );
            if($heading_downloadable)
                $woocommerce->mailer()->emails['WC_Email_Customer_Completed_Order']->heading_downloadable = icl_t($heading_downloadable[0]->context,'[woocommerce_customer_completed_order_settings]heading_downloadable',$heading_downloadable[0]->value);
            $subject_downloadable = $this->wcml_get_email_string_info( '[woocommerce_customer_completed_order_settings]subject_downloadable' );
            if($subject_downloadable)
                $woocommerce->mailer()->emails['WC_Email_Customer_Completed_Order']->subject_downloadable = icl_t($subject_downloadable[0]->context,'[woocommerce_customer_completed_order_settings]subject_downloadable',$subject_downloadable[0]->value);

            $enabled = $woocommerce->mailer()->emails['WC_Email_Customer_Completed_Order']->enabled;
            $woocommerce->mailer()->emails['WC_Email_Customer_Completed_Order']->enabled = false;
            $woocommerce->mailer()->emails['WC_Email_Customer_Completed_Order']->trigger($order_id);
            $woocommerce->mailer()->emails['WC_Email_Customer_Completed_Order']->enabled = $enabled;
        }
    }

    function email_heading_processing($order_id){
        global $woocommerce;
        if(class_exists('WC_Email_Customer_Processing_Order')){
            $heading = $this->wcml_get_email_string_info( '[woocommerce_customer_processing_order_settings]heading' );
            if($heading)
                $woocommerce->mailer()->emails['WC_Email_Customer_Processing_Order']->heading = icl_t($heading[0]->context,'[woocommerce_customer_processing_order_settings]heading',$heading[0]->value);
            $subject = $this->wcml_get_email_string_info( '[woocommerce_customer_processing_order_settings]subject' );
            if($subject)
                $woocommerce->mailer()->emails['WC_Email_Customer_Processing_Order']->subject = icl_t($subject[0]->context,'[woocommerce_customer_processing_order_settings]subject',$subject[0]->value);

            $enabled = $woocommerce->mailer()->emails['WC_Email_Customer_Processing_Order']->enabled;
            $woocommerce->mailer()->emails['WC_Email_Customer_Processing_Order']->enabled = false;
            $woocommerce->mailer()->emails['WC_Email_Customer_Processing_Order']->trigger($order_id);
            $woocommerce->mailer()->emails['WC_Email_Customer_Processing_Order']->enabled = $enabled;
        }
    }

    function email_heading_note($args){
        global $woocommerce,$sitepress;

        if(class_exists('WC_Email_Customer_Note')){
            $heading = $this->wcml_get_email_string_info( '[woocommerce_customer_note_settings]heading' );
            if($heading)
                $woocommerce->mailer()->emails['WC_Email_Customer_Note']->heading = icl_t($heading[0]->context,'[woocommerce_customer_note_settings]heading',$heading[0]->value);

            $subject = $this->wcml_get_email_string_info( '[woocommerce_customer_note_settings]subject' );
            if($subject)
                $woocommerce->mailer()->emails['WC_Email_Customer_Note']->subject = icl_t($subject[0]->context,'[woocommerce_customer_note_settings]subject',$subject[0]->value);

            $enabled = $woocommerce->mailer()->emails['WC_Email_Customer_Note']->enabled;
            $woocommerce->mailer()->emails['WC_Email_Customer_Note']->enabled = false;
            $woocommerce->mailer()->emails['WC_Email_Customer_Note']->trigger($args);
            $woocommerce->mailer()->emails['WC_Email_Customer_Note']->enabled = $enabled;
        }
    }


    function admin_email($order_id){
        global $woocommerce,$sitepress;
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
                $heading = $this->wcml_get_email_string_info( '[woocommerce_new_order_settings]heading' );
                if($heading)
                    $woocommerce->mailer()->emails['WC_Email_New_Order']->heading = icl_t($heading[0]->context,'[woocommerce_new_order_settings]heading',$heading[0]->value);
                $subject = $this->wcml_get_email_string_info( '[woocommerce_new_order_settings]subject' );
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
        $this->locale = $sitepress->get_locale( $lang );
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
                    WHERE trid = %d AND element_type = 'post_product' AND source_language_code IS NULL
                ", $trid ));

            if($original_product_id){
                $link = admin_url('admin.php?page=wpml-wcml&tab=products&prid='.$original_product_id);
            }
        }

        return $link;
    }

    function email_instructions($order, $sent_to_admin, $plain_text = false){
        global $woocommerce_wpml;
        $woocommerce_wpml->strings->translate_payment_instructions($order->payment_method);
    }

    function admin_string_return_cached( $value, $option ){
        if( in_array( $option, array ( 'woocommerce_email_from_address', 'woocommerce_email_from_name' ) ) )
            return false;

        return $value;
    }

    function wcml_get_email_string_info( $name ){
        global $wpdb;

        if ( WPML_SUPPORT_STRINGS_IN_DIFF_LANG ) {
            $result = $wpdb->get_results( $wpdb->prepare( "SELECT st.value,cn.context FROM {$wpdb->prefix}icl_strings as st LEFT JOIN {$wpdb->prefix}icl_string_contexts as cn ON st.context_id = cn.id WHERE st.name = %s ", $name ) );
        }else{
            global $sitepress_settings;
            $language =  $sitepress_settings['st']['strings_language'];
            $result = $wpdb->get_results( $wpdb->prepare( "SELECT value,context FROM {$wpdb->prefix}icl_strings WHERE language = %s AND name = %s ", $language, $name ) );
        }

        return $result;

    }

    function icl_current_string_language(  $current_language, $name ){
        $order_id = false;

        if( isset($_POST['action']) && $_POST['action'] == 'editpost' && isset($_POST['post_type']) && $_POST['post_type'] == 'shop_order' ){
            $order_id = $_POST['post_ID'];
        }elseif( isset($_POST['action']) && $_POST['action'] == 'woocommerce_add_order_note' && isset($_POST['note_type']) && $_POST['note_type'] == 'customer' ) {
            $order_id = $_POST['post_id'];
        }elseif( isset($_GET['action']) && isset($_GET['order_id']) && $_GET['action'] == 'woocommerce_mark_order_complete'){
            $order_id = $_GET['order_id'];
        }elseif(isset($_GET['action']) && $_GET['action'] == 'mark_completed' && $this->order_id){
            $order_id = $this->order_id;
        }

        if( $order_id ){
            $order_language = get_post_meta( $order_id, 'wpml_language', true );
            if( $order_language ){
                return $order_language;
            }else{
                global $sitepress;
                return $sitepress->get_current_language();
            }
        }

        return $current_language;
    }

    // set correct locale code for emails
    function set_locale_for_emails(  $locale, $domain ){

        if( $domain == 'woocommerce' && $this->locale ){
            $locale = $this->locale;
        }

        return $locale;
    }

}