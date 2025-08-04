<?php
/*
Plugin Name: Kamanlend Store
Description: ویژگی های جدید سایت کمان استور
Version: 7.0
Author: Sina Kazemi
*/

if ( ! defined( 'ABSPATH' ) ) exit;

// بارگذاری فایل‌های عملکردی پلاگین
require_once plugin_dir_path(__FILE__) . 'jdf.php';
require_once plugin_dir_path(__FILE__) . 'inc/price-list/limit-selles.php';
require_once plugin_dir_path(__FILE__) . 'inc/admin-menus.php';
require_once plugin_dir_path(__FILE__) . 'inc/price-list/ajax-handlers.php';
require_once plugin_dir_path(__FILE__) . 'inc/woocommerce-invoice-viewer/woocommerce-invoice-viewer.php';
require_once __DIR__ . '/inc/woocommerce-invoice-viewer/settings.php';
//require_once __DIR__ . '/inc/installment/settings.php';
//require_once __DIR__ . '/inc/installment/product-meta.php';
//require_once __DIR__ . '/inc/installment/pricing-handler.php';
require_once plugin_dir_path(__FILE__) . '/inc/shortcode/report-excel.php';
require_once plugin_dir_path(__FILE__) . 'inc/order-tracking.php';
require_once plugin_dir_path(__FILE__) . 'inc/installment-fee-settings.php';
require_once plugin_dir_path(__FILE__) . 'inc/installment-fee-handler.php';
require_once plugin_dir_path(__FILE__) . 'inc/feature-template/admin.php';
require_once plugin_dir_path(__FILE__) . 'inc/feature-template/ajax-handlers.php';
require_once plugin_dir_path(__FILE__) . 'inc/price-list/price-import.php';
require_once plugin_dir_path(__FILE__) . 'inc/popup-gateway/settings-page.php';
require_once plugin_dir_path(__FILE__) . 'inc/popup-gateway/functions.php';

// Load JS
add_action('wp_enqueue_scripts', function () {
    if (dokan_is_seller_dashboard()) {
        wp_enqueue_script('kam-tracking', plugin_dir_url(__FILE__) . 'assets/order-tracking.js', ['jquery'], false, true);
    }
});
// مسیر دانلود فایل Excel
add_action('init', function () {
    if (isset($_GET['kam_download_excel']) && $_GET['kam_download_excel'] == '1') {
        include_once plugin_dir_path(__FILE__) . 'inc/shortcode/kam-download-excel.php';
        exit;
    }
});

// بارگذاری jQuery و تنظیم ajaxurl
function kamanlend_enqueue_scripts() {
    wp_enqueue_script('jquery');
    wp_localize_script('jquery', 'ajaxurlObject', [
        'ajaxurl' => admin_url('admin-ajax.php')
    ]);
}
add_action('admin_enqueue_scripts', 'kamanlend_enqueue_scripts');
//add_action('wp_enqueue_scripts', 'kamanlend_enqueue_scripts');

// مسیر اختصاصی برای لیست قیمت
add_filter('dokan_query_var_filter', function ($query_vars) {
    $query_vars['price-list'] = 'price-list';
    return $query_vars;
}, 99);

add_action('wp_enqueue_scripts', function () {
    wp_enqueue_script('jquery');

    // اگر صفحه price-list بود → فقط price-list.js
    if (
        get_query_var('price-list') !== '' ||
        isset($_GET['price-list']) ||
        strpos($_SERVER['REQUEST_URI'], 'price-list') !== false
    ) {
        wp_enqueue_script(
            'price-list-js',
            plugins_url('assets/price-list.js', __FILE__),
            ['jquery'],
            null,
            true
        );

        wp_localize_script('price-list-js', 'ajaxurlObject', [
            'ajaxurl' => admin_url('admin-ajax.php')
        ]);

    } elseif (is_product()) {
        // اگر صفحه محصول بود → installment-price.js
        // wp_enqueue_script(
        //     'kam-installment-price',
        //     plugins_url('assets/installment-price.js', __FILE__),
        //     ['jquery'],
        //     '1.0',
        //     true
        // );

        wp_localize_script('kam-installment-price', 'ajaxurlObject', [
            'ajaxurl' => admin_url('admin-ajax.php')
        ]);

    } else {
        // در صفحات دیگر فقط ajaxurl برای jQuery
        wp_localize_script('jquery', 'ajaxurlObject', [
            'ajaxurl' => admin_url('admin-ajax.php')
        ]);
    }
});




add_filter('template_include', function ($template) {
    global $wp_query;
    if (get_query_var('price-list') !== '') {
        return plugin_dir_path(__FILE__) . 'inc/templates/price-list-wrapper.php';
    }
    return $template;
});

