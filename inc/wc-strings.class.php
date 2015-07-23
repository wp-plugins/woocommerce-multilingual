<?php

class WCML_WC_Strings{
    
    function __construct(){
        
        add_action('init', array($this, 'init'));
        add_action('init', array($this, 'pre_init'));
        add_filter('query_vars', array($this, 'translate_query_var_for_product'));
        add_filter('wp_redirect', array($this, 'encode_shop_slug'),10,2);

    }

    function pre_init(){
        // Slug translation
        add_filter('gettext_with_context', array($this, 'translate_default_slug'), 2, 4);
    }
    
    function init(){
        global $pagenow;
         
        add_filter('woocommerce_package_rates', array($this, 'register_shipping_methods'));
        add_action('option_woocommerce_tax_rates', array($this, 'translate_tax_rates'));
        
        add_filter('woocommerce_gateway_title', array($this, 'translate_gateway_title'), 10, 2);
        add_filter('woocommerce_gateway_description', array($this, 'translate_gateway_description'), 10, 2);
        add_action( 'woocommerce_thankyou_bacs', array( $this, 'translate_bacs_instructions' ),9 );
        add_action( 'woocommerce_thankyou_cheque', array( $this, 'translate_cheque_instructions' ),9 );
        add_action( 'woocommerce_thankyou_cod', array( $this, 'translate_cod_instructions' ),9 );
        //translate attribute label
        add_filter('woocommerce_attribute_label',array($this,'translated_attribute_label'),10,3);
        add_filter('woocommerce_cart_item_name',array($this,'translated_cart_item_name'),10,3);
        add_filter('woocommerce_checkout_product_title',array($this,'translated_checkout_product_title'),10,2);
        add_filter('woocommerce_countries_tax_or_vat', array($this, 'register_tax_label'));
        
        if(is_admin() && $pagenow == 'options-permalink.php'){
            add_filter('gettext_with_context', array($this, 'category_base_in_strings_language'), 99, 3);
            add_action('admin_footer', array($this, 'show_custom_url_base_language_requirement'));    
        }

        if(is_admin() && $pagenow == 'admin.php' && isset($_GET['page']) && $_GET['page'] == 'wc-settings'){
            add_action('admin_footer', array($this, 'show_language_notice_for_wc_settings'));
        }
        
        if(is_admin() && $pagenow == 'edit.php' && isset($_GET['page']) && $_GET['page'] == 'woocommerce_attributes'){
            add_action('admin_footer', array($this, 'show_attribute_label_language_warning'));    
        }
        
        add_filter('woocommerce_rate_label',array($this,'translate_woocommerce_rate_label'));
        
        add_action( 'woocommerce_product_options_attributes', array ( $this, 'notice_after_woocommerce_product_options_attributes' ) );

        add_filter( 'woocommerce_attribute_taxonomies', array( $this, 'translate_attribute_taxonomies_labels') );

        add_filter('woocommerce_get_breadcrumb', array($this, 'filter_woocommerce_breadcrumbs' ), 10, 2 );
    }

    function translated_attribute_label($label, $name, $product_obj = false){
        global $sitepress,$product,$woocommerce;

        $product_id = false;
        $lang = $sitepress->get_current_language();
        $name = sanitize_title($name);

        if( isset($product->id) ){
            $product_id = $product->id;
        }elseif( isset($product_obj->id) ){
            $product_id = $product_obj->id;
        }

        if( $product_id ){

            $custom_attr_translation =  get_post_meta( $product_id, 'attr_label_translations', true ) ;

            if( $custom_attr_translation ){
                if( isset( $custom_attr_translation[$lang][$name] ) ){
                    return  $custom_attr_translation[$lang][$name];
                }
            }

        }

        if(is_admin() && !wpml_is_ajax()){
            global $wpdb,$sitepress_settings;

            $string_id = icl_get_string_id('taxonomy singular name: '.$label,'WordPress');

            if ( WPML_SUPPORT_STRINGS_IN_DIFF_LANG ) {
                $strings_language = icl_st_get_string_language( $string_id );
            }else{
                $strings_language = $sitepress_settings['st']['strings_language'];
            }

            if($string_id && $sitepress_settings['admin_default_language'] != $strings_language){
                $string = $wpdb->get_var($wpdb->prepare("SELECT value FROM {$wpdb->prefix}icl_string_translations WHERE string_id = %s and language = %s", $string_id, $sitepress_settings['admin_default_language']));
                if($string){
                    return $string;
                }
            }else{
                return $label;
            }

        }

        $trnsl_label = apply_filters( 'wpml_translate_single_string', $label, 'WordPress','taxonomy singular name: '.$label, $lang );

        if( $label != $trnsl_label ){
            return $trnsl_label;
        }

        // backward compatibility for WCML < 3.6.1
        $trnsl_labels = get_option('wcml_custom_attr_translations');

        if( isset( $trnsl_labels[$lang][$name] ) && !empty( $trnsl_labels[$lang][$name] ) ){
            return $trnsl_labels[$lang][$name];
        }

        return $label;
    }

