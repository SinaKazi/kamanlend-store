<?php
if (!defined('ABSPATH')) exit;

// دکمه و نمایش کد در لیست سفارشات فروشنده (زیرسفارش خودش)
add_action('dokan_seller_order_after_order_status', function ($order, $order_id) {
    $wc_order = wc_get_order($order_id);
    if (!$wc_order) return;

    $seller_id = dokan_get_seller_id_by_order($order_id);
    if (get_current_user_id() !== (int) $seller_id) return;

    $tracking_code = $wc_order->get_meta('_tracking_code');
    $provider = $wc_order->get_meta('_tracking_provider');
    $custom = $wc_order->get_meta('_tracking_custom_name');

    if ($provider === 'post') $provider_name = 'پست';
    elseif ($provider === 'tipax') $provider_name = 'تیپاکس';
    elseif ($provider === 'custom') $provider_name = $custom ?: 'سایر';
    else $provider_name = '—';

    echo '<button class="button kam-add-tracking-btn" data-order-id="' . esc_attr($order_id) . '">کد رهگیری</button>';

    if ($tracking_code) {
        echo '<div style="margin-top: 5px; font-size: 12px;">';
        echo '<strong>کد:</strong> ' . esc_html($tracking_code) . '<br>';
        echo '<strong>روش:</strong> ' . esc_html($provider_name);
        echo '</div>';
    }
}, 10, 2);

// فرم در جزئیات زیرسفارش فروشنده
add_action('dokan_order_details_after_order_items', function ($order) {
    if (!current_user_can('dokandar')) return;

    $order_id = $order->get_id();
    $parent_id = wp_get_post_parent_id($order_id);
    if (!$parent_id) return; // این سفارش مادره، فروشنده نباید ببینه

    $wc_order = wc_get_order($order_id);
    if (!$wc_order) return;

    $seller_id = dokan_get_seller_id_by_order($order_id);
    if (get_current_user_id() !== (int) $seller_id) return;

    $tracking_code = $wc_order->get_meta('_tracking_code');
    $provider = $wc_order->get_meta('_tracking_provider');
    $custom = $wc_order->get_meta('_tracking_custom_name');

    if ($provider === 'post') $provider_name = 'پست';
    elseif ($provider === 'tipax') $provider_name = 'تیپاکس';
    elseif ($provider === 'custom') $provider_name = $custom ?: 'سایر';
    else $provider_name = '—';
    ?>

    <div class="dokan-panel">
        <h3>افزودن / ویرایش کد رهگیری</h3>

        <?php if ($tracking_code): ?>
            <p><strong>کد فعلی:</strong> <?= esc_html($tracking_code) ?></p>
            <p><strong>روش ارسال:</strong> <?= esc_html($provider_name) ?></p>
            <hr>
        <?php endif; ?>

        <form id="kam-tracking-form-detail">
            <input type="hidden" name="order_id" value="<?= esc_attr($order_id) ?>" />
            <label>کد رهگیری:</label>
            <input type="text" name="tracking_code" value="<?= esc_attr($tracking_code) ?>" required />

            <label>روش ارسال:</label>
            <select name="tracking_provider" id="tracking_provider_detail">
                <option value="post" <?= $provider == 'post' ? 'selected' : '' ?>>پست</option>
                <option value="tipax" <?= $provider == 'tipax' ? 'selected' : '' ?>>تیپاکس</option>
                <option value="custom" <?= $provider == 'custom' ? 'selected' : '' ?>>سایر...</option>
            </select>

            <input type="text" name="custom_name" id="custom_name_field_detail" placeholder="نام روش ارسال"
                   value="<?= esc_attr($custom) ?>" <?= $provider == 'custom' ? '' : 'style="display:none;"' ?> />

            <button type="submit">ذخیره</button>
        </form>
    </div>
<style>
    input#custom_name_field {
    margin-top: 8px;
    margin-bottom: 8px;
}

</style>
    <script>
        jQuery(document).ready(function($) {
            $('#tracking_provider_detail').on('change', function () {
                if ($(this).val() === 'custom') {
                    $('#custom_name_field_detail').show();
                } else {
                    $('#custom_name_field_detail').hide();
                }
            });

            $('#kam-tracking-form-detail').on('submit', function (e) {
                e.preventDefault();
                const data = $(this).serialize();
                $.post(ajaxurl, {
                    action: 'kam_save_tracking_code',
                    ...Object.fromEntries(new URLSearchParams(data))
                }, function (res) {
                    if (res.success) {
                        alert('ذخیره شد!');
                        location.reload();
                    } else {
                        alert('خطا: ' + res.data);
                    }
                });
            });
        });
    </script>
    <?php
});