add_action('wp_enqueue_scripts', function () {
    if (is_checkout() && !is_order_received_page()) {
        wp_enqueue_script(
            'installment-fee-js',
            plugin_dir_url(__FILE__) . 'assets/installment-fee.js',
            ['jquery'],
            '1.0',
            true
        );
    }
});

add_action('admin_enqueue_scripts', function ($hook) {
    if (in_array($hook, ['post.php', 'post-new.php'])) {
        wp_enqueue_script(
            'kam-feature-template-js',
            plugin_dir_url(__FILE__) . 'assets/feature-template.js',
            ['jquery'],
            '1.1',
            true
        );
        wp_localize_script('kam-feature-template-js', 'kamFeatureTemplate', [
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('load_feature_template_nonce'),
        ]);
    }
});


add_action('admin_enqueue_scripts', function ($hook) {
    if ($hook === 'edit.php' || $hook === 'post.php' || $hook === 'post-new.php') {
        wp_enqueue_script('select2');
        wp_enqueue_style('select2');
    }
});


// نمایش فیلتر فروشندگان در صفحه لیست محصولات دکان
add_action('restrict_manage_posts', 'filter_products_by_seller', 20);

function filter_products_by_seller() {
    global $typenow;

    if ($typenow !== 'product') return;

    // دریافت لیست فروشندگان از دکان
    $vendors = dokan_get_sellers();
    $current_vendor = isset($_GET['dokan_vendor']) ? $_GET['dokan_vendor'] : '';

    // نمایش فیلتر فروشندگان
    echo '<select name="dokan_vendor">';
    echo '<option value="">همه فروشندگان</option>';

    foreach ($vendors as $vendor) {
        printf(
            '<option value="%d"%s>%s</option>',
            $vendor->ID,
            $current_vendor == $vendor->ID ? ' selected="selected"' : '',
            esc_html($vendor->display_name)
        );
    }

    echo '</select>';
}

// اعمال فیلتر فروشندگان بر روی کوئری محصولات دکان
add_action('pre_get_posts', 'apply_seller_filter_to_product_query', 10, 1);

function apply_seller_filter_to_product_query($query) {
    global $pagenow, $typenow;

    // اطمینان از اینکه در صفحه ویرایش محصولات هستیم
    if (
        is_admin() &&
        $pagenow === 'edit.php' &&
        $typenow === 'product' &&
        isset($_GET['dokan_vendor']) && !empty($_GET['dokan_vendor'])
    ) {
        // فیلتر کردن بر اساس فروشنده
        $query->set('meta_key', '_seller_id');
        $query->set('meta_value', intval($_GET['dokan_vendor']));
    }
}

add_action('pre_get_posts', 'filter_dokan_orders_query_by_status');

function filter_dokan_orders_query_by_status($query) {
    if ( ! is_main_query() || ! is_user_logged_in() ) {
        return;
    }

    // فقط در فرانت اجرا بشه، نه wp-admin
    if ( is_admin() ) {
        return;
    }

    // اطمینان از اینکه در پیشخوان فروشنده هستیم
    if ( function_exists( 'dokan_is_seller_dashboard' ) && dokan_is_seller_dashboard() ) {
        global $wp;

        if ( isset( $wp->query_vars['dashboard'] ) && $wp->query_vars['dashboard'] === 'orders' ) {

            if ( isset( $query->query_vars['post_type'] ) && $query->query_vars['post_type'] === 'shop_order' ) {
                $query->set( 'post_status', array( 'wc-processing', 'wc-completed' ) );
                error_log('✅ سفارش‌ها فیلتر شدند فقط تکمیل شده و در حال انجام');
            }
        }
    }
}
/////////پاپ آپ درگاه
function kamanlend_popup_gateway_enqueue_scripts() {
    wp_enqueue_script('popup-gateway-js', plugin_dir_url(__FILE__) . 'assets/popup.js', array('jquery'), null, true);
    wp_enqueue_style('popup-gateway-css', plugin_dir_url(__FILE__) . 'assets/popup.css');

    // ارسال متن پاپ‌آپ و وضعیت فعال بودن آن به جاوااسکریپت
    $payment_gateways = WC()->payment_gateways->get_available_payment_gateways();
    $popup_data = array();

    foreach ($payment_gateways as $gateway) {
        $popup_data[$gateway->id] = array(
            'popup_text' => get_option('popup_text_' . $gateway->id, ''),
            'is_popup_enabled' => get_option('popup_enabled_' . $gateway->id, 'no') === 'yes' ? true : false
        );
    }

    wp_localize_script('popup-gateway-js', 'popupGatewayData', $popup_data);
}
add_action('wp_enqueue_scripts', 'kamanlend_popup_gateway_enqueue_scripts');
/////////////////