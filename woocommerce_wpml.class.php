<?php
class woocommerce_wpml {

    var $settings;

    var $currencies;
    var $products;
    var $store;
    var $emails;
    var $terms;
    var $orders;

    var $missing;

    function __construct(){
        add_action('plugins_loaded', array($this, 'init'), 2);
    }

    function init(){
        new WCML_Upgrade;

        $this->settings = $this->get_settings();

        $this->dependencies = new WCML_Dependencies;

        add_action('init', array($this, 'load_css_and_js'));
        add_action('admin_menu', array($this, 'menu'));

        if(!$this->dependencies->check()){
            return false;
        }

        global $sitepress,$pagenow;

        if($this->settings['enable_multi_currency'] == WCML_MULTI_CURRENCIES_INDEPENDENT){
            require_once WCML_PLUGIN_PATH . '/inc/multi-currency-support.class.php';            
            $this->multi_currency_support = new WCML_Multi_Currency_Support;
            require_once WCML_PLUGIN_PATH . '/inc/multi-currency.class.php';
            $this->multi_currency = new WCML_WC_MultiCurrency;            
            require_once WCML_PLUGIN_PATH . '/inc/currency-switcher.class.php';
            $this->currency_switcher = new WCML_CurrencySwitcher;
        }else{
            add_shortcode('currency_switcher', '__return_empty_string');
        }
                
        $this->products         = new WCML_Products;
        $this->store            = new WCML_Store_Pages;
        $this->emails           = new WCML_Emails;
        $this->terms            = new WCML_Terms;
        $this->orders           = new WCML_Orders;
        $this->troubleshooting  = new WCML_Troubleshooting();
        $this->compatibility    = new WCML_Compatibility();
        $this->strings          = new WCML_WC_Strings;
        

        if(isset($_GET['page']) && $_GET['page'] == 'wc-reports'){
            require_once WCML_PLUGIN_PATH . '/inc/reports.class.php';
            $this->reports          = new WCML_Reports;
        }
        
        include WCML_PLUGIN_PATH . '/inc/woocommerce-2.0-backward-compatibility.php';            

        new WCML_Ajax_Setup;

        new WCML_Requests;

        $this->install();

        add_action('init', array($this,'load_locale'));

        register_deactivation_hook(__FILE__, array($this, 'deactivation_actions'));

        if(is_admin()){
            add_action('admin_footer', array($this, 'documentation_links'));
            add_action('admin_notices', array($this, 'admin_notice_after_install'));
        }

        add_filter('woocommerce_get_checkout_payment_url', array($this, 'filter_woocommerce_redirect_location'));
        add_filter('woocommerce_get_cancel_order_url', array($this, 'filter_woocommerce_redirect_location'));
        add_filter('woocommerce_get_return_url', array($this, 'filter_woocommerce_redirect_location'));
        //add_filter('woocommerce_redirect', array($this, 'filter_woocommerce_redirect_location'));

        add_filter('option_woocommerce_permalinks', array($this, 'filter_woocommerce_permalinks_option'));
        add_filter('woocommerce_paypal_args', array($this, 'add_language_to_paypal'));

        //set translate product by default
        $this->translate_product_slug();

        if(is_admin() && ((isset($_GET['page']) && $_GET['page'] == 'wpml-wcml')
            || (($pagenow == 'edit.php' || $pagenow == 'post-new.php') && isset($_GET['post_type']) && ($_GET['post_type'] == 'shop_coupon' || $_GET['post_type'] == 'shop_order'))
            || ($pagenow == 'post.php' && isset($_GET['post']) && (get_post_type($_GET['post']) == 'shop_coupon' || get_post_type($_GET['post']) == 'shop_order')))){
            remove_action( 'wp_before_admin_bar_render', array($sitepress, 'admin_language_switcher') );
        }

        if((($pagenow == 'post.php' && isset($_GET['post']) && get_post_type($_GET['post']) == 'product') || (($pagenow == 'post-new.php' || $pagenow == 'edit.php') && isset($_GET['post_type']) && $_GET['post_type'] == 'product'))
            && !$this->settings['trnsl_interface']  && isset($_GET['lang']) && $_GET['lang'] != $sitepress->get_default_language()){
            add_action('init', array($this, 'load_lock_fields_js'));
            add_action( 'admin_footer', array($this,'hidden_label'));
        }
        
        add_action('wp_ajax_wcml_update_setting_ajx', array($this, 'update_setting_ajx'));

    }

