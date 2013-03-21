<?php 
/*
  Plugin Name: WooCommerce Multilingual
  Plugin URI: http://wpml.org/documentation/related-projects/woocommerce-multilingual/
  Description: Allows running fully multilingual e-Commerce sites with WooCommerce and WPML. <a href="http://wpml.org/documentation/related-projects/woocommerce-multilingual/">Documentation</a>.
  Author: ICanLocalize
  Author URI: http://wpml.org/
  Version: 2.0
*/

if(defined('WCML_VERSION')) return;
define('WCML_VERSION', '2.0');
define('WCML_PLUGIN_PATH', dirname(__FILE__));
define('WCML_PLUGIN_FOLDER', basename(WCML_PLUGIN_PATH));
define('WCML_PLUGIN_URL', plugins_url() . '/' . WCML_PLUGIN_FOLDER);

require WCML_PLUGIN_PATH . '/woocommerce_wpml.class.php';
$woocommerce_wpml = new woocommerce_wpml();
