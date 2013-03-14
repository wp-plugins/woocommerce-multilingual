=== WooCommerce Multilingual - run WooCommerce with WPML ===
Contributors: AmirHelzer, dominykasgel, dgwatkins
Donate link: http://wpml.org/documentation/related-projects/woocommerce-multilingual/
Tags: CMS, woocommerce, commerce, ecommerce, e-commerce, products, WPML, multilingual, e-shop, shop
License: GPLv2
Requires at least: 3.0
Tested up to: 3.5
Stable tag: 1.5

Allows running fully multilingual e-commerce sites using WooCommerce and WPML.

== Description ==

This 'glue' plugin makes it possible to run fully multilingual e-commerce sites using [WooCommerce](http://wordpress.org/extend/plugins/woocommerce/) and [WPML](http://wpml.org). It makes products and store pages translatable, lets visitors switch languages and order products in their language.

>> WooCommerce Multilingual is currently compatible with WooCommerce 1.x only. We are working on a new version for WooCommerce 2.x. We will write about updates in [WPML blog](http://wpml.org/blog/).

= Features =

* Lets you translate products, variations, attributes and categories
* Easily synchronizes between products and variations between different languages
* Keeps the same language through the checkout process
* Sends emails to clients and admins in their selected language
* Allows inventory tracking without breaking products into languages
* Enables running a single WooCommerce store with multiple currencies

= Usage Instructions =

You will need to translate all the standard WooCommerce pages. Then, translate products and product categories and you're on your way.

Strings that are not part of any product, will be translatable via WPML's String Translation.

For complete information on setting up a multilingual shop, please go to [WooCommerce Multilingual Manual](http://wpml.org/documentation/related-projects/woocommerce-multilingual/) page.

When you need help, go to [WPML technical support forum](http://wpml.org/forums/forum/english-support/).

= Downloads =

This version of WooCommerce Multilingual works with WooCommerce 1.x. The latest version is [WooCommerce 1.6.6](http://downloads.wordpress.org/plugin/woocommerce.1.6.6.zip). You can see other versions in the [WooCommerce Developers](http://wordpress.org/extend/plugins/woocommerce/developers/) page.

You will also need [WPML](http://wpml.org), together with the String Translation module, which is part of the [Multilingual CMS](http://wpml.org/purchase/) package.

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

= 1.5 =
* Fixed manually setting prices in translated products.
* Take advantage of WPML's new slug translation feature.
* Added the possibility of translating custom attributes.
* Improvements to product variation synchronization.
* Fixed product stock sync for variable products .
* Fix and improve checks made to incompatible permalink configurations.
* Fix tax label translation when there is more than one of them.
* Send order notifications in the language the order was made.
* Removed several warnings and updated deprecated code.
* Cleanup language configuration file and add missing strings.

= 1.4 =
* Allow translating the 'Terms & Conditions' page.
* Register shipping methods strings for translation.
* Register several tax-related strings for translation.
* Fix registration of payment gateway titles and descriptions.
* Synchronize the default attribute of a variable product across its translations.
* Allow saving WooCommerce/Settings while using a non-default language.
* Fix problems when the shop page is at the home page.
* Allow using Wordpress default permalink structure aswell.
* Fix amount sent to payment gateway when using multiple currencies.
* Fix for language switcher in shop pages (fixed in WPML)
* Fix for subscriptions module price not showing (fixed in WPML)
* Rewrite product variation sync: each variation is related to its translations, sync becomes easier
* Remove several PHP warnings and notices.
* Send order status update emails in the language the order was made.

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

= 1.5 =
Variation translation works a lot better now. This version runs best with WooCommerce 1.6.6.

= 1.4 =
This version runs with WooCommerce 1.6.5.x and 1.7.x. Recommeded WPML version is 2.6.2 and above.

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
