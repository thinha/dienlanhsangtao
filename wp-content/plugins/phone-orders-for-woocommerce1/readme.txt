=== Phone Orders for WooCommerce ===
Contributors: algolplus
Donate link: https://paypal.me/ipprokaev/0usd
Tags: woocommerce, phone orders, manual orders, call center
Requires PHP: 5.4.0
Requires at least: 4.8
Tested up to: 7.0
Stable tag: 3.11.0
License: GNU General Public License v3.0
License URI: http://www.gnu.org/licenses/gpl-3.0.html

Easy way to take a manual/phone order in WooCommerce

== Description ==

Speeds up adding manual/phone orders in WooCommerce backend.
Discount plugins correctly adjust product price too.

Having created an order, you can view the order, send an invoice and complete payment of the order ( [Pro version only](https://algolplus.com/plugins/downloads/phone-orders-woocommerce-pro/?utm_source=wordpress&utm_medium=banner&utm_campaign=2026)  ).

= Features =
* UI was adapted for keyboard input
* Search through existing customers or add new customers quickly
* Search through existing products or add new products on the fly
* Use default pricing or adjust pricing within the order
* Places autocomplete for address (requires Google Maps API key)
* Support free shipping (method works in admin area only)
* Ability to add coupons with auto find feature
* Copy url to populate cart
* Log created orders

= Pro features =
* Create new order based on existing order
* Pause and resume the order
* Separate web page for adding orders (without access to /wp-admin)
* Customer search by shipping/billing fields
* Configure fields and default values while adding new customers
* Setup custom fields for the order/customer
* Define products that can be sold (out of stock? without price?)
* Support composite/bundled/other complex products
* Add any additional fees
* A lot of UI options to suit different workflows and setups
* Extra WooCommerce reports
* and [much more ...](https://algolplus.com/plugins/downloads/phone-orders-woocommerce-pro/?utm_source=wordpress&utm_medium=banner&utm_campaign=2026)


== Installation ==

= Automatic Installation =
Go to Wordpress dashboard, click  Plugins / Add New  , type 'woocommerce phone orders' and hit Enter.
Install and activate plugin, visit WooCommerce > Phone Orders.

= Manual Installation =
[Please, visit the link and follow the instructions](http://codex.wordpress.org/Managing_Plugins#Manual_Plugin_Installation)


== Frequently Asked Questions ==

= Can many operators use same account to add orders?  =
It's not recommended, as WooCommerce remembers customer details and cart contents in active session.
Please, use [free plugin](https://wordpress.org/plugins/loggedin/) to prevent this situation.

= How to set default country/state for new customers =
[Pro version](https://algolplus.com/plugins/downloads/phone-orders-woocommerce-pro/?utm_source=wordpress&utm_medium=banner&utm_campaign=2026) has more settings  and we keep adding them.

= I can't add new customer, I see the message "Please enter an account password"  =
Please, visit >WooCommerce>Settings, select tab "Accounts & Privacy" and mark checkbox "When creating an account, automatically generate an account password".

= I can't add new customer, I see the message "Please enter a valid account username"  =
Please, visit >WooCommerce>Settings, select tab "Accounts & Privacy" and mark checkbox "When creating an account, automatically generate a username from the customer's email address".

= I don't see Free Shipping [Phone Orders] in popup  =
Please, visit >WooCommerce>Settings>Shipping  and add shipping method for necessary zones

= I enabled "Cash on delivery", but I don't see it in payment methods =
You should add non-virtual product to the cart at first. If you want to hide this method at frontend - [use this code](https://gist.github.com/alexv66/82d623841d33dc3f6abb1fd98873d710)

= How to pay order?  =
[Pro version](https://algolplus.com/plugins/downloads/phone-orders-woocommerce-pro/?utm_source=wordpress&utm_medium=banner&utm_campaign=2026) allows you to pay as customer, via checkout page.
You can pay directly from admin area too - use [this free plugin](https://wordpress.org/plugins/woo-mp/). They support Stripe and Authorize.Net.

= How to apply bulk/roles/others discounts?  =
Use our free [discount plugin](https://wordpress.org/plugins/advanced-dynamic-pricing-for-woocommerce/) to configure necessary pricing rules.

= My pricing plugin doesn't apply role-based discount =
Please, mark checkbox "Switch customer during cart calculations" at tab Settings.

= Button "Create Order" does nothing  =
Probably, there is a conflict with another plugin. [Please, check javascript errors at first](https://developer.wordpress.org/advanced-administration/debug/debug-javascript/#step-3-diagnosis)

= New phone order does not change the stock =
WooCommerce reduces stock only for orders having following statuses -  Completed, Processing, On hold

= Shipping cost is not added to Order Total =
Please, visit >WooCommerce>Settings>Shipping>Shipping Options and turn off "Hide shipping costs until an address is entered"

== Screenshots ==

1. Cart with added items
2. Created order
3. Address autocomplete
4. Apply coupon
5. Cart discount type and amount
6. Select shipping method
7. Common settings
8. Interface settings

== Changelog ==

= 3.11.0 2026-06-10 =
* Updated tab "Settings"
* Fixed bug - currency selector was not updated if currency switched by another plugin in backend
* Fixed bug - wrong taxes shown after creating order

= 3.10.5 2026-05-25 =
* Fixed UI bugs for WordPress 7.0

= 3.10.4 2026-05-05 =
* Fixed UI bugs in mobile view
* Renamed some buttons, to avoid confusion
* Updated compatibility with [our pricing plugin](https://wordpress.org/plugins/advanced-dynamic-pricing-for-woocommerce/)

= 3.10.3 2026-03-16 =
* Manual discounts (applied to items) are included in the "Discount" total line
* Updated compatibility with [our pricing plugin](https://wordpress.org/plugins/advanced-dynamic-pricing-for-woocommerce/)
* Fixed bug - button "Send Invoice" shown wrong order number
* Fixed bug - modified prices were invisible if taxes were disabled in >WooCommerce>Settings
* Fixed some warnings (PHP 8.5)

= 3.10.2 2025-12-15 =
* Fixed bug - "Manual discount" was ignored when apply "Individual use only" coupon
* Fixed bug - our shipping methods were visible in frontend cart
* Fixed bug - quotes doubled in product name when create custom product
* Fixed bug - private note was not assigned to correct user

= 3.10.1 2025-10-13 =
* Fixed bug - wrong sorting for product search
* Fixed bug - sale start/end dates were ignored
* Fixed bug - removed unused tags {wpo_billing_xxx} from Checkout block

= 3.10.0 2025-09-01 =
* USPS address validation uses [oAuth api!](https://developers.usps.com/getting-started)
* Fixed bug - product search can't find variations by exact name

= 3.9.9 2025-07-07 =
* Fixed bug - new autocomplete didn't work for many addresses
* Fixed bug - new autocomplete didn't work when option "Display autocomplete results only for selected countries" was active
* Added Portuguese language

= 3.9.8 2025-06-05 =
* Switched to new "Google Map API" library
* If you use  Google Maps - you must enable "Places API (New)" for existing project! Or just [create new project](https://docs.algolplus.com/algol_phone_order/settings-pro-version/common-2/)
* Updated UI - minified popup "New Customer" (removed labels for sections)
* Fixed bug - button "Copy url to populate cart" didn't work
* Fixed bug - other plugins broke layout for section "Find or create customer"

= 3.9.7 2025-04-23 =
* Speed up product search
* Updated UI - show spinner after selecting customer

= 3.9.6 2025-04-14 =
* Fixed bugs detected by [PCP plugin](https://wordpress.org/plugins/plugin-check/)
* Fixed bugs detected by [PHPStan tool](https://phpstan.org/)
* Fixed bug - wrong "Google autocomplete" position in "New Customer" and "Edit Address" popups
* Fixed bug - some plugins damaged form layout

= 3.9.5 2025-02-12 =
* Updated "New Customer" and "Edit Address" forms
* Fixed bug - product search was inactive when page loaded
* Fixed bug - conflict with coupons added by "WooCommerce Extended Coupon Features FREE"
* Fixed bug - corrected code for filter "woocommerce_quantity_input_args"

= 3.9.4 2024-12-16 =
* Internal, not released

= 3.9.3 2024-12-02 =
* Fixed critical bug - fatal error if shop has 1000+ products

= 3.9.2 2024-11-27 =
* Field "Discount" shows amount added by [our pricing plugin](https://wordpress.org/plugins/advanced-dynamic-pricing-for-woocommerce/)
* Fixed bug - some phrases can not be translated
* Fixed bug - minor vue(js) warnings

= 3.9.1 2024-10-14 =
* Fixed bug - option "Switch customer" worked incorrectly
* Fixed bug - can not edit cost of custom product
* Fixed bug - impossible to type coupon name in popup
* Fixed bug - our internal meta was visible in order items (>WooCommerce>Orders)

= 3.9.0 2024-05-22 =
* Added option "Autocomplete results only for selected countries (Google Map API)" to  >Settings>Common
* Fixed bug - can not create many item metas with same key
* Fixed bug - current user cart was not empty after creating new order
* Fixed some warnings (PHP 8.3)

= 3.8.10 2024-03-22 =
* Fixed critical bug - coupons ignored
* Fixed bug - fatal error when checking status of variable product

= 3.8.9 2024-03-20 =
* Speed up the plugin a bit, we prevented unnecessary shipping calculations
* Fixed bug - broken UI layout when [our pricing plugin](https://wordpress.org/plugins/advanced-dynamic-pricing-for-woocommerce/) is active
* Fixed bug - php warnings for action "woocommerce_payment_complete"

= 3.8.8 2023-12-12 =
* Added option "Hide results without house number" to >Settings>Common
* Reverted change - switched google autocomplete mode back to "geocode"
* Fixed bug - incorrect address parsing(autocomplete) for UK

= 3.8.7 2023-09-25 =
* Updated product search to  sort results better
* Fixed bug - autocomplete incorrectly filled AU addresses
* Fixed bug - autocomplete sometimes shown routes (not addresses)

= 3.8.4 2023-08-04 =
* Fixed bug - popup"Add fee" worked incorrectly in tax mode "Yes, I will enter prices inclusive of tax"
* Fixed bug - option "Don't close popup on click outside" didn't work
* Reverted change - all order notes was system

= 3.8.3 2023-05-15 =
* Minor UI tweaks
* Fixed bug - button "Get report" didn't work at tab Tools
* Fixed bug - HPOS verification error in WooCommerce 7.0 or earlier
* Fixed bug - incorrectly filled address_2 during google autocomplete

= 3.8.2 2023-03-29 =
* "Products History" popup shows previously purchased products for selected customer
* New option "Don't apply pricing rules" , it requires last version of [our pricing plugin](https://wordpress.org/plugins/advanced-dynamic-pricing-for-woocommerce/)
* Fixed bug - order notes added as system notes now
* Fixed bug - PHP warning "Constant FILTER_SANITIZE_STRING is deprecated"

= 3.8.1 2023-01-30 =
* Internal, not released

= 3.8.0 2023-01-09 =
* Support High-Performance order storage (COT)
* Added option "Allow to create orders without payment" to >Settings>Common
* Fixed bug -  dropdown "Orders Status" ignored
* Fixed bug -  correctly show errors if Create Order fails
* Fixed bug -  now we ignore results of woocommerce hooks if they return wrong minimal qty the product
* Added compatibility with WOOCS – Currency Switcher for WooCommerce Professional, by realmag777

= 3.7.4 2022-11-15 =
* Internal, not released

= 3.7.3 2022-11-09 =
* Fixed bug - item meta can't be saved
* Fixed bug - can't increase qty using arrows if option "Manage stock?" is off
* Fixed bug - minor js errors (undefined vue variables, etc)

= 3.7.2 2022-10-24 =
* Fixed Sensitive Data Exposure vulnerability

= 3.7.1 2022-10-12 =
* Fixed bug - field “State” shown as text box when adding new customer
* Fixed bug - any click on the tab "Log"  triggered the search
* Fixed bug - order creator(user) didn't see own orders in filter "Mine", at page >WooCommerce>Orders

= 3.7.0 2022-09-13 =
* Migrated to Vue3.js
* Added option "Show currency selector" to >Setting>Layout
* Fixed bug - selected Payment Method not getting on emails
* Fixed bug - incorrect check WooCommerce presence for multisite

= 3.6.13 2022-08-08 =
* Modified UI - last column is bold now
* Fixed bug - error message shown at Multisite Network websites
* Fixed bug - products were not sorted by "Menu Order"
* Minor UI tweaks

= 3.6.12 2022-05-19 =
* Show amount (inc vat) for applied coupons
* Modified UI - column Discount is ignored if user set own value in column Cost
* Fixed bug - empty labels in dropdown "Payment method", for some payment plugins
* Fixed bug - user had to click twice in column "Discount" , to switch discount type
* Fixed bug - wrong total amount if option "Disable shipping calculation" was ON in [Advanced Dynamic Pricing for WooCommerce](https://wordpress.org/plugins/advanced-dynamic-pricing-for-woocommerce/)

= 3.6.11 2022-03-28 =
* Fixed bug - shipping method was empty by default
* Fixed bug - popup "Advanced Search" ignored prices set by [Advanced Dynamic Pricing for WooCommerce](https://wordpress.org/plugins/advanced-dynamic-pricing-for-woocommerce/)
* Fixed bug - WooCommerce free shipping coupon didn't change shipping method

= 3.6.10 2022-02-16 =
* Fixed critical bug - wrong information was saved about order creator

= 3.6.9 2022-02-13 =
* internal, not published

= 3.6.8 2022-02-07 =
* Removed dropdowns in search inputs,  to avoid confusion for new users
* Speeded up cart calculations
* Added tab "Tools"
* Fixed bug - fatal error if WooCommerce is not active

= 3.6.7 2021-12-13 =
* Speed up adding item meta fields
* Increased QTY input if option "Allow to input fractional qty" is active
* Show "x.00" if option "Allow to input fractional qty" is active
* Fixed bug - item meta key "Product" didn't allow to create new order

= 3.6.6 2021-11-16 =
* Fixed critical bug - compatiblity issue when our pricing plugin is not active

= 3.6.5 2021-11-16 =
* Fixed critial bug - can not create order with 30+ items
* Fixed bug in compatibility with min/max QTY WooCommerce plugins
* Fixed compatibility with conditional payment gateways

= 3.6.4 2021-10-07 =
* Search by "first name + last name" works now
* Error message shown if wrong coupon was removed
* Line "Manual Discount" shows discount amount only once
* Fixed bug - zero of the shipping methods in popup
* Fixed bug - shipping cost ignored applied coupons
* Fixed bug - settings showed all countries (ignored WooCommerce settings)
* Added Finnish language

= 3.6.3 2021-05-17 =
* Show default values in popup "Create custom product"
* Highlight section with missed customer details
* Fixed bug - customer cart showed items of last order
* Fixed bug - manual discount conflicted with some coupon plugins
* Fixed bug in compatiblity with plugin "Advanced Dynamic Pricing for WooCommerce", discount was applied twice for tax mode "Yes, I will enter prices inclusive of tax"
* DEV - some requests (create order,create customer) send "multipart/form-data"

= 3.6.2 2021-03-31 =
* Added back option "Switch customer" to >Settings>Common
* Modified title for product search result

= 3.6.1 2021-03-17 =
* Imporved compatiblity with [Advanced Dynamic Pricing for WooCommerce](https://wordpress.org/plugins/advanced-dynamic-pricing-for-woocommerce/) to support gifts.

= 3.6.0 2021-03-16 =
* We switched to webpack to build single-page application
* Button "Create order" shows detailed error message if cart items must be removed from the cart
* Fixed some XSS vulnerabilities
* Fixed bug - customer created via plugin was invisible in >WooCommerce>Customers
* Fixed bug - payment method COD was visible in Settings only if  option “Accept for virtual orders” is active
* Fixed bug - incorrect item cost for mode "Yes, I will enter prices inclusive of tax"
* Fixed bug - button Tab ignored selectors Country/State in "Edit Address" popup
* Fixed bug - some phrases can not be translated
* Removed outdated option "Switch customer" from >Settings>Common
* Added compatibility with Loco Translate

= 3.5.2 2020-12-09 =
* Allow to drag/reorder items in the cart
* New option "Allow to edit shipping title", for any shipping method
* A lot of minor tweaks to calculate fees/shipping/subtotal correctly
* Fixed bug - product search was slow in some cases

= 3.5.1 2020-10-05 =
* Compatible with WooCommerce 4.5
* Added some hooks (for compatibility with other plugins)

= 3.5.0 2020-07-20 =
* New option  "Show column "Discount" (turn on it in >Settings>Cart Items)
* We don't stop product or customer search, when user switched to another tab
* Fixed bug - can not set qty more than "instock" for backorder orders
* Fixed bug - popup "New customer" freezed interface for some themes
* Fixed bug - PHP error "Call to undefined function determine_locale()"

= 3.4.4 2020-04-28 =
* Significantly increased speed of page loading

= 3.4.3 2020-04-16 =
* Interface settings were divided into 3 tabs: Interface, Layout, Cart Items
* Order date can be edited manually
* Show read-only attributes for selected variation
* Field "Google MAP API Key" shows the reason why button "Check" fails
* Minor UI bugs

= 3.4.2 2020-03-23 =
* internal, not published

= 3.4.1 2020-03-17 =
* Compatible with WooCommerce 4.0
* Product search supports "grouped" products
* Popup "Edit user" correctly shows errors for wrong countries/states
* Fixed bug - button "Advanced search" became invisible in some cases

= 3.4.0 2020-02-06 =
* Added button "Advanced search", to select many products in search results
* Speeded up cart processing
* New settings tab "Tax"
* New option "Hide 2nd(tax) line for item"
* New option "Allow to create orders without shipping"
* New option "Collapse WordPress menu" (page must be reloaded)
* New option "Scrollable cart contents"
* New option "Order fields position" (default - "below customer details")
* Support [multiple packages](https://wordpress.org/plugins/multiple-packages-for-woocommerce/)
* Show label "(granted by coupon)" for free shipping, if it was added by coupon
* Add records to "Order notes" when order created or an invoice was sent
* Added French language (thanks to @gevcen)
* Fixed bug - plugin didn't show phone and email in billing details
* Fixed bug - plugin didn't show attributes of selected variation (in the cart)
* Fixed some bugs in mobile view

= 3.3.7 2019-11-20 =
* All texts can be translated now (thanks to @gevcen)
* Product autocomplete shows correct prices for role-based discounts (you must set customer at first!)
* New settings tab "WooCommerce"
* Added selector "Default shipping method" to >Settings>Shipping
* Added button "Copy from billing address" to popup "Shipping Address"
* Show type and value for Manual discount

= 3.3.6 2019-10-15 =
* Product search updated again(to support partial matches)
* Product variations shown in same order as they are displayed inside the product
* Added some hooks (for compatibility with other plugins)

= 3.3.5 2019-10-02 =
* Product search updated, it seeks for text inside products' titles at first
* Field "Payment method" is required now  (if it's visible)
* New hooks to tweak interface

= 3.3.4 2019-08-23 =
* Backward compatibility with WooCommerce 3.5
* Supports address validation using USPS
* New hooks to format addresses
* Minor bugs

= 3.3.3 2019-08-14 =
* Compatible with WooCommerce 3.7
* Show order number (not order ID) in messages
* Support external address lookup API (for example, getaddress.io)
* New hooks to disable product/customer search or filter search results

= 3.3.2 2019-06-26 =
* New option "Don't close popup on click outside"
* New hooks for popup "Edit Address"
* Fixed bug - incorrectly work with users just created by admin
* Fixed bug - formatted amounts incorrectly if comma used as decimal separator
* Fixed bug - "Switch customer during cart calculations"  didn't work for guests

= 3.3.1 2019-05-20 =
* Format prices(items and totals) according to currency options (>WooCommerce>Settings)
* New option "Allow to input fractional qty"
* Fixed bug - phrase "List is empty" can't be translated
* Fixed bug - button "Check" used wrong API request to validate API key

= 3.3.0 2019-03-27 =
* Solved problem with slow search if shop has a lot of products/customers
* New option "Show payment method"
* New option "Show detailed taxes"
* New option "Allow to edit shipping cost", for any shipping method
* New option "Don't send order emails"
* Save address coordinates if Google address autocomplete was enabled
* Fixed bug - external coupons were ignored

= 3.2.5 2019-02-27 =
* New settings tab "Interface"
* New option "Show order status" (off by default)
* New option "Show icon for phone orders in orders list" (off by default)
* Fixed bug - selected attribute was ignored for variable product

= 3.2.4 2019-02-18 =
* Fixed bug - option "Ship to a different address" didn't copy address for guests
* Fixed some UI bugs
* Added a lot of hooks (for compatibility with other plugins)

= 3.2.3 2019-01-23 =
* Added - support [Advanced Dynamic Pricing for WooCommerce](https://wordpress.org/plugins/advanced-dynamic-pricing-for-woocommerce/) for bulk/roles/others discounts
* Added - option "Switch customer during cart calculations" to >Settings>Common (off by default)
* Added - own capability "manage_woocommerce_phone_orders" ("manage_woocommerce" still works!)
* Added - "Default customer location"(state/country) applied to address if country was not selected
* Fixed bug - incompatiblity with caching plugins
* Speeded up cart calculations

= 3.2.2 2018-12-04 =
* Show images in product autocomplete
* Show customer links  - profile and orders
* Allow to edit meta for cart items
* Places autocomplete for address (you must generate Google Maps API key)
* Added button "Copy url to populate cart" (off by default)
* Added link "Create order" to users list
* French translation was added

= 3.2.1 2018-10-30 =
* Compatible with WooCommerce 3.5
* Support any number of items in the cart (tested for 100+ items)
* Fixed bug - shipping calculated  automatically even if user turned off "Autocalculation" at tab "Settings"
* Fixed bug - user was able to submit empty attribute for variation

= 3.2.0 2018-10-11 =
* The plugin requires at least WooCommerce 3.3.0 !
* Show attribute dropdowns for item, if variation uses "Any" value
* Added option "Show order date/time"
* Added nested tabs to "Settings"
* Show warning message if coupons are disabled
* Fixed bug - settings were applied to admin only
* Fixed bug - slow customer autocomplete
* Fixed bug - empty default shipping method (it didn't work for some shipping plugins)
* Work correctly with "sold individually" products
* German translation was added

= 3.1.0 2018-08-20 =
* Increased  page loading speed
* Optimized product search
* Added section "References" to tab "Settings"
* Fixed minor UI bugs

= 3.0.0 2018-08-08 =
* UI migrated to modern JavaScript framework (Vue.js)
