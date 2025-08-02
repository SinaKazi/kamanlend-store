<?php

// ✅ صفحه تنظیمات و مدیریت شماره فاکتور و کد ملی

function kam_render_invoice_settings_page() {
    if (isset($_POST['kam_invoice_save'])) {
        foreach ($_POST as $key => $val) {
            if (strpos($key, 'kam_invoice_') === 0) {
                update_option($key, sanitize_text_field($val));
            }
        }
        echo '<div class="updated"><p>تنظیمات با موفقیت ذخیره شد.</p></div>';
    }

    $fields = [
        'seller_name' => 'نام فروشنده',
        'register_code' => 'شماره ثبت',
        'economic_code' => 'کد اقتصادی',
        'national_id' => 'شناسه ملی',
        'phone' => 'تلفن',
        'address' => 'آدرس کامل',
        'postal_code' => 'کد پستی',
        'state' => 'استان',
        'city' => 'شهرستان'
    ];

    echo '<div class="wrap"><h1>تنظیمات فاکتور رسمی</h1><form method="post">';

    foreach ($fields as $key => $label) {
        $value = esc_attr(get_option('kam_invoice_' . $key, ''));
        echo "<p><label for='kam_invoice_$key'><strong>$label:</strong></label><br>";
        echo "<input type='text' id='kam_invoice_$key' name='kam_invoice_$key' value='$value' class='regular-text'></p>";
    }

    echo '<p><input type="submit" name="kam_invoice_save" class="button button-primary" value="ذخیره تنظیمات"></p>';
    echo '</form></div>';
}

add_action('admin_menu', function() {
    add_menu_page('کمان استور', 'کمان استور', 'manage_options', 'kam-invoice-settings', 'kam_render_invoice_settings_page', 'dashicons-media-document');
});

//✅ افزودن کد ملی به صفحه تسویه حساب
add_filter('woocommerce_checkout_fields', function($fields) {
    $fields['billing']['billing_national_code'] = [
        'label' => 'کد ملی',
        'placeholder' => 'مثلاً: ۰۰۰۰۰۰۰۰۰۰',
        'required' => true,
        'class' => ['form-row-wide'],
        'priority' => 120,
    ];
    return $fields;
});

// ✅ نمایش کد ملی در پنل ادمین
add_filter('woocommerce_admin_billing_fields', function($fields) {
    $fields['national_code'] = [
        'label' => 'کد ملی',
        'show' => true,
        'editable' => true,
    ];
    return $fields;
});

add_action('woocommerce_admin_order_data_after_billing_address', function($order) {
    $national_code = $order->get_meta('billing_national_code');
    if ($national_code) {
        echo '<p><strong>کد ملی:</strong> ' . esc_html($national_code) . '</p>';
    }
});

// ✅ فیلد قابل ویرایش برای شماره فاکتور در پنل مدیریت + دکمه حذف
add_action('woocommerce_admin_order_data_after_order_details', function($order) {
    $invoice_number = $order->get_meta('_kam_invoice_number');
    $order_id = $order->get_id();
    echo '<p><label><strong>شماره فاکتور: </strong></label>';
    echo '<input type="text" name="_kam_invoice_number" value="' . esc_attr($invoice_number) . '" style="width:150px;">';
    echo ' <a href="' . esc_url(admin_url("post.php?post=$order_id&action=edit&delete_invoice=1")) . '" class="button" onclick="return confirm(\'آیا از حذف شماره فاکتور مطمئن هستید؟\')">حذف</a>';
    echo '</p>';
});

// ✅ حذف شماره فاکتور از سفارش در صورت درخواست
add_action('load-post.php', function() {
    if (! current_user_can('manage_woocommerce')) return;
    if (! isset($_GET['post'], $_GET['delete_invoice'])) return;
    $order_id = absint($_GET['post']);
    $order = wc_get_order($order_id);
    if ($order) {
        $order->delete_meta_data('_kam_invoice_number');
        $order->save();
        wp_safe_redirect(admin_url("post.php?post=$order_id&action=edit"));
        exit;
    }
});