    function translate_product_slug(){
        global $sitepress, $wpdb,$woocommerce, $sitepress_settings;

        if(!defined('WOOCOMMERCE_VERSION') || (!isset($GLOBALS['ICL_Pro_Translation']) || is_null($GLOBALS['ICL_Pro_Translation']))){
            return;
        }
        $permalinks = get_option('woocommerce_permalinks', array('product_base' => ''));
        $slug = get_option('woocommerce_product_slug') != false ? get_option('woocommerce_product_slug') : 'product';

        $string = $wpdb->get_row($wpdb->prepare("SELECT id,status FROM {$wpdb->prefix}icl_strings WHERE name = %s AND value = %s ", 'URL slug: ' . $slug, $slug));
        
        if(!$string){
            icl_register_string('WordPress', 'URL slug: ' . $slug, $slug);
            $string = $wpdb->get_row($wpdb->prepare("SELECT id,status FROM {$wpdb->prefix}icl_strings WHERE name = %s AND value = %s ", 'URL slug: ' . $slug, $slug));
        }

        if(isset($sitepress_settings['posts_slug_translation']['types'])){
            $iclsettings['posts_slug_translation']['types'] = $sitepress_settings['posts_slug_translation']['types'];
        }

        if(!empty($permalinks['product_base']) && isset($sitepress_settings['posts_slug_translation']['types'][$slug])){
            $iclsettings['posts_slug_translation']['types'][$slug] = 0;
            $sitepress->save_settings($iclsettings);
        }

        if( empty($sitepress_settings['theme_localization_type']) || $sitepress_settings['theme_localization_type'] != 1 ){
            $sitepress->save_settings(array('theme_localization_type' => 1));
        }


        if($string->status != ICL_STRING_TRANSLATION_COMPLETE){
            //get translations from .mo files
            $current_language = $sitepress->get_current_language();
            $default_language = $sitepress->get_default_language();
            $active_languages = $sitepress->get_active_languages();
            $string_id = $string->id;
            if(empty($string_id)){
                $string_id = icl_register_string('WordPress', 'URL slug: ' . $slug, $slug);
            }
            foreach($active_languages as $language){
                if($language['code'] != $sitepress_settings['st']['strings_language']){
                    $sitepress->switch_lang($language['code']);
                    $context = 'slug';
                    $domain = 'woocommerce';
                    $woocommerce->load_plugin_textdomain();
                    $string_text = _x( $slug, $context, $domain );
                    unload_textdomain($domain);
                    icl_add_string_translation($string_id,$language['code'],$string_text,ICL_STRING_TRANSLATION_COMPLETE,null);
                    $sitepress->switch_lang($current_language);
                }
            }
            $woocommerce->load_plugin_textdomain();
            $wpdb->update(
                $wpdb->prefix.'icl_strings',
                array(
                    'status' => ICL_STRING_TRANSLATION_COMPLETE
                ),
                array( 'id' => $string_id )
            );
        }

        $iclsettings['posts_slug_translation']['on'] = 1;
        $iclsettings['posts_slug_translation']['types'][$slug] = 1;
        $sitepress->save_settings($iclsettings);
    }

    function get_settings(){

        $defaults = array(
            'file_path_sync'               => 1,
            'is_term_order_synced'         => 0,
            'enable_multi_currency'        => WCML_MULTI_CURRENCIES_DISABLED,
            'dismiss_doc_main'             => 0,
            'trnsl_interface'              => 1,
            'currency_options'             => array()
        );

        if(empty($this->settings)){
            $this->settings = get_option('_wcml_settings');
        }

        foreach($defaults as $key => $value){
            if(!isset($this->settings[$key])){
                $this->settings[$key] = $value;
            }
        }

        return $this->settings;
    }

    function update_settings($settings = null){
        if(!is_null($settings)){
            $this->settings = $settings;
        }
        update_option('_wcml_settings', $this->settings);
    }

