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
        global $sitepress;

        new WCML_Upgrade;

        $this->settings = $this->get_settings();

        $WCML_Dependencies = new WCML_Dependencies;
        if(!$WCML_Dependencies->check()){
            return false;
        }

        $this->currencies = new WCML_Currencies;
        $this->products   = new WCML_Products;
        $this->store      = new WCML_Store_Pages;
        $this->emails     = new WCML_Emails;
        $this->terms      = new WCML_Terms;
        $this->orders     = new WCML_Orders;
        $this->troubleshooting  = new WCML_Troubleshooting();

        new WCML_Ajax_Setup;
        new WCML_WC_Strings;

        new WCML_Requests;

        $this->install();

        add_action('admin_menu', array($this, 'menu'));

        add_action('init', array($this,'load_locale'));

        register_deactivation_hook(__FILE__, array($this, 'deactivation_actions'));

        add_action('init', array($this, 'load_css_and_js'));

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
        
        if(is_admin() && isset($_GET['page']) && $_GET['page'] == 'wpml-wcml'){
            remove_action( 'wp_before_admin_bar_render', array($sitepress, 'admin_language_switcher') );    
        }
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
            return;
        }

        if($sitepress_settings['posts_slug_translation']['on'] == 0 && isset($sitepress_settings['posts_slug_translation']['types'])){
            foreach($sitepress_settings['posts_slug_translation']['types'] as $key=>$type){
                if($key != $slug){
                    $sitepress_settings['posts_slug_translation']['types'][$key] = 0;
                }
            }
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
                if($default_language != $language['code']){
                    $sitepress->switch_lang($language['code']);
                    $text = 'product';
                    $context = 'slug';
                    $domain = 'woocommerce';
                    $woocommerce->load_plugin_textdomain();
                    $string_text = _x( $text, $context, $domain );
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
            'enable_multi_currency'        => 0,
            'currency_converting_option'   => 1,
            'dismiss_doc_main'             => 0,
            'trnsl_interface'               => 1
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

    function load_locale(){
        load_plugin_textdomain('wpml-wcml', false, WCML_LOCALE_PATH);
    }

    function install(){
        global $wpdb;

        if ($this->settings['is_term_order_synced'] !== 'yes') {
            //global term ordering resync when moving to >= 3.3.x
            add_action('init', array($this->terms, 'sync_term_order_globally'), 20);
        }

        if(!get_option('wcml_custom_attr_translations')){
            add_option('wcml_custom_attr_translations',array());
        }

        $version_in_db = get_option('_wcml_version');
        if(!get_option('icl_is_created_languages_currencies') || ($version_in_db && version_compare($version_in_db, WCML_VERSION, '<'))){
            $this->currencies->install();
            update_option('icl_is_wcml_installed', 'yes');
        }

        if(is_null($this->settings['trnsl_interface'])){
            $this->settings['trnsl_interface'] = 1;
            $this->update_settings();
        }
    }

    function menu(){
        $top_page = apply_filters('icl_menu_main_page', basename(ICL_PLUGIN_PATH) .'/menu/languages.php');

        if(current_user_can('manage_options')){
            add_submenu_page($top_page, __('WooCommerce Multilingual','wpml-wcml'),
            __('WooCommerce Multilingual', 'wpml-wcml'), 'manage_options', 'wpml-wcml', array($this, 'menu_content'));


            if(isset($_GET['page']) && $_GET['page'] == basename(WCML_PLUGIN_PATH).'/menu/sub/troubleshooting.php'){
                add_submenu_page($top_page,
                    __('Troubleshooting','wpml-wcml'), __('Troubleshooting','wpml-wcml'),
                    'manage_options', basename(WCML_PLUGIN_PATH).'/menu/sub/troubleshooting.php');
            }

        }else{
            global $wpdb,$sitepress_settings;
            $user_lang_pairs = get_user_meta(get_current_user_id(), $wpdb->prefix.'language_pairs', true);
            if(isset($sitepress_settings['st']['strings_language']) && !empty($user_lang_pairs[$sitepress_settings['st']['strings_language']])){
                add_menu_page(__('WooCommerce Multilingual','wpml-wcml'),
                    __('WooCommerce Multilingual','wpml-wcml'), 'translate',
                    'wpml-wcml', array($this, 'menu_content'), ICL_PLUGIN_URL . '/res/img/icon16.png');
            }
        }
    }

    function menu_content(){
        include WCML_PLUGIN_PATH . '/menu/management.php';
    }

    function deactivation_actions(){
        delete_option('wpml_dismiss_doc_main');
    }

    function load_css_and_js() {
        if(isset($_GET['page']) && in_array($_GET['page'], array('wpml-wcml',basename(WCML_PLUGIN_PATH).'/menu/sub/troubleshooting.php'))) {
            wp_enqueue_style('wpml-wcml', WCML_PLUGIN_URL . '/assets/css/management.css', array(), WCML_VERSION);

            if ( !wp_style_is( 'toolset-font-awesome', 'registered' ) ) { // check if style are already registered
                wp_register_style('toolset-font-awesome', WCML_PLUGIN_URL . '/assets/css/font-awesome.min.css', null, WCML_VERSION); // register if not
            }
            wp_enqueue_style('toolset-font-awesome'); // enqueue styles
            wp_enqueue_style('cleditor', WCML_PLUGIN_URL . '/assets/css/jquery.cleditor.css', null, WCML_VERSION);
            wp_enqueue_media();
            wp_enqueue_script('wcml-tm-scripts', WCML_PLUGIN_URL . '/assets/js/scripts.js', array('jquery'), WCML_VERSION);
            wp_enqueue_script('jquery-cookie', WCML_PLUGIN_URL . '/assets/js/jquery.cookie.js', array('jquery'), WCML_VERSION);
            wp_enqueue_script('cleditor', WCML_PLUGIN_URL . '/assets/js/jquery.cleditor.min.js', array('jquery'), WCML_VERSION);
            wp_enqueue_script('suggest');
            wp_enqueue_style('wp-pointer');
            wp_enqueue_script('wp-pointer');
        }
    }

    function documentation_links(){
        global $post, $pagenow;

        $get_post_type = get_post_type(@$post->ID);

        if($get_post_type == 'product' && $pagenow == 'edit.php'){
            $prot_link = '<span class="button" style="padding:4px;margin-top:10px;"><img align="baseline" src="' . ICL_PLUGIN_URL .'/res/img/icon16.png" width="16" height="16" style="margin-bottom:-4px" /> <a href="http://wpml.org/documentation/related-projects/woocommerce-multilingual/#translating_products" target="_blank">' .
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
                $prot_link = '<span class="button" style="padding:4px;margin-top:0px; float: left;"><img align="baseline" src="' . ICL_PLUGIN_URL .'/res/img/icon16.png" width="16" height="16" style="margin-bottom:-4px" /> <a href="http://wpml.org/documentation/related-projects/woocommerce-multilingual/#translating_attributes" target="_blank" style="text-decoration: none;">' .
                            __('How to translate attributes', 'sitepress') . '<\/a>' . '<\/span><br \/><br \/>'
                ?>
                        <script type="text/javascript">
                            jQuery("table.widefat").before('<?php echo $prot_link ?>');
                        </script>
                <?php
            }
        }

        if(isset($_GET['taxonomy']) && $_GET['taxonomy'] == 'product_cat'){

                $prot_link = '<span class="button" style="padding:4px;margin-top:0px; float: left;"><img align="baseline" src="' . ICL_PLUGIN_URL .'/res/img/icon16.png" width="16" height="16" style="margin-bottom:-4px" /> <a href="http://wpml.org/documentation/related-projects/woocommerce-multilingual/#translating_product_categories" target="_blank" style="text-decoration: none;">' .
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
                <a class="button-primary" href="http://wpml.org/documentation/related-projects/woocommerce-multilingual/" target="_blank"><?php _e('Learn how to turn your e-commerce site multilingual', 'wpml-wcml') ?></a>
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
        
        $value['product_base'] = trim($value['product_base'], '/');
        
        return $value;        
        
    }

    function add_language_to_paypal($args) {
        global $sitepress;
        $args['lc'] = $sitepress->get_current_language();
        return $args;
    }

}