    function translated_cart_item_name($title, $values, $cart_item_key){

        if($values){

            $parent = $values['data']->post->post_parent;
            $tr_product_id = apply_filters( 'translate_object_id', $values['product_id'], 'product', true );
            $trnsl_title = get_the_title($tr_product_id);
            
            if($parent){
                $tr_parent = apply_filters( 'translate_object_id', $parent, 'product', true );
                $trnsl_title = get_the_title( $tr_parent ) . ' &rarr; ' . $trnsl_title;
            }

            if( strstr( $title,'</a>' ) ){
                $trnsl_title = sprintf( '<a href="%s">%s</a>', $values['data']->get_permalink(), $trnsl_title );
            }else{
                $trnsl_title = $trnsl_title. '&nbsp;';
            }

              $title = $trnsl_title;
        }

        return $title;
    }

    function translated_checkout_product_title($title,$product){
        global $sitepress;

        if(isset($product->id)){
            $tr_product_id = apply_filters( 'translate_object_id',$product->id,'product',true,$sitepress->get_current_language());
            $title = get_the_title($tr_product_id);
        }

        return $title;
    }
    
    
    function translate_query_var_for_product($public_query_vars){
        global $wpdb, $sitepress, $sitepress_settings;

        $strings_language = $this->get_wc_context_language();

        if($sitepress->get_current_language() != $strings_language){
            $product_permalink  = $this->product_permalink_slug();

            $translated_slug = $this->get_translated_product_base_by_lang(false,$product_permalink);
            
            if(isset($_GET[$translated_slug])){
                $buff = $_GET[$translated_slug];
                unset($_GET[$translated_slug]);
                $_GET[$product_permalink] = $buff;
            }
            
        }
        
        return $public_query_vars;
    }
    
    function get_translated_product_base_by_lang($language = false, $product_permalink = false){
        if(!$language){
            global $sitepress;
            $language = $sitepress->get_current_language();

        }

        if(!$product_permalink){
            $product_permalink  = $this->product_permalink_slug();
        }

        global $wpdb;

        // Use new API for WPML >= 3.2.3
        if ( apply_filters( 'wpml_slug_translation_available', false) ) {
            $translated_slug = apply_filters( 'wpml_get_translated_slug', $product_permalink, $language );
        } else {
            // Try the old way.
            $translated_slug = $wpdb->get_var($wpdb->prepare("
                    SELECT t.value FROM {$wpdb->prefix}icl_string_translations t
                    JOIN {$wpdb->prefix}icl_strings s ON t.string_id = s.id
                    WHERE s.name=%s AND s.value = %s AND t.language = %s AND t.status = %d",
                'URL slug: ' . $product_permalink, $product_permalink, $language, ICL_STRING_TRANSLATION_COMPLETE ));
        }

        return $translated_slug;
    }

    // Catch the default slugs for translation
    function translate_default_slug($translation, $text, $context, $domain) {
        global $sitepress_settings, $sitepress, $woocommerce_wpml;

        if ($context == 'slug' || $context == 'default-slug') {
            $wc_slug = $woocommerce_wpml->get_woocommerce_product_slug();
            if(is_admin()){
                $admin_language = $sitepress->get_admin_language();
            }
            $current_language = $sitepress->get_current_language();
            $strings_language = false;
            if ( WPML_SUPPORT_STRINGS_IN_DIFF_LANG ) {
                $context_ob = icl_st_get_context( 'WordPress' );
                if($context_ob){
                    $strings_language = $context_ob->language;
                }
            }elseif(isset($sitepress_settings['st'])){
                $strings_language = $sitepress_settings['st']['strings_language'];
            }

            if ($text == $wc_slug && $domain == 'woocommerce' && $strings_language) {
                $sitepress->switch_lang($strings_language);
                $translation = _x($text, 'URL slug', $domain);
                $sitepress->switch_lang($current_language);
                if(is_admin()){
                    $sitepress->set_admin_language($admin_language);
                }
            }else{
               $translation = $text;
            }

            if(!is_admin()){
                $sitepress->switch_lang($current_language);
            }
        }

        return $translation;

    }

