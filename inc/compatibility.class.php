<?php

class WCML_Compatibility {
    
    function __construct(){

        add_action('init', array($this, 'init'));

    }

    function init(){
        //hardcoded list of extensions and check which ones the user has and then include the corresponding file from the ‘compatibility’ folder

        //WooCommerce Tab Manager plugin
        if(class_exists('WC_Tab_Manager')){
            require_once WCML_PLUGIN_PATH . '/compatibility/wc_tab_manager.class.php';
            $this->tab_manager = new WCML_Tab_Manager();
        }

        //WooCommerce Table Rate Shipping plugin
        if(defined('TABLE_RATE_SHIPPING_VERSION')){
            require_once WCML_PLUGIN_PATH . '/compatibility/wc_table_rate_shipping.class.php';
            $this->table_rate_shipping = new WCML_Table_Rate_Shipping();
        }
        
        //WooCommerce Subscriptions
        if(class_exists('WC_Subscriptions')){
            require_once WCML_PLUGIN_PATH . '/compatibility/wc_subscriptions.class.php';
            $this->wp_subscriptions = new WCML_WC_Subscriptions();
        }
        

    }

}