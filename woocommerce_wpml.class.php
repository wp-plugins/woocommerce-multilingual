<?php
class woocommerce_wpml {

    var $currencies;
    var $missing;

    function __construct(){
        add_action('plugins_loaded', array($this, 'init'), 2);
        $this->currencies = array(
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
    }

    function init(){
        global $sitepress;
		$allok = true;
		$this->missing = array();

		if(!defined('ICL_SITEPRESS_VERSION') || ICL_PLUGIN_INACTIVE){
			if(!function_exists('is_multisite') || !is_multisite()) {
				$this->missing['WPML'] = 'http://wpml.org';
				$allok = false;
			}
		} else if(version_compare(ICL_SITEPRESS_VERSION, '2.0.5', '<')){
			add_action('admin_notices', array($this, 'old_wpml_warning'));
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

		if (!$allok) {
			add_action('admin_notices', array($this, 'missing_plugins_warning'));
			return false;
		}

        if(get_option('icl_is_wcml_installed') !== 'yes'){
		   $this->wcml_install();
        }

        if (get_option('icl_is_wcml_term_order_synched') !== 'yes') { 
        	//global term ordering resync when moving to >= 3.3.x
        	add_action('init',array($this,'sync_term_order_globally'), 20);
        }

        add_action('admin_notices', array($this, 'check_for_incompatible_permalinks'));

        add_filter('woocommerce_get_checkout_url', array($this, 'get_checkout_page_url'));
        add_filter('woocommerce_get_checkout_payment_url', array($this, 'do_redirect'));
        add_filter('woocommerce_get_cancel_order_url', array($this, 'do_redirect'));
        add_filter('woocommerce_get_return_url', array($this, 'do_redirect'));
        add_filter('woocommerce_params', array($this, 'ajax_params'));
        //add_filter('woocommerce_redirect', array($this, 'do_redirect'));
        add_filter('woocommerce_attribute_label', array($this, 'translate_attributes'), 14, 2);
        add_filter('get_term', array($this, 'clean_term'), 14, 2);
        add_filter('wp_get_object_terms', array($sitepress, 'get_terms_filter'));
        add_filter('woocommerce_upsell_crosssell_search_products', array($this, 'woocommerce_upsell_crosssell_search_posts'));
        add_filter('icl_post_alternative_languages', array($this, 'post_alternative_languages'));
        add_filter('woocommerce_gateway_title', array($this, 'gateway_title'), 10);
        add_filter('woocommerce_gateway_description', array($this, 'gateway_description'), 10, 2);
		add_filter('woocommerce_json_search_found_products', array($this, 'search_products'));
		add_filter('woocommerce_currency', array($this, 'set_ml_currency'));
		add_action('admin_print_scripts', array($this,'js_scripts_setup'), 11);

		// cart functions
		add_action('woocommerce_get_cart_item_from_session', array($this, 'translate_cart_contents'), 10, 3);
		add_action('woocommerce_cart_loaded_from_session', array($this, 'translate_cart_subtotal'));
		
		// Slug translation
		add_filter('gettext_with_context', array($this, 'default_slug_translation'), 0, 4);
		
		// Translate shop page ids
		add_filter('option_woocommerce_shop_page_id', array($this, 'translate_pages_in_settings'));
		add_filter('option_woocommerce_terms_page_id', array($this, 'translate_pages_in_settings'));
		add_filter('option_woocommerce_cart_page_id', array($this, 'translate_pages_in_settings'));
		add_filter('option_woocommerce_checkout_page_id', array($this, 'translate_pages_in_settings'));
		add_filter('option_woocommerce_review_order_page_id', array($this, 'translate_pages_in_settings'));
		add_filter('option_woocommerce_pay_page_id', array($this, 'translate_pages_in_settings'));
		add_filter('option_woocommerce_thanks_page_id', array($this, 'translate_pages_in_settings'));
		add_filter('option_woocommerce_lost_password_page_id', array($this, 'translate_pages_in_settings'));
		add_filter('option_woocommerce_myaccount_page_id', array($this, 'translate_pages_in_settings'));
		add_filter('option_woocommerce_edit_address_page_id', array($this, 'translate_pages_in_settings'));
		add_filter('option_woocommerce_view_order_page_id', array($this, 'translate_pages_in_settings'));
		add_filter('option_woocommerce_change_password_page_id', array($this, 'translate_pages_in_settings'));
		add_filter('option_woocommerce_logout_page_id', array($this, 'translate_pages_in_settings'));
		
        if(get_option('icl_enable_multi_currency') == 'yes'){
			if(get_option('currency_converting_option') == '1'){
				add_filter('raw_woocommerce_price', array($this, 'woocommerce_price'));
				add_filter('woocommerce_order_amount_total', array($this, 'woocommerce_price'));
				add_filter('woocommerce_order_amount_item_total', array($this, 'woocommerce_price'));
				add_filter('woocommerce_order_amount_item_subtotal', array($this, 'woocommerce_price'));
				add_filter('woocommerce_order_amount_shipping', array($this, 'woocommerce_price'));
				add_filter('woocommerce_order_amount_total_tax', array($this, 'woocommerce_price'));
				add_filter('woocommerce_order_amount_cart_discount',array($this,'woocommerce_price'));
			}
            add_filter('woocommerce_currency_symbol', array($this, 'woocommerce_currency_symbol'), 2);
        }

		//wrappers for email's body
		add_filter('woocommerce_order_status_completed_notification', array($this, 'email_header')); 
		add_filter('woocommerce_order_status_processing_notification', array($this, 'email_header')); 
		add_filter('woocommerce_new_customer_note_notification', array($this, 'email_header')); 
		add_filter('woocommerce_before_resend_order_emails', array($this, 'email_header')); 
		add_filter('woocommerce_after_resend_order_email', array($this, 'email_footer')); 
        
		add_action('localize_woocommerce_on_ajax', array($this, 'localize_on_ajax'));
		add_action('woocommerce_shipping_update_ajax', array($this, 'shipping_update'));

        add_action('woocommerce_reduce_order_stock', array($this, 'sync_product_stocks'));

        add_action('woocommerce_checkout_update_order_meta', array($this, 'order_language'));

        add_filter('woocommerce_price_filter_min_price', array($this, 'price_filter_min_price'));
        add_filter('woocommerce_price_filter_max_price', array($this, 'price_filter_max_price'));

		// filters to sync variable products
		add_action('save_post', array($this, 'sync_variations'), 11, 2); // After WPML
		add_filter('icl_make_duplicate', array($this, 'sync_variations_for_duplicates'), 11, 4);
		add_action('icl_pro_translation_completed',array($this,'icl_pro_translation_completed'));

        add_action('admin_menu', array($this, 'menu'));
        add_action('init', array($this, 'load_css_and_js'));

        if(is_admin()){
            add_action('admin_init', array($this, 'make_new_attributes_translatable'));
        } else {
            add_filter('pre_get_posts', array($this, 'shop_page_query'), 9);
            add_filter('icl_ls_languages', array($this, 'translate_ls_shop_url'));
			add_filter('parse_request', array($this, 'adjust_shop_page'));
        }

        // Hooks for translating product attribute values
        add_filter('woocommerce_variation_option_name', array($this, 'variation_term_name'));
        add_filter('woocommerce_attribute', array($this, 'attribute_terms'));

        if(isset($_POST['general_options']) && check_admin_referer('general_options', 'general_options_nonce')){
            $enable_multi_currency = (isset($_POST['multi_currency'])) ? trim($_POST['multi_currency']) : null;
			$currency_converting_option = $_POST['currency_converting_option'];
			
			if($currency_converting_option[0] == '1'){
				update_option('currency_converting_option', '1');
			} else if($currency_converting_option[0] == '2'){
				update_option('currency_converting_option', '2');
			}

            if($enable_multi_currency == 'yes'){
                add_option('icl_enable_multi_currency', 'yes');
            } else {
                delete_option('icl_enable_multi_currency');
            }
			
        }

		// set prices to copy/translate depending on settings
		add_action('init', array($this, 'set_price_config'), 16); // After TM parses wpml-config.xml

        if(isset($_POST['add_currency']) && check_admin_referer('add_currency', 'add_currency_nonce')){
            global $wpdb, $pagenow;

            $language_code = (isset($_POST['language'])) ? trim($_POST['language']) : null;
            $currency_code = (isset($_POST['currency_code'])) ? mb_convert_case(trim($_POST['currency_code']), MB_CASE_UPPER, "UTF-8") : null;
            $exchange_rate = (isset($_POST['exchange_rate'])) ? trim($_POST['exchange_rate']) : null;
            $currency_id = (isset($_POST['currency_id'])) ? trim($_POST['currency_id']) : null;
            $date = date('Y-m-d H:i:s');

            if($currency_code == ''){
                wp_die(__('<strong>ERROR</strong>: please fill the currency code field.'));
            }

            if($exchange_rate == ''){
                wp_die(__('<strong>ERROR</strong>: please fill the exchange rate field.'));
            } else if(!is_numeric($exchange_rate)){
                wp_die(__('<strong>ERROR</strong>: please enter the correct exchange rate.'));
            }

            $result = $wpdb->get_var("SELECT COUNT(*) FROM ". $wpdb->prefix ."icl_currencies WHERE language_code = '$language_code'");

            if($result && !$currency_id){
                add_action('admin_notices', array($this, 'currency_exists_error'));
            } else {
                // Add
                if(!$currency_id){
                    $wpdb->insert($wpdb->prefix .'icl_currencies', array( 
                        'language_code' => $language_code, 
                        'code' => $currency_code,
                        'value' => (double) $exchange_rate,
                        'changed' => $date
                        )
                    );

                // Update
                } else {
                    $wpdb->update( 
                        $wpdb->prefix .'icl_currencies', 
                            array( 
                                'code' => $currency_code,
                                'value' => (double) $exchange_rate,
                                'changed' => $date
                            ), 
                            array( 'id' => $currency_id ) 
                    );

                    wp_safe_redirect(admin_url('admin.php?page=wpml-wcml'));
                }
            }
        }

        if(isset($_GET['page']) && $_GET['page'] == 'wpml-wcml' && isset($_GET['delete']) && $_GET['delete'] == $_GET['delete']){
            global $wpdb;

            $remove_id = $_GET['delete'];

            $delete = $wpdb->query("DELETE FROM ". $wpdb->prefix ."icl_currencies WHERE id = '$remove_id'");

            if(!$delete){
                wp_die(__('<strong>ERROR</strong>: currency can not be deleted. Please try again.'));
            }

            wp_safe_redirect(admin_url('admin.php?page=wpml-wcml'));
        }	

		add_action('admin_footer', array($this, 'documentation_links'));
		add_action('admin_notices', array($this, 'admin_notice_after_install'));

		if(isset($_GET['wcml_action']) && $_GET['wcml_action'] = 'dismiss'){
			update_option('wpml_dismiss_doc_main', 'yes');
		}
		
		if (defined('DOING_AJAX') && DOING_AJAX){
			do_action('localize_woocommerce_on_ajax');
		}

		register_deactivation_hook(__FILE__, array($this, 'wcml_deactivate'));
		
		add_filter('woocommerce_available_shipping_methods', array($this, 'register_shipping_methods'));
		add_filter('woocommerce_countries_tax_or_vat', array($this, 'register_tax_label'));
		add_action('option_woocommerce_tax_rates', array($this, 'tax_rates'));
		add_action('updated_post_meta', array($this,'update_post_meta'), 100, 4);
		add_action('added_post_meta', array($this,'update_post_meta'), 100, 4); 
		add_action('updated_woocommerce_term_meta',array($this,'sync_term_order'), 100, 4);
		add_filter('term_link', array($this, 'translate_brand_link'), 10, 3);
	}

	function translate_brand_link($url, $term, $taxonomy) {
		global $sitepress;
		if ($taxonomy == 'product_brand')
			$url = $sitepress->convert_url($url);
		return $url;
	}

	function set_price_config() {
		global $sitepress, $iclTranslationManagement;

		$wpml_settings = $sitepress->get_settings();
		if (!isset($wpml_settings['translation-management'])) {
			return;
		}

		$multi = get_option('icl_enable_multi_currency', false);
		$option = get_option('currency_converting_option', 1);
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
	
	// Catch the default slugs for translation
	function default_slug_translation($translation, $text, $context, $domain) {
		if ($context == 'slug') {
			// taxonomy slug translation is not ready yet, return no translation
			if ($text == 'product-category' || $text == 'product-tag') {
				return $text;
			}
			// re-request translation through URL slug context to trigger slug translation
			return _x($text, 'URL slug', $domain);
		}
		return $translation;
	}
	
	function clean_term($terms) {
		global $sitepress;
		$terms->name = $sitepress->the_category_name_filter($terms->name);
		return $terms;
	}

	function register_shipping_methods($available_methods){
		foreach($available_methods as $method){
			$method->label = icl_translate('woocommerce', $method->label .'_shipping_method_title', $method->label);
		}

		return $available_methods;
	}

	function tax_rates($rates){
		if (!empty($rates)) {
			foreach ($rates as &$rate) {
				$rate['label'] = icl_translate('woocommerce', 'tax_label_' . esc_url_raw($rate['label']), $rate['label']);
			}
		}

		return $rates; 
	}

	function register_tax_label($label){
		global $sitepress;
		
		if(function_exists('icl_translate')){
			$label = icl_translate('woocommerce', 'VAT_tax_label', $label);
		}
		
		return $label;
	}

	function translate_pages_in_settings($id) {
		return icl_object_id($id, 'page', true);
	}
	
	/**
	 * Translate shop url
	 */
	function translate_ls_shop_url($languages) {
		global $sitepress;
		$shop_id = get_option('woocommerce_shop_page_id');
		$front_id = icl_object_id(get_option('page_on_front'), 'page');
		foreach ($languages as &$language) {
			// shop page
			if (is_post_type_archive('product')) {
				if ($front_id == $shop_id) {
					$url = $sitepress->language_url($language['language_code']);
				} else {
					$url = get_permalink(icl_object_id($shop_id, 'page', true, $language['language_code']));
				}
				$language['url'] = $url;
			}
			// brand page
			if (is_tax('product_brand')) {
				$sitepress->switch_lang($language['language_code']);
				$language['url'] = get_term_link(get_queried_object_id(), 'product_brand');
			}
		}
		$sitepress->switch_lang();
		return $languages;
	}

	/**
	 * Translate WooCommerce emails.
	 *
	 * @global type $sitepress
	 * @global type $order_id
	 * @return type
	 */
	function email_header($order) {
		global $sitepress;
		
		if (is_array($order)) {
			$order = $order['order_id'];
		} elseif (is_object($order)) {
			$order = $order->id;
		}

		$lang = get_post_meta($order, 'wpml_language', TRUE);
		if(!empty($lang)){
			$sitepress->switch_lang($lang, true);
		}
	}

	/**
	 * After email translation switch language to default.
	 *
	 * @global type $sitepress
	 * @return type
	 */
	function email_footer() {
		global $sitepress;

		$sitepress->switch_lang(ICL_LANGUAGE_CODE, true);
	}

	/**
	 * Adds admin notice.
	 */
	function missing_plugins_warning(){
		$missing = '';
		$counter = 0;
		foreach ($this->missing as $title => $url) {
			$counter ++;
			if ($counter == sizeof($this->missing)) {
				$sep = '';
			} elseif ($counter == sizeof($this->missing) - 1) {
				$sep = ' ' . __('and', 'plugin woocommerce') . ' ';
			} else {
				$sep = ', ';
			}
			$missing .= '<a href="' . $url . '">' . $title . '</a>' . $sep;
		}
	?>
		<div class="message error"><p><?php printf(__('WooCommerce Multilingual is enabled but not effective. It requires %s in order to work.', 'plugin woocommerce'), $missing); ?></p></div>
	<?php
	}

	/**
	 * Adds admin notice.
	 */
	function old_wpml_warning(){
	?>
		<div class="message error"><p><?php printf(__('WooCommerce Multilingual is enabled but not effective. It is not compatible with  <a href="%s">WPML</a> versions prior 2.0.5.', 'plugin woocommerce'), 
		'http://wpml.org/'); ?></p></div>
	<?php
	}

    /**
     * Install the plugin.
     */
    function wcml_install(){
        global $wpdb;

        add_option('icl_is_wcml_installed', 'yes'); 
		add_option('currency_converting_option', '1');

        $sql = "CREATE TABLE IF NOT EXISTS `". $wpdb->prefix ."icl_currencies` (
          `id` int(11) NOT NULL AUTO_INCREMENT,
          `language_code` varchar(7) COLLATE utf8_unicode_ci NOT NULL,
          `code` varchar(7) COLLATE utf8_unicode_ci NOT NULL,
          `value` varchar(7) COLLATE utf8_unicode_ci DEFAULT NULL,
          `changed` datetime DEFAULT NULL,
          PRIMARY KEY (`id`)
        )";

        $install = $wpdb->query($sql);
    }
    
    /**
     * For all the urls to work we need either:
     * 1) the shop page slug must be the same in all languages
     * 2) or the shop prefix disabled in woocommerce settings
     * one of these must be true for product urls to work
     * if none of these are true, display a warning message
     */
	function check_for_incompatible_permalinks() {
		global $sitepress, $sitepress_settings;

		if ( version_compare( WOOCOMMERCE_VERSION, "2.0.0" ) >= 0 ) {
			// WooCommerce 2.x specific checks
			$permalinks = get_option('woocommerce_permalinks', array('product_base' => ''));
			if (empty($permalinks['product_base'])) {
				return;
			}
			$message = sprintf(__('If you want to translate product slugs, you need to keep the default permalink structure for products in <a href="%s">Permalink Settings</a>', 'plugin woocommerce'), 'options-permalink.php');
		} else {
			// WooCommerce 1.x specific checks
			if (get_option('woocommerce_prepend_shop_page_to_products', 'yes') != "yes") {
				return;
			}
			$message = sprintf(__('If you want to translate product slugs, you need to disable the shop prefix for products in <a href="%s">WooCommerce Settings</a>', 'plugin woocommerce'), 'admin.php?page=woocommerce_settings&tab=pages');
		}

		// Check if translated shop pages have the same slug (only 1.x)
		$allsame = true;
		if ( version_compare( WOOCOMMERCE_VERSION, "2.0.0" ) >= 0 ) {
		} else {
			$shop_page_id = get_option('woocommerce_shop_page_id', false);
			if (!empty($shop_page_id)) {
				$slug = @get_page($shop_page_id)->post_name;
				$languages = icl_get_languages('skip_missing=0');
				if (sizeof($languages) < 2) {
					return;
				}
				foreach ($languages as $language) {
					if ($language['language_code'] != $sitepress->get_default_language()) {
						$translated_shop_page_id = icl_object_id($shop_page_id, 'page', false, $language['language_code']);
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
		if (!empty($sitepress_settings['posts_slug_translation']['on'])
			&& !empty($sitepress_settings['posts_slug_translation']['types'])
			&& $sitepress_settings['posts_slug_translation']['types']['product']) {
			$compatible = false;
		}

		// display messages
		if (!$allsame) {
	?>
		<div class="message error"><p><?php printf(__('If you want different slugs for shop pages (%s/%s), you need to disable the shop prefix for products in <a href="%s">WooCommerce Settings</a>', 'plugin woocommerce'),
		$slug, $translated_slug, 'admin.php?page=woocommerce_settings&tab=pages'); ?></p></div>
	<?php
		}
		if (!$compatible) {
	?>
		<div class="message error"><p><?php echo $message; ?></p></div>
	<?php
		}
	}
	
	/**
     * Switch the language on AJAX action, because in some ways the correct language is missing.
     * Fix is for the checkout page review order table texts and the payment gateways table texts, they're loaded on AJAX.
	 */
	function localize_on_ajax(){
		global $sitepress;
		
		$current_language = $sitepress->get_current_language();
		
		$sitepress->switch_lang($current_language, true);
	}
	
	/**
	 * Filters upsell/crosell products in the correct language.
	 */
	function search_products($found_products){
		global $wpdb, $sitepress;
		
		$current_page_language = $sitepress->get_current_language();
		
		foreach($found_products as $product_id => $output_v){
			$post_data = $wpdb->get_row("SELECT * FROM ". $wpdb->prefix ."icl_translations WHERE element_id = '$product_id' AND element_type LIKE 'post_%'");
			$product_language = $post_data->language_code;
			
			if($product_language !== $current_page_language){
				unset($found_products[$product_id]);
			}
		}
	
		return $found_products;
	}

	// Set multilingual currency.
	function set_ml_currency($currency){
		global $wpdb, $sitepress;
	
		$db_currency = $wpdb->get_row("SELECT code FROM ". $wpdb->prefix ."icl_currencies WHERE language_code = '". $sitepress->get_current_language() ."'");
		
		if(!empty($db_currency) && get_option('icl_enable_multi_currency') == 'yes'){
		
			$currency = strtoupper(trim($db_currency->code));
			
		}
	
		return $currency;
	}
	
	// Fix for shipping update on the checkout page.
	function shipping_update($amount){
		global $sitepress, $post;
		
		if($sitepress->get_current_language() !== $sitepress->get_default_language() && $post->ID == $this->checkout_page_id()){
		
			$_SESSION['icl_checkout_shipping_amount'] = $amount;
			
			$amount = $_SESSION['icl_checkout_shipping_amount'];
		
		}
	
		return $amount;
	}
	
    /**
     * Filters WooCommerce checkout link.
     */
    function get_checkout_page_url(){
        return get_permalink(icl_object_id(get_option('woocommerce_checkout_page_id'), 'page', true));
    }

    /**
     * Filters WooCommerce AJAX params
     * 
     * @global type $sitepress
     * @global type $post
     * @param type $value
     * @return type 
     */
    function ajax_params($value){
        global $sitepress, $post;

        if(!isset($post->ID)){
            return $value;
        }

        if($sitepress->get_current_language() !== $sitepress->get_default_language()){
            $value['checkout_url'] = admin_url('admin-ajax.php?action=woocommerce-checkout&lang=' . ICL_LANGUAGE_CODE);
            $value['ajax_url'] = admin_url('admin-ajax.php?lang=' . ICL_LANGUAGE_CODE);
        }

        $checkout_page_id = get_option('woocommerce_checkout_page_id');
        $pay_page_id = get_option('woocommerce_pay_page_id');
        $cart_page_id = get_option('woocommerce_cart_page_id');

        $translated_checkout_page_id = icl_object_id($checkout_page_id, 'page', false);
        $translated_pay_page_id = icl_object_id($pay_page_id, 'page', false);
        $translated_cart_page_id = icl_object_id($cart_page_id, 'page', false);

        if($translated_cart_page_id == $post->ID){
            $value['is_cart'] = 1;
        } else if($translated_checkout_page_id == $post->ID || $checkout_page_id == $post->ID){
            $value['is_checkout'] = 1;

            //$value['locale'] = '';

            $_SESSION['wpml_globalcart_language'] = $sitepress->get_current_language();

        } else if($translated_pay_page_id == $post->ID){
            $value['is_pay_page'] = 1;
        }

        return $value;
    }

    /**
     * Filters WooCommerce redirect location.
     * 
     * @global type $sitepress
     * @param type $link
     * @return type 
     */
    function do_redirect($link){
        global $sitepress;
        return html_entity_decode($sitepress->convert_url($link));
    }

    /**
     * Translates attributes names.
     * 
     * @param type $name
     * @return type
     */
    function translate_attributes($name){
        if(function_exists('icl_register_string')){
            icl_register_string('woocommerce', $name .'_attribute', $name);
            $name = icl_t('woocommerce', $name .'_attribute', $name);
        }

        return $name;
    }

    /**
     * Takes off translated products from the Up-sells/Cross-sells tab.
     * 
     * @global type $sitepress
     * @global type $wpdb
     * @return type
     */
    function woocommerce_upsell_crosssell_search_posts($posts){
        global $sitepress, $wpdb;

        foreach($posts as $key => $post){
            $post_id = $posts[$key]->ID;
            $post_data = $wpdb->get_row("SELECT * FROM ". $wpdb->prefix ."icl_translations WHERE element_id = '$post_id'", ARRAY_A);

            if($post_data['language_code'] !== $sitepress->get_current_language()){
                unset($posts[$key]);
            }
        }

        return $posts;
    }

    /**
     * Avoids the post translation links on the product post type.
     * 
     * @global type $post
     * @return type
     */
    function post_alternative_languages($output){
        global $post;

        $post_type = get_post_type($post->ID);
        $checkout_page_id = get_option('woocommerce_checkout_page_id');

        if($post_type == 'product' || is_page($checkout_page_id)){
            $output = '';
        }

        return $output;
    }

	/**
	 * Translates custom attribute/variation title.
	 * 
	 * @return type
	 */
	function variation_term_name($term){
		return  icl_t('woocommerce', $term .'_attribute_name', $term);
	}

	function attribute_terms($terms){
		global $sitepress;
		// remove autop
		$terms = str_replace('<p>', '', $terms);
		$terms = str_replace('</p>', '', $terms);

		// iterate terms translating
		$terms = explode(",", $terms);
		$out = array();
		foreach ($terms as $term) {
			$term = trim($term);
			$term = icl_t('woocommerce', $term .'_attribute_name', $term);
			$out[] = $term;
		}

		return wpautop(wptexturize(implode(", ", $out)));
	}

    /**
     * Translates the payment gateway title text.
     * 
     * @return type
     */
    function gateway_title($title) {
        if (function_exists('icl_translate')) {
            $title = icl_translate('woocommerce', $title .'_gateway_title', $title);
        }
        return $title;
    }

    /**
     * Translates the payment gateway description text.
     * 
     * @return type
     */
    function gateway_description($description, $gateway_title) {
        if (function_exists('icl_translate')) {
            $description = icl_translate('woocommerce', $gateway_title .'_gateway_description', $description);
        }
        return $description;
    }

	/**
	 * Filters WooCommerce query for translated shop page
	 * 
	 */
	function shop_page_query($q) {
		if ( ! $q->is_main_query() )
			return;

		$front_page_id = get_option('page_on_front');
		$shop_page_id = get_option('woocommerce_shop_page_id');
		$shop_page = get_post( woocommerce_get_page_id('shop') );

		if (!empty($shop_page) && $q->get('page_id') !== $front_page_id && $shop_page_id == $q->get('page_id')) {
			$q->set( 'post_type', 'product' );
			$q->set( 'page_id', '' );
			if ( isset( $q->query['paged'] ) )
				$q->set( 'paged', $q->query['paged'] );

			// Get the actual WP page to avoid errors
			// This is hacky but works. Awaiting http://core.trac.wordpress.org/ticket/21096
			global $wp_post_types;

			$q->is_page = true;

			$wp_post_types['product']->ID 			= $shop_page->ID;
			$wp_post_types['product']->post_title 	= $shop_page->post_title;
			$wp_post_types['product']->post_name 	= $shop_page->post_name;

			// Fix conditional functions
			$q->is_singular = false;
			$q->is_post_type_archive = true;
			$q->is_archive = true;
			$q->queried_object = get_post_type_object('product');
		}
	}

	function adjust_shop_page($q) {
		global $sitepress;
		if ($sitepress->get_default_language() != $sitepress->get_current_language()) {
			if (!empty($q->query_vars['pagename'])) {
				$shop_page = get_post( woocommerce_get_page_id('shop') );
				if ($shop_page->post_name == $q->query_vars['pagename']) {
					unset($q->query_vars['page']);
					unset($q->query_vars['pagename']);
					$q->query_vars['post_type'] = 'product';
				}
			}
		}
	}

	/**
	 * Filters the currency symbol.
	 */
	function woocommerce_currency_symbol($currency_symbol){
		global $sitepress, $wpdb;

		// Dont process currency symbols in the settings screen
		if(function_exists('get_current_screen')) {
			$screen = get_current_screen();
			if (!empty($screen) && $screen->id == 'woocommerce_page_woocommerce_settings') {
				return $currency_symbol;
			}
		}

		$db_currency = $wpdb->get_row("SELECT code FROM ". $wpdb->prefix ."icl_currencies WHERE language_code = '". $sitepress->get_current_language() ."'");

		if($db_currency && get_option('icl_enable_multi_currency') == 'yes'){
			$db_currency = $db_currency->code;
			if(in_array($db_currency, array_keys($this->currencies))){
				$currency_symbol = $this->currencies[$db_currency];
			} else {
				$currency_symbol = $db_currency;
			}
		}

		return $currency_symbol;
	}

	/**
	 * Filters the product price.
	 */
	function woocommerce_price($price){
		global $sitepress, $wpdb;

		if (get_option('icl_enable_multi_currency') == 'yes') {
			$sql = "SELECT value FROM ". $wpdb->prefix ."icl_currencies WHERE language_code = '". $sitepress->get_current_language() ."'";
			$currency = $wpdb->get_results($sql, OBJECT);

			if($currency){
				$exchange_rate = $currency[0]->value;
				$price = round($price * $exchange_rate, (int) get_option( 'woocommerce_price_num_decimals' ));
				$price = apply_filters('woocommerce_multilingual_price', $price);
			}
		}

		return $price;
	}

    
	/*
    * WC compat layer: get product
    */
  
    function wcml_get_product($product_id) {
    	if ( version_compare( WOOCOMMERCE_VERSION, "2.0.0" ) >= 0 ) {
    		// WC 2.0
   			return get_product( $product_id);
    	} else {
    		return new WC_Product($product_id);
    	}
	}

    function sync_product_stocks($order) {
    	if ( version_compare( WOOCOMMERCE_VERSION, "2.0.0" ) >= 0 ) {
    		// WC 2.0
   			return $this->sync_product_stocks20($order);
    	} else {
    		return $this->sync_product_stocks16($order);
    	}
	}

    /* 
      Only when translated products are ordered, force adjusting stock information for all translations
      When a product in the default language is ordered stocks are adjusted automatically
    */
	function sync_product_stocks16($order){
		global $sitepress;
		$order_id = $order->id;

		foreach ( $order->get_items() as $item ) {
			if (isset($item['variation_id']) && $item['variation_id']>0){
				$ld = $sitepress->get_element_language_details($item['variation_id'], 'post_product_variation');
				$product_id = icl_object_id( $item['variation_id'], 'product_variation', true, $sitepress->get_default_language());
				$_product = new WC_Product_Variation( $product_id );
			}else{
				$ld = $sitepress->get_element_language_details($item['id'], 'post_product');
				$product_id = icl_object_id( $item['id'], 'product', true, $sitepress->get_default_language());
				$_product = new WC_Product( $product_id );
			}

			// Process for non-default languages
			if ($ld->language_code != $sitepress->get_default_language()) {

				// Out of stock attribute
				if ($_product->managing_stock() && !$_product->backorders_allowed() && $_product->get_total_stock()<=0){
					$outofstock = 'outofstock';
				}else{
					$outofstock = false;
				}

				if ( $_product && $_product->exists() && $_product->managing_stock() ) {
					$stock          = $_product->reduce_stock($item['qty']);
					$total_sales    = get_post_meta($_product->id, 'total_sales', true);
					$total_sales   += $item['qty'];
					update_post_meta($product_id, 'total_sales', $total_sales);
				}
			}
		}

	}

	function sync_product_stocks20($order){
		global $sitepress;
		$order_id = $order->id;

		foreach ( $order->get_items() as $item ) {
			if (isset($item['variation_id']) && $item['variation_id']>0){
				$ld = $sitepress->get_element_language_details($item['variation_id'], 'post_product_variation');
				$product_id = icl_object_id( $item['variation_id'], 'product_variation', true, $sitepress->get_default_language());
				$_product = get_product($product_id);
			}else{
				$ld = $sitepress->get_element_language_details($item['product_id'], 'post_product');
				$product_id = icl_object_id( $item['product_id'], 'product', true, $sitepress->get_default_language());
				$_product = get_product($product_id);
			}
			
			// Process for non-default languages
			if ($ld->language_code != $sitepress->get_default_language()) {

				// Out of stock attribute
				if ($_product->managing_stock() && !$_product->backorders_allowed() && $_product->get_total_stock()<=0){
					$outofstock = 'outofstock';
				}else{
					$outofstock = false;
				}

				if ( $_product && $_product->exists() && $_product->managing_stock() ) {
					$stock          = $_product->reduce_stock($item['qty']);
					$total_sales    = get_post_meta($_product->id, 'total_sales', true);
					$total_sales   += $item['qty'];
					update_post_meta($product_id, 'total_sales', $total_sales);
				}
			}
		}

	}
    /**
     * Adds language to order post type.
     * 
     * Language was stored in the session created on checkout page.
     * See params().
     * 
     * @param type $order_id
     */ 
    function order_language($order_id) { 
        if(!get_post_meta($order_id, 'wpml_language')){
            $language = isset($_SESSION['wpml_globalcart_language']) ? $_SESSION['wpml_globalcart_language'] : ICL_LANGUAGE_CODE;
            update_post_meta($order_id, 'wpml_language', $language);
        }
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

        if(get_option('icl_enable_multi_currency') == 'yes'){
            $sql = "SELECT (value) FROM ". $wpdb->prefix ."icl_currencies WHERE language_code = '". $sitepress->get_current_language() ."'";
            $currency = $wpdb->get_results($sql, OBJECT);

            if($currency){
                $exchange_rate = $currency[0]->value;
                $min_price = $min_price / $exchange_rate;
                $min_price = round($min_price,(int) get_option( 'woocommerce_price_num_decimals' ));
            }
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

        if(get_option('icl_enable_multi_currency') == 'yes'){
            $sql = "SELECT (value) FROM ". $wpdb->prefix ."icl_currencies WHERE language_code = '". $sitepress->get_current_language() ."'";
            $currency = $wpdb->get_results($sql, OBJECT);

            if($currency){
                $exchange_rate = $currency[0]->value;
                $max_price = $max_price / $exchange_rate;
                $max_price = round($max_price,(int) get_option( 'woocommerce_price_num_decimals' ));
            }
        }

        return $max_price;
    }

	function icl_pro_translation_completed($new_post_id) {
		$this->sync_variations($new_post_id, get_post($new_post_id));
	}

	/**
	 * This function synchronizes variations when we first create a duplicate
	 */
	function sync_variations_for_duplicates($master_post_id, $lang, $postarr, $id) {
		$this->sync_variations($id, $postarr);
	}

	/**
	 * This function takes care of synchronizing variations from original to translations
	 */
	function sync_variations($post_id, $post){
		global $wpdb, $pagenow, $sitepress, $sitepress_settings;

		// check its a product
		$post_type = get_post_type($post_id);
		if ($post_type != 'product') {
			return;
		}

		// exceptions
		$ajax_call = (!empty($_POST['icl_ajx_action']) && $_POST['icl_ajx_action'] == 'make_duplicates');
		$duplicated_post_id = icl_object_id($post_id, 'product', false, $sitepress->get_default_language());
		if (empty($duplicated_post_id) || isset($_POST['autosave'])) {
			return;
		}
		if($pagenow != 'post.php' && $pagenow != 'post-new.php' && $pagenow != 'admin.php' && !$ajax_call){
			return;
		}
		if (isset($_GET['action']) && $_GET['action'] == 'trash') {
			return;
		}

		// get language code
		$language_details = $sitepress->get_element_language_details($post_id, 'post_product');
        if ($pagenow == 'admin.php' && empty($language_details)) {
            //translation editor support: sidestep icl_translations_cache
            global $wpdb;
            $language_details = $wpdb->get_row("SELECT element_id, trid, language_code, source_language_code FROM {$wpdb->prefix}icl_translations WHERE element_id=$post_id AND element_type = 'post_product'");
        }
		if (empty($language_details)) {
			return;
		}

		// If we reach this point, we go ahead with sync.
		// Remove filter to avoid double sync
		remove_action('save_post', array($this, 'sync_variations'), 11, 2);

		// pick posts to sync
		$posts = array();
		$translations = $sitepress->get_element_translations($language_details->trid, 'post_product');
		foreach ($translations as $translation) {
			if ($translation->original) {
				$duplicated_post_id = $translation->element_id;
			} else {
				$posts[$translation->element_id] = $translation;
			}
		}
		

		// TODO: move outside the loop all db queries on duplicated_post_id
		foreach ($posts as $post_id => $translation) {
			$lang = $translation->language_code;

			// Filter upsell products, crosell products and default attributes for translations
			$original_product_upsell_ids = get_post_meta($duplicated_post_id, '_upsell_ids', TRUE);
			if(!empty($original_product_upsell_ids)){
				$unserialized_upsell_ids = maybe_unserialize($original_product_upsell_ids);

				foreach($unserialized_upsell_ids as $k => $product_id){
					// get the correct language
					$upsell_product_translation_id = icl_object_id($product_id, 'product', false, $lang);

					if($upsell_product_translation_id){
						$unserialized_upsell_ids[$k] = $upsell_product_translation_id;
					// if it isn't translated - unset it
					} else {
						unset($unserialized_upsell_ids[$k]);
					}
				}

				$data = array('meta_value' => maybe_serialize($unserialized_upsell_ids));
				$where = array('post_id' => $post_id, 'meta_key' => '_upsell_ids');
				$wpdb->update($wpdb->postmeta, $data, $where);
			}

			$original_product_crosssell_ids = get_post_meta($duplicated_post_id, '_crosssell_ids', TRUE);
			if(!empty($original_product_crosssell_ids)){
				$unserialized_crosssell_ids = maybe_unserialize($original_product_crosssell_ids);

				foreach($unserialized_crosssell_ids as $k => $product_id){
					// get the correct language
					$crosssell_product_translation_id = icl_object_id($product_id, 'product', false, $lang);

					if($crosssell_product_translation_id){
						$unserialized_crosssell_ids[$k] = $crosssell_product_translation_id;
					// if it isn't translated - unset it
					} else {
						unset($unserialized_crosssell_ids[$k]);
					}
				}

				$data = array('meta_value' => maybe_serialize($unserialized_crosssell_ids));
				$where = array('post_id' => $post_id, 'meta_key' => '_crosssell_ids');
				$wpdb->update($wpdb->postmeta, $data, $where);
			}

			$original_default_attributes = get_post_meta($duplicated_post_id, '_default_attributes', TRUE);
			if(!empty($original_default_attributes)){
				$unserialized_default_attributes = maybe_unserialize($original_default_attributes);
				foreach($unserialized_default_attributes as $attribute => $default_term_slug){
					// get the correct language
					if (substr($attribute, 0, 3) == 'pa_') {
						$default_term = get_term_by('slug', $default_term_slug, $attribute);
						$default_term_id = icl_object_id($default_term->term_id, $attribute, false, $lang);

						if($default_term_id){
							$default_term = get_term_by('id', $default_term_id, $attribute);
							$unserialized_default_attributes[$attribute] = $default_term->slug;
						} else {
							// if it isn't translated - unset it
							unset($unserialized_default_attributes[$attribute]);
						}
					}
				}

				$data = array('meta_value' => maybe_serialize($unserialized_default_attributes));
				$where = array('post_id' => $post_id, 'meta_key' => '_default_attributes');
				$wpdb->update($wpdb->postmeta, $data, $where);
			}

			//sync product categories
			$taxs = array();
			$updates = array();

			$taxs[] = 'product_cat';
			$updates['product_cat'] = array();
			$terms = get_the_terms($duplicated_post_id, 'product_cat');
			if ($terms) foreach ($terms as $term) {
				$trid = $sitepress->get_element_trid($term->term_taxonomy_id, 'tax_product_cat');
				if ($trid) {
					$translations = $sitepress->get_element_translations($trid,'tax_product_cat');
					//error_log("translations ".var_export($translations,true));
					if (isset($translations[$lang])) {
						$updates['product_cat'][] = intval($translations[$lang]->term_id);
					}
				}
			}

			$taxs[] = 'product_tag';
			$updates['product_tag'] = array();
			$terms = get_the_terms($duplicated_post_id, 'product_tag');
			if ($terms) foreach ($terms as $term) {
				$trid = $sitepress->get_element_trid($term->term_taxonomy_id, 'tax_product_tag');
				if ($trid) {
					$translations = $sitepress->get_element_translations($trid,'tax_product_tag');
					//error_log("translations ".var_export($translations,true));
					if (isset($translations[$lang])) {
						$updates['product_tag'][] = intval($translations[$lang]->term_id);
					}
				}
			}

			//synchronize term data, postmeta (Woocommerce "global" product attributes and custom attributes)
			
			$taxonomies = get_post_meta($duplicated_post_id, '_product_attributes', true);
			//error_log('Trans tax '.var_export($taxonomies,true));
			foreach ($taxonomies as $taxonomy) {
				if ($taxonomy['is_taxonomy']) { // Global product attribute
					$tax = $taxonomy['name'];
					$taxs[] = $tax;
					$updates[$tax] = array();
					$terms = get_the_terms($duplicated_post_id, $tax);
					if ($terms) foreach ($terms as $term) {
						$trid = $sitepress->get_element_trid($term->term_taxonomy_id, 'tax_' . $tax);
						if ($trid) {
							$translations = $sitepress->get_element_translations($trid,'tax_' . $tax);
							if (isset($translations[$lang])) {
								$updates[$tax][] = intval($translations[$lang]->term_id);
							}
						}
					}
				}
			} 

			// Sync terms for main product
			$taxs = array_unique($taxs);
			foreach ($taxs as $tax) {
				wp_set_object_terms($post_id, $updates[$tax], $tax);
			}

			// synchronize post variations
			$get_variation_term_id = $wpdb->get_var("SELECT term_id FROM $wpdb->terms WHERE name = 'variable'");
			$get_variation_term_taxonomy_id = $wpdb->get_var("SELECT tt.term_taxonomy_id FROM $wpdb->term_relationships tr
					LEFT JOIN $wpdb->term_taxonomy tt ON tt.term_taxonomy_id = tr.term_taxonomy_id
					WHERE object_id = '$duplicated_post_id' AND taxonomy = 'product_type' AND tt.term_id = '$get_variation_term_id'");

			$is_post_has_variations = $wpdb->get_results("SELECT * FROM $wpdb->term_relationships WHERE object_id = '$duplicated_post_id' AND term_taxonomy_id = '$get_variation_term_taxonomy_id'");
			if(!empty($is_post_has_variations)) $is_post_has_variations = TRUE;

			if($is_post_has_variations){
				// synchronize post variations
				$get_all_post_variations = $wpdb->get_results("SELECT * FROM $wpdb->posts 
				WHERE post_status = 'publish' AND post_type = 'product_variation' AND post_parent = '$duplicated_post_id' ORDER BY ID");

				$duplicated_post_variation_ids = array();
				foreach($get_all_post_variations as $k => $post_data){
					$duplicated_post_variation_ids[] = $post_data->ID;
				}

				foreach($get_all_post_variations as $k => $post_data){
					// Find if this has already been duplicated
					$variation_id = $wpdb->get_var("SELECT post_id FROM $wpdb->postmeta
						JOIN {$wpdb->prefix}icl_translations ON element_id = post_id AND element_type = 'post_product_variation' AND language_code = '$lang'
						WHERE meta_key = '_wcml_duplicate_of_variation' AND meta_value = '$post_data->ID'");
					if (!empty($variation_id)) {
						// Update variation
						wp_update_post(array(
							'ID' => $variation_id,
							'post_author' => $post_data->post_author,
							'post_date_gmt' => $post_data->post_date_gmt,
							'post_content' => $post_data->post_content,
							'post_title' => $post_data->post_title,
							'post_excerpt' => $post_data->post_excerpt,
							'post_status' => $post_data->post_status,
							'comment_status' => $post_data->comment_status,
							'ping_status' => $post_data->ping_status,
							'post_password' => $post_data->post_password,
							'post_name' => $post_data->post_name,
							'to_ping' => $post_data->to_ping,
							'pinged' => $post_data->pinged,
							'post_modified' => $post_data->post_modified,
							'post_modified_gmt' => $post_data->post_modified_gmt,
							'post_content_filtered' => $post_data->post_content_filtered,
							'post_parent' => $post_id, // current post ID
							'menu_order' => $post_data->menu_order,
							'post_type' => $post_data->post_type,
							'post_mime_type' => $post_data->post_mime_type,
							'comment_count' => $post_data->comment_count
						));
					} else {
						// Add new variation
						$guid = $post_data->guid;
						$replaced_guid = str_replace($duplicated_post_id, $post_id, $guid);
						$slug = $post_data->post_name;
						$replaced_slug = str_replace($duplicated_post_id, $post_id, $slug);
						$variation_id = wp_insert_post(array( 
							'post_author' => $post_data->post_author,
							'post_date_gmt' => $post_data->post_date_gmt,
							'post_content' => $post_data->post_content,
							'post_title' => $post_data->post_title,
							'post_excerpt' => $post_data->post_excerpt,
							'post_status' => $post_data->post_status,
							'comment_status' => $post_data->comment_status,
							'ping_status' => $post_data->ping_status,
							'post_password' => $post_data->post_password,
							'post_name' => $replaced_slug,
							'to_ping' => $post_data->to_ping,
							'pinged' => $post_data->pinged,
							'post_modified' => $post_data->post_modified,
							'post_modified_gmt' => $post_data->post_modified_gmt,
							'post_content_filtered' => $post_data->post_content_filtered,
							'post_parent' => $post_id, // current post ID
							'guid' => $replaced_guid,
							'menu_order' => $post_data->menu_order,
							'post_type' => $post_data->post_type,
							'post_mime_type' => $post_data->post_mime_type,
							'comment_count' => $post_data->comment_count
						));
						update_post_meta($variation_id, '_wcml_duplicate_of_variation', $post_data->ID);
						$trid = $sitepress->get_element_trid($post_data->ID, 'post_product_variation');
						$sitepress->set_element_language_details($variation_id, 'post_product_variation', $trid, $lang, $language_details->source_language_code);
					}
				}

				$get_current_post_variations = $wpdb->get_results("SELECT * FROM $wpdb->posts 
				WHERE post_status = 'publish' AND post_type = 'product_variation' AND post_parent = '$post_id' ORDER BY ID");

				// Delete variations that no longer exist
				foreach ($get_current_post_variations as $post_data) {
					$variation_id = get_post_meta($post_data->ID, '_wcml_duplicate_of_variation', true);
					if (!in_array($variation_id, $duplicated_post_variation_ids)) {
						wp_delete_post($variation_id, true);
					}
				}

				// custom fields to copy
				$cf = (array)$sitepress_settings['translation-management']['custom_fields_translation'];

				// synchronize post variations post meta
				$current_post_variation_ids = array();
				foreach($get_current_post_variations as $k => $post_data){
					$current_post_variation_ids[] = $post_data->ID;
				}

				foreach($duplicated_post_variation_ids as $dp_key => $duplicated_post_variation_id){
					$get_all_post_meta = $wpdb->get_results("SELECT * FROM $wpdb->postmeta WHERE post_id = '$duplicated_post_variation_id'");

					foreach($get_all_post_meta as $k => $post_meta){
						$meta_key = $post_meta->meta_key;
						$meta_value = $post_meta->meta_value;

						// adjust the global attribute slug in the custom field
						$attid = null;
						if (substr($meta_key, 0, 10) == 'attribute_') {
							$tax = substr($meta_key, 10);
							if (taxonomy_exists($tax)) {
								$attid = get_term_by('slug', $meta_value, $tax)->term_taxonomy_id;
								$trid = $sitepress->get_element_trid($attid, 'tax_' . $tax);
								if ($trid) {
									$translations = $sitepress->get_element_translations($trid,'tax_' . $tax);
									if (isset($translations[$lang])) {
										$meta_value = $wpdb->get_var($wpdb->prepare("SELECT slug FROM $wpdb->terms WHERE term_id = %s", $translations[$lang]->term_id));
									}
								}
							}
						}
						// update current post variations meta
						if ((substr($meta_key, 0, 10) == 'attribute_' || isset($cf[$meta_key]) && $cf[$meta_key] == 1)) {
							update_post_meta($current_post_variation_ids[$dp_key], $meta_key, $meta_value);
						}
					}
				}

			} 
		}
	}

	/**
	*	Sync term order for product attributes, categories and tags
	*/
	function sync_term_order($meta_id, $object_id, $meta_key, $meta_value) {
		global $sitepress,$wpdb,$pagenow;

		if (!isset($_POST['thetaxonomy']) || !taxonomy_exists($_POST['thetaxonomy']) || substr($meta_key,0,5) != 'order') 
			return;
		
		$tax = $_POST['thetaxonomy'];
		//error_log(__FUNCTION__." $tax ".var_export(func_get_args(),true));

		$lang_details = $sitepress->get_element_language_details($object_id,'tax_' . $tax);
		//error_log(var_export($lang_details,true));
		$lang = $lang_details->language_code;
		$translations = $sitepress->get_element_translations($lang_details->trid,'tax_' . $tax);
		if ($translations) foreach ($translations as $trans) {
			if ($trans->language_code != $lang) {
				//error_log("set_term_order {$trans->language_code} {$trans->element_id} $meta_key $meta_value");
				//cannot use update_woocommerce_termmeta or update_metadata as it would end calling this function again in endless loop
				$wpdb->update($wpdb->prefix.'woocommerce_termmeta', 
					array('meta_value' => $meta_value),
					array('woocommerce_term_id' => $trans->element_id,'meta_key' => $meta_key));
			}
		}
	}

	function sync_term_order_globally() {
		//syncs the term order of any taxonomy in $wpdb->prefix.'woocommerce_attribute_taxonomies'
		//use it when term orderings have become unsynched, e.g. before WCML 3.3.
		global $sitepress, $wpdb, $woocommerce;
		$cur_lang = $sitepress->get_current_language();
		$lang = $sitepress->get_default_language();
		$sitepress->switch_lang($lang);

		$taxes = $woocommerce->get_attribute_taxonomies(); //="SELECT * FROM " . $wpdb->prefix . "woocommerce_attribute_taxonomies"

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
        add_option('icl_is_wcml_term_order_synched', 'yes');
	}
	
	function sanitize_cpa_values($values) {
		// Text based, separate by pipe
 		$values = explode('|', esc_html(stripslashes($values)));
 		$values = array_map('trim', $values);
 		$values = implode('|', $values);
 		return $values;
	}

	function update_post_meta($meta_id, $object_id, $meta_key, $_meta_value) {
		if ($meta_key == '_product_attributes' || $meta_key == 'attribute_names') {
			$array = maybe_unserialize($_meta_value);
			foreach ((array)$array as $attr_slug => $attr) {
				if (!empty($attr['value'])) {
					$values = explode('|',$this->sanitize_cpa_values($attr['value']));
					foreach($values as $value) {
						icl_register_string('woocommerce',ucfirst($value).'_attribute_name',$value);
					} 
				}
			}
		}
	}
    
    /**
     * Creates WCML page.
     */
    function menu(){
        $top_page = apply_filters('icl_menu_main_page', basename(ICL_PLUGIN_PATH) .'/menu/languages.php');

        add_submenu_page($top_page, __('WooCommerce Multilingual','wpml-wcml'),
        __('WooCommerce Multilingual', 'wpml-wcml'), 'manage_options', 'wpml-wcml', array($this, 'menu_content'));
    }

    /**
     * Creates WCML page content.
     */
    function menu_content(){
        include WCML_PLUGIN_PATH . '/menu/management.php';
    }

    /**
     * Adds additional CSS and JS.
     */
    function load_css_and_js() {
        if(isset($_GET['page']) && $_GET['page'] == 'wpml-wcml') {
            wp_enqueue_style('wpml-wcml', WCML_PLUGIN_URL . '/assets/css/management.css', array(), WCML_VERSION);
            wp_enqueue_script('jquery-validate', plugin_dir_url(__FILE__) . '/assets/js/jquery.validate.min.js', array('jquery'), '1.8.1', true);
        }
    }

    /**
     * Makes all new attributes translatable.
     */
    function make_new_attributes_translatable(){
        global $sitepress;
        if(isset($_GET['page']) && $_GET['page'] == 'woocommerce_attributes'){

            $wpml_settings = $sitepress->get_settings();

            $get_all_taxonomies = get_taxonomies();

            foreach($get_all_taxonomies as $tax_key => $taxonomy){
                $pos = strpos($taxonomy, 'pa_');

                // get only product attribute taxonomy name
                if($pos !== false){	
                    $wpml_settings['taxonomies_sync_option'][$taxonomy] = 1;
                }
            }

            $sitepress->save_settings($wpml_settings);
        }
    }

    /**
     * Preselects product-type in admin screen
     */
    function js_scripts_setup(){
        global $pagenow, $wpdb, $sitepress;
        if('post-new.php' == $pagenow){
            if(isset($_GET['post_type']) && $_GET['post_type'] == 'product' && isset($_GET['trid'])){
                $translations = $sitepress->get_element_translations($_GET['trid'], 'post_product_type');
                $source_lang = isset($_GET['source_lang'])?$_GET['source_lang']:$sitepress->get_default_language();
                $terms = get_the_terms($translations[$source_lang]->element_id, 'product_type');
                echo '<script type="text/javascript">';
                echo PHP_EOL . '// <![CDATA[' . PHP_EOL;
                echo 'addLoadEvent(function(){'. PHP_EOL;
                echo "jQuery('#product-type option').removeAttr('selected');" . PHP_EOL;
                echo "jQuery('#product-type option[value=\"" . $terms[0]->slug . "\"]').attr('selected', 'selected');" . PHP_EOL;
                echo '});'. PHP_EOL;
                echo PHP_EOL . '// ]]>' . PHP_EOL;
                echo '</script>';
            }
        }
    }

    /**
     * Adds admin notice.
     */
    function currency_exists_error(){
    ?>
        <div class="message error"><p><?php echo __('Currency of the selected language already exists.', 'wpml-wcml'); ?></p></div>
    <?php
    }

    /**
     * Outputs documentation links.
     */
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

    /**
     * Admin notice after plugin install.
     */
    function admin_notice_after_install(){
        if(get_option('wpml_dismiss_doc_main') != 'yes'){

            $url = $_SERVER['REQUEST_URI'];
            $pos = strpos($url, '?');

            if($pos !== false){
                $url .= '&wcml_action=dismiss';
            } else {
                $url .= '?wcml_action=dismiss';
            }
    ?>
            <div id="message" class="updated message fade" style="clear:both;margin-top:5px;"><p>
                <?php _e('Would you like to see a quick overview?', 'sitepress'); ?>
                </p>
                <p>
                <a class="button-primary" href="http://wpml.org/documentation/related-projects/woocommerce-multilingual/" target="_blank">Learn how to turn your e-commerce site multilingual</a>
                <a class="button-secondary" href="<?php echo $url; ?>">Dismiss</a>
                </p>
            </div>
    <?php
        }
    }

	function translate_cart_contents($item, $values, $key) {
		if ( version_compare( WOOCOMMERCE_VERSION, "2.0.0" ) < 0 ) {
			// clearing subtotal triggers calculate_totals (WC 1.x)
			// for WC 2.x its done with the function below
			$_SESSION['subtotal'] = 0;
		}

		// translate the product id and product data
		$item['product_id'] = icl_object_id($item['product_id'], 'product', true);
		if ($item['variation_id']) {
			$item['variation_id'] = icl_object_id($item['variation_id'], 'product_variation', true);
		}
		$product_id = $item['variation_id'] ? $item['variation_id'] : $item['product_id'];
		$item['data']->post->post_title = get_the_title($item['product_id']);
		return $item;
	}

	function translate_cart_subtotal($cart) {
		$cart->calculate_totals();
	}

	

	/**
	 * WooCommerce Multilingual deactivation hook.
	 */
	function wcml_deactivate(){
		delete_option('wpml_dismiss_doc_main');
	}

}
