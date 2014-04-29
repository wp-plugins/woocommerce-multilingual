<?php
/*
  Plugin Name: WooCommerce Multilingual
  Plugin URI: http://wpml.org/documentation/related-projects/woocommerce-multilingual/
  Description: Allows running fully multilingual e-Commerce sites with WooCommerce and WPML. <a href="http://wpml.org/documentation/related-projects/woocommerce-multilingual/">Documentation</a>.
  Author: ICanLocalize
  Author URI: http://wpml.org/
  Version: 3.3
*/

if(defined('WCML_VERSION')) return;
define('WCML_VERSION', '3.3');
define('WCML_PLUGIN_PATH', dirname(__FILE__));
define('WCML_PLUGIN_FOLDER', basename(WCML_PLUGIN_PATH));
define('WCML_PLUGIN_URL', plugins_url() . '/' . WCML_PLUGIN_FOLDER);
define('WCML_LOCALE_PATH',WCML_PLUGIN_FOLDER.'/locale');
define('WPML_LOAD_API_SUPPORT',true);

define('WCML_MULTI_CURRENCIES_DISABLED', 0);
define('WCML_MULTI_CURRENCIES_PER_LANGUAGE', 1); //obsolete - migrate to 2
define('WCML_MULTI_CURRENCIES_INDEPENDENT', 2);


require WCML_PLUGIN_PATH . '/inc/missing-php-functions.php';
require WCML_PLUGIN_PATH . '/inc/dependencies.class.php';
require WCML_PLUGIN_PATH . '/inc/store-pages.class.php';
require WCML_PLUGIN_PATH . '/inc/products.class.php';
require WCML_PLUGIN_PATH . '/inc/emails.class.php';
require WCML_PLUGIN_PATH . '/inc/upgrade.class.php';
require WCML_PLUGIN_PATH . '/inc/ajax-setup.class.php';
require WCML_PLUGIN_PATH . '/inc/wc-strings.class.php';
require WCML_PLUGIN_PATH . '/inc/terms.class.php';
require WCML_PLUGIN_PATH . '/inc/orders.class.php';
require WCML_PLUGIN_PATH . '/inc/requests.class.php';
require WCML_PLUGIN_PATH . '/inc/functions-troubleshooting.class.php';
require WCML_PLUGIN_PATH . '/inc/compatibility.class.php';

require WCML_PLUGIN_PATH . '/woocommerce_wpml.class.php';
$woocommerce_wpml = new woocommerce_wpml();

