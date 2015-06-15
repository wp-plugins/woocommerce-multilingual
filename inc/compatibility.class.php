<?php

class WCML_Compatibility {
    
    function __construct(){

        $this->init();

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
        
        //Gravity Forms
        if(class_exists('GFForms')){
            require_once WCML_PLUGIN_PATH . '/compatibility/gravityforms.class.php';
            $this->gravityforms = new WCML_gravityforms();
        }

        //Sensei WooThemes
        if(class_exists('WooThemes_Sensei')){
            require_once WCML_PLUGIN_PATH . '/compatibility/wc_sensei.class.php';
            $this->sensei = new WCML_sensei();
        }

        //Extra Product Options
        if(class_exists('TM_Extra_Product_Options')){
            require_once WCML_PLUGIN_PATH . '/compatibility/wc_extra_product_options.class.php';
            $this->extra_product_options = new WCML_Extra_Product_Options();
        }

        // Dynamic Pricing
        if(class_exists( 'WC_Dynamic_Pricing' )){
            require_once WCML_PLUGIN_PATH . '/compatibility/wc_dynamic_pricing.class.php';
            $this->dynamic_pricing = new WCML_Dynamic_Pricing();
        }

        // WooCommerce Bookings
        if(defined( 'WC_BOOKINGS_VERSION' ) && version_compare(WC_BOOKINGS_VERSION, '2.0', '>') ){
            require_once WCML_PLUGIN_PATH . '/compatibility/wc_bookings.class.php';
            $this->bookings = new WCML_Bookings();
        }

        // WooCommerce Checkout Field Editor
        if ( function_exists( 'woocommerce_init_checkout_field_editor' ) ) {
            require_once WCML_PLUGIN_PATH . '/compatibility/wc_checkout_field_editor.class.php';
            $this->checkout_field_editor = new WCML_Checkout_Field_Editor();
        }
				
				if (class_exists('WC_Bulk_Stock_Management')) {
						require_once WCML_PLUGIN_PATH . '/compatibility/wc_bulk_stock_management.class.php';
            $this->wc_bulk_stock_management = new WCML_Bulk_Stock_Management();
				}

    }

}