<?php
/**
 * Plugin Name:         Addon for Braintree and WooCommerce
 * Plugin URL:          Woo Braintree Addon
 * Description:         Woo Braintree Addon allows you to accept payments on your Woocommerce store. It accpets credit card payments and processes them securely with your merchant account.
 * Version:             2.0.1
 * WC requires at least:2.3
 * WC tested up to:     3.8.1
 * Requires at least:   4.0+
 * Tested up to:        5.3.2
 * Contributors:        wp_estatic
 * Author:              Estatic Infotech Pvt Ltd
 * Author URI:          http://estatic-infotech.com/
 * License:             GPLv3
 * @package WooCommerce
 * @category Woocommerce Payment Gateway
 */
add_action('plugins_loaded', 'init_my_braintree_payment');
require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
include(plugin_dir_path(__FILE__) . "lib/Braintree.php");

if (!in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {
    deactivate_plugins(plugin_basename(__FILE__));
    add_action('load-plugins.php', function() {
        add_filter('gettext', 'change_text_brntre', 99, 3);
    });

    function change_text_brntre($translated_text, $untranslated_text, $domain) {
        $old = array(
            "Plugin <strong>activated</strong>.",
            "Selected plugins <strong>activated</strong>."
        );

        $new = "Please activate <b>Woocommerce</b> Plugin to use Woo Braintree Addon plugin";

        if (in_array($untranslated_text, $old, true)) {
            $translated_text = $new;
            remove_filter(current_filter(), __FUNCTION__, 99);
        }
        return $translated_text;
    }

    return FALSE;
}

/**
 * 
 * @return boolean
 */
function init_my_braintree_payment() {

    if (class_exists('WC_Payment_Gateway')) {

        function add_braintree_gateway_class($methods) {
            $methods[] = 'WC_Braintree_Gateway';
            return $methods;
        }

        add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'add_action_links_cc_braintree');

        function add_action_links_cc_braintree($links) {
            $mylinks = array(
                '<a href="' . admin_url('admin.php?page=wc-settings&tab=checkout&section=wc_braintree_gateway') . '">Settings</a>',
            );
            return array_merge($links, $mylinks);
        }

        add_filter('woocommerce_payment_gateways', 'add_braintree_gateway_class');

        class WC_Braintree_Gateway extends WC_Payment_Gateway {

            public function __construct() {
                $this->id = 'braintree';
                $this->has_fields = true;
                $title = $this->get_option('braintree_title');
                if (!empty($title)) {
                    $this->title = $this->get_option('braintree_title');
                } else {
                    $this->title = 'Credit Card';
                }
                $this->method_title = 'Braintree';
                $this->init_form_fields();
                $this->init_settings();
                $this->supports = array('products', 'refunds');
                $this->merchant_id = $this->get_option('sandbox_merchant_id');
                $this->private_key = $this->get_option('sandbox_private_key');
                $this->public_key = $this->get_option('sandbox_public_key');
                $this->mode = $this->get_option('mode');
                if ($this->mode == 'Live') {
                    $this->mode = 'Production';
                }
                $this->currency = $this->get_option('currency');
                $this->braintree_cardtypes = $this->get_option('braintree_cardtypes');
                
                $method_description = $this->get_option('braintree_description');
                if (!empty($method_description)) {
                    $this->method_description = $method_description;
                } else {
                    $this->method_description = sprintf(__('Paypal allows you to accept payments on your Woocommerce store. It accpets credit card payments and processes them securely with your merchant account.Please dont forget to test with sandbox account first.', 'woocommerce'));
                }
                
                add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
                add_action('wp_enqueue_scripts', 'add_custom_js_braintree');
                add_action('woocommerce_order_status_processing_to_cancelled', array($this, 'restore_stock_braintree_cancel'), 10, 1);
                add_action('woocommerce_order_status_completed_to_cancelled', array($this, 'restore_stock_braintree_cancel'), 10, 1);
                add_action('woocommerce_order_status_on-hold_to_cancelled', array($this, 'restore_stock_braintree_cancel'), 10, 1);
                add_action('woocommerce_order_status_processing_to_refunded', array($this, 'restore_stock_braintree'), 10, 1);
                add_action('woocommerce_order_status_completed_to_refunded', array($this, 'restore_stock_braintree'), 10, 1);
                add_action('woocommerce_order_status_on-hold_to_refunded', array($this, 'restore_stock_braintree'), 10, 1);
                add_action('woocommerce_order_status_cancelled_to_processing', array($this, 'reduce_stock_braintree'), 10, 1);
                add_action('woocommerce_order_status_cancelled_to_completed', array($this, 'reduce_stock_braintree'), 10, 1);
                add_action('woocommerce_order_status_cancelled_to_on-hold', array($this, 'reduce_stock_braintree'), 10, 1);
            }

            /**
             * 
             * @return type
             */
            public function get_icon() {
                if ($this->get_option('show_accepted') == 'yes') {
                    $get_cardtypes = $this->get_option('braintree_cardtypes');
                    $icons = "";
                    foreach ($get_cardtypes as $val) {
                        $cardimage = plugins_url('images/' . $val . '.png', __FILE__);
                        $icons .= '<img src="' . $cardimage . '" alt="' . $val . '" />';
                    }
                } else {
                    $icons = "";
                }
                return apply_filters('woocommerce_gateway_icon', $icons, $this->id);
            }

            /**
             * init_form_fields
             */
            public function init_form_fields() {
                $this->form_fields = array(
                    'enabled' => array(
                        'title' => __('Enable/Disable', 'woocommerce'),
                        'type' => 'checkbox',
                        'label' => __('Enable Braintree', 'woocommerce'),
                        'default' => 'yes'
                    ),
                    'braintree_title' => array(
                        'title' => __('Title', 'woocommerce'),
                        'type' => 'text',
                        'description' => __('Display this title on checkout page.', 'woocommerce'),
                        'default' => 'Credit Card',
                        'desc_tip' => true
                    ),
                    'braintree_description' => array(
                        'title' => __('Description', 'woocommerce'),
                        'type' => 'textarea',
                        'description' => __('Display this description on checkout page.', 'woocommerce'),
                        'default' => $this->method_description,
                        'desc_tip' => true,
                        'css' => 'width: 100% !important;max-width: 400px;',
                    ),
                    'mode' => array(
                        'title' => __('Mode', 'woocommerce'),
                        'type' => 'select',
                        'class' => 'chosen_select',
                        'css' => 'width: 350px;',
                        'desc_tip' => __('Select the mode to accept.', 'woocommerce'),
                        'options' => array(
                            'sandbox' => 'SandBox',
                            'live' => 'Live',
                        ),
                        'default' => array('sandbox')
                    ),
                    'sandbox_merchant_id' => array(
                        'title' => __('Merchant ID', 'woocommerce'),
                        'type' => 'text',
                        'description' => __('<span style="color:red;">Get your Merchant ID from your Braintree account.</span>', 'woocommerce'),
                        'default' => '',
                        'desc_tip' => ''
                    ),
                    'sandbox_public_key' => array(
                        'title' => __('Public Key', 'woocommerce'),
                        'type' => 'text',
                        'description' => __('<span style="color:red;">Get your Public Key from your Braintree account.</span>', 'woocommerce'),
                        'default' => '',
                        'desc_tip' => ''
                    ),
                    'sandbox_private_key' => array(
                        'title' => __('Private Key', 'woocommerce'),
                        'type' => 'text',
                        'description' => __('<span style="color:red;">Get your Private Key from your Braintree account.</span>', 'woocommerce'),
                        'default' => '',
                        'desc_tip' => ''
                    ), 'show_accepted' => array(
                        'title' => __('Show Accepted Card Icons', 'woocommerce'),
                        'type' => 'select',
                        'class' => 'chosen_select',
                        'css' => 'width: 350px;',
                        'desc_tip' => __('Select the mode to accept.', 'woocommerce'),
                        'options' => array(
                            'yes' => 'Yes',
                            'no' => 'No',
                        ),
                        'default' => array('yes'),
                    ),
                    'braintree_cardtypes' => array(
                        'title' => __('Add/Remove Card Types', 'woocommerce'),
                        'type' => 'multiselect',
                        'class' => 'chosen_select',
                        'css' => 'width: 350px;',
                        'desc_tip' => __('Add/Remove credit card types to accept.', 'woocommerce'),
                        'options' => array(
                            'mastercard' => 'MasterCard',
                            'visa' => 'Visa',
                            'discover' => 'Discover',
                            'amex' => 'AMEX',
                            'jcb' => 'JCB'
                        ),
                        'default' => array('mastercard' => 'MasterCard',
                            'visa' => 'Visa',
                            'discover' => 'Discover',
                            'amex' => 'AMEX')
                    )
                );
            }

            /**
             * 
             * @param type $number
             * @return string
             */
            public function get_card_type($number) {
                $number = preg_replace('/[^\d]/', '', $number);

                if (preg_match('/^3[47][0-9]{13}$/', $number)) {
                    $card = 'amex';
                } elseif (preg_match('/^3(?:0[0-5]|[68][0-9])[0-9]{11}$/', $number)) {
                    $card = 'dinersclub';
                } elseif (preg_match('/^6(?:011|5[0-9][0-9])[0-9]{12}$/', $number)) {
                    $card = 'discover';
                } elseif (preg_match('/^(?:2131|1800|35\d{3})\d{11}$/', $number)) {
                    $card = 'jcb';
                } elseif (preg_match('/^5[1-5][0-9]{14}$/', $number)) {
                    $card = 'mastercard';
                } elseif (preg_match('/^4[0-9]{12}(?:[0-9]{3})?$/', $number)) {
                    $card = 'visa';
                } else {
                    $card = 'unknown card';
                }
                return $card;
            }

            /**
             * 
             * @return boolean
             */
            public function is_available() {

                if ($this->enabled == "yes") {

                    if (!$this->mode && is_checkout()) {
                        return false;
                    }
                    // Required fields check
                    if ($this->merchant_id && $this->private_key && $this->public_key) {
                        return true;
                    }
                }
                return false;
            }

            /**
             * 
             * @global type $woocommerce
             * @param type $order_id
             * @return type
             */
            public function process_payment($order_id) {

                global $woocommerce;
                $order = new WC_Order($order_id);
                $order_id_1 = $order->id;

                $public_key = $this->public_key;
                $merchant_id = $this->merchant_id;
                $private_key = $this->private_key;
                if (empty($public_key) || empty($merchant_id) || empty($private_key)) {
                    wc_add_notice('Please Do correct setup of Braintree Payment Gateway', $notice_type = 'error');
                    return array(
                        'result' => 'success',
                        'redirect' => WC()->cart->get_checkout_url(),
                    );
                    die;
                }
                $cardtype = $this->get_card_type(sanitize_text_field(str_replace(' ', '', $_POST['ei_braintree-card-number'])));
                if (!in_array($cardtype, $this->braintree_cardtypes)) {
                    wc_add_notice('Merchant do not support accepting in ' . $cardtype, $notice_type = 'error');
                    return array(
                        'result' => 'success',
                        'redirect' => WC()->cart->get_checkout_url(),
                    );
                    die;
                }

                $card_num = sanitize_text_field(str_replace(' ', '', $_POST['ei_braintree-card-number']));
                $exp_date = explode("/", sanitize_text_field($_POST['ei_braintree-card-expiry']));
                $exp_month = str_replace(' ', '', $exp_date[0]);
                $exp_year = str_replace(' ', '', $exp_date[1]);
                $cvc = sanitize_text_field($_POST['ei_braintree-card-cvc']);


                Braintree_Configuration::environment($this->mode);
                Braintree_Configuration::merchantId($this->merchant_id);
                Braintree_Configuration::publicKey($this->public_key);
                Braintree_Configuration::privateKey($this->private_key);

                $result = Braintree_Transaction::sale(array(
                            "amount" => $order->order_total,
                            'orderId' => $order_id_1,
                            "creditCard" => array(
                                "number" => $card_num,
                                "cvv" => $cvc,
                                "expirationMonth" => $exp_month,
                                "expirationYear" => $exp_year
                            ),
                            "customer" => array(
                                "firstName" => $order->billing_first_name,
                                "lastName" => $order->billing_last_name,
                                "company" => $order->billing_company,
                                "phone" => $order->billing_phone,
                                "email" => $order->billing_email
                            ),
                            "billing" => array(
                                'firstName' => $order->billing_first_name,
                                'lastName' => $order->billing_last_name,
                                'company' => $order->billing_company,
                                'streetAddress' => isset($_POST['billing_address_1']) ? $_POST['billing_address_1'] : '',
                                'extendedAddress' => isset($_POST['billing_address_2']) ? $_POST['billing_address_2'] : '',
                                'locality' => isset($_POST['billing_city']) ? $_POST['billing_city'] : '',
                                'region' => isset($_POST['billing_state']) ? $_POST['billing_state'] : '',
                                'postalCode' => isset($_POST['billing_postcode']) ? $_POST['billing_postcode'] : '',
                                'countryCodeAlpha2' => isset($_POST['billing_country']) ? $_POST['billing_country'] : ''
                            ),
                            'shipping' => array(
                                'firstName' => isset($_POST['billing_first_name']) ? $_POST['billing_first_name'] : '',
                                'lastName' => isset($_POST['billing_last_name']) ? $_POST['billing_last_name'] : '',
                                'company' => isset($_POST['billing_company']) ? $_POST['billing_company'] : '',
                                'streetAddress' => isset(WC()->customer->shipping_address_1) ? WC()->customer->shipping_address_1 : '',
                                'extendedAddress' => isset(WC()->customer->shipping_address_2) ? WC()->customer->shipping_address_2 : '',
                                'locality' => isset(WC()->customer->shipping_city) ? WC()->customer->shipping_city : '',
                                'region' => isset(WC()->customer->shipping_state) ? WC()->customer->shipping_state : '',
                                'postalCode' => isset(WC()->customer->shipping_postcode) ? WC()->customer->shipping_postcode : '',
                                'countryCodeAlpha2' => isset(WC()->customer->shipping_country) ? WC()->customer->shipping_country : ''
                            ),
                            "options" => array(
                                "submitForSettlement" => true
                            )
                ));

                $transaction = $result->transaction;

                $transaction_id = htmlentities($transaction->id);
                if ($result->success) {
                    add_post_meta($order_id_1, '_transaction_id', $transaction_id, true);
                    $timestamp = date('Y-m-d H:i:s e');
                    $order->add_order_note(__('Braintree payment completed at ' . $timestamp . ' with Transcation Id ' . $transaction_id, 'braintree'));
                    $order->payment_complete($transaction_id);
                    $woocommerce->cart->empty_cart();
                    return array(
                        'result' => 'success',
                        'redirect' => $this->get_return_url($order)
                    );
                } else {
                    wc_add_notice($result->message . "<br>", 'error');
                    return array(
                        'result' => 'success',
                        'redirect' => WC()->cart->get_checkout_url(),
                    );
                    die;
                }
            }

            /**
             * 
             * @param type $order_id
             * @return type
             */
            public function restore_stock_braintree($order_id) {
                $order = new WC_Order($order_id);

                $payment_method = get_post_meta($order->id, '_payment_method', true);
                if ($payment_method == 'braintree') {
                    $refund = self::process_refund($order_id, $amount = NULL);
                    if ($refund == TRUE) {
                        if (!get_option('woocommerce_manage_stock') == 'yes' && !sizeof($order->get_items()) > 0) {
                            return;
                        }

                        foreach ($order->get_items() as $item) {

                            if ($item['product_id'] > 0) {

                                $_product = $order->get_product_from_item($item);

                                if ($_product && $_product->exists() && $_product->managing_stock()) {

                                    $old_stock = $_product->stock;

                                    $qty = apply_filters('woocommerce_order_item_quantity', $item['qty'], $this, $item);

                                    $new_quantity = $_product->increase_stock($qty);

                                    $order->add_order_note(sprintf(__('Item #%s stock incremented from %s to %s.', 'woocommerce'), $item['product_id'], $old_stock, $new_quantity));

                                    $order->send_stock_notifications($_product, $new_quantity, $item['qty']);
                                }
                            }
                        }
                    }
                }
            }

            /**
             * 
             * @param type $order_id
             * @return type
             */
            public function restore_stock_braintree_cancel($order_id) {
                $order = new WC_Order($order_id);
                $payment_method = get_post_meta($order->id, '_payment_method', true);
                if ($payment_method == 'braintree') {
                    if (!get_option('woocommerce_manage_stock') == 'yes' && !sizeof($order->get_items()) > 0) {
                        return;
                    }

                    foreach ($order->get_items() as $item) {

                        if ($item['product_id'] > 0) {

                            $_product = $order->get_product_from_item($item);

                            if ($_product && $_product->exists() && $_product->managing_stock()) {

                                $old_stock = $_product->stock;

                                $qty = apply_filters('woocommerce_order_item_quantity', $item['qty'], $this, $item);

                                $new_quantity = $_product->increase_stock($qty);

                                $order->add_order_note(sprintf(__('Item #%s stock incremented from %s to %s.', 'woocommerce'), $item['product_id'], $old_stock, $new_quantity));

                                $order->send_stock_notifications($_product, $new_quantity, $item['qty']);
                            }
                        }
                    }
                }
            }

            /**
             * 
             * @param type $order_id
             * @return type
             */
            public function reduce_stock_braintree($order_id) {
                $order = new WC_Order($order_id);
                $payment_method = get_post_meta($order->id, '_payment_method', true);
                if ($payment_method == 'braintree') {
                    if (!get_option('woocommerce_manage_stock') == 'yes' && !sizeof($order->get_items()) > 0) {
                        return;
                    }

                    foreach ($order->get_items() as $item) {

                        if ($item['product_id'] > 0) {
                            $_product = $order->get_product_from_item($item);

                            if ($_product && $_product->exists() && $_product->managing_stock()) {

                                $old_stock = $_product->stock;

                                $qty = apply_filters('woocommerce_order_item_quantity', $item['qty'], $this, $item);

                                $new_quantity = $_product->reduce_stock($qty);

                                do_action('woocommerce_auto_stock_restored', $_product, $item);

                                $order->add_order_note(sprintf(__('Item #%s stock reduce from %s to %s.', 'woocommerce'), $item['product_id'], $old_stock, $new_quantity));

                                $order->send_stock_notifications($_product, $new_quantity, $item['qty']);
                            }
                        }
                    }
                }
            }

            /**
             * 
             * @param type $order_id
             * @param type $amount
             * @param type $reason
             * @return boolean
             */
            public function process_refund($order_id, $amount = NULL, $reason = '') {

                if ($amount > 0) {
                    $transaction_id = get_post_meta($order_id, '_transaction_id', true);
                    $braintree_setting = get_option('woocommerce_braintree_settings');
                    Braintree_Configuration::environment($braintree_setting['mode']);
                    Braintree_Configuration::merchantId($braintree_setting['sandbox_merchant_id']);
                    Braintree_Configuration::publicKey($braintree_setting['sandbox_public_key']);
                    Braintree_Configuration::privateKey($braintree_setting['sandbox_private_key']);

                    $check_transaction = Braintree_Transaction::find($transaction_id);
                    $wc_order = new WC_Order($order_id);
                    $transaction_status = $check_transaction->status;
                    if ($transaction_status == 'settled') {
                        $result = Braintree_Transaction::refund($transaction_id, $amount);
                        $transaction = $result->transaction;
                        $rtimestamp = date('Y-m-d H:i:s e');
                        $transaction_id = htmlentities($transaction->id);
                        $wc_order->add_order_note(__('Braintree Refund completed at ' . $rtimestamp . ' with Refund ID : ' . $transaction_id, 'woocommerce'));
                        return true;
                    } else {
                        $wc_order->add_order_note(__('Payment is not settled,Please Try After 24 hours after payment'));
                        return false;
                    }
                } else {
                    $transaction_id = get_post_meta($order_id, '_transaction_id', true);
                    $order_total = get_post_meta($order_id, '_order_total', true);
                    $braintree_setting = get_option('woocommerce_braintree_settings');
                    Braintree_Configuration::environment($braintree_setting['mode']);
                    Braintree_Configuration::merchantId($braintree_setting['sandbox_merchant_id']);
                    Braintree_Configuration::publicKey($braintree_setting['sandbox_public_key']);
                    Braintree_Configuration::privateKey($braintree_setting['sandbox_private_key']);

                    $check_transaction = Braintree_Transaction::find($transaction_id);
                    $wc_order = new WC_Order($order_id);
                    $transaction_status = $check_transaction->status;
                    if ($transaction_status == 'settled') {
                        $result = Braintree_Transaction::refund($transaction_id, $order_total);
                        $transaction = $result->transaction;
                        $rtimestamp = date('Y-m-d H:i:s e');
                        $transaction_id = htmlentities($transaction->id);
                        $wc_order->add_order_note(__('Braintree Refund completed at ' . $rtimestamp . ' with Refund ID : ' . $transaction_id, 'woocommerce'));
                        return true;
                    } else {
                        $wc_order->add_order_note(__('Payment is not settled,Please Try After 24 hours after payment'));
                        return false;
                    }
                }
            }

            /* Start of credit card form */

            /**
             * payment_fields
             */
            public function payment_fields() {
                echo apply_filters('wc_braintree_description', wpautop(wp_kses_post(wptexturize(trim($this->method_description)))));
                $this->form();
            }

            /**
             * 
             * @param type $name
             * @return type
             */
            public function field_name($name) {
                return $this->supports('tokenization') ? '' : ' name="ei_' . esc_attr($this->id . '-' . $name) . '" ';
            }

            /**
             * load form
             */
            public function form() {
                wp_enqueue_script('wc-credit-card-form');
                $fields = array();
                $cvc_field = '<p class="form-row form-row-last">
	<label for="ei_braintree-card-cvc">' . __('Card Code', 'woocommerce') . ' <span class="required">*</span></label>
	<input id="ei_braintree-card-cvc" class="input-text wc-credit-card-form-card-cvc" type="text" autocomplete="off" placeholder="' . esc_attr__('CVC', 'woocommerce') . '" ' . $this->field_name('card-cvc') . ' />
</p>';
                $default_fields = array(
                    'card-number-field' => '<p class="form-row form-row-wide">
	<label for="ei_braintree-card-number">' . __('Card Number', 'woocommerce') . ' <span class="required">*</span></label>
	<input id="ei_braintree-card-number" class="input-text wc-credit-card-form-card-number" type="text" maxlength="20" autocomplete="off" placeholder="&bull;&bull;&bull;&bull; &bull;&bull;&bull;&bull; &bull;&bull;&bull;&bull; &bull;&bull;&bull;&bull;" ' . $this->field_name('card-number') . ' />
</p>',
                    'card-expiry-field' => '<p class="form-row form-row-first">
<label for="ei_braintree-card-expiry">' . __('Expiry (MM/YY)', 'woocommerce') . ' <span class="required">*</span></label>
<input id="ei_braintree-card-expiry" class="input-text wc-credit-card-form-card-expiry" type="text" autocomplete="off" placeholder="' . esc_attr__('MM / YY', 'woocommerce') . '" ' . $this->field_name('card-expiry') . ' />
</p>',
                    'card-cvc-field' => $cvc_field
                );

                $fields = wp_parse_args($fields, apply_filters('woocommerce_credit_card_form_fields', $default_fields, $this->id));
                ?>

                <fieldset id="wc-<?php echo esc_attr($this->id); ?>-cc-form" class='wc-credit-card-form wc-payment-form'>
                    <?php do_action('woocommerce_credit_card_form_start', $this->id); ?>
                    <?php
                    foreach ($fields as $field) {
                        echo $field;
                    }
                    ?>
                    <?php do_action('woocommerce_credit_card_form_end', $this->id); ?>
                    <div class="clear"></div>
                </fieldset>
                <?php
            }

            /**
             * check SSL
             */
            public function sslerror_braintree() {
                $html = '<div class="error">';
                $html .= '<p>';
                $html .= __('Please use <b>ssl</b> and activate Force secure checkout to use this plugin');
                $html .= '</p>';
                $html .= '</div>';
                echo $html;
            }

        }

    } else {
        if (!class_exists('WC_Payment_Gateway')) {
            add_action('admin_notices', 'activate_error_braintree');
        }
        deactivate_plugins(plugin_basename(__FILE__));
        return FALSE;
    }
}

/**
 * display error
 */
function activate_error_braintree() {
    $html = '<div class="error">';
    $html .= '<p>';
    $html .= __('Please activate <b>Woocommerce</b> Plugin to use this plugin');
    $html .= '</p>';
    $html .= '</div>';
    echo $html;
}

/**
 * add_custom_js_braintree
 */
function add_custom_js_braintree() {
    wp_enqueue_script('jquery-cc-braintree', plugin_dir_url(__FILE__) . 'js/cc.custom_braintree.js', array('jquery'), '1.0', True);
    wp_enqueue_style('brainrtee-css', plugin_dir_url(__FILE__) . 'css/style.css');
}

$woo_braintree_settings = get_option('woocommerce_braintree_settings');
if (!empty($woo_braintree_settings)) {
    if ($woo_braintree_settings['enabled'] == 'yes' && $woo_braintree_settings['mode'] == 'live' && !is_ssl()) {
        add_action('admin_notices', array('WC_Braintree_Gateway', 'sslerror_braintree'));
    } else {
        remove_action('admin_notices', array('WC_Braintree_Gateway', 'sslerror_braintree'));
    }
}   