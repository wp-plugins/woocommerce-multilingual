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

        add_action('init', array($this, 'init'),2);
        add_action('init', array($this, 'load_css_and_js'));
        add_action('widgets_init', array($this, 'register_widget'));

    }

    function init(){
        new WCML_Upgrade;

        $this->settings = $this->get_settings();

        $this->dependencies = new WCML_Dependencies;
        add_action('admin_menu', array($this, 'menu'));

        if(!$this->dependencies->check()){
            return false;
        }

        global $sitepress,$pagenow;

        if($this->settings['enable_multi_currency'] == WCML_MULTI_CURRENCIES_INDEPENDENT
            || ( isset($_GET['page']) && $_GET['page'] == 'wpml-wcml' && !isset($_GET['tab']) )
            || ( isset( $_POST[ 'action' ] ) && in_array( $_POST[ 'action' ], array( 'wcml_new_currency', 'wcml_save_currency', 'wcml_delete_currency', 'wcml_currencies_list', 'wcml_update_currency_lang', 'wcml_update_default_currency') ) )
        ){
            require_once WCML_PLUGIN_PATH . '/inc/multi-currency-support.class.php';
            $this->multi_currency_support = new WCML_Multi_Currency_Support;
            require_once WCML_PLUGIN_PATH . '/inc/multi-currency.class.php';
            $this->multi_currency = new WCML_WC_MultiCurrency;
        }else{
            add_shortcode('currency_switcher', '__return_empty_string');
        }

        $this->endpoints        = new WCML_Endpoints;
        $this->products         = new WCML_Products;
        $this->store            = new WCML_Store_Pages;
        $this->emails           = new WCML_Emails;
        $this->terms            = new WCML_Terms;
        $this->orders           = new WCML_Orders;
        $this->troubleshooting  = new WCML_Troubleshooting();
        $this->compatibility    = new WCML_Compatibility();
        $this->strings          = new WCML_WC_Strings;
        $this->currency_switcher = new WCML_CurrencySwitcher;
        $this->xdomain_data      = new xDomain_Data;



        if(isset($_GET['page']) && $_GET['page'] == 'wc-reports'){
            require_once WCML_PLUGIN_PATH . '/inc/reports.class.php';
            $this->reports          = new WCML_Reports;
        }

        include WCML_PLUGIN_PATH . '/inc/woocommerce-2.0-backward-compatibility.php';
        include WCML_PLUGIN_PATH . '/inc/wc-rest-api-support.php';

        new WCML_Ajax_Setup;

        new WCML_Requests;

        new WCML_WooCommerce_Rest_API_Support;

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

        if(is_admin() &&
            (
                (isset($_GET['page']) && $_GET['page'] == 'wpml-wcml') ||
                (($pagenow == 'edit.php' || $pagenow == 'post-new.php') && isset($_GET['post_type']) && ($_GET['post_type'] == 'shop_coupon' || $_GET['post_type'] == 'shop_order')) ||
                ($pagenow == 'post.php' && isset($_GET['post']) && (get_post_type($_GET['post']) == 'shop_coupon' || get_post_type($_GET['post']) == 'shop_order')) ||
                (isset($_GET['page']) && $_GET['page'] == 'shipping_zones') || ( isset($_GET['page']) && $_GET['page'] == 'product_attributes')
            )
        ){
            remove_action( 'wp_before_admin_bar_render', array($sitepress, 'admin_language_switcher') );
        }

        if( ($pagenow == 'post.php' && isset($_GET['post']) && get_post_type($_GET['post']) == 'product' && !$this->products->is_original_product($_GET['post'])) ||
            ($pagenow == 'post-new.php' && isset($_GET['source_lang']) && isset($_GET['post_type']) && $_GET['post_type'] == 'product')
            && !$this->settings['trnsl_interface']){
            add_action('init', array($this, 'load_lock_fields_js'));
            add_action( 'admin_footer', array($this,'hidden_label'));
        }

        add_action('wp_ajax_wcml_update_setting_ajx', array($this, 'update_setting_ajx'));

        //load WC translations
        add_action( 'icl_update_active_languages', array( $this, 'download_woocommerce_translations_for_active_languages' ) );
        add_filter( 'pre_set_site_transient_update_plugins', array( $this, 'check_for_update' ), 11 );
        add_filter( 'upgrader_pre_download', array( $this, 'version_update' ), 10, 2 );
        add_action( 'admin_notices', array( $this, 'translation_upgrade_notice' ) );
        add_action( 'wp_ajax_hide_wcml_translations_message', array($this, 'hide_wcml_translations_message') );

    }

    function register_widget(){

        $settings = $this->get_settings();
        if($settings['enable_multi_currency'] == WCML_MULTI_CURRENCIES_INDEPENDENT){
            require_once WCML_PLUGIN_PATH . '/inc/currency-switcher-widget.class.php';
            register_widget('WC_Currency_Switcher_Widget');
        }

    }

    function translate_product_slug(){
        global $sitepress, $wpdb,$woocommerce, $sitepress_settings;

        if(!defined('WOOCOMMERCE_VERSION') || (!isset($GLOBALS['ICL_Pro_Translation']) || is_null($GLOBALS['ICL_Pro_Translation']))){
            return;
        }

        $slug = $this->get_woocommerce_product_slug();

        $string = $wpdb->get_row($wpdb->prepare("SELECT id,status FROM {$wpdb->prefix}icl_strings WHERE name = %s AND value = %s ", 'URL slug: ' . $slug, $slug));

        if(!$string){
            do_action('wpml_register_single_string', 'WordPress', 'URL slug: ' . $slug, $slug);
            $string = $wpdb->get_row($wpdb->prepare("SELECT id,status FROM {$wpdb->prefix}icl_strings WHERE name = %s AND value = %s ", 'URL slug: ' . $slug, $slug));
        }

        if(empty($sitepress_settings['posts_slug_translation']['on']) || empty($sitepress_settings['posts_slug_translation']['types']['product'])){
            $iclsettings['posts_slug_translation']['on'] = 1;
            $iclsettings['posts_slug_translation']['types']['product'] = 1;
            $sitepress->save_settings($iclsettings);
        }

    }

    function get_settings(){

        $defaults = array(
            'file_path_sync'               => 1,
            'is_term_order_synced'         => 0,
            'enable_multi_currency'        => WCML_MULTI_CURRENCIES_DISABLED,
            'dismiss_doc_main'             => 0,
            'trnsl_interface'              => 1,
            'currency_options'             => array(),
            'currency_switcher_product_visibility'             => 1
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
        $nonce = filter_input( INPUT_POST, 'nonce', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
        if(!$nonce || !wp_verify_nonce($nonce, 'wcml_settings')){
            die('Invalid nonce');
        }

        $data = $_POST;
        $error = '';
        $html = '';

        $this->settings[$data['setting']] = $data['value'];
        $this->update_settings();

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

            if(!isset($this->settings['products_sync_order'])){
                $this->settings['products_sync_order'] = 1;
            }

            if(!isset($this->settings['display_custom_prices'])){
                $this->settings['display_custom_prices'] = 0;
            }

            self::set_up_capabilities();

            $this->set_language_information();

            $this->settings['set_up'] = 1;
            $this->update_settings();


        }

        if(empty($this->settings['downloaded_translations_for_wc'])){ //from 3.3.3
            $this->download_woocommerce_translations_for_active_languages();
            $this->settings['downloaded_translations_for_wc'] = 1;
            $this->update_settings();
        }
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
            if(!$exist){
            $sitepress->set_element_language_details($product->ID, 'post_product',false,$def_lang);
        }
        }

        //set language info for taxonomies
        $terms = $wpdb->get_results("SELECT term_taxonomy_id FROM $wpdb->term_taxonomy WHERE taxonomy = 'product_cat'");
        foreach($terms as $term){
            $exist = $sitepress->get_language_for_element($term->term_taxonomy_id, 'tax_product_cat');
            if(!$exist){
            $sitepress->set_element_language_details($term->term_taxonomy_id, 'tax_product_cat',false,$def_lang);
        }
        }
        $terms = $wpdb->get_results("SELECT term_taxonomy_id FROM $wpdb->term_taxonomy WHERE taxonomy = 'product_tag'");
        foreach($terms as $term){
            $exist = $sitepress->get_language_for_element($term->term_taxonomy_id, 'tax_product_tag');
            if(!$exist){
            $sitepress->set_element_language_details($term->term_taxonomy_id, 'tax_product_tag',false,$def_lang);
        }
        }

        $terms = $wpdb->get_results("SELECT term_taxonomy_id FROM $wpdb->term_taxonomy WHERE taxonomy = 'product_shipping_class'");
        foreach($terms as $term){
            $exist = $sitepress->get_language_for_element($term->term_taxonomy_id, 'tax_product_shipping_class');
            if(!$exist){
            $sitepress->set_element_language_details($term->term_taxonomy_id, 'tax_product_shipping_class',false,$def_lang);
        }
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
                if( !empty( $user_lang_pairs[$sitepress->get_default_language()] ) ){
                    add_menu_page(__('WooCommerce Multilingual','wpml-wcml'),
                        __('WooCommerce Multilingual','wpml-wcml'), 'translate',
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
        if(isset($_GET['page'])){

            if( in_array($_GET['page'], array('wpml-wcml',basename(WCML_PLUGIN_PATH).'/menu/sub/troubleshooting.php',basename(WCML_PLUGIN_PATH).'/menu/plugins.php'))) {


                if ( !wp_style_is( 'toolset-font-awesome', 'registered' ) ) { // check if style are already registered
                    wp_register_style('toolset-font-awesome', WCML_PLUGIN_URL . '/assets/css/font-awesome.min.css', null, WCML_VERSION); // register if not
                }

                wp_register_style('wpml-wcml', WCML_PLUGIN_URL . '/assets/css/management.css', array('toolset-font-awesome'), WCML_VERSION);
                wp_register_style('cleditor', WCML_PLUGIN_URL . '/assets/css/jquery.cleditor.css', null, WCML_VERSION);
                wp_register_script('wcml-tm-scripts', WCML_PLUGIN_URL . '/assets/js/scripts.js', array('jquery', 'jquery-ui-core', 'jquery-ui-resizable'), WCML_VERSION);
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

                if( $_GET['page'] == 'wpml-wcml' ){
                    //load wp-editor scripts
                    wp_enqueue_script('word-count');
                    wp_enqueue_script('editor');
                    wp_enqueue_script( 'quicktags' );
                    wp_enqueue_script( 'wplink' );
                    wp_enqueue_style( 'buttons' );
                }

                $this->load_tooltip_resources();

            }elseif( $_GET['page'] == WPML_TM_FOLDER.'/menu/main.php' ){
                wp_register_script('wpml_tm', WCML_PLUGIN_URL . '/assets/js/wpml_tm.js', array('jquery'), WCML_VERSION);
                wp_enqueue_script('wpml_tm');
            }
        }
    }

    //load Tooltip js and styles from WC
    function load_tooltip_resources(){
        if( class_exists('woocommerce') ){
            wp_register_script( 'jquery-tiptip', WC()->plugin_url() . '/assets/js/jquery-tiptip/jquery.tipTip.min.js', array( 'jquery' ), WC_VERSION, true );
            wp_register_script( 'wcml-tooltip-init', WCML_PLUGIN_URL . '/assets/js/tooltip_init.js', array('jquery'), WCML_VERSION);
            wp_enqueue_script( 'jquery-tiptip' );
            wp_enqueue_script( 'wcml-tooltip-init' );
            wp_enqueue_style( 'woocommerce_admin_styles', WC()->plugin_url() . '/assets/css/admin.css', array(), WC_VERSION );
            wp_enqueue_style( 'wcml_tooltip_styles', WCML_PLUGIN_URL . '/assets/css/tooltip.css', null, WCML_VERSION);
        }
    }

    function load_lock_fields_js(){
        wp_register_script('wcml-lock-script', WCML_PLUGIN_URL . '/assets/js/lock_fields.js', array('jquery'), WCML_VERSION);
        wp_enqueue_script('wcml-lock-script');

        wp_localize_script( 'wcml-lock-script', 'unlock_fields', array( 'menu_order' => $this->settings['products_sync_order']) );
    }

    function hidden_label(){
        echo '<img src="'.WCML_PLUGIN_URL.'/assets/images/locked.png" class="wcml_lock_img" alt="'.__('This field is locked for editing because WPML will copy its value from the original language.','wpml-wcml').'" title="'.__('This field is locked for editing because WPML will copy its value from the original language.','wpml-wcml').'" style="display: none;position:relative;left:2px;top:2px;">';

        if( isset($_GET['post']) ){
            $original_language = $this->products->get_original_product_language($_GET['post']);
            $original_id = apply_filters( 'translate_object_id',$_GET['post'],'product',true,$original_language);
        }elseif( isset($_GET['trid']) ){
            global $sitepress;
            $original_id = $sitepress->get_original_element_id_by_trid( $_GET['trid'] );
        }

        echo '<h3 class="wcml_prod_hidden_notice">'.sprintf(__("This is a translation of %s. Some of the fields are not editable. It's recommended to use the %s for translating products.",'wpml-wcml'),'<a href="'.get_edit_post_link($original_id).'" >'.get_the_title($original_id).'</a>','<a href="'.admin_url('admin.php?page=wpml-wcml&tab=products&prid='.$original_id).'" >'.__('WooCommerce Multilingual products translator','wpml-wcml').'</a>').'</h3>';
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
                    __('How to translate products', 'sitepress') . '<\/a>' . '<\/span>';
            $quick_edit_notice = '<div id="quick_edit_notice" style="display:none;"><p>'. sprintf(__("Quick edit is disabled for product translations. It\'s recommended to use the %s for editing products translations. %s",'wpml-wcml'), '<a href="'.admin_url('admin.php?page=wpml-wcml&tab=products').'" >'.__('WooCommerce Multilingual products editor','wpml-wcml').'</a>','<a href="" class="quick_product_trnsl_link" >'.__('Edit this product translation','wpml-wcml').'</a>').'</p></div>';
            $quick_edit_notice_prod_link = '<input type="hidden" id="wcml_product_trnsl_link" value="'.admin_url('admin.php?page=wpml-wcml&tab=products&prid=').'">';
        ?>
                <script type="text/javascript">
                    jQuery(".subsubsub").append('<?php echo $prot_link ?>');
                    jQuery(".subsubsub").append('<?php echo $quick_edit_notice ?>');
                    jQuery(".subsubsub").append('<?php echo $quick_edit_notice_prod_link ?>');
                    jQuery(".quick_hide a").on('click',function(){
                        jQuery(".quick_product_trnsl_link").attr('href',jQuery("#wcml_product_trnsl_link").val()+jQuery(this).closest('tr').attr('id').replace(/post-/,''));
                    });
                </script>
        <?php
        }

        if(isset($_GET['taxonomy'])){
            $pos = strpos($_GET['taxonomy'], 'pa_');

            if($pos !== false && $pagenow == 'edit-tags.php'){
                $prot_link = '<span class="button" style="padding:4px;margin-top:0px; float: left;"><img align="baseline" src="' . ICL_PLUGIN_URL .'/res/img/icon16.png" width="16" height="16" style="margin-bottom:-4px" /> <a href="'. $this->generate_tracking_link('http://wpml.org/documentation/related-projects/woocommerce-multilingual/','woocommerce-multilingual','documentation','#3') .'" target="_blank" style="text-decoration: none;">' .
                            __('How to translate attributes', 'sitepress') . '<\/a>' . '<\/span><br \/><br \/>';
                ?>
                        <script type="text/javascript">
                            jQuery("table.widefat").before('<?php echo $prot_link ?>');
                        </script>
                <?php
            }
        }

        if(isset($_GET['taxonomy']) && $_GET['taxonomy'] == 'product_cat'){

                $prot_link = '<span class="button" style="padding:4px;margin-top:0px; float: left;"><img align="baseline" src="' . ICL_PLUGIN_URL .'/res/img/icon16.png" width="16" height="16" style="margin-bottom:-4px" /> <a href="'. $this->generate_tracking_link('http://wpml.org/documentation/related-projects/woocommerce-multilingual/','woocommerce-multilingual','documentation','#3') .'" target="_blank" style="text-decoration: none;">' .
                            __('How to translate product categories', 'sitepress') . '<\/a>' . '<\/span><br \/><br \/>';
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
        global $sitepress_settings;

        if( function_exists('icl_t') ) {

            if (WPML_SUPPORT_STRINGS_IN_DIFF_LANG && isset($value['product_base']) && $value['product_base']) {
                do_action('wpml_register_single_string', 'URL slugs', 'URL slug: ' . trim($value['product_base'], '/'), trim($value['product_base'], '/'));
                // only register. it'll have to be translated via the string translation
            }

            $category_base = !empty($value['category_base']) ? $value['category_base'] : 'product-category';
            do_action('wpml_register_single_string', 'URL product_cat slugs - ' . $category_base, 'Url product_cat slug: ' . $category_base, $category_base);

            $tag_base = !empty($value['tag_base']) ? $value['tag_base'] : 'product-tag';
            do_action('wpml_register_single_string', 'URL product_tag slugs - ' . $tag_base, 'Url product_tag slug: ' . $tag_base, $tag_base);

            if (isset($value['attribute_base']) && $value['attribute_base']) {
                $attr_base = trim($value['attribute_base'], '/');
                do_action('wpml_register_single_string', 'URL attribute slugs - ' . $attr_base, 'Url attribute slug: ' . $attr_base, $attr_base);
            }
        }

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

    /**
     * Automatically download translations for WC ( when user install WCML ( from 3.3.3) / add new language in WPML )
     *
     * @param  string $lang_code Language code
     *
     */
    function download_woocommerce_translations( $lang_code ){
        global $sitepress;

        $locale = $sitepress->get_locale( $lang_code );

        if( $locale != 'en_US' && class_exists( 'WC_Language_Pack_Upgrader' ) ){

            include_once( ABSPATH . 'wp-admin/includes/class-wp-upgrader.php' );
            require_once( ABSPATH . 'wp-admin/includes/file.php' );
            require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
            require_once( ABSPATH . 'wp-admin/includes/template.php' );

            $url = 'update-core.php?action=do-translation-upgrade';
            $nonce = 'upgrade-translations';
            $title = '';
            $context = WP_LANG_DIR;
            $wc_upgrader_class = new WC_Language_Pack_Upgrader();

            $upgrader = new Language_Pack_Upgrader( new Automatic_Upgrader_Skin( compact( 'url', 'nonce', 'title', 'context' ) ) ); // use Language_Pack_Upgrader_Skin instead of Automatic_Upgrader_Skin to display upgrade process

            $upgr_object = array();
            $upgr_object[0] = new stdClass();
            $upgr_object[0]->type = 'plugin';
            $upgr_object[0]->slug = 'woocommerce';
            $upgr_object[0]->language = $locale;
            $upgr_object[0]->version = WC_VERSION;
            $upgr_object[0]->updated = date('Y-m-d H:i:s');
            $upgr_object[0]->package = $this->get_language_pack_uri( $locale );
            $upgr_object[0]->autoupdate = 1;

            $upgrader->bulk_upgrade( $upgr_object );

            $this->save_translation_version( $locale );
        }

    }


    /*
     * Automatically download translations for WC for active languages
     *
     */
    function download_woocommerce_translations_for_active_languages(){
        global $sitepress;

        $active_languages = $sitepress->get_active_languages();

        $current_language = $sitepress->get_current_language();

        foreach( $active_languages as $language ){

            $this->download_woocommerce_translations( $language['code'] );

        }

        $sitepress->switch_lang( $current_language );
    }


    /*
     * Check for WC language updates
     *
     * @param  object $data Transient update data
     *
     * @return object
     */
    function check_for_update( $data ){
        global $sitepress;

        if( class_exists( 'WC_Language_Pack_Upgrader' ) ){

            $wc_upgrader_class = new WC_Language_Pack_Upgrader();

            $active_languages = $sitepress->get_active_languages();
            $current_language = $sitepress->get_current_language();

            foreach( $active_languages as $language ){
                if( $language['code'] == 'en' )
                    continue;

                $locale = $sitepress->get_locale( $language['code'] );

                if ( $this->has_available_update( $locale, $wc_upgrader_class ) && isset( $data->translations ) ) {

                    $data->translations[] = array(
                        'type'       => 'plugin',
                        'slug'       => 'woocommerce',
                        'language'   => $locale,
                        'version'    => WC_VERSION,
                        'updated'    => date( 'Y-m-d H:i:s' ),
                        'package'    => $this->get_language_pack_uri( $locale ),
                        'autoupdate' => 1
                    );

                }

            }

        }

        return $data;
    }


    function get_language_pack_uri( $locale ){
        $repo = 'https://github.com/woothemes/woocommerce-language-packs/raw/v';

        return $repo . WC_VERSION . '/packages/' . $locale . '.zip';

    }

    /*
     * Update the WC language version in database
     *
     *
     * @param  bool   $reply   Whether to bail without returning the package (default: false)
     * @param  string $package Package URL
     *
     * @return bool
     */
    function version_update( $reply, $package ) {

        $notices = maybe_unserialize( get_option( 'wcml_translations_upgrade_notice' ) );

        if( !is_array( $notices ) ){
            return $reply;
        }

        foreach( $notices as $key => $locale){
            if( strstr( $package, 'woocommerce-language-packs') && strstr( $package, $locale) ){

                $this->save_translation_version( $locale, $key );

            }
        }

        return $reply;
    }


    function save_translation_version( $locale, $key = false ){

        $notices = maybe_unserialize( get_option( 'wcml_translations_upgrade_notice' ) );

        // Update the language pack version
        update_option( 'woocommerce_language_pack_version_'.$locale, array( WC_VERSION, $locale ) );

        if( is_array( $notices ) ){

            if( !$key )
                $key = array_search( $locale, $notices );

            // Remove the translation upgrade notice
            unset( $notices[ $key ] );

            update_option( 'wcml_translations_upgrade_notice', $notices );

        }

    }

    /*
     * Check if has available translation update
     *
     * @param string $locale Locale code
     * @param object $wc_upgrader_class WC_Language_Pack_Upgrader class object
     *
     * @return bool
     */
    function has_available_update( $locale, $wc_upgrader_class ) {
        $version = get_option( 'woocommerce_language_pack_version_'.$locale, array( '0', $locale ) );

        $notices = maybe_unserialize( get_option( 'wcml_translations_upgrade_notice' ) );

        if ( 'en_US' !== $locale && ( ! is_array( $version ) || version_compare( $version[0], WC_VERSION, '<' ) || $version[1] !== $locale ) ) {
            if ( $wc_upgrader_class->check_if_language_pack_exists() ) {

                if( !$notices || !in_array( $locale, $notices )){
                    $notices[] = $locale;

                    update_option( 'wcml_translations_upgrade_notice', $notices );
                    update_option( 'hide_wcml_translations_message', 0 );
                }

                return true;
            } else {
                // Updated the woocommerce_language_pack_version to avoid searching translations for this release again
                update_option( 'woocommerce_language_pack_version_'.$locale, array( WC_VERSION, $locale ) );
            }
        }

        return false;
    }


    /*
     * Display Translations upgrade notice message
     */
    function translation_upgrade_notice(){
        $screen = get_current_screen();

        $notices = maybe_unserialize( get_option( 'wcml_translations_upgrade_notice' ) );

        if ( 'update-core' !== $screen->id && !empty ( $notices ) && !get_option( 'hide_wcml_translations_message' ) ) {
            include( 'menu/sub/notice-translation-upgrade.php' );
        }
    }

    /*
     * Hide Translations upgrade notice message ( update option in DB )
     */
    function hide_wcml_translations_message(){
        $nonce = filter_input( INPUT_POST, 'wcml_nonce', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
        if(!$nonce || !wp_verify_nonce($nonce, 'hide_wcml_translations_message' ) ){
            die('Invalid nonce');
        }
        update_option( 'hide_wcml_translations_message', true );

        die();
    }

    function get_woocommerce_product_slug(){

        $woocommerce_permalinks = maybe_unserialize( get_option('woocommerce_permalinks') );

        if( isset( $woocommerce_permalinks['product_base'] ) && !empty( $woocommerce_permalinks['product_base'] ) ){
            return trim( $woocommerce_permalinks['product_base'], '/');
        }elseif(get_option('woocommerce_product_slug') != false ){
            return trim( get_option('woocommerce_product_slug'), '/');
        }else{
            return 'product';
        }

    }

}