    function register_shipping_methods($available_methods){
        foreach($available_methods as $key => $method){
            do_action('wpml_register_single_string', 'woocommerce', $key .'_shipping_method_title', $method->label );
            $method->label = apply_filters( 'wpml_translate_single_string', $method->label, 'woocommerce', $key .'_shipping_method_title');
        }

        return $available_methods;
    }

    function translate_tax_rates($rates){
        if (!empty($rates)) {
            foreach ($rates as &$rate) {
                do_action('wpml_register_single_string', 'woocommerce', 'tax_label_' . esc_url_raw($rate['label']), $rate['label'] );
                $rate['label'] = apply_filters( 'wpml_translate_single_string', $rate['label'], 'woocommerce', 'tax_label_' . esc_url_raw($rate['label']) );
            }
        }

        return $rates;
    }

    function translate_gateway_title($title, $gateway_title) {

        do_action('wpml_register_single_string', 'woocommerce', $gateway_title .'_gateway_title', $title  );
        $title = apply_filters( 'wpml_translate_single_string', $title, 'woocommerce', $gateway_title .'_gateway_title' );

        return $title;
    }

    function translate_gateway_description($description, $gateway_title) {

        do_action('wpml_register_single_string', 'woocommerce', $gateway_title .'_gateway_description', $description  );
        $description =apply_filters( 'wpml_translate_single_string', $description, 'woocommerce', $gateway_title .'_gateway_description' );

        return $description;
    }

    function translate_bacs_instructions(){
        $this->translate_payment_instructions('bacs');
    }

    function translate_cheque_instructions(){
        $this->translate_payment_instructions('cheque');
    }

    function translate_cod_instructions(){
        $this->translate_payment_instructions('cod');
    }

    function translate_payment_instructions($id){

        $gateways = WC()->payment_gateways();
        foreach($gateways->payment_gateways as $key => $gateway){
            if($gateway->id == $id && isset(WC_Payment_Gateways::instance()->payment_gateways[$key]->instructions)){
                do_action('wpml_register_single_string', 'woocommerce', $gateway->id .'_gateway_instructions', $gateway->instructions  );
                WC_Payment_Gateways::instance()->payment_gateways[$key]->instructions = apply_filters( 'wpml_translate_single_string', $gateway->instructions, 'woocommerce', $gateway->id .'_gateway_instructions' );
                break;
            }
        }

    }

    function register_tax_label($label){
        global $sitepress;

        do_action('wpml_register_single_string', 'woocommerce', 'VAT_tax_label', $label );
        $label = apply_filters( 'wpml_translate_single_string', $label, 'woocommerce', 'VAT_tax_label' );

        return $label;
    }

    function show_custom_url_base_language_requirement(){
        $this->string_language_notice();
        ?>
        <script>
            if(jQuery('#woocommerce_permalink_structure').length){
                jQuery('#woocommerce_permalink_structure').parent().append(jQuery('#wpml_wcml_custom_base_req').html());
            }
            if(jQuery('input[name="woocommerce_product_category_slug"]').length){
                jQuery('input[name="woocommerce_product_category_slug"]').parent().append('<br><i><?php _e('Please use a different product category base than "category"', 'wpml-wcml') ?></i>');
            }
        </script>
        <?php

    }

