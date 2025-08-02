<?php

if ( ! defined( 'ABSPATH' ) ) exit;

// ➤ روت سفارشی برای تولید PDF
add_action('init', function() {
    if (isset($_GET['view_invoice_pdf']) && isset($_GET['order_id'])) {
        $order_id = absint($_GET['order_id']);
        include plugin_dir_path(__FILE__) . 'invoice-pdf.php';
        exit;
    }
});

// ➤ افزودن دکمه در صفحه "سفارشات من" مشتری
add_filter('woocommerce_my_account_my_orders_actions', function($actions, $order) {
    if (in_array($order->get_status(), ['processing', 'completed'])) {
        $actions['view_invoice'] = array(
            'url'  => add_query_arg(array(
                'view_invoice_pdf' => 1,
                'order_id' => $order->get_id(),
            ), home_url('/')),
            'name' => __('مشاهده فاکتور', 'woocommerce'),
        );
    }
    return $actions;
}, 10, 2);

// ➤ افزودن دکمه به ستون اکشن‌ها در لیست سفارشات پنل ادمین
add_filter('woocommerce_admin_order_actions', function($actions, $order) {
    if (in_array($order->get_status(), ['processing', 'completed'])) {
        $url = add_query_arg(array(
            'view_invoice_pdf' => 1,
            'order_id' => $order->get_id(),
        ), home_url('/'));
        $actions['view_invoice'] = array(
            'url'    => $url,
            'name'   => __('مشاهده فاکتور', 'woocommerce'),
            'action' => 'view_invoice',
        );
    }
    return $actions;
}, 10, 2);

// ➤ آیکن برای دکمه ستون اکشن‌ها
add_action('admin_head', function() {
    echo '<style>
        .wc-action-button-view_invoice::after {
            font-family: Dashicons !important;
            content: "\f495"; /* آیکن پرینتر */
        }
    </style>';
});

// ➤ افزودن ستون جدید در جدول سفارشات پنل ادمین
add_filter('manage_edit-shop_order_columns', function($columns) {
    $columns['invoice_pdf'] = 'فاکتور PDF';
    return $columns;
});

// ➤ محتوای ستون جدید: دکمه برای سفارش‌های معتبر
add_action('manage_shop_order_posts_custom_column', function($column, $post_id) {
    if ($column === 'invoice_pdf') {
        $order = wc_get_order($post_id);
        if ($order && in_array($order->get_status(), ['processing', 'completed'])) {
            $url = add_query_arg(array(
                'view_invoice_pdf' => 1,
                'order_id' => $order->get_id(),
            ), home_url('/'));
            echo '<a class="button" target="_blank" href="' . esc_url($url) . '">مشاهده فاکتور</a>';
        } else {
            echo '—';
        }
    }
}, 10, 2);

// ➤ افزودن دکمه در بالای صفحه ویرایش سفارش در پنل ادمین
add_action('woocommerce_admin_order_data_after_order_details', function($order) {
    if (in_array($order->get_status(), ['processing', 'completed'])) {
        $url = add_query_arg(array(
            'view_invoice_pdf' => 1,
            'order_id' => $order->get_id(),
        ), home_url('/'));

        echo '<p><a class="button button-primary" target="_blank" href="' . esc_url($url) . '">📄 مشاهده فاکتور PDF</a></p>';
    }
});
