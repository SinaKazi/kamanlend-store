<?php
// افزودن فیلدهای متا به صفحه محصول
add_action('woocommerce_product_options_general_product_data', function () {
    global $post;

    echo '<div class="options_group">';

    // غیرفعال‌سازی فروش قسطی
    woocommerce_wp_checkbox([
        'id' => '_disable_installment',
        'label' => 'غیرفعال‌سازی فروش قسطی',
        'description' => 'با فعال کردن این گزینه، فروش قسطی برای این محصول غیرفعال خواهد شد.',
        'value' => get_post_meta($post->ID, '_disable_installment', true)
    ]);

    // درصد اختصاصی سود قسطی
    woocommerce_wp_text_input([
        'id' => '_custom_installment_percent',
        'label' => 'درصد سود قسطی اختصاصی',
        'description' => 'در صورت پر کردن، این درصد جایگزین درصد پیش‌فرض خواهد شد.',
        'type' => 'number',
        'custom_attributes' => ['step' => '0.1', 'min' => '0'],
        'value' => get_post_meta($post->ID, '_custom_installment_percent', true)
    ]);

    echo '</div>';
});

// ذخیره متاهای محصول
add_action('woocommerce_process_product_meta', function ($post_id) {
    $disable = isset($_POST['_disable_installment']) ? 'yes' : 'no';
    update_post_meta($post_id, '_disable_installment', $disable);

    if (isset($_POST['_custom_installment_percent'])) {
        update_post_meta($post_id, '_custom_installment_percent', wc_clean($_POST['_custom_installment_percent']));
    } else {
        delete_post_meta($post_id, '_custom_installment_percent');
    }
});
