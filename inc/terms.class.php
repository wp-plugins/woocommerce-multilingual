<?php
  
class WCML_Terms{
    
    const ALL_TAXONOMY_TERMS_TRANSLATED = 0;
    const NEW_TAXONOMY_TERMS = 1;
    const NEW_TAXONOMY_IGNORED = 2;
    
    private $_tmp_locale_val = false;
    
    function __construct(){
        
        add_action('init', array($this, 'init'));
        
    }
    
    function init(){
        global $sitepress;
        
        add_action('updated_woocommerce_term_meta',array($this,'sync_term_order'), 100,4);
        
        add_filter('option_rewrite_rules', array($this, 'rewrite_rules_filter'), 3, 1); // high priority
        
        add_filter('term_link', array($this, 'translate_category_base'), 0, 3); // high priority
        //add_filter('term_link', array($this, 'translate_brand_link'), 10, 3);
        
        add_filter('get_term', array($this, 'clean_term'), 14, 2);
        add_filter('wp_get_object_terms', array($sitepress, 'get_terms_filter'));
        
        add_action('icl_save_term_translation', array($this,'save_wc_term_meta'), 100,4);
        
        add_action('created_term', array($this, 'translated_terms_status_update'), 10,3);
        add_action('edit_term', array($this, 'translated_terms_status_update'), 10,3);
        add_action('wp_ajax_wcml_update_term_translated_warnings', array('WCML_Terms', 'wcml_update_term_translated_warnings'));
        add_action('wp_ajax_wcml_ingore_taxonomy_translation', array('WCML_Terms', 'wcml_ingore_taxonomy_translation'));
        add_action('wp_ajax_wcml_uningore_taxonomy_translation', array('WCML_Terms', 'wcml_uningore_taxonomy_translation'));
        
        add_action('created_term', array('WCML_Terms', 'set_flag_for_variation_on_attribute_update'), 10, 3);
        
        add_action('wpml_taxonomy_translation_bottom', array('WCML_Terms', 'show_variations_sync_button'), 10, 1);
        add_filter('wpml_taxonomy_show_tax_sync_button', array('WCMl_Terms', 'hide_tax_sync_button_for_attributes'));
        
        add_action('wp_ajax_wcml_sync_product_variations', array('WCML_Terms', 'wcml_sync_product_variations'));
        
        if(is_admin()){
            add_action('admin_menu', array($this, 'admin_menu_setup'));    
        }
        
        add_action('delete_term',array($this, 'wcml_delete_term'),10,4);
        add_filter('get_the_terms',array($this,'shipping_terms'),10,3);
        //filter coupons terms in admin
        add_filter('get_terms',array($this,'filter_coupons_terms'),10,3);
        
        add_filter('woocommerce_attribute',array($this, 'hide_language_suffix'));
        
    }
    
    function admin_menu_setup(){
        global $pagenow;
        if($pagenow == 'edit-tags.php' && isset($_GET['action']) && $_GET['action'] == 'edit'){
            add_action('admin_notices', array($this, 'show_term_translation_screen_notices'));    
        }
        
    }
            
