<?php
// فایل: kamanlend-store/admin-menus.php یا مستقیماً در فایل اصلی پلاگین

add_action('admin_menu', function () {
    // منوی اصلی کمان استور
    add_menu_page(
        'کمان استور',           // عنوان صفحه
        'کمان استور',           // عنوان منو
        'manage_options',       // سطح دسترسی
        'kam_store_main',       // slug
        null,                   // بدون callback
        'dashicons-admin-home', // آیکن
        56                      // موقعیت
    );

    // زیرمنو: تنظیمات فاکتور
    add_submenu_page(
        'kam_store_main',
        'تنظیمات فاکتور',
        'فاکتور',
        'manage_options',
        'kam_invoice_settings',
        'kam_render_invoice_settings_page'
    );

    // زیرمنو: تنظیمات فروش قسطی
    // add_submenu_page(
    //     'kam_store_main',
    //     'تنظیمات فروش قسطی',
    //     'قسطی',
    //     'manage_options',
    //     'kam_installment_settings',
    //     'kam_render_installment_settings_page'
    // );
});
