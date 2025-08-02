<?php
// صفحه تنظیمات کارمزد درگاه‌ها
add_action('admin_menu', function () {
    add_submenu_page(
        'woocommerce',
        'تنظیمات کارمزد اقساطی',
        'کارمزد اقساطی',
        'manage_options',
        'installment-fee-settings',
        'render_installment_fee_settings_page'
    );
});

function render_installment_fee_settings_page() {
    if (!current_user_can('manage_options')) return;

    if (isset($_POST['installment_fee_save'])) {
        check_admin_referer('installment_fee_save_action');
        $gateways = WC()->payment_gateways->payment_gateways();
        foreach ($gateways as $gateway_id => $gateway) {
            $percent = isset($_POST["percent_$gateway_id"]) ? floatval($_POST["percent_$gateway_id"]) : 0;
            $label = isset($_POST["label_$gateway_id"]) ? sanitize_text_field($_POST["label_$gateway_id"]) : 'کارمزد خرید اقساطی';
            update_option("installment_fee_percent_$gateway_id", $percent);
            update_option("installment_fee_label_$gateway_id", $label);
        }
        echo '<div class="updated"><p>تنظیمات ذخیره شد.</p></div>';
    }

    $gateways = WC()->payment_gateways->payment_gateways();
    ?>
    <div class="wrap">
        <h1>تنظیمات کارمزد درگاه‌های پرداخت</h1>
        <form method="post">
            <?php wp_nonce_field('installment_fee_save_action'); ?>
            <table class="widefat">
                <thead>
                    <tr>
                        <th>درگاه</th>
                        <th>درصد کارمزد (%)</th>
                        <th>عنوان نمایشی</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($gateways as $gateway_id => $gateway): ?>
                        <tr>
                            <td><?php echo esc_html($gateway->get_title()); ?> (<?php echo esc_html($gateway_id); ?>)</td>
                            <td><input type="number" step="0.01" name="percent_<?php echo $gateway_id; ?>" value="<?php echo esc_attr(get_option("installment_fee_percent_$gateway_id", 0)); ?>" /></td>
                            <td><input type="text" name="label_<?php echo $gateway_id; ?>" value="<?php echo esc_attr(get_option("installment_fee_label_$gateway_id", 'کارمزد خرید اقساطی')); ?>" /></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <br>
            <input type="submit" name="installment_fee_save" class="button-primary" value="ذخیره تنظیمات">
        </form>
    </div>
    <?php
}