add_action('woocommerce_process_shop_order_meta', function($order_id) {
    if (isset($_POST['_kam_invoice_number'])) {
        $new_value = sanitize_text_field($_POST['_kam_invoice_number']);

        $args = [
            'limit'  => -1,
            'status' => ['processing'],
            'return' => 'ids',
        ];
        $orders = wc_get_orders($args);
        foreach ($orders as $oid) {
            if ($oid != $order_id && get_post_meta($oid, '_kam_invoice_number', true) === $new_value) {
                return;
            }
        }

        update_post_meta($order_id, '_kam_invoice_number', $new_value);
    }
});

// ✅ تولید شماره فاکتور در اولین ورود به حالت درحال انجام (با سازگاری HPO)
// add_action('woocommerce_order_status_processing', function($order_id) {
//     $order = wc_get_order($order_id);
//     if (!$order) return;

//     $invoice_number = $order->get_meta('_kam_invoice_number');
//     if (!$invoice_number) {
//         global $wpdb;
//         $last = $wpdb->get_var("SELECT MAX(CAST(meta_value AS UNSIGNED)) FROM {$wpdb->postmeta} WHERE meta_key = '_kam_invoice_number'");
//         $next_number = str_pad(intval($last) + 1, 10, '0', STR_PAD_LEFT);
//         $order->update_meta_data('_kam_invoice_number', $next_number);
//         $order->save();
//     }
// });

add_action('woocommerce_order_status_processing', function($order_id) {
    $order = wc_get_order($order_id);
    if (!$order) return;

    // جلوگیری از صدور فاکتور برای زیرسفارش‌های دکان
    $parent_id = wp_get_post_parent_id($order_id);
    if ($parent_id) return; // اگر سفارش زیرسفارش باشد، خروج

    $invoice_number = $order->get_meta('_kam_invoice_number');
    if (!$invoice_number) {
        global $wpdb;
        $last = $wpdb->get_var("SELECT MAX(CAST(meta_value AS UNSIGNED)) FROM {$wpdb->postmeta} WHERE meta_key = '_kam_invoice_number'");
        $next_number = str_pad(intval($last) + 1, 10, '0', STR_PAD_LEFT);
        $order->update_meta_data('_kam_invoice_number', $next_number);
        $order->save();
        error_log("✅ شماره فاکتور ثبت شد: $next_number برای سفارش $order_id");
    }
});


add_action('wp_footer', function () {
    if (is_checkout()) :
        ?>
        <script>
        function checkCodeMeli(code) {
            var L = code.length;
            if (L < 8 || parseInt(code, 10) == 0) return false;
            code = ('0000' + code).substr(L + 4 - 10);
            if (parseInt(code.substr(3, 6), 10) == 0) return false;
            var c = parseInt(code.substr(9, 1), 10);
            var s = 0;
            for (var i = 0; i < 9; i++)
                s += parseInt(code.substr(i, 1), 10) * (10 - i);
            s = s % 11;
            return (s < 2 && c == s) || (s >= 2 && c == (11 - s));
        }

        document.addEventListener('DOMContentLoaded', function () {
            const input = document.querySelector('#billing_national_code');
            if (!input) return;

            const form = document.querySelector('form.checkout');
            const errorDiv = document.createElement('div');
            errorDiv.style.color = 'red';
            errorDiv.style.marginTop = '5px';
            input.parentNode.appendChild(errorDiv);

            input.addEventListener('blur', function () {
                const code = input.value.trim();
                if (!checkCodeMeli(code)) {
                    errorDiv.textContent = 'کد ملی وارد شده معتبر نیست.';
                    input.classList.add('woocommerce-invalid');
                } else {
                    errorDiv.textContent = '';
                    input.classList.remove('woocommerce-invalid');
                }
            });

            form.addEventListener('submit', function (e) {
                const code = input.value.trim();
                if (!checkCodeMeli(code)) {
                    errorDiv.textContent = 'کد ملی وارد شده معتبر نیست.';
                    input.classList.add('woocommerce-invalid');
                    e.preventDefault();
                }
            });
        });
        </script>
        <?php
    endif;
});