    function save_wc_term_meta($original_tax,$result){
        global $wpdb;
        $term_wc_meta = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->woocommerce_termmeta} WHERE woocommerce_term_id=%s", $original_tax->term_id));
        foreach ( $term_wc_meta as $wc_meta )
        {
            $wc_original_metakey = $wc_meta->meta_key;
            $wc_original_metavalue = $wc_meta->meta_value;
            update_woocommerce_term_meta($result['term_id'], $wc_original_metakey, $wc_original_metavalue);
        }
    }
    
    function rewrite_rules_filter($value){
        global $sitepress, $sitepress_settings, $wpdb, $wp_taxonomies,$woocommerce;
        
        $strings_language = $sitepress_settings['st']['strings_language'];
        
        if($sitepress->get_current_language() != $strings_language){
            
            $cache_key = 'wcml_rewrite_filters_translate_taxonomies';
            
            if($val = wp_cache_get($cache_key)){
                
                $value = $val;
                
            }else{
                
                $taxonomies = array('product_cat', 'product_tag');
                
                foreach($taxonomies as $taxonomy ){
                    
                    $taxonomy_obj  = get_taxonomy($taxonomy);
                    $slug = isset($taxonomy_obj->rewrite['slug']) ? trim($taxonomy_obj->rewrite['slug'],'/') : false;

                    if($slug && $sitepress->get_current_language() != $strings_language){
                        
                        $slug_translation = $wpdb->get_var($wpdb->prepare("
                                    SELECT t.value 
                                    FROM {$wpdb->prefix}icl_string_translations t
                                        JOIN {$wpdb->prefix}icl_strings s ON t.string_id = s.id
                                    WHERE t.language = %s AND s.name = %s AND s.value = %s
                                ", $sitepress->get_current_language(), 'URL ' . $taxonomy . ' slug: ' . $slug, $slug));
                        
                        if(!$slug_translation){
                            // handle exception - default woocommerce category and tag bases used
                            // get translation from WooCommerce mo files?
                            unload_textdomain('woocommerce');
                            $woocommerce->load_plugin_textdomain();
                            $slug_translation = _x($slug, 'slug', 'woocommerce');
                            
                            $slug = $taxonomy == 'product_tag' ? 'product-tag' : 'product-category'; // strings language

                        }
                        
                        if($slug_translation){

                            $buff_value = array();                     
                            foreach((array)$value as $k=>$v){            
                                
                                if($slug != $slug_translation){                        
                                    if(preg_match('#^[^/]*/?' . $slug . '/#', $k) && $slug != $slug_translation){
                                        $k = preg_replace('#^([^/]*)(/?)' . $slug . '/#',  '$1$2' . $slug_translation . '/' , $k);    
                                    }
                                }
                                
                                $buff_value[$k] = $v;
                                
                            }
                            
                            $value = $buff_value;
                            unset($buff_value);                     
                            
                        }
           
                    }                
                    
                }
                
                // handle attributes
                $wc_taxonomies = wc_get_attribute_taxonomies();
                $wc_taxonomies_wc_format = array();
                foreach($wc_taxonomies as $k => $v){
                    $wc_taxonomies_wc_format[] = 'pa_' . $v->attribute_name;    
                }
                
                foreach($wc_taxonomies_wc_format as $taxonomy ){
                    $taxonomy_obj  = get_taxonomy($taxonomy);
                    
                    if(isset($taxonomy_obj->rewrite['slug'])){
                        $exp = explode('/', $taxonomy_obj->rewrite['slug']);    
                        $slug = join('/', array_slice($exp, 0, count($exp) - 1));
                    }

                    if($slug && $sitepress->get_current_language() != $strings_language){
                        
                        $slug_translation = $wpdb->get_var($wpdb->prepare("
                                    SELECT t.value 
                                    FROM {$wpdb->prefix}icl_string_translations t
                                        JOIN {$wpdb->prefix}icl_strings s ON t.string_id = s.id
                                    WHERE t.language = %s AND s.name = %s AND s.value = %s
                                ", $sitepress->get_current_language(), 'URL attribute slug: ' . $slug, $slug));
                        
                        if($slug_translation){
                            
                            $buff_value = array();                     
                            foreach((array)$value as $k=>$v){            
                                
                                if($slug != $slug_translation){                        
                                    if(preg_match('#^' . $slug . '/(.*)#', $k) && $slug != $slug_translation){
                                        $k = preg_replace('#^' . $slug . '/(.*)#',   $slug_translation . '/$1' , $k);    
                                    }
                                }
                                
                                $buff_value[$k] = $v;
                                
                            }
                            
                            $value = $buff_value;
                            unset($buff_value);                     
                            
                        }
           
                    }                
                    
                }                
                
                wp_cache_add($cache_key, $value);
                
            }
            
        }         
        
        return $value;
        
        
    }
    
    function _switch_wc_locale(){
        global $sitepress;
        $locale = !empty($this->_tmp_locale_val) ? $this->_tmp_locale_val : $sitepress->get_locale($sitepress->get_current_language());
        return $locale;
    }
    
    function translate_category_base($termlink, $term, $taxonomy){
        global $sitepress_settings, $sitepress, $wp_rewrite, $wpdb, $woocommerce;
        static $no_recursion_flag;
        
        // handles product categories, product tags and attributes
        
        $wc_taxonomies = wc_get_attribute_taxonomies();
        foreach($wc_taxonomies as $k => $v){
            $wc_taxonomies_wc_format[] = 'pa_' . $v->attribute_name;    
        }
        
        if(($taxonomy == 'product_cat' || $taxonomy == 'product_tag' || !empty($wc_taxonomies_wc_format) && in_array($taxonomy, $wc_taxonomies_wc_format)) && !$no_recursion_flag){
            
            $cache_key = 'termlink#' . $taxonomy .'#' . $term->term_id;
            if($link = wp_cache_get($cache_key, 'terms')){
                $termlink = $link;
                
            }else{               
            
                $no_recursion_flag = false;
                    
                $strings_language = $sitepress_settings['st']['strings_language'];
                $term_language = $sitepress->get_element_language_details($term->term_taxonomy_id, 'tax_' . $taxonomy);
                                
                if(!empty($term_language)){
                
                    $permalinks     = get_option( 'woocommerce_permalinks' );
                    $base           = $taxonomy == 'product_tag' ? $permalinks['tag_base'] : ($taxonomy == 'product_cat' ? $permalinks['category_base'] : $permalinks['attribute_base']);
                    
                    if($base === ''){
                        // handle exception - default woocommerce category and tag bases used
                        // get translation from WooCommerce mo files?

                        $base_sl = $taxonomy == 'product_tag' ? 'product-tag' : 'product-category'; // strings language
                        if($term_language->language_code == $strings_language){                            
                            $base            = _x($base_sl, 'slug', 'woocommerce');
                            $base_translated = $base_sl;
                        }else{
                            $base            = _x($base_sl, 'slug', 'woocommerce');
                            
                            $mo_file = $woocommerce->plugin_path() . '/i18n/languages/woocommerce-' . $sitepress->get_locale($term_language->language_code) .'.mo';
                            if(file_exists($mo_file)){
                                $mo = new MO();     
                                $mo->import_from_file( $mo_file );
                                $base_translated = $mo->translate($base_sl, 'slug');
                            }else{
                                $base_translated = $base_sl;
                            }
                            
                        }
                    }else{
                        
                        $string_identifier = $taxonomy == 'product_tag' || $taxonomy == 'product_cat' ? $taxonomy : 'attribute';
                        //
                        if($term_language->language_code != $strings_language){
                            $base_translated = $wpdb->get_var("
                                            SELECT t.value 
                                            FROM {$wpdb->prefix}icl_strings s    
                                            JOIN {$wpdb->prefix}icl_string_translations t ON t.string_id = s.id
                                            WHERE s.value='". esc_sql($base)."' 
                                                AND s.language = '{$strings_language}' 
                                                AND s.name LIKE 'Url {$string_identifier} slug:%' 
                                                AND t.language = '{$term_language->language_code}'
                            ");
                            
                        }else{
                            $base_translated = $base;
                        }
                    }
                    
                    if(!empty($base_translated) && $base_translated != $base){
                        
                        $buff = $wp_rewrite->extra_permastructs[$taxonomy]['struct'];
                        $wp_rewrite->extra_permastructs[$taxonomy]['struct'] = str_replace($base, $base_translated, $wp_rewrite->extra_permastructs[$taxonomy]['struct']);
                        $no_recursion_flag = true;
                        $termlink = get_term_link($term, $taxonomy);
                       
                        $wp_rewrite->extra_permastructs[$taxonomy]['struct'] = $buff;
                       
                   }
                   
                
                }
                
                $no_recursion_flag = false;     
                                                
                wp_cache_add($cache_key, $termlink, 'terms', 0);
            }          
               
        }
        
        return $termlink;
    }
    
    /*
    function translate_brand_link($url, $term, $taxonomy) {
         global $sitepress;
         if ($taxonomy == 'product_brand')
             $url = $sitepress->convert_url($url);
         return $url;
    }
    */
     
    function clean_term($terms) {
        global $sitepress;
        $terms->name = $sitepress->the_category_name_filter($terms->name);
        return $terms;
    }
    
    function show_term_translation_screen_notices(){
        global $sitepress, $wpdb;
        $taxonomies = array_keys(get_taxonomies(array('object_type'=>array('product')),'objects'));
        $taxonomies = $taxonomies + array_keys(get_taxonomies(array('object_type'=>array('product_variations')),'objects'));
        $taxonomies = array_unique($taxonomies);
        $taxonomy = isset($_GET['taxonomy']) ? $_GET['taxonomy'] : false;
        if($taxonomy && in_array($taxonomy, $taxonomies)){
            $taxonomy_obj = get_taxonomy($taxonomy);
            $language = isset($_GET['lang']) ? $_GET['lang'] : false;
            if(empty($language) && isset($_GET['tag_ID'])){
                $tax_id = $wpdb->get_var($wpdb->prepare("SELECT term_taxonomy_id FROM {$wpdb->term_taxonomy} WHERE term_id=%d AND taxonomy=%s", $_GET['tag_ID'], $taxonomy));                
                $language = $sitepress->get_language_for_element($tax_id, 'tax_' . $taxonomy);
            }
            if(empty($language)){
                $language = $sitepress->get_default_language();
            }
            if($language == $sitepress->get_default_language()){

                $message = sprintf(__('To translate %s please use the %s translation%s page, inside the %sWooCommerce Multilingual admin%s.', 'wpml-wcml'),
                    $taxonomy_obj->labels->name, 
                    '<strong><a href="' . admin_url('admin.php?page=wpml-wcml&tab=' . $taxonomy ) . '">' . $taxonomy_obj->labels->singular_name,  '</a></strong>', 
                    '<strong><a href="' . admin_url('admin.php?page=wpml-wcml">'), '</a></strong>');
                
            }else{
                
                $message = sprintf(__('Wait! There is a better way to translate %s.  Please go to the %s translation%s page, inside the %sWooCommerce Multilingual admin%s, and translate from there.', 'wpml-wcml'),
                    $taxonomy_obj->labels->name, 
                    '<strong><a href="' . admin_url('admin.php?page=wpml-wcml&tab=' . $taxonomy ) . '">' . $taxonomy_obj->labels->singular_name , '</a></strong>', 
                    '<strong><a href="' . admin_url('admin.php?page=wpml-wcml">'), '</a></strong>');
                
                
            }
            echo '<div class="updated"><p>' . $message . '</p></div>';
            
        }
        
        
    } 
    
    function sync_term_order_globally() {
        //syncs the term order of any taxonomy in $wpdb->prefix.'woocommerce_attribute_taxonomies'
        //use it when term orderings have become unsynched, e.g. before WCML 3.3.
        global $sitepress, $wpdb, $woocommerce_wpml;

        if(!defined('WOOCOMMERCE_VERSION')){
            return;
        }

        $cur_lang = $sitepress->get_current_language();
        $lang = $sitepress->get_default_language();
        $sitepress->switch_lang($lang);

        $taxes = wc_get_attribute_taxonomies (); //="SELECT * FROM " . $wpdb->prefix . "woocommerce_attribute_taxonomies"

        if ($taxes) foreach ($taxes as $woo_tax) {
            $tax = 'pa_'.$woo_tax->attribute_name;
            $meta_key = 'order_'.$tax;
            //if ($tax != 'pa_frame') continue;
            $terms = get_terms($tax);
            if ($terms)foreach ($terms as $term) {
                $term_order = get_woocommerce_term_meta($term->term_id,$meta_key);
                $trid = $sitepress->get_element_trid($term->term_taxonomy_id,'tax_'.$tax);
                error_log("trid $trid tt_id {$term->term_taxonomy_id}");
                $translations = $sitepress->get_element_translations($trid,'tax_' . $tax);
                if ($translations) foreach ($translations as $trans) {
                    if ($trans->language_code != $lang) {
                        error_log("Updating {$trans->term_id} {$trans->language_code} to $term_order" );
                        update_woocommerce_term_meta( $trans->term_id, $meta_key, $term_order);
                    }
                }
            }
        }
        
        //sync product categories ordering
        $terms = get_terms('product_cat');
        if ($terms) foreach($terms as $term) {
            $term_order = get_woocommerce_term_meta($term->term_id,'order');
            $trid = $sitepress->get_element_trid($term->term_taxonomy_id,'tax_product_cat');
            //error_log("product_cat: trid $trid tt_id {$term->term_taxonomy_id}");
            $translations = $sitepress->get_element_translations($trid,'tax_product_cat');
            if ($translations) foreach ($translations as $trans) {
                if ($trans->language_code != $lang) {
                    error_log("Updating {$trans->term_id} {$trans->language_code} to $term_order" );
                    update_woocommerce_term_meta( $trans->term_id, 'order', $term_order);
                }
            }
        }

        $sitepress->switch_lang($cur_lang);
        
        $woocommerce_wpml->settings['is_term_order_synced'] = 'yes';
        $woocommerce_wpml->update_settings();
        
    }    
    
    function sync_term_order($meta_id, $object_id, $meta_key, $meta_value) {
        global $sitepress,$wpdb,$pagenow;
        
        if (!isset($_POST['thetaxonomy']) || !taxonomy_exists($_POST['thetaxonomy']) || substr($meta_key,0,5) != 'order') 
            return;
        
        $tax = $_POST['thetaxonomy'];
        
        $term_taxonomy_id = $wpdb->get_var($wpdb->prepare("SELECT term_taxonomy_id FROM {$wpdb->term_taxonomy} WHERE term_id=%d AND taxonomy=%s", $object_id, $tax));
        $trid = $sitepress->get_element_trid($term_taxonomy_id, 'tax_' . $tax);
        $translations = $sitepress->get_element_translations($trid,'tax_' . $tax);
        if ($translations) foreach ($translations as $trans) {
            if ($trans->element_id != $term_taxonomy_id) {
                $wpdb->update($wpdb->prefix.'woocommerce_termmeta', 
                    array('meta_value' => $meta_value),
                    array('woocommerce_term_id' => $trans->term_id,'meta_key' => $meta_key));
            }
        }
        
    }
    
    function translated_terms_status_update($term_id, $tt_id, $taxonomy){

        if ( isset( $_POST['product_cat_thumbnail_id'] ) ){
            global $sitepress,$sitepress_settings;

            if($sitepress_settings['sync_taxonomy_parents'] && $sitepress->get_language_for_element($tt_id,'tax_'.$taxonomy) == $sitepress->get_default_language()){
                $trid = $sitepress->get_element_trid($tt_id,'tax_'.$taxonomy);
                $translations = $sitepress->get_element_translations($trid,'tax_'.$taxonomy);
                
                foreach($translations as $translation){
                    if($translation->language_code != $sitepress->get_default_language()){
                        if(isset($_POST['display_type'])){
                        update_woocommerce_term_meta( $translation->term_id, 'display_type', esc_attr( $_POST['display_type'] ) );
                        }
                        update_woocommerce_term_meta( $translation->term_id, 'thumbnail_id', icl_object_id(esc_attr( $_POST['product_cat_thumbnail_id'] ),'attachment',true,$translation->language_code));
                    }
                }
            }
        }

        global $wp_taxonomies;
        if(in_array('product', $wp_taxonomies[$taxonomy]->object_type) || in_array('product_variation', $wp_taxonomies[$taxonomy]->object_type)){
            self::update_terms_translated_status($taxonomy);    
        }

    }
    
    static function wcml_update_term_translated_warnings(){
        global $woocommerce_wpml, $sitepress, $wpdb;
        
        $ret = array();
        if($_POST['wcml_nonce'] == wp_create_nonce('wcml_update_term_translated_warnings_nonce')){
            $taxonomy = $_POST['taxonomy'];
            
            $wcml_settings = $woocommerce_wpml->get_settings();
            
            if($wcml_settings['untranstaled_terms'][$taxonomy]['status'] == self::ALL_TAXONOMY_TERMS_TRANSLATED || 
                $wcml_settings['untranstaled_terms'][$taxonomy]['status'] == self::NEW_TAXONOMY_IGNORED){
                
                $ret['hide'] = 1;
                        
            }else{
                $ret['hide'] = 0;
            }
        }
        
        echo json_encode($ret);
        exit;
        
    }
    
    static function wcml_ingore_taxonomy_translation(){
        global $woocommerce_wpml, $sitepress, $wpdb;
        
        $ret = array();        
        
        if($_POST['wcml_nonce'] == wp_create_nonce('wcml_ingore_taxonomy_translation_nonce')){
        
            $taxonomy = $_POST['taxonomy'];
            
            $wcml_settings = $woocommerce_wpml->get_settings();
            $wcml_settings['untranstaled_terms'][$taxonomy]['status'] = self::NEW_TAXONOMY_IGNORED;
            
            $woocommerce_wpml->update_settings($wcml_settings);               
            
            $ret['html']  = '<i class="icon-ok"></i> ';
            $ret['html'] .= sprintf(__('%s do not require translation.', 'wpml-wcml'), get_taxonomy($taxonomy)->labels->name);
            $ret['html'] .= '<div class="actions">';
            $ret['html'] .= '<a href="#unignore-' . $taxonomy . '">' . __('Change', 'wpml-wcml') . '</a>';
            $ret['html'] .= '</div>';
            
        }
        
        echo json_encode($ret);
        exit;
    }
    
    static function wcml_uningore_taxonomy_translation(){
        global $woocommerce_wpml, $sitepress, $wpdb;
        
        $ret = array();        
        
        if($_POST['wcml_nonce'] == wp_create_nonce('wcml_ingore_taxonomy_translation_nonce')){
        
            $taxonomy = $_POST['taxonomy'];
            
            $wcml_settings = $woocommerce_wpml->get_settings();
            
            if($wcml_settings['untranstaled_terms'][$taxonomy]['count'] > 0){
                $wcml_settings['untranstaled_terms'][$taxonomy]['status'] = self::NEW_TAXONOMY_TERMS;                

                $ret['html']  = '<i class="icon-warning-sign"></i> ';
                $ret['html'] .= sprintf(__('Some %s are missing translations (%d translations missing).', 'wpml-wcml'), get_taxonomy($taxonomy)->labels->name, $wcml_settings['untranstaled_terms'][$taxonomy]['count']);
                $ret['html'] .= '<div class="actions">';
                $ret['html'] .= '<a href="' . admin_url('admin.php?page=wpml-wcml&tab=' . $taxonomy) . '">' . __('Translate now', 'wpml-wcml') . '</a> | ';
                $ret['html'] .= '<a href="#ignore-' . $taxonomy . '">' . __('Change', 'wpml-wcml') . '</a>';
                $ret['html'] .= '</div>';
                
                $ret['warn'] = 1;
                
            }else{
                $wcml_settings['untranstaled_terms'][$taxonomy]['status'] = self::ALL_TAXONOMY_TERMS_TRANSLATED;    
                
                $ret['html']  = '<i class="icon-ok"></i> ';
                $ret['html'] .= sprintf(__('All %s are translated.', 'wpml-wcml'), get_taxonomy($taxonomy)->labels->name);
                
                $ret['warn'] = 0;
            }
            
            $woocommerce_wpml->update_settings($wcml_settings);               
            
            
        }
        
        echo json_encode($ret);
        exit;
        
        
    }    
    
    static function update_terms_translated_status($taxonomy){
        global $woocommerce_wpml, $sitepress, $wpdb;
        
        $wcml_settings = $woocommerce_wpml->get_settings();
        
        $default_language = $sitepress->get_default_language();
        $active_languages = $sitepress->get_active_languages();
        
        $not_translated_count = 0;
        foreach($active_languages as $language){                
            if($language['code'] != $default_language){
                
                $terms = $wpdb->get_results($wpdb->prepare("
                    SELECT t1.element_id AS e1, t2.element_id AS e2 FROM {$wpdb->term_taxonomy} x 
                    JOIN {$wpdb->prefix}icl_translations t1 ON x.term_taxonomy_id = t1.element_id AND t1.element_type = %s AND t1.language_code = %s
                    LEFT JOIN {$wpdb->prefix}icl_translations t2 ON t2.trid = t1.trid AND t2.language_code = %s
                ", 'tax_' . $taxonomy, $default_language, $language['code']));
                foreach($terms as $term){
                    if(empty($term->e2)){
                        $not_translated_count ++;
                    }
                    
                }
            }
        }
        
        $status = $not_translated_count ? self::NEW_TAXONOMY_TERMS : self::ALL_TAXONOMY_TERMS_TRANSLATED;    
        
        if(isset($wcml_settings['untranstaled_terms'][$taxonomy]) && $wcml_settings['untranstaled_terms'][$taxonomy] === self::NEW_TAXONOMY_IGNORED){
            $status = self::NEW_TAXONOMY_IGNORED; 
        }
        
        $wcml_settings['untranstaled_terms'][$taxonomy] = array('count' => $not_translated_count , 'status' => $status);
                
        $woocommerce_wpml->update_settings($wcml_settings);               
        
        return $wcml_settings['untranstaled_terms'][$taxonomy];        
        
    }
    
    static function is_fully_translated($taxonomy){
        global $woocommerce_wpml;
        
        $wcml_settings = $woocommerce_wpml->get_settings();
        
        $return = true;
        
        if(!isset($wcml_settings['untranstaled_terms'][$taxonomy])){
            $wcml_settings['untranstaled_terms'][$taxonomy] = self::update_terms_translated_status($taxonomy);
        }

        if($wcml_settings['untranstaled_terms'][$taxonomy]['status'] == self::NEW_TAXONOMY_TERMS){
            $return = false;
        }
        
        
        return $return;
        
    }
    
    static function get_untranslated_terms_number($taxonomy){
        global $woocommerce_wpml;
        
        $wcml_settings = $woocommerce_wpml->get_settings();
        
        if(!isset($wcml_settings['untranstaled_terms'][$taxonomy])){
            $wcml_settings['untranstaled_terms'][$taxonomy] = self::update_terms_translated_status($taxonomy);
        }
        
        return $wcml_settings['untranstaled_terms'][$taxonomy]['count'];
        
    }
    
    static function set_flag_for_variation_on_attribute_update($term_id, $tt_id, $taxonomy){    
        global $woocommerce_wpml, $sitepress;
        
        $attribute_taxonomies = wc_get_attribute_taxonomies();        
        foreach($attribute_taxonomies as $a){
            $attribute_taxonomies_arr[] = 'pa_' . $a->attribute_name;
        }

        if(isset( $attribute_taxonomies_arr ) && in_array($taxonomy, $attribute_taxonomies_arr)){

				$wcml_settings = $woocommerce_wpml->get_settings();

				// get term language
				$term_language = $sitepress->get_element_language_details($tt_id, 'tax_' . $taxonomy);

				if($term_language->language_code != $sitepress->get_default_language()){
					// get term in the default language
					$term_id = icl_object_id($term_id, $taxonomy, false, $sitepress->get_default_language());

					//does it belong to any posts (variations)
					$objects = get_objects_in_term($term_id, $taxonomy);

					if(!isset($wcml_settings['variations_needed'][$taxonomy])){
						$wcml_settings['variations_needed'][$taxonomy] = 0;
					}
					$wcml_settings['variations_needed'][$taxonomy] += count($objects);

					$woocommerce_wpml->update_settings($wcml_settings);

				}
		}
        
    }
    
    static function show_variations_sync_button($taxonomy){
        global $woocommerce_wpml;
        
        if(is_admin() && isset($_GET['page']) && $_GET['page'] == 'wpml-wcml' && isset($_GET['tab'])){
            $wcml_settings = $woocommerce_wpml->get_settings();
            $attribute_taxonomies = wc_get_attribute_taxonomies();        
            foreach($attribute_taxonomies as $a){
                $attribute_taxonomies_arr[] = 'pa_' . $a->attribute_name;
            }
            
        if(isset( $attribute_taxonomies_arr ) && in_array($taxonomy, $attribute_taxonomies_arr)){
            
            ?>
            
            <form id="icl_tt_sync_variations" method="post">
            <input type="hidden" name="action" value="wcml_sync_product_variations" />        
            <input type="hidden" name="taxonomy" value="<?php echo $taxonomy ?>" />
            <input type="hidden" name="wcml_nonce" value="<?php echo wp_create_nonce('wcml_sync_product_variations') ?>" />
            <input type="hidden" name="last_post_id" value="" />        
            <input type="hidden" name="languages_processed" value="0" />
            
            <p>
                <input class="button-secondary" type="submit" value="<?php esc_attr_e("Synchronize attributes and update product variations", 'wpml-wcml') ?>" />
                <img src="<?php echo ICL_PLUGIN_URL . '/res/img/ajax-loader.gif' ?>" alt="loading" height="16" width="16" class="wpml_tt_spinner" />
            </p>
            <span class="errors icl_error_text"></span>    
            <div class="icl_tt_sycn_preview"></div>
            </form>
            
                   
            <p><?php _e('This will automatically generate variations for translated products corresponding to recently translated attributes.'); ?></p>    
            <?php if(!empty($wcml_settings['variations_needed'][$taxonomy])): ?>
            <p><?php printf(__('Currently, there are %s variations that need to be created.', 'wpml-wcml'), '<strong>' . $wcml_settings['variations_needed'][$taxonomy] . '</strong>') ?></p>
            <?php endif; ?>

            
            <?php 
            
            }
            
        }
        
    }
    
    static function hide_tax_sync_button_for_attributes($value){
        global $woocommerce_wpml;
        
        if(is_admin() && isset($_GET['page']) && $_GET['page'] == 'wpml-wcml' && isset($_GET['tab'])){
        
            $wcml_settings = $woocommerce_wpml->get_settings();
            $attribute_taxonomies = wc_get_attribute_taxonomies();        
            foreach($attribute_taxonomies as $a){
                $attribute_taxonomies_arr[] = 'pa_' . $a->attribute_name;
            }
            
            $taxonomy = isset($_GET['tab']) ? $_GET['tab'] : false;
            
            if(isset($attribute_taxonomies_arr) && in_array($taxonomy, $attribute_taxonomies_arr)){
                $value = false;
            }
            
        }
        
        return $value;
        
    }
    
    static function wcml_sync_product_variations($taxonomy){
        global $woocommerce_wpml, $wpdb, $sitepress;
        
        $VARIATIONS_THRESHOLD = 20;
        
        $wcml_settings = $woocommerce_wpml->get_settings();
        $response = array();
        
        $taxonomy = $_POST['taxonomy'];
        
        $languages_processed = intval($_POST['languages_processed']);

        $condition = $languages_processed?'>=':'>';

        $where = isset($_POST['last_post_id']) && $_POST['last_post_id'] ? ' ID '.$condition.' ' . intval($_POST['last_post_id']) . ' AND ' : '';
        
        $post_ids = $wpdb->get_col($wpdb->prepare("                
                SELECT DISTINCT tr.object_id 
                FROM {$wpdb->term_relationships} tr
                JOIN {$wpdb->term_taxonomy} tx on tr.term_taxonomy_id = tx.term_taxonomy_id
                JOIN {$wpdb->posts} p ON tr.object_id = p.ID
                JOIN {$wpdb->prefix}icl_translations t ON t.element_id = p.ID 
                WHERE {$where} tx.taxonomy = %s AND p.post_type = 'product' AND t.element_type='post_product' AND t.language_code = %s 
                ORDER BY ID ASC
                
        ", $taxonomy, $sitepress->get_default_language()));
        
        if($post_ids){
            
            $variations_processed = 0;
            $posts_processed = 0;
            foreach($post_ids as $post_id){
                $terms = wp_get_post_terms($post_id, $taxonomy);    
                $terms_count = count($terms) . "\n\n";
                
                $trid = $sitepress->get_element_trid($post_id, 'post_product');
                $translations = $sitepress->get_element_translations($trid, 'post_product');
                
                $i = 1;

                foreach($translations as $translation){

                    if($i > $languages_processed && $translation->element_id != $post_id){
                        $woocommerce_wpml->products->sync_product_taxonomies($post_id, $translation->element_id, $translation->language_code);
                        $woocommerce_wpml->products->sync_product_variations($post_id, $translation->element_id, $translation->language_code, false, true);
                        $woocommerce_wpml->products->create_product_translation_package($post_id,$trid, $translation->language_code,ICL_TM_COMPLETE);
                        $variations_processed += $terms_count*2;
                        $response['languages_processed'] = $i;
                        $i++;
                        //check if sum of 2 iterations doesn't exceed $VARIATIONS_THRESHOLD
                        if($variations_processed >= $VARIATIONS_THRESHOLD){                    
                            break;
                        }
                    }else{
                        $i++;
                    }
                }
                $response['last_post_id'] = $post_id;
                if(--$i == count($translations)){
                    $response['languages_processed'] = 0;
                    $languages_processed = 0;
                }else{
                    break;
                }
                
                $posts_processed ++;
                
            }

            $response['go'] = 1;
            
        }else{
            
            $response['go'] = 0;
            
        }
        
        $response['progress']   = $response['go'] ? sprintf(__('%d products left', 'wpml-wcml'), count($post_ids) - $posts_processed) : __('Synchronization complete!', 'wpml-wcml');
        
        if($response['go'] && isset($wcml_settings['variations_needed'][$taxonomy]) && !empty($variations_processed)){
            $wcml_settings['variations_needed'][$taxonomy] = max($wcml_settings['variations_needed'][$taxonomy] - $variations_processed, 0);            
        }else{
            if($response['go'] == 0){
                $wcml_settings['variations_needed'][$taxonomy] = 0;    
            }            
        }
        $woocommerce_wpml->update_settings($wcml_settings);                       
        
        echo json_encode($response);
        exit;
        
        
    }
    
    function shipping_terms($terms, $post_id, $taxonomy){
        if(!is_admin() && get_post_type($post_id) == 'product' && $taxonomy == 'product_shipping_class'){
            global $sitepress;
            remove_filter('get_the_terms',array($this,'shipping_terms'), 10, 3);
            $terms = get_the_terms(icl_object_id($post_id,'product',true,$sitepress->get_default_language()),'product_shipping_class');
            add_filter('get_the_terms',array($this,'shipping_terms'), 10, 3);
            return $terms;
        }

        return $terms;
    }

    function filter_coupons_terms($terms, $taxonomies, $args){
        global $sitepress,$pagenow;

        if(is_admin() && (($pagenow == 'post-new.php' && isset($_GET['post_type']) && $_GET['post_type'] == 'shop_coupon') || ($pagenow == 'post.php' && isset($_GET['post']) && get_post_type($_GET['post']) == 'shop_coupon')) && in_array('product_cat',$taxonomies)){
            remove_filter('get_terms',array($this,'filter_coupons_terms'));
            $current_language = $sitepress->get_current_language();
            $sitepress->switch_lang($sitepress->get_default_language());
            $terms = get_terms( 'product_cat', 'orderby=name&hide_empty=0');
            add_filter('get_terms',array($this,'filter_coupons_terms'),10,3);
            $sitepress->switch_lang($current_language);
        }
        return $terms;
    }

    function wcml_delete_term($term, $tt_id, $taxonomy, $deleted_term){
        global $wp_taxonomies;

        foreach($wp_taxonomies as $key=>$taxonomy_obj){
            if((in_array('product',$taxonomy_obj->object_type) || in_array('product_variation',$taxonomy_obj->object_type) ) && $key==$taxonomy){
        self::update_terms_translated_status($taxonomy);
                break;
            }
        }

    }
    
    function hide_language_suffix($terms_string){
        global $sitepress;
        $terms = explode(', ', $terms_string);
        if($terms){
            foreach($terms as $k => $term){
                $terms[$k] = $sitepress->the_category_name_filter($term);
            }
            $terms_string = implode(', ', $terms);
        }
        return $terms_string;
    }

}