    function update_setting_ajx(){
        $data = $_POST;
        $error = '';
        $html = '';
        if($data['nonce'] == wp_create_nonce('woocommerce_multilingual')){ 
            $this->settings[$data['setting']] = $data['value'];
            $this->update_settings();
        }
        echo json_encode(array('html' => $html, 'error'=> $error));
        exit;
    }
    
    function load_locale(){
        load_plugin_textdomain('wpml-wcml', false, WCML_LOCALE_PATH);
    }

    function install(){
        global $wpdb;
        
        if(empty($this->settings['set_up'])){ // from 3.2     
            
            if ($this->settings['is_term_order_synced'] !== 'yes') {
                //global term ordering resync when moving to >= 3.3.x
                add_action('init', array($this->terms, 'sync_term_order_globally'), 20);
            }

            if(!get_option('wcml_custom_attr_translations')){
                add_option('wcml_custom_attr_translations',array());
            }

            if(!isset($this->settings['wc_admin_options_saved'])){
                $this->handle_admin_texts();
                $this->settings['wc_admin_options_saved'] = 1;
            }

            if(!isset($this->settings['trnsl_interface'])){
                $this->settings['trnsl_interface'] = 1;
            }
            
            if(!isset($this->settings['products_sync_date'])){
                $this->settings['products_sync_date'] = 1;
            }
            
            self::set_up_capabilities();
            
            $this->set_language_information();
            
            $this->settings['set_up'] = 1;
            $this->update_settings();
            
        }
        
        require_once WCML_PLUGIN_PATH . '/inc/multi-currency.class.php';
        WCML_WC_MultiCurrency::install();
        
    }
    
    public static function set_up_capabilities(){
        
        $role = get_role( 'administrator' );
        if($role){
            $role->add_cap( 'wpml_manage_woocommerce_multilingual' );
            $role->add_cap( 'wpml_operate_woocommerce_multilingual' );
        }
        
        $role = get_role( 'super_admin' );
        if($role){
            $role->add_cap( 'wpml_manage_woocommerce_multilingual' );
            $role->add_cap( 'wpml_operate_woocommerce_multilingual' );
        }

        $super_admins = get_super_admins();
        foreach ($super_admins as $admin) {
            $user = new WP_User( $admin );
            $user->add_cap( 'wpml_manage_woocommerce_multilingual' );
            $user->add_cap( 'wpml_operate_woocommerce_multilingual' );
        }
        
        $role = get_role( 'shop_manager' );
        if($role){
            $role->add_cap( 'wpml_operate_woocommerce_multilingual' );    
        }
        
    }

    function set_language_information(){
        global $sitepress,$wpdb;

        $def_lang = $sitepress->get_default_language();
        //set language info for products
        $products = $wpdb->get_results("SELECT ID FROM $wpdb->posts WHERE post_type = 'product' AND post_status <> 'auto-draft'");
        foreach($products as $product){
            $exist = $sitepress->get_language_for_element($product->ID,'post_product');
            if(!$exist)
            $sitepress->set_element_language_details($product->ID, 'post_product',false,$def_lang);
        }

        //set language info for taxonomies
        $terms = $wpdb->get_results("SELECT term_taxonomy_id FROM $wpdb->term_taxonomy WHERE taxonomy = 'product_cat'");
        foreach($terms as $term){
            $exist = $sitepress->get_language_for_element($term->term_taxonomy_id, 'tax_product_cat');
            if(!$exist)
            $sitepress->set_element_language_details($term->term_taxonomy_id, 'tax_product_cat',false,$def_lang);
        }
        $terms = $wpdb->get_results("SELECT term_taxonomy_id FROM $wpdb->term_taxonomy WHERE taxonomy = 'product_tag'");
        foreach($terms as $term){
            $exist = $sitepress->get_language_for_element($term->term_taxonomy_id, 'tax_product_tag');
            if(!$exist)
            $sitepress->set_element_language_details($term->term_taxonomy_id, 'tax_product_tag',false,$def_lang);
        }

        $terms = $wpdb->get_results("SELECT term_taxonomy_id FROM $wpdb->term_taxonomy WHERE taxonomy = 'product_shipping_class'");
        foreach($terms as $term){
            $exist = $sitepress->get_language_for_element($term->term_taxonomy_id, 'tax_product_shipping_class');
            if(!$exist)
            $sitepress->set_element_language_details($term->term_taxonomy_id, 'tax_product_shipping_class',false,$def_lang);
        }
    }