// ذخیره‌سازی کد رهگیری (زیرسفارش فروشنده)
add_action('wp_ajax_kam_save_tracking_code', function () {
    $order_id = absint($_POST['order_id']);
    $code = sanitize_text_field($_POST['tracking_code']);
    $provider = sanitize_text_field($_POST['tracking_provider']);
    $custom_name = sanitize_text_field($_POST['custom_name']);

    if (!current_user_can('dokandar')) wp_send_json_error('Unauthorized');

    $order = wc_get_order($order_id);
    if (!$order) wp_send_json_error('Order not found');

    $parent_id = wp_get_post_parent_id($order_id);
    if (!$parent_id) wp_send_json_error('This is not a sub-order');

    $seller_id = dokan_get_seller_id_by_order($order_id);
    if (get_current_user_id() !== (int) $seller_id) wp_send_json_error('Not your order');

    $order->update_meta_data('_tracking_code', $code);
    $order->update_meta_data('_tracking_provider', $provider);
    $order->update_meta_data('_tracking_custom_name', $provider === 'custom' ? $custom_name : '');
    $order->save();

    wp_send_json_success('کد رهگیری ذخیره شد');
});


add_action('dokan_order_content_inside_after', function () {
    global $wp;

    if (!isset($wp->query_vars['orders']) || !isset($_GET['order_id'])) return;

    $order_id = absint($_GET['order_id']);
    $order = wc_get_order($order_id);
    if (!$order || !current_user_can('dokandar')) return;

    // ذخیره‌سازی در صورت ارسال فرم
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['tracking_code'])) {
        $tracking_code = sanitize_text_field($_POST['tracking_code']);
        $provider = sanitize_text_field($_POST['tracking_provider']);
        $custom = sanitize_text_field($_POST['custom_name']);

        $order->update_meta_data('_tracking_code', $tracking_code);
        $order->update_meta_data('_tracking_provider', $provider);
        $order->update_meta_data('_tracking_custom_name', $provider === 'custom' ? $custom : '');
        $order->save();

        echo '<div style="color:green;">✅ کد رهگیری ذخیره شد.</div>';
    }

    // مقادیر فعلی
    $tracking_code = $order->get_meta('_tracking_code');
    $provider = $order->get_meta('_tracking_provider');
    $custom = $order->get_meta('_tracking_custom_name');

    if ($provider === 'post') $provider_name = 'پست';
    elseif ($provider === 'tipax') $provider_name = 'تیپاکس';
    elseif ($provider === 'custom') $provider_name = $custom ?: 'سایر';
    else $provider_name = '';

    echo '<div style="padding:10px; background:#eef; margin-top:20px;">';
    echo '<strong>فرم کد رهگیری</strong>';
    echo '<form method="post">';
    echo '<label>کد رهگیری:</label>';
    echo '<input type="text" name="tracking_code" value="' . esc_attr($tracking_code) . '" required>';

    echo '<label>روش ارسال:</label>';
    echo '<select name="tracking_provider" id="tracking_provider">';
    echo '<option value="post"' . selected($provider, 'post', false) . '>پست</option>';
    echo '<option value="tipax"' . selected($provider, 'tipax', false) . '>تیپاکس</option>';
    echo '<option value="custom"' . selected($provider, 'custom', false) . '>سایر</option>';
    echo '</select>';

    echo '<input type="text" name="custom_name" placeholder="نام روش ارسال" id="custom_name_field" value="' . esc_attr($custom) . '" ' . ($provider === 'custom' ? '' : 'style="display:none;"') . '>';

    echo '<button type="submit">ذخیره</button>';
    echo '</form>';
    echo '</div>';

    // JS برای نمایش فیلد سایر
    echo <<<JS
<script>
document.getElementById('tracking_provider').addEventListener('change', function () {
    const val = this.value;
    const customField = document.getElementById('custom_name_field');
    if (val === 'custom') {
        customField.style.display = 'block';
    } else {
        customField.style.display = 'none';
    }
});
</script>
JS;
});

