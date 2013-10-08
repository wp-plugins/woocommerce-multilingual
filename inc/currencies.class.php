<?php
  
class WCML_Currencies{
    
    private $default_currencies = array(
            'BRL' => 'R&#36;',
            'USD' => '&#36;',
            'EUR' => '&euro;',
            'JPY' => '&yen;',
            'TRY' => 'TL',
            'NOK' => 'kr',
            'ZAR' => 'R',
            'CZK' => '&#75;&#269;',
            'GBP' => '&pound;'
    );
    
    private $currencies = false;
    
    
    function __construct(){
        
        add_action('init', array($this, 'init'));
        
        if(get_option('icl_is_wcml_installed') === 'yes'){
        $this->load_currencies();
        }
        
    }
    
    function init(){
        global $woocommerce_wpml;
        
        add_filter('woocommerce_currency', array($this, 'woocommerce_currency_filter'));
        if($woocommerce_wpml->settings['enable_multi_currency'] == 'yes'){
            add_filter('woocommerce_currency_symbol', array($this, 'woocommerce_currency_symbol_filter'), 2);
            
            if($woocommerce_wpml->settings['currency_converting_option'] == '1'){
                add_filter('raw_woocommerce_price',                 array($this, 'woocommerce_price_filter'));
                add_filter('woocommerce_order_amount_total',        array($this, 'woocommerce_price_filter'));
                add_filter('woocommerce_order_amount_item_total',   array($this, 'woocommerce_price_filter'));
                add_filter('woocommerce_order_amount_item_subtotal',array($this, 'woocommerce_price_filter'));
                add_filter('woocommerce_order_amount_shipping',     array($this, 'woocommerce_price_filter'));
                add_filter('woocommerce_order_amount_total_tax',    array($this, 'woocommerce_price_filter'));
                add_filter('woocommerce_order_amount_cart_discount',array($this, 'woocommerce_price_filter'));
            }            
            
            add_filter('woocommerce_price_filter_min_price', array($this, 'price_filter_min_price'));
            add_filter('woocommerce_price_filter_max_price', array($this, 'price_filter_max_price'));
            
            add_action('wp_ajax_wcml_update_languages_curencies', array($this,'echo_languages_currencies'));
            add_action('wp_ajax_wcml_update_currency', array($this,'wcml_update_currency'));
            add_action('wp_ajax_wcml_delete_currency', array($this,'wcml_delete_currency'));        
            
                        
        }
        
        // set prices to copy/translate depending on settings
        add_action('init', array($this, 'set_price_config'), 16); // After TM parses wpml-config.xml
        
    }
    
    function install(){
        global $wpdb;
        
        $sql = "CREATE TABLE IF NOT EXISTS `". $wpdb->prefix ."icl_currencies` (
              `id` int(11) NOT NULL AUTO_INCREMENT,
              `code` varchar(7) COLLATE utf8_unicode_ci NOT NULL,
              `value` varchar(7) COLLATE utf8_unicode_ci DEFAULT NULL,
              `changed` datetime DEFAULT NULL,
              PRIMARY KEY (`id`)
            )";
        $wpdb->query($sql);
        