    function menu(){
        if($this->dependencies->check()){
            $top_page = apply_filters('icl_menu_main_page', basename(ICL_PLUGIN_PATH) .'/menu/languages.php');

            if(current_user_can('wpml_manage_woocommerce_multilingual')){
                add_submenu_page($top_page, __('WooCommerce Multilingual','wpml-wcml'),
                __('WooCommerce Multilingual', 'wpml-wcml'), 'wpml_manage_woocommerce_multilingual', 'wpml-wcml', array($this, 'menu_content'));

                if(isset($_GET['page']) && $_GET['page'] == basename(WCML_PLUGIN_PATH).'/menu/sub/troubleshooting.php'){
                    add_submenu_page($top_page,
                        __('Troubleshooting','wpml-wcml'), __('Troubleshooting','wpml-wcml'),
                        'wpml_manage_troubleshooting', basename(WCML_PLUGIN_PATH).'/menu/sub/troubleshooting.php');
                }

            }else{
                global $wpdb,$sitepress_settings,$sitepress;
                $user_lang_pairs = get_user_meta(get_current_user_id(), $wpdb->prefix.'language_pairs', true);
                if(isset($sitepress_settings['st']['strings_language']) && !empty($user_lang_pairs[$sitepress->get_default_language()])){
                    add_menu_page(__('WooCommerce Multilingual','wpml-wcml'),
                        __('WooCommerce Multilingual','wpml-wcml'), 'wpml_operate_woocommerce_multilingual',
                        'wpml-wcml', array($this, 'menu_content'), ICL_PLUGIN_URL . '/res/img/icon16.png');
                }
            }
            
        }elseif(current_user_can('wpml_manage_woocommerce_multilingual')){
            if(!defined('ICL_SITEPRESS_VERSION')){
                add_menu_page( __( 'WooCommerce Multilingual', 'wpml-wcml' ), __( 'WooCommerce Multilingual', 'wpml-wcml' ), 
                    'wpml_manage_woocommerce_multilingual', WCML_PLUGIN_PATH . '/menu/plugins.php', null, WCML_PLUGIN_URL . '/assets/images/icon16.png' );
            }else{
                $top_page = apply_filters('icl_menu_main_page', basename(ICL_PLUGIN_PATH) .'/menu/languages.php');
                add_submenu_page($top_page, __('WooCommerce Multilingual','wpml-wcml'),
                    __('WooCommerce Multilingual', 'wpml-wcml'), 'wpml_manage_woocommerce_multilingual', 'wpml-wcml', array($this, 'menu_content'));
            }

        }
    }

    function menu_content(){
        if($this->dependencies->check()){
        include WCML_PLUGIN_PATH . '/menu/management.php';
        }else{
            include WCML_PLUGIN_PATH . '/menu/plugins.php';
        }

    }

    function deactivation_actions(){
        delete_option('wpml_dismiss_doc_main');
    }

