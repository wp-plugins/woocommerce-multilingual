<?php
/**
 * Compatibility class for plugin WooCommerce Bulk Stock Management
 * http://www.woothemes.com/products/bulk-stock-management/
 *
 * @author konrad
 */
class WCML_Bulk_Stock_Management {
	function __construct() {
		if (is_admin() && isset($_GET['page']) && $_GET['page'] == 'woocommerce-bulk-stock-management') {
			global $sitepress;
			remove_action('admin_enqueue_scripts', array($sitepress, 'language_filter'));
		}
	}
}