        $sql = "CREATE TABLE IF NOT EXISTS `". $wpdb->prefix ."icl_languages_currencies` (
                  `id` int(11) NOT NULL AUTO_INCREMENT,
                  `language_code` varchar(7) COLLATE utf8_unicode_ci NOT NULL,
                  `currency_id` int(11) NOT NULL,
                  PRIMARY KEY (`id`)
                )";
        $install = $wpdb->query($sql);        
        if($install){
            add_option('icl_is_created_languages_currencies', '1');     
        }
        
        return;
        
    }
    
    /**
    * Filters the currency symbol.
    */
    function woocommerce_currency_symbol_filter($currency_symbol){
        global $sitepress, $wpdb, $woocommerce_wpml;

        // Dont process currency symbols in the settings screen
        if(function_exists('get_current_screen')) {
            $screen = get_current_screen();
            if (!empty($screen) && $screen->id == 'woocommerce_page_woocommerce_settings') {
                return $currency_symbol;
            }
        }

        $db_currency = $wpdb->get_row("SELECT c.code FROM ". $wpdb->prefix ."icl_currencies as c LEFT JOIN ". $wpdb->prefix ."icl_languages_currencies as lc ON c.id=lc.currency_id WHERE lc.language_code = '". $sitepress->get_current_language() ."'");

        if($db_currency && $woocommerce_wpml->settings['enable_multi_currency'] == 'yes'){
            $db_currency = $db_currency->code;
            if(in_array($db_currency, array_keys($this->default_currencies))){
                $currency_symbol = $this->default_currencies[$db_currency];
            } else {
                $currency_symbol = $db_currency;
            }
        }

        return $currency_symbol;
    }  
    
    // Set multilingual currency.
    function woocommerce_currency_filter($currency){
        global $wpdb, $sitepress, $woocommerce_wpml;
    
        $db_currency = $wpdb->get_row("SELECT c.code FROM ". $wpdb->prefix ."icl_currencies as c LEFT JOIN ". $wpdb->prefix ."icl_languages_currencies as lc ON c.id=lc.currency_id WHERE lc.language_code = '". $sitepress->get_current_language() ."'");
        
        if(!empty($db_currency) && $woocommerce_wpml->settings['enable_multi_currency'] == 'yes'){
        
            $currency = strtoupper(trim($db_currency->code));
            
        }
    
        return $currency;
    }
    
    /**
     * Filters the product price.
     */
    function woocommerce_price_filter($price){
        global $sitepress, $wpdb, $woocommerce_wpml;

        if ($woocommerce_wpml->settings['enable_multi_currency'] == 'yes') {

            $sql = "SELECT c.value FROM ". $wpdb->prefix ."icl_currencies as c LEFT JOIN ". $wpdb->prefix ."icl_languages_currencies as lc ON c.id=lc.currency_id WHERE lc.language_code = '". $sitepress->get_current_language() ."'";
            $currency = $wpdb->get_results($sql, OBJECT);

            if($currency){
                $exchange_rate = $currency[0]->value;
                $price = round($price * $exchange_rate, (int) get_option( 'woocommerce_price_num_decimals' ));
                $price = apply_filters('woocommerce_multilingual_price', $price);
            }
        }

        return $price;
    }

    /**
     * Filters the minimum price of  price filter widget, when the multi-currency feature is enabled.
     * 
     * @param type $min_price
     * @global type $sitepress
     * @global type $wpdb
     * @return type
     */
    function price_filter_min_price($min_price){
        global $sitepress, $wpdb;

        
        $sql = "SELECT c.value FROM ". $wpdb->prefix ."icl_currencies as c LEFT JOIN ". $wpdb->prefix ."icl_languages_currencies as lc ON c.id=lc.currency_id WHERE lc.language_code = '". $sitepress->get_current_language() ."'";
        $currency = $wpdb->get_results($sql, OBJECT);

        if($currency){
            $exchange_rate = $currency[0]->value;
            $min_price = $min_price / $exchange_rate;
            $min_price = round($min_price,(int) get_option( 'woocommerce_price_num_decimals' ));
        }
        

        return $min_price;
    }

    /**
     * Filters the maximum price of price filter widget, when the multi-currency feature is enabled.
     * 
     * @param type $min_price
     * @global type $sitepress
     * @global type $wpdb
     * @return type
     */
    function price_filter_max_price($max_price){
        global $sitepress, $wpdb;

        
        $sql = "SELECT c.value FROM ". $wpdb->prefix ."icl_currencies as c LEFT JOIN ". $wpdb->prefix ."icl_languages_currencies as lc ON c.id=lc.currency_id WHERE lc.language_code = '". $sitepress->get_current_language() ."'";
        $currency = $wpdb->get_results($sql, OBJECT);

        if($currency){
            $exchange_rate = $currency[0]->value;
            $max_price = $max_price / $exchange_rate;
            $max_price = round($max_price,(int) get_option( 'woocommerce_price_num_decimals' ));
        }
        

        return $max_price;
    }    
    
    private function load_currencies(){
        global $wpdb;
        $this->currencies = $wpdb->get_results("SELECT * FROM " . $wpdb->prefix . "icl_currencies ORDER BY `id` DESC", OBJECT);    
    }
    
    function get_currencies(){
        if(false === $this->currencies){
            $this->load_currencies();
        }        
        return $this->currencies;
    }
    
    function echo_languages_currencies(){
        global $sitepress,$wpdb;
        
        foreach($sitepress->get_active_languages() as $language){
            if($language['code'] != $sitepress->get_default_language()){
            ?>
            <tr>
                <td>
                    <img src="<?php echo ICL_PLUGIN_URL ?>/res/flags/<?php echo $language['code'] ?>.png" width="18" height="12" class="flag_img"/><?php echo $language['english_name'] ?>
                </td>
                <td>
                    <select name="currency_for[<?php echo $language['code']; ?>]">
                        <option value="<?php echo get_woocommerce_currency() ?>"><?php echo get_woocommerce_currency() ?></option>
                        <?php foreach ($this->currencies as $key => $currency): ?>
                        <?php $exist_currency_code = $wpdb->get_var($wpdb->prepare("SELECT currency_id FROM " . $wpdb->prefix . "icl_languages_currencies WHERE language_code = %s", $language['code'])); ?>
                        <option value="<?php echo $currency->id ?>" <?php echo $currency->id == $exist_currency_code ? 'selected="selected"' : ''; ?>><?php echo $currency->code ?></option>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>
            <?php
            }
        }
    }
    
    function wcml_update_currency(){
        if(!wp_verify_nonce($_REQUEST['wcml_nonce'], 'wcml_update_currency')){
            die('Invalid nonce');
        }

        global $wpdb;
        if($_POST['currency_id'] == 0){
            $wpdb->insert($wpdb->prefix .'icl_currencies', array(
                    'code' => $_POST['currency_code'],
                    'value' => (double) $_POST['currency_value'],
                    'changed' => date('Y-m-d H:i:s')
                )
            );

            echo $wpdb->insert_id;
        } else {
            $wpdb->update(
                $wpdb->prefix .'icl_currencies',
                array(
                    'code' => $_POST['currency_code'],
                    'value' => (double) $_POST['currency_value'],
                    'changed' => date('Y-m-d H:i:s')
                ),
                array( 'id' => $_POST['currency_id'] )
            );
        }
        die();
    }

    function wcml_delete_currency(){
        if(!wp_verify_nonce($_REQUEST['wcml_nonce'], 'wcml_delete_currency')){
            die('Invalid nonce');
        }
        global $wpdb;
        $wpdb->query($wpdb->prepare("DELETE FROM ". $wpdb->prefix ."icl_currencies WHERE id = %d", $_POST['currency_id']));
        die();
    }
    
    function set_price_config() {
        global $sitepress, $iclTranslationManagement,$sitepress_settings, $woocommerce_wpml;

        $all_products_taxonomies = get_taxonomies(array('object_type'=>array('product')),'objects');
        foreach($all_products_taxonomies as $tax_key => $tax){
            if($tax_key == 'product_type') continue;
            $sitepress_settings["translation-management"]["taxonomies_readonly_config"][$tax_key] = 1;
            $sitepress_settings["taxonomies_sync_option"][$tax_key] = 1;
        }
        $sitepress->save_settings($sitepress_settings);

        $wpml_settings = $sitepress->get_settings();
        if (!isset($wpml_settings['translation-management'])) {
            return;
        }

        $multi = $woocommerce_wpml->settings['enable_multi_currency'];
        $option = $woocommerce_wpml->settings['currency_converting_option'];
        if ($multi && $option == 2) {
            $mode = 2; // translate
        } else {
            $mode = 1; // copy
        }
        $keys = array(
            '_regular_price', 
            '_sale_price', 
            '_price', 
            '_min_variation_regular_price', 
            '_min_variation_sale_price', 
            '_min_variation_price', 
            '_max_variation_regular_price', 
            '_max_variation_sale_price', 
            '_max_variation_price' 
        );
        $save = false;
        foreach ($keys as $key) {
            $iclTranslationManagement->settings['custom_fields_readonly_config'][] = $key;
            if (!isset($sitepress_settings['translation-management']['custom_fields_translation'][$key]) ||
                $wpml_settings['translation-management']['custom_fields_translation'][$key] != $mode) {
                $wpml_settings['translation-management']['custom_fields_translation'][$key] = $mode;
                $save = true;
            }
        }
        if ($save) {
            $sitepress->save_settings($wpml_settings);
        }
    }    
    
  
    function set_auto_currency_to_all_products_and_variations(){
        global $wpdb,$sitepress;
        $active_languages = $sitepress->get_active_languages();
        $default_language = $sitepress->get_default_language();
        $all_products = $wpdb->get_results($wpdb->prepare("SELECT element_id FROM " . $wpdb->prefix . "icl_translations WHERE language_code = %s AND element_type = 'post_product'", $default_language));

        foreach($active_languages as $language){
            if($default_language != $language['code']){
                foreach($all_products as $product){
                    $product_id = $product->element_id;
                    $trnsl_product = icl_object_id($product_id,'product',false,$language['code']);

                    if(!is_null($trnsl_product)){
                        $variations = $wpdb->get_results($wpdb->prepare("SELECT ID FROM " . $wpdb->posts . " WHERE post_type = 'product_variation' AND post_parent = %d", $product_id));

                        if($variations){
                            foreach($variations as $variation){
                                $variation_id = $variation->ID;
                                $trnsl_variation = icl_object_id($variation_id,'product_variation',false,$language['code']);
                                if(!is_null($trnsl_variation)){
                                    $var_regular_price = get_post_meta($variation_id,'_regular_price',true);
                                    $var_sale_price = get_post_meta($variation_id,'_sale_price',true);
                                    $var_price = get_post_meta($variation_id,'_price',true);
                                    update_post_meta($trnsl_variation,'_regular_price',$var_regular_price);
                                    update_post_meta($trnsl_variation,'_sale_price',$var_sale_price);
                                    update_post_meta($trnsl_variation,'_price',$var_price);
                                }
                            }
                            $price = get_post_meta($product_id,'_price',true);
                            $min_variation_price = get_post_meta($product_id,'_min_variation_price',true);
                            $max_variation_price = get_post_meta($product_id,'_max_variation_price',true);
                            $min_variation_regular_price = get_post_meta($product_id,'_min_variation_regular_price',true);
                            $max_variation_regular_price = get_post_meta($product_id,'_max_variation_regular_price',true);
                            $min_variation_sale_price = get_post_meta($product_id,'_min_variation_sale_price',true);
                            $max_variation_sale_price = get_post_meta($product_id,'_max_variation_sale_price',true);
                            update_post_meta($trnsl_product,'_price',$price);
                            update_post_meta($trnsl_product,'_min_variation_price',$min_variation_price);
                            update_post_meta($trnsl_product,'_max_variation_price',$max_variation_price);
                            update_post_meta($trnsl_product,'_min_variation_regular_price',$min_variation_regular_price);
                            update_post_meta($trnsl_product,'_max_variation_regular_price',$max_variation_regular_price);
                            update_post_meta($trnsl_product,'_min_variation_sale_price',$min_variation_sale_price);
                            update_post_meta($trnsl_product,'_max_variation_sale_price',$max_variation_sale_price);
                        }else{
                            $regular_price = get_post_meta($product_id,'_regular_price',true);
                            $sale_price = get_post_meta($product_id,'_sale_price',true);
                            $price = get_post_meta($product_id,'_price',true);
                            update_post_meta($trnsl_product,'_regular_price',$regular_price);
                            update_post_meta($trnsl_product,'_sale_price',$sale_price);
                            update_post_meta($trnsl_product,'_price',$price);
                        }
                    }
                }
            }
        }
    }
    
  
}