    function load_css_and_js() {
        if(isset($_GET['page']) && in_array($_GET['page'], array('wpml-wcml',basename(WCML_PLUGIN_PATH).'/menu/sub/troubleshooting.php',basename(WCML_PLUGIN_PATH).'/menu/plugins.php'))) {


            if ( !wp_style_is( 'toolset-font-awesome', 'registered' ) ) { // check if style are already registered
                wp_register_style('toolset-font-awesome', WCML_PLUGIN_URL . '/assets/css/font-awesome.min.css', null, WCML_VERSION); // register if not
            }
            wp_register_style('wpml-wcml', WCML_PLUGIN_URL . '/assets/css/management.css', array('toolset-font-awesome'), WCML_VERSION);
            wp_register_style('cleditor', WCML_PLUGIN_URL . '/assets/css/jquery.cleditor.css', null, WCML_VERSION);
            wp_register_script('wcml-tm-scripts', WCML_PLUGIN_URL . '/assets/js/scripts.js', array('jquery', 'jquery-ui-resizable'), WCML_VERSION);
            wp_register_script('jquery-cookie', WCML_PLUGIN_URL . '/assets/js/jquery.cookie.js', array('jquery'), WCML_VERSION);
            wp_register_script('cleditor', WCML_PLUGIN_URL . '/assets/js/jquery.cleditor.min.js', array('jquery'), WCML_VERSION);

            wp_enqueue_style('toolset-font-awesome'); // enqueue styles
            wp_enqueue_style('wpml-wcml');
            wp_enqueue_style('cleditor');
            wp_enqueue_style('wp-pointer');

            wp_enqueue_media();
            wp_enqueue_script('wcml-tm-scripts');
            wp_enqueue_script('jquery-cookie');
            wp_enqueue_script('cleditor');
            wp_enqueue_script('suggest');
            wp_enqueue_script('wp-pointer');
            
            
            wp_localize_script('wcml-tm-scripts', 'wcml_settings', 
                array(
                    'nonce'             => wp_create_nonce( 'woocommerce_multilingual' )
                )
            ); 
            
        }
    }

    function load_lock_fields_js(){
        wp_register_script('wcml-lock-script', WCML_PLUGIN_URL . '/assets/js/lock_fields.js', array('jquery'), WCML_VERSION);
        wp_enqueue_script('wcml-lock-script');
    }

    function hidden_label(){
        echo '<img src="'.WCML_PLUGIN_URL.'/assets/images/locked.png" class="wcml_lock_img" alt="'.__('This field is locked for editing because WPML will copy its value from the original language.','wpml-wcml').'" title="'.__('This field is locked for editing because WPML will copy its value from the original language.','wpml-wcml').'" style="display: none;position:relative;left:2px;top:2px;">';

        if($this->settings['enable_multi_currency'] == WCML_MULTI_CURRENCIES_INDEPENDENT){ ?>
            <script type="text/javascript">
            jQuery(document).ready(function($){
                $('input[name^="variable_regular_price"],input[name^="variable_sale_price"]').each(function(){
                    $(this).removeAttr('readonly');
                    $(this).parent().find('img').remove();
                });
            });
            </script>
        <?php }
    }

    function generate_tracking_link($link,$term=false,$content = false, $id = false){
        $params = '?utm_source=wcml-admin&utm_medium=plugin&utm_term=';
        $params .= $term?$term:'WPML';
        $params .= '&utm_content=';
        $params .= $content?$content:'required-plugins';
        $params .= '&utm_campaign=WCML';

        if($id){
            $params .= $id;
        }
        return $link.$params;
    }

    function documentation_links(){
        global $post, $pagenow;

        $get_post_type = get_post_type(@$post->ID);

        if($get_post_type == 'product' && $pagenow == 'edit.php'){
            $prot_link = '<span class="button" style="padding:4px;margin-top:10px;"><img align="baseline" src="' . ICL_PLUGIN_URL .'/res/img/icon16.png" width="16" height="16" style="margin-bottom:-4px" /> <a href="'. $this->generate_tracking_link('http://wpml.org/documentation/related-projects/woocommerce-multilingual/','woocommerce-multilingual','documentation','#4') .'" target="_blank">' .
                    __('How to translate products', 'sitepress') . '<\/a>' . '<\/span>'
        ?>
                <script type="text/javascript">
                    jQuery(".subsubsub").append('<?php echo $prot_link ?>');
                </script>
        <?php
        }

        if(isset($_GET['taxonomy'])){
            $pos = strpos($_GET['taxonomy'], 'pa_');

            if($pos !== false && $pagenow == 'edit-tags.php'){
                $prot_link = '<span class="button" style="padding:4px;margin-top:0px; float: left;"><img align="baseline" src="' . ICL_PLUGIN_URL .'/res/img/icon16.png" width="16" height="16" style="margin-bottom:-4px" /> <a href="'. $this->generate_tracking_link('http://wpml.org/documentation/related-projects/woocommerce-multilingual/','woocommerce-multilingual','documentation','#3') .'" target="_blank" style="text-decoration: none;">' .
                            __('How to translate attributes', 'sitepress') . '<\/a>' . '<\/span><br \/><br \/>'
                ?>
                        <script type="text/javascript">
                            jQuery("table.widefat").before('<?php echo $prot_link ?>');
                        </script>
                <?php
            }
        }

        if(isset($_GET['taxonomy']) && $_GET['taxonomy'] == 'product_cat'){

                $prot_link = '<span class="button" style="padding:4px;margin-top:0px; float: left;"><img align="baseline" src="' . ICL_PLUGIN_URL .'/res/img/icon16.png" width="16" height="16" style="margin-bottom:-4px" /> <a href="'. $this->generate_tracking_link('http://wpml.org/documentation/related-projects/woocommerce-multilingual/','woocommerce-multilingual','documentation','#3') .'" target="_blank" style="text-decoration: none;">' .
                            __('How to translate product categories', 'sitepress') . '<\/a>' . '<\/span><br \/><br \/>'
                ?>
                        <script type="text/javascript">
                            jQuery("table.widefat").before('<?php echo $prot_link ?>');
                        </script>
                <?php
        }
    }

