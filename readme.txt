=== WooCommerce Multilingual - run WooCommerce with WPML ===
Contributors: AmirHelzer, dominykasgel
Donate link: http://wp-types.com
Tags: CMS, woocommerce, commerce, ecommerce, e-commerce, products, WPML, multilingual, e-shop, shop
Requires at least: 3.0
Tested up to: 3.4.1
Stable tag: 1.3
Version: 1.3

Allows running fully multilingual e-commerce sites using WooCommerce and WPML.

== Description ==

This 'glue' plugin makes it possible to run fully multilingual e-commerce sites using WooCommerce and WPML. It makes products and store pages translatable, lets visitors switch languages and order products in their language.

= Features =

* Lets you translate products, attributes and categories
* Keeps the same language through the checkout process
* Sends emails to clients and admins in their selected language
* Allows inventory tracking without breaking products into languages
* Enables running a single WooCommerce store with multiple currencies

= Documentation =

Please go to [WooCommerce Multilingual Doc](http://wpml.org/documentation/related-projects/woocommerce-multilingual/) page. You'll find instructions for translating the shop pages, the products and plugin strings.

= Downloads =

You will need:

* [WooCommerce](http://wordpress.org/extend/plugins/woocommerce/) version 1.6.5 and up. Please note that the official WooCommerce release might not be 100% compatible. Always check the [development version on wpml.org](http://wpml.org/documentation/related-projects/woocommerce-multilingual/).
* [WPML](http://wpml.org) version 2.6.0 and up - the multilingual WordPress plugin

== Installation ==

1. Upload 'woocommerce-multilingual' to the '/wp-content/plugins/' directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Translate the shop pages

== Frequently Asked Questions ==

= Does this work with other e-commerce plugins? =

No. This plugin is tailored for WooCommerce.

= What do I need to do in my theme? =

Make sure that your theme is not hard-coding any URL. Always use API calls to receive URLs to pages and you'll be fine.

= My checkout page displays in the same language =

In order for the checkout and store pages to appear translated, you need to create several WordPress pages and insert the WooCommerce shortcodes into them. You'll have to go over the [documentation](http://wpml.org/documentation/related-projects/woocommerce-multilingual/) and see that you performed all steps on the way.

== Screenshots ==

1. Translation controls for products
2. Enabling multi-currency
3. Product categories translations

== Changelog ==

= 1.3 =
* Fixed all custom fields synchronization between translations
* Fixed the stock issue for translations
* Fixed the price filter widget for multiple currencies feature
* Fixed product duplication to a second language 
* Payment gateways texts now are translatable
* Custom variables translations now will be shown in the correct language

= 1.2 =
* Added helpful documentation buttons
* Added makes new attributes translatable automatically
* Added payment gateways translations
* Fixed order statuses disappeared in the orders page
* Fixed attributes translations in duplicated variations
* Fixed PHP warning when adding variations is in question

= 1.1 =
* Added multi-currency feature
* Fixed synchronization of attributes and variations 
* Fixed translation of attributes
* Fixed JS error in the checkout page
* Fixed enable guest checkout (no account required) issue
* Fixed Up-sells/Cross-sells search (showed all translated products)
* Fixed 'Show post translation link' repeating issue

= 1.0 =
* Fixed 'Return to store' URL
* Fixed language selector for the translated shop base pages
* Fixed the product remove URL in the translated language
* Fixed the checkout URL in the translated language
* Fix to prevent incorrect product URL in the shop base page when the permalink is not 'shop'

= 0.9 =
* First release

== Upgrade Notice ==

= 1.3 =
Fixed compatibility between WooCommerce 1.5.8 and WPML 2.5.2

= 1.2 =
Added a few improvements and fixed bugs.

= 1.1 =
Fixed a few bugs. Added multi-currency mode.

= 1.0 =
Recommended update! Fixed a few bugs;

= 0.9 =
* First release
