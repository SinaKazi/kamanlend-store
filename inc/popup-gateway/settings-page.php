<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

function kamanlend_popup_gateway_settings_page() {
    ?>
    <div class="wrap">
        <h1>تنظیمات پاپ‌آپ درگاه‌های پرداخت</h1>

        <form method="post" action="options.php">
            <?php
            settings_fields('kamanlend_popup_gateway_options_group'); 
            do_settings_sections('kamanlend_popup_gateway_settings_page');
            ?>
            
            <table class="form-table">
                <?php
                $payment_gateways = WC()->payment_gateways->get_available_payment_gateways();
                foreach ($payment_gateways as $gateway) {
                    $popup_text = get_option('popup_text_' . $gateway->id, '');  // Get stored popup text
                    $is_popup_enabled = get_option('popup_enabled_' . $gateway->id, 'no'); // Check if popup is enabled
                    ?>
                    <tr>
                        <th scope="row"><?php echo esc_html($gateway->get_title()); ?></th>
                        <td>
                            <textarea name="popup_text_<?php echo esc_attr($gateway->id); ?>" rows="5" cols="50"><?php echo esc_textarea($popup_text); ?></textarea>
                            <br/>
                            <label>
                                <input type="checkbox" name="popup_enabled_<?php echo esc_attr($gateway->id); ?>" value="yes" <?php checked($is_popup_enabled, 'yes'); ?> />
                                فعال بودن پاپ‌آپ
                            </label>
                        </td>
                    </tr>
                    <?php
                }
                ?>
            </table>

            <?php submit_button('ذخیره تنظیمات'); ?>
        </form>
    </div>
    <?php
}

// Register the settings
function kamanlend_popup_gateway_register_settings() {
    $payment_gateways = WC()->payment_gateways->get_available_payment_gateways();
    
    foreach ($payment_gateways as $gateway) {
        register_setting('kamanlend_popup_gateway_options_group', 'popup_text_' . $gateway->id);
        register_setting('kamanlend_popup_gateway_options_group', 'popup_enabled_' . $gateway->id);
    }
}

add_action('admin_init', 'kamanlend_popup_gateway_register_settings');


// Add menu item for settings page
function kamanlend_popup_gateway_menu() {
    add_submenu_page(
        'woocommerce',
        'پاپ‌آپ درگاه‌ها',
        'پاپ‌آپ درگاه‌ها',
        'manage_options',
        'kamanlend-popup-gateway',
        'kamanlend_popup_gateway_settings_page'
    );
}
add_action('admin_menu', 'kamanlend_popup_gateway_menu');