    function show_language_notice_for_wc_settings(){
        $this->string_language_notice();
        ?>
        <script>
            var notice_ids = ['woocommerce_new_order_subject','woocommerce_new_order_heading',
                            'woocommerce_cancelled_order_subject','woocommerce_cancelled_order_heading',
                            'woocommerce_customer_processing_order_subject','woocommerce_customer_processing_order_heading',
                            'woocommerce_customer_completed_order_subject','woocommerce_customer_completed_order_heading',
                            'woocommerce_customer_invoice_subject','woocommerce_customer_invoice_heading',
                            'woocommerce_customer_note_subject','woocommerce_customer_note_heading',
                            'woocommerce_customer_reset_password_subject','woocommerce_customer_reset_password_heading',
                            'woocommerce_customer_new_account_subject','woocommerce_customer_new_account_heading',
                            'woocommerce_bacs_title','woocommerce_bacs_description','woocommerce_bacs_instructions',
                            'woocommerce_cheque_title','woocommerce_cheque_description','woocommerce_cheque_instructions',
                            'woocommerce_cod_title','woocommerce_cod_description','woocommerce_cod_instructions',
                            'woocommerce_paypal_title','woocommerce_paypal_description',
                            'woocommerce_checkout_pay_endpoint',
                            'woocommerce_checkout_order_received_endpoint',
                            'woocommerce_myaccount_add_payment_method_endpoint',
                            'woocommerce_myaccount_view_order_endpoint',
                            'woocommerce_myaccount_edit_account_endpoint',
                            'woocommerce_myaccount_edit_address_endpoint',
                            'woocommerce_myaccount_lost_password_endpoint',
                            'woocommerce_logout_endpoint'
            ];

            for (i = 0; i < notice_ids.length; i++) {

                if( jQuery('#'+notice_ids[i]).length ){
                    jQuery('#'+notice_ids[i]).after(jQuery('#wpml_wcml_custom_base_req').html());
                }

            }

        </script>
    <?php
    }

    function string_language_notice(){
        global $sitepress_settings, $sitepress,$woocommerce_wpml;

        echo '<div id="wpml_wcml_custom_base_req" style="display:none"><div><i>';
        if(  !WPML_SUPPORT_STRINGS_IN_DIFF_LANG && $sitepress_settings['st']['strings_language'] != $sitepress->get_default_language() ){
            $strings_language = $sitepress->get_language_details($sitepress_settings['st']['strings_language']);
            echo sprintf(__('Please enter this text in %s', 'wpml-wcml'), '<strong>' . $strings_language['display_name'] . '</strong>');
            echo '</i>&nbsp;<i class="icon-question-sign wcml_tootlip_icon" data-tip="';
            echo sprintf(__('You have to enter this text in the strings language ( %s ) so you can translate it using the WPML String Translation.', 'wpml-wcml'), $strings_language['display_name'] ).'">';
        }
        echo '</i></div></div>';

        $woocommerce_wpml->load_tooltip_resources();
    }

