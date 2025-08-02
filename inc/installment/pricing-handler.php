<?php

// نمایش JSON قیمت‌ها + باکس‌های پرداخت
add_action('woocommerce_before_add_to_cart_button', function () {
    global $product;
    if (!$product || !$product->is_type(['variable', 'simple'])) return;

    $product_id = $product->get_id();
    $disabled = get_post_meta($product_id, '_disable_installment', true);
    if ($disabled === 'yes') return;

    $default_percent = floatval(get_option('kam_installment_percent', 20));
    $custom_percent = get_post_meta($product_id, '_custom_installment_percent', true);
    $percent = is_numeric($custom_percent) ? floatval($custom_percent) : $default_percent;

    $prices = [];

    if ($product->is_type('variable')) {
        foreach ($product->get_available_variations() as $variation) {
            $vid = $variation['variation_id'];
            $v_product = wc_get_product($vid);
            $v_price = floatval($v_product->get_price());
            $prices[$vid] = [
                'cash' => wc_price($v_price),
                'installment' => wc_price($v_price * (1 + $percent / 100)),
            ];
        }
    } else {
        $v_price = floatval($product->get_price());
        $prices['simple'] = [
            'cash' => wc_price($v_price),
            'installment' => wc_price($v_price * (1 + $percent / 100)),
        ];
    }

    echo '<div id="kam-installment-prices" data-prices=\'' . json_encode($prices, JSON_UNESCAPED_UNICODE) . '\' style="display:none"></div>';
    ?>
    <style>
        .kam-payment-options {
            margin-bottom: 20px;
        }
        .kam-payment-box {
            border: 2px solid #ccc;
            border-radius: 8px;
            padding: 15px 20px;
            margin-bottom: 10px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            cursor: pointer;
            transition: all 0.3s ease;
            opacity: 0.5;
            pointer-events: none;
        }
        .kam-payment-box:hover {
            border-color: #0071a1;
            background-color: #f7fafd;
        }
        .kam-payment-box input[type="radio"] {
            display: none;
        }
        .kam-payment-box.active {
            border-color: #0071a1;
            background-color: #eaf6fb;
        }
        .kam-payment-box.enabled {
            opacity: 1;
            pointer-events: auto;
        }
        .kam-payment-left {
            font-weight: bold;
        }
        .kam-payment-right {
            text-align: right;
        }
        .kam-payment-sub {
            font-size: 13px;
            color: #777;
        }
    </style>

    <div class="kam-payment-options">
        <p><strong>روش‌های پرداخت:</strong></p>

        <label class="kam-payment-box kam-box-cash">
            <input type="radio" name="kam_payment_type" value="cash" disabled>
            <div class="kam-payment-right" id="kam-cash-label">خرید نقدی</div>
            <div class="kam-payment-left" id="kam-cash-price">---</div>
        </label>

        <label class="kam-payment-box kam-box-installment">
            <input type="radio" name="kam_payment_type" value="installment" disabled>
            <div class="kam-payment-right" id="kam-installment-label">خرید قسطی</div>
            <div class="kam-payment-left" id="kam-installment-price">
                ---<br><span class="kam-payment-sub"><a href="https://app.kamanlend.ir/" target="_blank">توسط کمان‌لند</a></span>
            </div>
        </label>
    </div>
    <?php
});

add_filter('woocommerce_add_to_cart_validation', function (
    $passed,
    $product_id,
    $quantity,
    $variation_id = '',
    $variations = '',
    $cart_item_data = []
) {
    $new_type = $_POST['kam_payment_type'] ?? $cart_item_data['kam_payment_type'] ?? null;

    if (!$new_type) return $passed;

    foreach (WC()->cart->get_cart() as $item) {
        if (isset($item['kam_payment_type']) && $item['kam_payment_type'] !== $new_type) {
            wc_add_notice('نمی‌توانید محصولات نقدی و قسطی را با هم در سبد خرید داشته باشید.', 'error');
            return false;
        }
    }

    return $passed;
}, 10, 6);

add_filter('woocommerce_add_cart_item_data', function ($cart_item_data, $product_id, $variation_id) {
    if (isset($_POST['kam_payment_type'])) {
        $cart_item_data['kam_payment_type'] = sanitize_text_field($_POST['kam_payment_type']);
    }
    return $cart_item_data;
}, 10, 3);
