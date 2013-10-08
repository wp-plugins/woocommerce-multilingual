<?php

class WCML_Upgrade{
    
    private $versions = array(
            
                '2.9.9.1'
                
    );
    
    function __construct(){
        
        add_action('plugins_loaded', array($this, 'run')); 
        add_action('plugins_loaded', array($this, 'setup_upgrade_notices'));
        add_action('admin_notices',  array($this, 'show_upgrade_notices'));
        
        add_action('wp_ajax_wcml_hide_notice', array($this, 'hide_upgrade_notice'));
        
    }   
    
    function setup_upgrade_notices(){
        
        $wcml_settings = get_option('_wcml_settings');
        $version_in_db = get_option('_wcml_version');
        
        if(!empty($version_in_db) && version_compare($version_in_db, '2.9.9.1', '<')){
            $n = 'varimages';
            $wcml_settings['notifications'][$n] = 
                array(
                    'show' => 1, 
                    'text' => __('Looks like you are upgrading from a previous version of WooCommerce Multilingual. Would you like to automatically create translated variations and images?', 'wcml').
                            '<br /><strong>' .
                            ' <a href="' .  admin_url('admin.php?page=' . basename(WCML_PLUGIN_PATH) . '/menu/sub/troubleshooting.php') . '">' . __('Yes, go to the troubleshooting page', 'wcml') . '</a> |' . 
                            ' <a href="#" onclick="jQuery.ajax({type:\'POST\',url: ajaxurl,data:\'action=wcml_hide_notice&notice='.$n.'\',success:function(){jQuery(\'#' . $n . '\').fadeOut()}});return false;">'  . __('No - dismiss', 'wcml') . '</a>' . 
                            '</strong>'
                );
            update_option('_wcml_settings', $wcml_settings);
        }
        
    }
    
    function show_upgrade_notices(){
        $wcml_settings = get_option('_wcml_settings');
        if(!empty($wcml_settings['notifications'])){ 
            foreach($wcml_settings['notifications'] as $k => $notification){
                
                // exceptions
                if(isset($_GET['page']) && $_GET['page'] == basename(WCML_PLUGIN_PATH) . '/menu/sub/troubleshooting.php' && $k == 'varimages') continue;
                
                if($notification['show']){
                    ?>
                    <div id="<?php echo $k ?>" class="updated">
                        <p><?php echo $notification['text']  ?></p>
                    </div>
                    <?php    
                }
            }
        }
    }
    
    function hide_upgrade_notice($k){
        
        if(empty($k)){
            $k = $_POST['notice'];
        }
        
        $wcml_settings = get_option('_wcml_settings');
        if(isset($wcml_settings['notifications'][$k])){
            $wcml_settings['notifications'][$k]['show'] = 0;
            update_option('_wcml_settings', $wcml_settings);
        }
    }
    
    function run(){
        
        $version_in_db = get_option('_wcml_version');
        
        // exception - starting in 2.3.2
        if(empty($version_in_db) && get_option('icl_is_wcml_installed')){
            $version_in_db = '2.3.2';
            //delete_option('icl_is_wcml_installed');
        }

        $migration_ran = false;
        
        if($version_in_db && version_compare($version_in_db, WCML_VERSION, '<')){
                        
            foreach($this->versions as $version){
                
                if(version_compare($version, WCML_VERSION, '<=') && version_compare($version, $version_in_db, '>')){

                    $upgrade_method = 'upgrade_' . str_replace('.', '_', $version);
                    
                    if(method_exists($this, $upgrade_method)){
                        $this->$upgrade_method();
                        $migration_ran = true;
                    }
                    
                }
                
            }
            
        }
        
        if($migration_ran || empty($version_in_db)){
            update_option('_wcml_version', WCML_VERSION);            
        }
    }
    
    function upgrade_2_9_9_1(){
        global $wpdb;
        
        //migrate exists currencies
        $currencies = $wpdb->get_results("SELECT * FROM " . $wpdb->prefix . "icl_currencies ORDER BY `id` DESC");
        foreach($currencies as $currency){
            if(isset($currency->language_code)){
            $wpdb->insert($wpdb->prefix .'icl_languages_currencies', array(
                    'language_code' => $currency->language_code,
                    'currency_id' => $currency->id
                )
            );
        }
        }

        $cols = $wpdb->get_col("SHOW COLUMNS FROM {$wpdb->prefix}icl_currencies");        
        if(in_array('language_code', $cols)){
            $wpdb->query("ALTER TABLE {$wpdb->prefix}icl_currencies DROP COLUMN language_code");
        }
        
        // migrate settings
        $new_settings = array(
            'is_term_order_synced'       => get_option('icl_is_wcml_term_order_synched'),
            'file_path_sync'             => get_option('wcml_file_path_sync'),
            'is_installed'               => get_option('icl_is_wpcml_installed'),
            'dismiss_doc_main'           => get_option('wpml_dismiss_doc_main'),
            'enable_multi_currency'      => get_option('icl_enable_multi_currency'),
            'currency_converting_option' => get_option('currency_converting_option')
        );
        
        if(!get_option('_wcml_settings')){
            add_option('_wcml_settings', $new_settings, false, true);
        }
        
        delete_option('icl_is_wcml_term_order_synced');
        delete_option('wcml_file_path_sync');
        delete_option('icl_is_wpcml_installed');
        delete_option('wpml_dismiss_doc_main');
        delete_option('icl_enable_multi_currency');
        delete_option('currency_converting_option');
        
        
    }
    
    
    
    
    
    
    
}

