<?php
  
class WCML_Dependencies{
    
      
    private $missing = array();
    private $err_message = '';
            
    function __construct(){}      
      
    function check(){
          
        $allok = true;
        
        if(!defined('ICL_SITEPRESS_VERSION') || ICL_PLUGIN_INACTIVE){
            if(!function_exists('is_multisite') || !is_multisite()) {
                $this->missing['WPML'] = 'http://wpml.org';
                $allok = false;
            }
        } else if(version_compare(ICL_SITEPRESS_VERSION, '2.0.5', '<')){
            add_action('admin_notices', array($this, '_old_wpml_warning'));
            $allok = false;
        }

        if(!class_exists('woocommerce')){
            $this->missing['WooCommerce'] = 'http://www.woothemes.com/woocommerce/';
            $allok = false;
        }

        if(!defined('WPML_TM_VERSION')){
            $this->missing['WPML Translation Management'] = 'http://wpml.org';
            $allok = false;
        }

        if(!defined('WPML_ST_VERSION')){
            $this->missing['WPML String Translation'] = 'http://wpml.org';
            $allok = false;
        }

        if(is_admin() && !defined('WPML_MEDIA_VERSION')){
            $this->missing['WPML Media'] = 'http://wpml.org';
            $allok = false;
        }

        if (!$allok) {
            add_action('admin_notices', array($this, '_missing_plugins_warning'));
            return false;
        }
        
        $this->check_for_incompatible_permalinks();
        return true;
    }
      
    /**
    * Adds admin notice.
    */
    function _old_wpml_warning(){ ?>
        <div class="message error"><p><?php printf(__('WooCommerce Multilingual is enabled but not effective. It is not compatible with  <a href="%s">WPML</a> versions prior 2.0.5.',
                    'wpml-wcml'), 'http://wpml.org/'); ?></p></div>
    <?php }
      
    /**
    * Adds admin notice.
    */
    function _missing_plugins_warning(){
        $missing = '';
        $counter = 0;
        foreach ($this->missing as $title => $url) {
            $counter ++;
            if ($counter == sizeof($this->missing)) {                
                $sep = '';
            } elseif ($counter == sizeof($this->missing) - 1) {              
                $sep = ' ' . __('and', 'wpml-wcml') . ' ';
            } else {                    
                $sep = ', ';
            }
            $missing .= '<a href="' . $url . '">' . $title . '</a>' . $sep;              
        } ?>

        <div class="message error"><p><?php printf(__('WooCommerce Multilingual is enabled but not effective. It requires %s in order to work.', 'wpml-wcml'), $missing); ?></p></div>
        <?php
    }
      
    /**
    * For all the urls to work we need either:
    * 1) the shop page slug must be the same in all languages
    * 2) or the shop prefix disabled in woocommerce settings
    * one of these must be true for product urls to work
    * if none of these are true, display a warning message
    */
    private function check_for_incompatible_permalinks() {        
        global $sitepress, $sitepress_settings, $pagenow;

        if ( version_compare( WOOCOMMERCE_VERSION, "2.0.0" ) >= 0 ) {
            // WooCommerce 2.x specific checks
            $permalinks = get_option('woocommerce_permalinks', array('product_base' => ''));
            if (empty($permalinks['product_base'])) {                
                return;
            }
            
            $message = sprintf('Because this site uses the default permalink structure, you cannot use slug translation for product permalinks.', 'wpml-wcml');
            $message .= '<br /><br />';
            $message .= sprintf('Please choose a different permalink structure or disable slug translation.', 'wpml-wcml');
            $message .= '<br /><br />';            
            $message .= '<a href="' . admin_url('options-permalink.php') . '">' . __('Permalink settings', 'wpml-wcml') . '</a>';
            $message .= ' | ';
            $message .= '<a href="' . admin_url('admin.php?page=' . WPML_TM_FOLDER . '/menu/main.php&sm=mcsetup#icl_custom_posts_sync_options') . '">' . __('Configure products slug translation', 'wpml-wcml') . '</a>';
            
        } else {                                          
            // WooCommerce 1.x specific checks
            if (get_option('woocommerce_prepend_shop_page_to_products', 'yes') != "yes") {                
                return;
            }
            $message = sprintf(__('If you want to translate product slugs, you need to disable the shop prefix for products in <a href="%s">WooCommerce Settings</a>', 'wpml-wcml'), 'admin.php?page=woocommerce_settings&tab=pages');
        }

        // Check if translated shop pages have the same slug (only 1.x)
        $allsame = true;        
        if ( version_compare( WOOCOMMERCE_VERSION, "2.0.0" ) >= 0 ) {
        } else {
            $shop_page_id = get_option('woocommerce_shop_page_id', false);
            if (!empty($shop_page_id)) {              
                $slug = @get_page($shop_page_id)->post_name;
                $languages = $sitepress->get_active_languages();
                if (sizeof($languages) < 2) {                  
                    return;
                }
                foreach ($languages as $language) {                    
                    if ($language['code'] != $sitepress->get_default_language()) {
                        $translated_shop_page_id = icl_object_id($shop_page_id, 'page', false, $language['code']);
                        if (!empty($translated_shop_page_id)) {                            
                            $translated_slug = get_page($translated_shop_page_id)->post_name;
                            if (!empty($translated_slug) && $translated_slug != $slug) {                                
                                $allsame = false;                                
                                break;
                            }
                        }
                    }
                }
            }
        }

        // Check if slug translation is enabled
        $compatible = true;
        $permalink_structure = get_option('permalink_structure');        
        if ( empty($permalink_structure)
            && !empty($sitepress_settings['posts_slug_translation']['on'])
            && !empty($sitepress_settings['posts_slug_translation']['types'])
            && $sitepress_settings['posts_slug_translation']['types']['product']) {
            $compatible = false;
        }
        
        // display messages
        if (!$allsame) {
            $this->err_message = '<div class="message error"><p>'.printf(__('If you want different slugs for shop pages (%s/%s), you need to disable the shop prefix for products in <a href="%s">WooCommerce Settings</a>', 'wpml-wcml'),
                $slug, $translated_slug, "admin.php?page=woocommerce_settings&tab=pages").'</p></div>';
            add_action('admin_notices', array($this,'plugin_notice_message'));
        }

        if (!$compatible && ($pagenow == 'options-permalink.php' || (isset($_GET['page']) && $_GET['page'] == 'wpml-wcml'))) {
            $this->err_message = '<div class="message error"><p>'.$message.'</p></div>';
            add_action('admin_notices', array($this,'plugin_notice_message'));
        }
    }      

    function plugin_notice_message(){
        echo $this->err_message;
    }
}
  