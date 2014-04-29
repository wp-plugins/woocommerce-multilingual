<?php
class WCML_Orders{
    
    private $standart_order_notes = array('Order status changed from %s to %s.',
        'Order item stock reduced successfully.','Item #%s stock reduced from %s to %s.','Awaiting BACS payment','Awaiting cheque payment','Payment to be made upon delivery.',
        'Validation error: PayPal amounts do not match (gross %s).','Validation error: PayPal IPN response from a different email address (%s).','Payment pending: %s',
        'Payment %s via IPN.','Validation error: PayPal amounts do not match (amt %s).','IPN payment completed','PDT payment completed'
    );
    
    function __construct(){
        
        add_action('init', array($this, 'init'));
        
        //checkout page
        add_action( 'wp_ajax_woocommerce_checkout',array($this,'switch_to_current'),9);
        add_action( 'wp_ajax_nopriv_woocommerce_checkout',array($this,'switch_to_current'),9);
    }
    
    function init(){
        
        add_action('woocommerce_shipping_update_ajax', array($this, 'fix_shipping_update'));
        add_action('woocommerce_checkout_update_order_meta', array($this, 'set_order_language'));
        
        add_filter('icl_lang_sel_copy_parameters', array($this, 'append_query_parameters'));

        add_filter('the_comments', array($this, 'get_filtered_comments'));
        add_filter('gettext',array($this, 'filtered_woocommerce_new_order_note_data'),10,3);

        add_filter('woocommerce_order_get_items',array($this,'woocommerce_order_get_items'),10);

    }

    function filtered_woocommerce_new_order_note_data($translations, $text, $domain ){
        if(in_array($text,$this->standart_order_notes)){
            global $sitepress_settings,$wpdb;

            if($sitepress_settings['admin_default_language'] == $sitepress_settings['st']['strings_language']){
                return $text;
            }

            $string_id = $wpdb->get_var($wpdb->prepare("SELECT id FROM {$wpdb->prefix}icl_strings WHERE language = %s AND value = %s ", $sitepress_settings['st']['strings_language'], $text));
            if($string_id){
                $string = $wpdb->get_var($wpdb->prepare("SELECT value FROM {$wpdb->prefix}icl_string_translations WHERE string_id = %s and language = %s", $string_id, $sitepress_settings['admin_default_language']));
                if($string){
                    $translations = $string;
                }
            }
        }

        return $translations;
    }

    function get_filtered_comments($comments){
        global $sitepress_settings, $wpdb, $current_user;
        
        if(!empty($current_user) && !is_null($current_user->data)){
            
            $user_language    = get_user_meta( $current_user->data->ID, 'icl_admin_language', true );

            foreach($comments as $key=>$comment){
                $comment_string_id = $wpdb->get_var($wpdb->prepare("SELECT id FROM {$wpdb->prefix}icl_strings WHERE language = %s AND value = %s ", $sitepress_settings['st']['strings_language'], $comment->comment_content));
                if($comment_string_id){
                    $comment_string = $wpdb->get_var($wpdb->prepare("SELECT value FROM {$wpdb->prefix}icl_string_translations WHERE string_id = %s and language = %s", $comment_string_id, $user_language));
                if($comment_string){
                        $comments[$key]->comment_content = $comment_string;
                    }
                }
            }        
            
        }

        return $comments;

    }
    
    function woocommerce_order_get_items($items){
        if(isset($_GET['post']) && get_post_type($_GET['post']) == 'shop_order'){
            global $sitepress_settings;
            foreach($items as $index=>$item){
                foreach($item as $key=>$item_data){
                    if($key == 'product_id'){
                        $tr_product_id = icl_object_id($item_data,'product',false,$sitepress_settings['admin_default_language']);
                        if(!is_null($tr_product_id)){
                            $items[$index][$key] = $tr_product_id;
                            $items[$index]['name'] = get_the_title($tr_product_id);
                        }
                    }
                    if($key == 'variation_id'){
                        $tr_variation_id = icl_object_id($item_data,'product_variation',false,$sitepress_settings['admin_default_language']);
                        if(!is_null($tr_variation_id)){
                            $items[$index][$key] = $tr_variation_id;
                        }
                    }

                    if (substr($key, 0, 3) == 'pa_') {
                        global $wpdb;
                        //attr is taxonomy
                        $default_term = get_term_by('slug', $item_data, $key);
                        $tr_id = icl_object_id($default_term->term_id, $key, false, $sitepress_settings['admin_default_language']);

                        if(!is_null($tr_id)){
                            $translated_slug = $wpdb->get_var($wpdb->prepare("
                                    SELECT t.slug FROM {$wpdb->terms} t JOIN {$wpdb->term_taxonomy} x ON x.term_id = t.term_id WHERE t.term_id = %d AND x.taxonomy = %s", $tr_id, $key));
                            $items[$index][$key] = $translated_slug;
                        }
                    }
                }
            }
        }
        return $items;
    }
    
    // Fix for shipping update on the checkout page.
    function fix_shipping_update($amount){
        global $sitepress, $post;
        
        if($sitepress->get_current_language() !== $sitepress->get_default_language() && $post->ID == $this->checkout_page_id()){
        
            $_SESSION['icl_checkout_shipping_amount'] = $amount;
            
            $amount = $_SESSION['icl_checkout_shipping_amount'];
        
        }
    
        return $amount;
    }


    /**
     * Adds language to order post type.
     * 
     * Language was stored in the session created on checkout page.
     * See params().
     * 
     * @param type $order_id
     */ 
    function set_order_language($order_id) { 
        if(!get_post_meta($order_id, 'wpml_language')){
            $language = isset($_SESSION['wpml_globalcart_language']) ? $_SESSION['wpml_globalcart_language'] : ICL_LANGUAGE_CODE;
            update_post_meta($order_id, 'wpml_language', $language);
        }
    }
    
    function append_query_parameters($parameters){
        
        if(is_order_received_page() || is_checkout()){
            if(!in_array('order', $parameters)) $parameters[] = 'order';
            if(!in_array('key', $parameters)) $parameters[] = 'key';
        }
            
        return $parameters;
    }

    function switch_to_current(){
        global $sitepress,$woocommerce_wpml;
        $woocommerce_wpml->emails->change_email_language($sitepress->get_current_language());
    }

}
