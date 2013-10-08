=== WooCommerce Multilingual - run WooCommerce with WPML ===
Contributors: AmirHelzer, dominykasgel, dgwatkins, adelval
Donate link: http://wpml.org/documentation/related-projects/woocommerce-multilingual/
Tags: CMS, woocommerce, commerce, ecommerce, e-commerce, products, WPML, multilingual, e-shop, shop
License: GPLv2
Requires at least: 3.0
Tested up to: 3.7
Stable tag: 3.0

Allows running fully multilingual e-commerce sites using WooCommerce and WPML.

== Description ==

This 'glue' plugin makes it possible to run fully multilingual e-commerce sites using [WooCommerce](http://wordpress.org/extend/plugins/woocommerce/) and [WPML](http://wpml.org). It makes products and store pages translatable, lets visitors switch languages and order products in their language.

= Features =

* Lets you different kinds of WooCommerce product types
* Central management for translating product categories, tags and custom attributes
* Automatically synchronizes product variations and images
* Keeps the same language through the checkout process
* Sends emails to clients and admins in their selected language
* Allows inventory tracking without breaking products into languages
* Enables running a single WooCommerce store with multiple currencies

= Usage Instructions =

For step by step instructions on setting up a multilingual shop, please go to [WooCommerce Multilingual Manual](http://wpml.org/documentation/related-projects/woocommerce-multilingual/) page.

After installing, go to WPML->WooCommerce Multilingual. The 'General settings' tab will let you translate the store pages and report what taxonomy requires translation.

Then, continue to the 'Products' and any categories, tags and custom taxonomy that you use.

When you need help, go to [WPML technical support forum](http://wpml.org/forums/forum/english-support/).

= Downloads =

This version of WooCommerce Multilingual works with WooCommerce 2.x.

You will also need [WPML](http://wpml.org), together with the String Translation and the Translation Management modules, which are part of the [Multilingual CMS](http://wpml.org/purchase/) package.

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
2. Product categories translations

== Changelog ==

= 3.0 =
* Brand new GUI and workflow
* Support for easy taxonomy translation 
* Bariations synchronization
* Product images synchronization


= 2.3.3 =
* Fix logout link not working in secondary language
* Fix accepting orders in backend leading to 404
* Set email headings & subjects as translatable
* Set order language when sending order emails from admin
* Sync product tags the same way as categories
* Fix bug in ajax product search filter
* Support for WooCommerce Brands extension (http://www.woothemes.com/products/brands/)
* Initial support for Translation Editor
* Fix bug with cart currency updates and variations
* Fix language in new customer note notifications

= 2.3.2 =
* Sync also default options for custom attributes.
* Global resync (done only once) of the orderings of product attribute values and categories across all languages.
* Fixed a bug and a corner case in variation synchronization.

= 2.3.1 =
* Fixed incompatibility with PHP 5.2

= 2.3 =
* Refactor translation and currency conversion of products & variations in cart
* A problem we had with shipping selection was resolved in WooCommerce itself
* Improved synchronization of global product attributes, whether used for variations or not
* Custom product attributes registered as strings when defined in the backend
* Don't adjust the currency symbol in WooCommerce settings page
* Term and product category order is synchronized among languages
* Additional filters for WooCommerce emails
* Fixed layered nav widgets in translated shop page
* Synchronize Product Categories

= 2.2 =
* Price in mini-cart refreshed when changing language
* Fix bug in multilingual currency setting that slipped in 2.1

= 2.1 =
* Add admin notices for required plugins
* Add support for 'Review Order' and 'Lost Password' pages
* Fix rounding issues in currency conversion
* Variations: pick translated terms using 'trid' gives better results
* Variations: sync to all languages when there are more than 2 languages
* Improvement: load JS/CSS only when needed

= 2.0 =
* Fix variation sync to more than one language
* Fix custom field sync for new variations
* Fix rounding of amounts in PayPal
* Adjust product stock sync to WC 2.x
* Add automatic id translation of logout page
* Adjust permalink warnings to WC 2.x
* Clean up code

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

= 2.0 =
More variation fixes and compatibility with WooCommerce 2.x

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
