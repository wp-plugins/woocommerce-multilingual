<?php

class WCML_Troubleshooting{

    function __construct(){

        add_action('init', array($this, 'init'));

    }

    function init(){

        add_action('wp_ajax_trbl_sync_variations', array($this,'trbl_sync_variations'));
        add_action('wp_ajax_trbl_update_count', array($this,'trbl_update_count'));

    }

    function wcml_count_products_with_variations(){
       return count(get_option('wcml_products_to_sync'));
    }

    function trbl_update_count(){

        if(!wp_verify_nonce($_REQUEST['wcml_nonce'], 'trbl_update_count')){
            die('Invalid nonce');
        }

            $this->wcml_sync_variations_update_option();
            echo $this->wcml_count_products_with_variations();

        die();
    }

    function wcml_sync_variations_update_option(){
        global $wpdb;

        $get_variation_term_taxonomy_id = $wpdb->get_var("SELECT tt.term_taxonomy_id FROM $wpdb->terms AS t LEFT JOIN $wpdb->term_taxonomy AS tt ON t.term_id = tt.term_id WHERE t.name = 'variable'");

        $get_variables_products = $wpdb->get_results($wpdb->prepare("SELECT tr.element_id as id FROM {$wpdb->prefix}icl_translations AS tr LEFT JOIN $wpdb->term_relationships as t ON tr.element_id = t.object_id LEFT JOIN $wpdb->posts AS p ON tr.element_id = p.ID
                                WHERE p.post_status = 'publish' AND tr.source_language_code is NULL AND tr.element_type = 'post_product' AND t.term_taxonomy_id = %d ORDER BY tr.element_id",$get_variation_term_taxonomy_id),ARRAY_A);

        update_option('wcml_products_to_sync',$get_variables_products);
    }

    function trbl_sync_variations(){

        if(!wp_verify_nonce($_REQUEST['wcml_nonce'], 'trbl_sync_variations')){
            die('Invalid nonce');
        }

            global $woocommerce_wpml,$wpdb,$sitepress;

            $get_variables_products = get_option('wcml_products_to_sync');
            $all_active_lang = $sitepress->get_active_languages();
            $default_lang = $sitepress->get_default_language();
            $unset_keys = array();
            $products_for_one_ajax = array_slice($get_variables_products,0,3,true);

            foreach($all_active_lang as $language){
                if($language['code'] != $default_lang){
                    foreach ($products_for_one_ajax as $key => $product){
                        $tr_product_id = icl_object_id($product['id'],'product',false,$language['code']);

                        if(!is_null($tr_product_id)){
                            $woocommerce_wpml->products->sync_product_variations($product['id'],$tr_product_id,$language['code'],false,true);
                        }
                        if(!in_array($key,$unset_keys)){
                        $unset_keys[] = $key;
                    }
                }
            }
            }


            foreach($unset_keys as $unset_key){
                unset($get_variables_products[$unset_key]);
            }


                update_option('wcml_products_to_sync',$get_variables_products);

            
            $wcml_settings = get_option('_wcml_settings');
            if(isset($wcml_settings['notifications']) && isset($wcml_settings['notifications']['varimages'])){
                $wcml_settings['notifications']['varimages']['show'] = 0;
                update_option('_wcml_settings', $wcml_settings);    
            }
                
            echo 1;


        die();
    }

}