    function admin_notice_after_install(){
        if($this->settings['dismiss_doc_main'] != 'yes'){

            $url = $_SERVER['REQUEST_URI'];
            $pos = strpos($url, '?');

            if($pos !== false){
                $url .= '&wcml_action=dismiss';
            } else {
                $url .= '?wcml_action=dismiss';
            }
    ?>
            <div id="message" class="updated message fade" style="clear:both;margin-top:5px;"><p>
                <?php _e('Would you like to see a quick overview?', 'wpml-wcml'); ?>
                </p>
                <p>
                <a class="button-primary" href="<?php echo $this->generate_tracking_link('http://wpml.org/documentation/related-projects/woocommerce-multilingual/','woocommerce-multilingual','documentation'); ?>" target="_blank"><?php _e('Learn how to turn your e-commerce site multilingual', 'wpml-wcml') ?></a>
                <a class="button-secondary" href="<?php echo $url; ?>"><?php _e('Dismiss', 'wpml-wcml') ?></a>
                </p>
            </div>
    <?php
        }
    }

    function filter_woocommerce_redirect_location($link){
        global $sitepress;
        return html_entity_decode($sitepress->convert_url($link));
    }

    function filter_woocommerce_permalinks_option($value){
        global $wpdb, $sitepress_settings;

        if(isset($value['product_base']) && $value['product_base']){
            icl_register_string('URL slugs - ' . trim($value['product_base'] ,'/'), 'Url slug: ' . trim($value['product_base'] ,'/'), trim($value['product_base'] ,'/'));
            // only register. it'll have to be translated via the string translation
        }

        if(isset($value['category_base']) && $value['category_base']){
            icl_register_string('URL product_cat slugs - ' . trim($value['category_base'] ,'/'), 'Url product_cat slug: ' . trim($value['category_base'] ,'/'), trim($value['category_base'] ,'/'));
        }

        if(isset($value['tag_base']) && $value['tag_base']){
            icl_register_string('URL product_tag slugs - ' . trim($value['tag_base'] ,'/'), 'Url product_tag slug: ' . trim($value['tag_base'] ,'/'), trim($value['tag_base'] ,'/'));
        }

        if(isset($value['attribute_base']) && $value['attribute_base']){
            icl_register_string('URL attribute slugs - ' . trim($value['attribute_base'] ,'/'), 'Url attribute slug: ' . trim($value['attribute_base'] ,'/'), trim($value['attribute_base'] ,'/'));
        }
        
        $value['product_base'] = trim($value['product_base'], '/');

        return $value;

    }

    function add_language_to_paypal($args) {
        global $sitepress;
        $args['lc'] = $sitepress->get_current_language();
        return $args;
    }


    function handle_admin_texts(){
        if(class_exists('woocommerce')){
            //emails texts
            $emails = new WC_Emails();
            foreach($emails->emails as $email){
                $option_name  = $email->plugin_id.$email->id.'_settings';
                if(!get_option($option_name)){
                    add_option($option_name,$email->settings);
                }
            }
        }
    }
}
