<?php
// فایل: kamanlend-store/inc/installment/settings.php

// تابع رندر محتوای صفحه تنظیمات
function kam_render_installment_settings_page() {
    ?>
    <div class="wrap">
        <h1>تنظیمات فروش قسطی</h1>
        <form method="post" action="options.php">
            <?php
            settings_fields('kam_installment_options');
            do_settings_sections('kam_installment_settings');
            submit_button();
            ?>
        </form>
    </div>
    <?php
}

// ثبت تنظیمات در وردپرس
add_action('admin_init', function () {
    register_setting('kam_installment_options', 'kam_installment_percent');

    add_settings_section(
        'kam_installment_main',
        'درصد سود قسطی',
        null,
        'kam_installment_settings'
    );

    add_settings_field(
        'kam_installment_percent',
        'درصد سود',
        function () {
            $value = get_option('kam_installment_percent', '20');
            echo '<input type="number" name="kam_installment_percent" value="' . esc_attr($value) . '" step="1" min="0" /> %';
        },
        'kam_installment_settings',
        'kam_installment_main'
    );
});