add_action('woocommerce_order_details_after_order_table', function ($order) {
    if (!is_account_page() || !is_user_logged_in()) return;

    $sub_orders = get_children([
        'post_parent' => $order->get_id(),
        'post_type'   => 'shop_order',
        'post_status' => ['wc-completed', 'wc-processing', 'wc-on-hold', 'wc-pending'],
    ]);

    if (!$sub_orders) return;

    echo '<section class="woocommerce-order-tracking">';
    echo '<h3>اطلاعات رهگیری سفارش‌ها</h3>';

    foreach ($sub_orders as $sub) {
        $sub_order = wc_get_order($sub->ID);
        if (!$sub_order) continue;

        $tracking_code = $sub_order->get_meta('_tracking_code');
        $provider = $sub_order->get_meta('_tracking_provider');
        $custom = $sub_order->get_meta('_tracking_custom_name');

        if (!$tracking_code) continue;

        if ($provider === 'post') {
            $provider_name = 'پست';
            $link = 'https://tracking.post.ir/?id=' . urlencode($tracking_code);
        } elseif ($provider === 'tipax') {
            $provider_name = 'تیپاکس';
            $link = 'https://tipaxco.com/tracking?id=' . urlencode($tracking_code);
        } elseif ($provider === 'custom') {
            $provider_name = $custom ?: 'سایر';
            $link = '';
        } else {
            $provider_name = '—';
            $link = '';
        }

        echo '<div style="margin:10px 0; padding:10px; background:#f7f7f7; border:1px solid #ccc;">';
        echo '<strong>زیرسفارش #' . esc_html($sub->ID) . '</strong><br>';
        echo 'روش ارسال: <strong>' . esc_html($provider_name) . '</strong><br>';

        if ($link) {
            echo 'کد رهگیری: <a href="' . esc_url($link) . '" target="_blank" style="color:#0073aa;">' . esc_html($tracking_code) . '</a>';
        } else {
            echo 'کد رهگیری: <strong>' . esc_html($tracking_code) . '</strong>';
        }

        echo '</div>';
    }

    echo '</section>';
});

///
add_action('woocommerce_admin_order_data_after_order_details', function ($order) {
    $order_id = $order->get_id();

    $tracking_code     = get_post_meta($order_id, '_tracking_code', true);
    $tracking_provider = get_post_meta($order_id, '_tracking_provider', true);
    $custom_name       = get_post_meta($order_id, '_tracking_custom_name', true);

    ?>
    <div class="order_data_column_">
        <h4>کد رهگیری</h4>
        <p class="form-field">
            <label for="tracking_code">کد رهگیری</label>
            <input type="text" name="tracking_code" id="tracking_code" value="<?php echo esc_attr($tracking_code); ?>">
        </p>
        <p class="form-field">
            <label for="tracking_provider">روش ارسال</label>
            <select name="tracking_provider" id="tracking_provider">
                <option value="">— انتخاب کنید —</option>
                <option value="post" <?php selected($tracking_provider, 'post'); ?>>پست</option>
                <option value="tipax" <?php selected($tracking_provider, 'tipax'); ?>>تیپاکس</option>
                <option value="custom" <?php selected($tracking_provider, 'custom'); ?>>سایر</option>
            </select>
        </p>
        <p class="form-field">
            <label for="tracking_custom_name">نام روش ارسال (در صورت انتخاب "سایر")</label>
            <input type="text" name="tracking_custom_name" id="tracking_custom_name" value="<?php echo esc_attr($custom_name); ?>">
        </p>
    </div>
    <?php
});

add_action('woocommerce_process_shop_order_meta', function ($order_id) {
    if (isset($_POST['tracking_code'])) {
        update_post_meta($order_id, '_tracking_code', sanitize_text_field($_POST['tracking_code']));
    }

    if (isset($_POST['tracking_provider'])) {
        update_post_meta($order_id, '_tracking_provider', sanitize_text_field($_POST['tracking_provider']));
    }

    if (isset($_POST['tracking_custom_name'])) {
        update_post_meta($order_id, '_tracking_custom_name', sanitize_text_field($_POST['tracking_custom_name']));
    }
});
