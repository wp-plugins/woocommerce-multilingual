<?php

class WCML_Compatibility {
    
    function __construct(){

        add_action('init', array($this, 'init'),9);


        // Dynamic Pricing
        if(class_exists( 'WC_Dynamic_Pricing' )){
            require_once WCML_PLUGIN_PATH . '/compatibility/wc_dynamic_pricing.class.php';
            $this->dynamic_pricing = new WCML_Dynamic_Pricing();
        }
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
        
        //Product Bundle
        if(class_exists('WC_Product_Bundle')){
            require_once WCML_PLUGIN_PATH . '/compatibility/wc_product_bundles.class.php';
            $this->product_bundles = new WCML_Product_Bundles();
        }
        
         // WooCommerce Variation Swatches and Photos
        if(class_exists('WC_SwatchesPlugin')){	
            require_once WCML_PLUGIN_PATH . '/compatibility/wc_variation_swatches_photos.class.php';
            $this->variation_sp = new WCML_Variation_Swatches_and_Photos();
        }
     
        // Product Add-ons
        if(class_exists( 'Product_Addon_Display' )){
            require_once WCML_PLUGIN_PATH . '/compatibility/wc_product_addons.class.php';
            $this->product_addons = new WCML_Product_Addons();
        }

        // Product Per Product Shipping
        if(defined( 'PER_PRODUCT_SHIPPING_VERSION' )){
            require_once WCML_PLUGIN_PATH . '/compatibility/wc_per_product_shipping.class.php';
            new WCML_Per_Product_Shipping();
        }
        //Store Exporter plugin
        if(defined('WOO_CE_PATH')){
            require_once WCML_PLUGIN_PATH . '/compatibility/wc_exporter.class.php';
            $this->wc_exporter = new WCML_wcExporter();
        }

    }

}