    function show_attribute_label_language_warning(){
        global $sitepress_settings, $sitepress;

        if(!WPML_SUPPORT_STRINGS_IN_DIFF_LANG && $sitepress_settings['st']['strings_language'] != $sitepress->get_default_language()){
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


    function translate_woocommerce_rate_label($label){

        do_action('wpml_register_single_string', 'woocommerce taxes', $label , $label );
        $label = apply_filters( 'wpml_translate_single_string', $label, 'woocommerce taxes', $label );

        return $label;
    }

    function category_base_in_strings_language($text, $original_value, $context){
        if($context == 'slug' && ($original_value == 'product-category' || $original_value == 'product-tag')){
            $text = $original_value;
        }
        return $text;
    }

    function encode_shop_slug($location, $status){
        if(get_post_type(get_query_var('p')) == 'product'){
            global $sitepress;
            $language = $sitepress->get_language_for_element(get_query_var('p'), 'post_product');
            $base_slug = $this->get_translated_product_base_by_lang($language);

            $location = str_replace($base_slug , urlencode($base_slug),$location);
        }

        return $location;
    }


    function get_missed_product_slag_translations_languages(){
        global $sitepress,$wpdb,$sitepress_settings;

        $slug = $this->product_permalink_slug();
        $default_language = $sitepress->get_default_language();

        if ( apply_filters( 'wpml_slug_translation_available', false) ) {
            // Use new API for WPML >= 3.2.3
            $slug_translation_languages = apply_filters( 'wpml_get_slug_translation_languages', array(), $slug );
        } else {
            $slug_translation_languages = $wpdb->get_col($wpdb->prepare("SELECT tr.language FROM {$wpdb->prefix}icl_strings AS s LEFT JOIN {$wpdb->prefix}icl_string_translations AS tr ON s.id = tr.string_id WHERE s.name = %s AND s.value = %s AND tr.status = %s", 'URL slug: ' . $slug, $slug, ICL_STRING_TRANSLATION_COMPLETE));
        }
        $miss_slug_lang = array();

        if ( WPML_SUPPORT_STRINGS_IN_DIFF_LANG ) {

            $context_ob = icl_st_get_context( 'WordPress' );
            if($context_ob){
                $strings_language = $context_ob->language;
            }else{
                $strings_language = false;
            }
        }else{
            $strings_language = $sitepress_settings['st']['strings_language'];
        }

        foreach( $sitepress->get_active_languages() as $lang_info ){
            if( !in_array( $lang_info['code'], $slug_translation_languages ) && $lang_info['code'] != $strings_language ){
                $miss_slug_lang[] = ucfirst($lang_info['display_name']);
            }
        }

        return $miss_slug_lang;
    }


    function product_permalink_slug(){
        $permalinks         = get_option( 'woocommerce_permalinks' );
        $slug = empty( $permalinks['product_base'] ) ? 'product' : trim($permalinks['product_base'],'/');

        return $slug;
    }

    function get_wc_context_language(){

        if ( WPML_SUPPORT_STRINGS_IN_DIFF_LANG ) {

            $context_ob = icl_st_get_context( 'woocommerce' );
            if($context_ob){
                $context_language = $context_ob->language;
            }else{
                $context_language = false;
            }

            return $context_language;
        }else{
            global $sitepress_settings;

            return $sitepress_settings['st']['strings_language'];
        }

    }

    /*
     * Filter breadcrumbs
     *
     */
    function filter_woocommerce_breadcrumbs( $breadcrumbs, $object ){
        global $sitepress;

        if( $sitepress->get_current_language() != $this->get_wc_context_language() ){

            $permalinks   = get_option( 'woocommerce_permalinks' );
            $shop_page_id = wc_get_page_id( 'shop' );
            $orig_shop_page = get_post( apply_filters( 'translate_object_id', $shop_page_id, 'page', true, $this->get_wc_context_language() ) );

            // If permalinks contain the shop page in the URI prepend the breadcrumb with shop
            if ( $shop_page_id && $orig_shop_page && strstr( $permalinks['product_base'], '/' . $orig_shop_page->post_name ) && get_option( 'page_on_front' ) != $shop_page_id ) {
                $breadcrumbs_buff = array();
                $i =0;
                foreach( $breadcrumbs as $key => $breadcrumb ){
                    $breadcrumbs_buff[ $i ] = $breadcrumb;
                    if( $key === 0 ){
                        $i++;
                        $breadcrumbs_buff[ $i ] = array( get_the_title( $shop_page_id ), get_permalink( $shop_page_id ) );
                    }
                    $i++;
                }
                $breadcrumbs = $breadcrumbs_buff;
            }
        }

        return $breadcrumbs;
    }

    /*
     * Add notice message to users
     */
    function notice_after_woocommerce_product_options_attributes(){
        global $sitepress;

        if( isset( $_GET['post'] ) && $sitepress->get_default_language() != $sitepress->get_current_language() ){
            $original_product_id = apply_filters( 'translate_object_id', $_GET['post'], 'product', true, $sitepress->get_default_language() );

            printf( '<p>'.__('In order to edit custom attributes you need to use the <a href="%s">custom product translation editor</a>', 'wpml-wcml').'</p>', admin_url('admin.php?page=wpml-wcml&tab=products&prid='.$original_product_id ) );
        }
    }

    function translate_attribute_taxonomies_labels( $attribute_taxonomies ){

        if( is_admin() && !wpml_is_ajax() ){

            global $wpdb,$sitepress;

            foreach( $attribute_taxonomies as $key => $attribute_taxonomy ){
                $string = $wpdb->get_var($wpdb->prepare("SELECT st.value FROM {$wpdb->prefix}icl_string_translations AS st JOIN {$wpdb->prefix}icl_strings AS s ON s.id = st.string_id WHERE s.context = %s AND s.name = %s AND st.language = %s", 'WordPress', 'taxonomy singular name: '.$attribute_taxonomy->attribute_name, $sitepress->get_current_language() ) );
                if($string) {
                    $attribute_taxonomies[$key]->attribute_label = $string;
                }
            }

        }


        return $attribute_taxonomies;
    }

}