<?php
// add_action('woocommerce_cart_calculate_fees', function ($cart) {
//     // فقط در بخش front-end و فقط زمانی که سبد خرید مقدار داشته باشد
//     if (is_admin() || is_cart() || !is_checkout() || !WC()->session || !WC()->cart) return;

//     // گرفتن درگاه انتخاب‌شده
//     $gateway = isset($_POST['payment_method'])
//         ? sanitize_text_field($_POST['payment_method'])
//         : WC()->session->get('chosen_payment_method');

//     if (!$gateway) return;

//     $percent = floatval(get_option("installment_fee_percent_$gateway", 0));
//     if ($percent <= 0) return;

//     $label = get_option("installment_fee_label_$gateway", 'کارمزد خرید اقساطی');
//     $cart_total = WC()->cart->get_subtotal(); // بدون مالیات و حمل‌ونقل

//     $fee_amount = ($cart_total * $percent) / 100;
//     if ($fee_amount > 0.01) {
//         $cart->add_fee($label, $fee_amount, true);
//     }
// }, 20, 1);


add_action('woocommerce_cart_calculate_fees', function () {
    if (is_admin() && !defined('DOING_AJAX')) return;

    $chosen_gateway = WC()->session->get('chosen_payment_method');
    if (!$chosen_gateway) return;

    $percent = floatval(get_option("installment_fee_percent_$chosen_gateway", 0));
    if ($percent <= 0) return;

    $label = get_option("installment_fee_label_$chosen_gateway", 'کارمزد خرید اقساطی');
    $cart_total = WC()->cart->get_subtotal(); // قبل از حمل‌ونقل

    $fee_amount = ($cart_total * $percent) / 100;
    if ($fee_amount > 0) {
        WC()->cart->add_fee($label, $fee_amount, true);
    }
}, 20, 1);
