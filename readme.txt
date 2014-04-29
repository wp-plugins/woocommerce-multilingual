=== WooCommerce Multilingual - run WooCommerce with WPML ===
Contributors: AmirHelzer, dominykasgel, dgwatkins, adelval
Donate link: http://wpml.org/documentation/related-projects/woocommerce-multilingual/
Tags: CMS, woocommerce, commerce, ecommerce, e-commerce, products, WPML, multilingual, e-shop, shop
License: GPLv2
Requires at least: 3.0
Tested up to: 3.9
Stable tag: 3.3

Allows running fully multilingual e-commerce sites using WooCommerce and WPML.

== Description ==

This 'glue' plugin makes it possible to run fully multilingual e-commerce sites using [WooCommerce](http://wordpress.org/extend/plugins/woocommerce/) and [WPML](http://wpml.org). It makes products and store pages translatable, lets visitors switch languages and order products in their language.

= Features =

* Lets you translate different kinds of WooCommerce product types
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

When you need help, go to [WooCommerce Multilingual support forum](http://wpml.org/forums/topic-tag/woocommerce/).

= Downloads =

This version of WooCommerce Multilingual works with WooCommerce 2.x.

You will also need [WPML](http://wpml.org), together with the String Translation and the Translation Management modules, which are part of the [Multilingual CMS](http://wpml.org/purchase/) package.

= Minimum versions for WPML and modules =

WooCommerce Multilingual checks that the following versions of WPML and its components are active:

* WPML Multilingual CMS       - 3.1.5
* WPML String Translation     - 2.0
* WPML Translation Managenet  - 1.9
* WPML Media                  - 2.1

Without having all these running, WooCommerce Multilingual will not be able to run.

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

= 3.3 =
* Performance improvements: optimized database queries
* Support rounding rules for converted prices
* More advanced GUI for Multi-currency options
* GUI for currency switchers (including widget)
* Added option to synchronize product category display type & thumbnail
* Performance improvement for WCML_Terms::translate_category_base (avoid switching locales)
* Send admin notifications to admin default language
* Dependencies update: WooCommerce Multilingual requires WPML 3.1.5
* Set language information for existing products when installing WCML the first time.
* Do not allow disabling all currencies for a language
* Removed 'clean up test content' and 'send to translation' dropdown on products editor page
* Message about overwritten settings in wpml-config made more explicit
* Lock 'Default variation' select field in product translations
* After change shipping method on cart page we will see not translated strings
* Fixed bug related to shipping cost calculation in multi-currency mode
* With php magic quotes on, products translations with quotes have backslashes
* Bug related to translation of grouped products – simple product not showing up on front end
* Stock actions on the order page don't work correct with translated products
* For Orders save attributes in default language and display them on order page in admin language
* Attribute Label appearing untranslated in backend order
* Memory issues on the Products tab when we have a large number of products
* 'product-category' not translated in the default language.
* 'WCML_Products' does not have a method 'translated_cart_item_name'
* Order completed emails sent in default currency
* Language suffix (e.g. @en) not hidden for product attributes on the front end
* Quick edit functionality issues fixed
* Fixed 'Call to undefined method WC_Session_Handler::get()'
* Fatal error when updating the order status to 'complete'
* Currency is not converted when you switch language until you refresh the page.
* “Super Admin” not able to see the WCML menu
* Checkout validation errors in default language instead of user language
* Fixes for compatibility with Tab manager: Can't translate “Additional Information” tab title
* Bug: SEO title & meta description changed to original
* Bug: 404 on 'view my order' on secondary language using 'language name added as a parameter'
* Bug: Permalink placeholders appear translated when using default language different than English
* Fixes for compatibility with Table Rate shipping: shipping classes not decoded correctly in multi-currency mode
* Bug: 'show all products' link on WCML products page points to the wrong page – no products
* Bug fix: product page redirecting to homepage when the product post type slug was identical in different languages and 'language added as a parameter' was set
* Bug fixes related to File paths functionality (WooComemrce 2.1.x)
* Bug: Product parents not synced between translations (grouped products)
* Bug: Grouped products title incomplete
* Bug: Db Error when saving translation of variable products with custom attributes
* Bug: WooCommerce translated product attributes with spaces not showing
* Bug: Deactivated currency still appears if you maintain the default currency for that language to 'Keep'.
* Bug: Incorrect shipping value on translated page
* Bug: Reports for products including only products in the current language (WooCommerce 2.1.x)
* Bug: WooCommerce translated product attributes with spaces not showing
* Bug: Problems creating translations for shop pages when existing pages were trashed
* Bug fix: Fatal error when Multi-currency is not enabled and 'Table Rate Shipping' plugin is active
* Fixed bug in compatibility with Tab Manager
* Bug fix: Cart strings falling to default language after updating chosen shipping method
* Bug fix: Reports not including selected product/category translations


= 3.2.1 =
* Fixed bug related to product category urls translaiton
* Fixed bug related to back-compatibility with WooCommerce 2.0.20

= 3.2 =
* Compatibility with upcoming WooCommerce 2.1
* Multi-currency support: configure currencies per languages
* Multi-currency support: custom prices for different currencies
* Support translation for the attribute base (permalinks)
* Bug: Emails not sent in the correct language when uses bulk action on orders list page
* Bug: Order notes email in wrong language in certain circumstances
* Bug: Shipping method names are being registered in the wrong language
* Bug: WooCommerce Multilingual menu doesn't display for translators 
* Bug: Using 'category' for products cat slug conflicts with posts 'category'
* Bug: Paypal rejects payments with decimals on certain currencies

= 3.1 =
* Support for multi-currency (independent of language) BETA
* Support for translating products via ICanLocalize (professional translation)
* Option to synchronize product translation dates
* Compatibility with Table Rate Shipping and other extensions
* Better handling for couponse
* Fixed bug: product attributes not saved on orders
* Fixed bug: Can't get to the cart & checkout pages if they are set as child pages
* Fixed bug: Style conflicts in Dashboard for Arabic
* Fixed various issues with notification emails
* Fixed bug: Variable products default selection is not copied to translations.
* Fixed bug: Product Table is not showing Product Draft count

= 3.0.1 =
* Replaced deprecated jQuery function live()
* Fixed bug: language names not localized on products editor page
* Fixed bug: Can't set "Custom post type" to translate
* Fixed bug: Translation fields not visible - In certain circumstances (e.g. search) the translation fields corresponding to the translated languages were missing
* Fixed alignment for 'Update/Save' button in the products translation editor
* Fixed bug: Default selection not copied to duplicate products
* Fixed bug: Price doesn't change when change language on the cart page when set "I will manage the pricing in each currency myself"
* Resolved one compatibility issue with Woosidebars
* Direct translators to the products translation editor automatically (instead of the standard post translation editor)
* Fixed bug: In some situations (different child categories with the same name) the wrong categories were set to a duplicated product.
* Enhancement: Add icons for products in the products translation editor
* Register WooCommerce strings (defined as admin texts in the wpml config file) automatically on plugin activation
* WPML (+addons) - new versions required.
* lcfirst is only available since php 5.3
* Identify fields on known plugins and show their human name in our product translation table (support for WordPress SEO for now)

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
