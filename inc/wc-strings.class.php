<?php

class WCML_WC_Strings{
    
    function __construct(){
        
        add_action('init', array($this, 'init'));
        add_action('plugins_loaded', array($this, 'pre_init'));
        add_filter('query_vars', array($this, 'translate_query_var_for_product'));
        
    }

    function pre_init(){
        // Slug translation
        add_filter('gettext_with_context', array($this, 'translate_default_slug'), 2, 4);
    }
    
    function init(){
        global $pagenow;
         
        add_filter('woocommerce_available_shipping_methods', array($this, 'register_shipping_methods'));
        add_action('option_woocommerce_tax_rates', array($this, 'translate_tax_rates'));
        
        add_filter('woocommerce_gateway_title', array($this, 'translate_gateway_title'), 10);
        add_filter('woocommerce_gateway_description', array($this, 'translate_gateway_description'), 10, 2);
        
        add_filter('woocommerce_attribute_label', array($this, 'translate_attributes'), 14, 2);
        add_filter('woocommerce_countries_tax_or_vat', array($this, 'register_tax_label'));
        
        if(is_admin() && $pagenow == 'options-permalink.php'){
            add_action('admin_footer', array($this, 'show_custom_url_base_language_requirement'));    
        }
        
        if(is_admin() && $pagenow == 'edit.php' && isset($_GET['page']) && $_GET['page'] == 'woocommerce_attributes'){
            add_action('admin_footer', array($this, 'show_attribute_label_language_warning'));    
        }
        
        
        
        
    }
    
    
    function translate_query_var_for_product($public_query_vars){
        global $wpdb, $sitepress, $sitepress_settings;
        
        if($sitepress->get_current_language() != $sitepress_settings['st']['strings_language']){                    
            $permalinks         = get_option( 'woocommerce_permalinks' );
            $product_permalink  = empty( $permalinks['product_base'] ) ? _x( 'product', 'slug', 'woocommerce' ) : trim($permalinks['product_base'], '/');
            
            
            $translated_slug = $wpdb->get_var($wpdb->prepare("
                SELECT t.value FROM {$wpdb->prefix}icl_string_translations t
                JOIN {$wpdb->prefix}icl_strings s ON t.string_id = s.id
                WHERE s.name=%s AND s.value = %s AND t.language = %s", 
                    'URL slug: ' . $product_permalink, $product_permalink, $sitepress->get_current_language()));
            
            if(isset($_GET[$translated_slug])){
                $_GET[$product_permalink] = $_GET[$translated_slug];
                unset($_GET[$translated_slug]);
            }
            
        }
        
        return $public_query_vars;
    }
    
    // Catch the default slugs for translation
    function translate_default_slug($translation, $text, $context, $domain) {
        global $sitepress_settings, $sitepress;
        
        if ($context == 'slug' || $context == 'default-slug') {
            $wc_slug = get_option('woocommerce_product_slug') != false ? get_option('woocommerce_product_slug') : 'product';
            if(is_admin()){
                $admin_language = $sitepress->get_admin_language();
            }
            $current_language = $sitepress->get_current_language();
            if ($text == $wc_slug && $domain == 'woocommerce') {
                $sitepress->switch_lang($sitepress_settings['st']['strings_language']);
                $translation = _x($text, 'URL slug', $domain);                
                $sitepress->switch_lang($current_language);
                if(is_admin()){
                $sitepress->set_admin_language($admin_language);
            }            
        }
            if(!is_admin()){
                $sitepress->switch_lang($current_language);
            }
        }
        
        return $translation;
        
    }

    function register_shipping_methods($available_methods){
        foreach($available_methods as $method){
            $method->label = icl_translate('woocommerce', $method->label .'_shipping_method_title', $method->label);
        }

        return $available_methods;
    }
    
    function translate_tax_rates($rates){
        if (!empty($rates)) {
            foreach ($rates as &$rate) {
                $rate['label'] = icl_translate('woocommerce', 'tax_label_' . esc_url_raw($rate['label']), $rate['label']);
            }
        }

        return $rates; 
    }
    
    function translate_gateway_title($title) {
        if (function_exists('icl_translate')) {
            $title = icl_translate('woocommerce', $title .'_gateway_title', $title);
        }
        return $title;
    }
    
    function translate_gateway_description($description, $gateway_title) {
        if (function_exists('icl_translate')) {
            $description = icl_translate('woocommerce', $gateway_title .'_gateway_description', $description);
        }
        return $description;
    }    
    
    function translate_attributes($name){
        if(function_exists('icl_register_string')){
            icl_register_string('woocommerce', $name .'_attribute', $name);
            $name = icl_t('woocommerce', $name .'_attribute', $name);
        }

        return $name;
    }
    
    function register_tax_label($label){
        global $sitepress;
        
        if(function_exists('icl_translate')){
            $label = icl_translate('woocommerce', 'VAT_tax_label', $label);
        }
        
        return $label;
    }
    
    function show_custom_url_base_language_requirement(){
        global $sitepress_settings, $sitepress;
        
        echo '<div id="wpml_wcml_custom_base_req" style="display:none"><br /><i>';
        $strings_language = $sitepress->get_language_details($sitepress_settings['st']['strings_language']);
        echo sprintf(__('Please enter string in %s (the strings language)', 'wpml-wcml'), '<strong>' . $strings_language['display_name'] . '</strong>');
        echo '</i></div>';
        ?>
        <script>
            if(jQuery('#woocommerce_permalink_structure').length){
                jQuery('#woocommerce_permalink_structure').parent().append(jQuery('#wpml_wcml_custom_base_req').html());
            }    
        </script>
        <?php
            
    }
    
    function show_attribute_label_language_warning(){
        global $sitepress_settings, $sitepress;
        
        if($sitepress_settings['st']['strings_language'] != $sitepress->get_default_language()){
            $default_language = $sitepress->get_language_details($sitepress->get_default_language());
            $strings_language = $sitepress->get_language_details($sitepress_settings['st']['strings_language']);
            echo '<div id="wpml_wcml_attr_language" style="display:none"><div class="icl_cyan_box"><i>';
            echo sprintf(__("You need to enter attribute names in %s (even though your site's default language is %s). Then, translate it to %s and the rest of the site's languages using in the %sWooCommerce Multlingual admin%s.", 'wpml-wcml'), 
                 $strings_language['display_name'],
                 $default_language['display_name'],  $default_language['display_name'],
                '<strong><a href="' . admin_url('admin.php?page=wpml-wcml') . '">', '</a></strong>');
            echo '</i></div><br /></div>';
            ?>
            <script>
                if(jQuery('#attribute_label').length){
                    jQuery('#attribute_label').parent().prepend(jQuery('#wpml_wcml_attr_language').html());
                }    
            </script>
            <?php
        
        }
        
    }
    
    
}