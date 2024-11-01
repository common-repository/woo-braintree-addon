=== Addon for Braintree and WooCommerce ===
Contributors: wp_estatic
Tags: woocommerce, braintree, payment gateway,credit card, ecommerce, e-commerce, commerce, cart, checkout,braintree addon,refund,credit cards payment braintree and woocommerce,braintree for woocommerce,braintree payment gateway for woocommerce,braintree payment in wordpress,braintree payment refunds,braintree plugin for woocommerce,braintree woocommerce addon,free braintree woocommerce plugin,woocommerce credit cards payment with braintree,woocommerce plugin braintree
Requires at least: 4.0 & WooCommerce 2.3+
Tested up to: 5.3.2
Stable tag: trunk
License: GPLv3
License URI: http://www.gnu.org/licenses/gpl-3.0.html

This plugin is used to accept the payments using credit cards on checkout page in Woocommerce.

== Description ==
Braintree Payment Gateway is used for capturing credit card payments on your Woocommerce store.
This plugin shows you that how you can use braintree to take credit card payments in their WooCommerce store without writing code. 
All you have to do is add Merchant ID, Public Key and Private Key to a settings page and you're done.


= Why our plugin is better than other Braintree Plugins? =
1. Better Validation for Credit Card On checkout page.
2. Very Clean coding to accept the Credit card payments via Braintree.
3. No Technical Skills needed.
4. Can Customize the Credit Card Title and Display Credit card type icons as per your choice.
5. Accept the type of credit card you like.
6. Display the credit card type icons of your choice.
7. Manage Stock ,Restore stock for order status which get cancelled and refunded

= Features =
1. Simple Code to accept Credit cards via Braintree payment gateway in woocommerce
2. jQuery validations for Credit Cards.
3. Display the credit card type icons of your choice.
4. This plugin Supports Restoring stock if order status is changed to Cancelled or Refunded.
5. No technical skills required.
6. Visualized on screen shots.
7. Adds Transaction Id and Transaction time to Order Note.
8. Adds Refund Id and Refund time to Order Note.
9. Add Stock details for products to Order Note if the order status is Cancelled or Refunded.
10. This plugin accept the of credit card you like.
11. This plugin does not store Credit Card Details.
12. This plugin requires SSL on merchant site.    
13. This plugin Support refunds (Only in Cents) in woocommerce.
14. This plugin Supports many currencies ,please check <a href="https://developers.braintreepayments.com/reference/general/currencies">here</a> which currencies are supported by this plugin for braintree.
15. This plugin uses the latest api of Braintree.

= Our other plugins =
1. [Woo Stripe Addon](https://wordpress.org/plugins/woo-stripe-addon/)
2. [Woo Eway Addon](https://wordpress.org/plugins/woo-eway-addon/) 
3. [Woo Paypal Addon](https://wordpress.org/plugins/woo-paypal-addon/)
4. [WooCommerce SMS Alert - Twilio/Plivo](https://wordpress.org/plugins/woo-sms-alert/)


**Support**

* Neither Woocommerce nor Braintree provides support for this plugin.
* If you think you've found a bug or you're not sure if you need to contact support, feel free to [contact us](http://estatic-infotech.com/).

== Installation ==
= Minimum Requirements =

* WooCommerce 2.2.0 or later
* Wordpress 3.8 or later

= Automatic installation =
In the search field type Woo Braintree addon and click Search Plugins. Once you've found our plugin you can view details about it such as the the point release, rating and description. Most importantly of course, you can install it by simply clicking Install Now button.

= Manual installation =

Steps to install and setup this plugin are:
1.Download the plugin
2.Copy paste the folder to wp-content/plugins folder
3.Activate the plugin and click on settings
4.Add Braintree API key(Merchant ID,Public Key,Private Key)
5.Set the Currency in Woocommerce General settings

== What after installation? ==
After installing and activation of plugin, first check if it displays any Notices at the top, if yes resolve that issues and then deactivate plugin and activate plugin.

Then start testing for the Test/Sandbox account by setting mode as Sandbox in Settings.
Once you are ready to take live payments, make sure the mode is set as live. You'll also need to force SSL on checkout in the WooCommerce settings and have an SSL certificate. As long as the Live API Keys are saved, your store will be ready to process credit card payments.

= Updating =

The plugin should automatically update with new features, but you could always download the new version of the plugin and manually update the same way you would manually install.

== Screenshots ==

1. Settings Page.
2. How to get the Braintree Api Keys.
3. The standard credit card form on the checkout page with javascript validation.
4. Woocommerce Order with different order Note.
5. Order payment detail in braintree Merchant account
6. Detail page of refund Amount.


== Frequently Asked Questions ==

= Does I need to have an SSL Certificate? =

Yes you do. For any transaction involving sensitive information, you should take security seriously, and credit card information is incredibly sensitive.You can read [Braintree's reasaoning for using SSL here](https://www.braintreepayments.com/features/data-security).


== Changelog ==
= 1.0.1 =
* Fix - Woocommerce Credit Card Form Compitable
= 1.0.2 =
* Fix - Payment method reduce stock issue
= 1.0.3 =
* Fix - Product restock issue
= 1.0.4 =
* Fix - Product restock issue
= 1.0.5 =
* Fix - Woocommerce and Wordpress version with credit card form compatible
== Upgrade Notice ==



