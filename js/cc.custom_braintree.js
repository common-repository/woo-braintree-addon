jQuery(document).ready(function () {
    if (jQuery('input[type=radio][name=payment_method]:checked').attr('id') == 'payment_method_braintree') {
        if (document.getElementById('braintree-card-number') != null) {
            setTimeout(braintree_add_required, 1000);
        }
    } else {
        braintree_remove_required();
    }
    jQuery('input[name="payment_method"]').load('change', function () {
        var checked = jQuery('input[type=radio][name=payment_method]:checked').attr('id');
        if (checked == 'payment_method_braintree') {
            if (jQuery("#payment_method_braintree").is(':checked')) {
                setTimeout(braintree_add_required, 1000);
            }
        } else {
            braintree_remove_required();
        }
    });
    jQuery('input[name="payment_method"]').live('change', function () {
        if (this.id == 'payment_method_braintree') {
            if (jQuery("#payment_method_braintree").is(':checked')) {
                setTimeout(braintree_add_required, 1000);
            }
        } else {
            braintree_remove_required();
        }
    });
});

function braintree_add_required() {
    jQuery('#ei_braintree-card-number').prop('required', true);
    jQuery('#ei_braintree-card-expiry').prop('required', true);
    jQuery('#ei_braintree-card-cvc').prop('required', true);
    return true;
}

function braintree_remove_required() {
    jQuery('#ei_braintree-card-number').prop('required', false);
    jQuery('#ei_braintree-card-expiry').prop('required', false);
    jQuery('#ei_braintree-card-cvc').prop('required', false);
    jQuery('.cc-braintree').css('box-shadow', 'none');
    return true;
}
