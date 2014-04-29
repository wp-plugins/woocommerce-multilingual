<?php
class WCML_Requests{
    
    function __construct(){
        
        add_action('init', array($this, 'run'));
        
        
    }
    
    function run(){
        global $woocommerce_wpml;
        
        if(isset($_POST['general_options']) && check_admin_referer('general_options', 'general_options_nonce') && wp_verify_nonce($_POST['wcml_nonce'], 'general_options')){
            
            $woocommerce_wpml->settings['enable_multi_currency'] = $_POST['multi_currency'];                


            $woocommerce_wpml->update_settings();
            
        }
        if(isset($_POST['currency_switcher_options']) && check_admin_referer('currency_switcher_options', 'currency_switcher_options_nonce') && wp_verify_nonce($_POST['wcml_nonce'], 'general_options')){

            if(isset($_POST['currency_switcher_style'])) $woocommerce_wpml->settings['currency_switcher_style'] = $_POST['currency_switcher_style'];  
            if(isset($_POST['wcml_curr_sel_orientation'])) $woocommerce_wpml->settings['wcml_curr_sel_orientation'] = $_POST['wcml_curr_sel_orientation'];
            if(isset($_POST['wcml_curr_template'])) $woocommerce_wpml->settings['wcml_curr_template'] = $_POST['wcml_curr_template'];
            
            $woocommerce_wpml->update_settings();
            
        }

        if(isset($_POST['wcml_update_languages_currencies']) && isset($_POST['currency_for']) && wp_verify_nonce($_POST['wcml_nonce'], 'wcml_update_languages_currencies')){
            global $wpdb;
            $currencies = $_POST['currency_for'];
            foreach($currencies as $key=>$language_currency){
                $exist_currency = $wpdb->get_var($wpdb->prepare("SELECT currency_id FROM " . $wpdb->prefix . "icl_languages_currencies WHERE language_code = %s",$key));
                if($language_currency != get_woocommerce_currency()){
                    if(!$exist_currency){
                        $wpdb->insert($wpdb->prefix .'icl_languages_currencies', array(
                                'currency_id' => $language_currency,
                                'language_code' => $key
                            )
                        );
                    } else {
                        $wpdb->update(
                            $wpdb->prefix .'icl_languages_currencies',
                            array(
                                'currency_id' => $language_currency
                            ),
                            array( 'language_code' => $key )
                        );

                        wp_safe_redirect(admin_url('admin.php?page=wpml-wcml'));
                    }
                }elseif($exist_currency){
                    $wpdb->query("DELETE FROM ". $wpdb->prefix ."icl_languages_currencies WHERE language_code = '$key'");
                }
            }
        }


        if(isset($_POST['wcml_file_path_options_table']) && wp_verify_nonce($_POST['wcml_nonce'], 'wcml_file_path_options_table')){
            global $sitepress,$sitepress_settings;
            
            $woocommerce_wpml->settings['file_path_sync'] = $_POST['wcml_file_path_sync'];
            $woocommerce_wpml->update_settings();
            
            $new_value = $_POST['wcml_file_path_sync'] == 0?2:$_POST['wcml_file_path_sync'];
            $sitepress_settings['translation-management']['custom_fields_translation']['_downloadable_files'] = $new_value;
            $sitepress_settings['translation-management']['custom_fields_translation']['_file_paths'] = $new_value;
            $sitepress->save_settings($sitepress_settings);
            }
      
        if(isset($_POST['wcml_trsl_interface_table']) && wp_verify_nonce($_POST['wcml_nonce'], 'wcml_trsl_interface_table')){
            $woocommerce_wpml->settings['trnsl_interface'] = $_POST['trnsl_interface'];
            $woocommerce_wpml->update_settings();
        }
        
        if(isset($_POST['wcml_products_sync_prop']) && wp_verify_nonce($_POST['wcml_nonce'], 'wcml_products_sync_prop')){
            $woocommerce_wpml->settings['products_sync_date'] = empty($_POST['products_sync_date']) ? 0 : 1;
            $woocommerce_wpml->update_settings();
        }

        if(isset($_GET['wcml_action']) && $_GET['wcml_action'] = 'dismiss'){
            $woocommerce_wpml->settings['dismiss_doc_main'] = 'yes';
            $woocommerce_wpml->update_settings();
        }
        
        
                
        

    }

}