<?php

// لود ویژگی‌ها
add_action('wp_ajax_kam_load_feature_fields', function () {
    global $wpdb;
    $group_id = intval($_POST['group_id']);
    $features = get_post_meta($group_id, 'kam_feature_terms', true);

    if (!is_array($features)) {
        wp_send_json_error('ویژگی‌ای پیدا نشد');
    }

    foreach ($features as $slug) {
        $taxonomy = 'pa_' . sanitize_title($slug);
        $terms = get_terms(['taxonomy' => $taxonomy, 'hide_empty' => false]);

        $label = $slug;
        $attr = $wpdb->get_row($wpdb->prepare("SELECT attribute_label FROM {$wpdb->prefix}woocommerce_attribute_taxonomies WHERE attribute_name = %s", $slug));
        if ($attr && $attr->attribute_label) {
            $label = $attr->attribute_label;
        }

        echo "<div style='margin-bottom: 15px'>";
        echo "<label><strong>" . esc_html($label) . ":</strong></label><br>";
        echo "<select class='kam-select2' name='kam_features[$slug][]' multiple style='width: 300px'>";
        foreach ($terms as $term) {
            echo "<option value='" . esc_attr($term->name) . "'>" . esc_html($term->name) . "</option>";
        }
        echo "</select> ";
        echo "<input type='text' name='kam_features_custom[$slug]' style='min-width: 200px' placeholder='مثلاً طلایی | نقره‌ای'>";
        echo " <button type='button' class='button kam-add-feature' data-attr='" . esc_attr($slug) . "'>➕ افزودن</button>";
        echo "</div>";
    }

    wp_die();
});

// افزودن term جدید ایجکسی
add_action('wp_ajax_kam_add_custom_feature_term', function () {
    if (!current_user_can('edit_products')) {
        wp_send_json_error('دسترسی ندارید');
    }

    $attr_slug = sanitize_text_field($_POST['attr_slug'] ?? '');
    $term_name = sanitize_text_field($_POST['term_name'] ?? '');

    if (!$attr_slug || !$term_name) {
        wp_send_json_error('داده ناقص است');
    }

    $taxonomy = 'pa_' . sanitize_title($attr_slug);
    if (!taxonomy_exists($taxonomy)) {
        wp_send_json_error('taxonomy وجود ندارد');
    }

    $existing = term_exists($term_name, $taxonomy);
    if ($existing && is_array($existing)) {
        $term_id = $existing['term_id'];
    } else {
        $result = wp_insert_term($term_name, $taxonomy, ['slug' => sanitize_title($term_name)]);
        if (is_wp_error($result)) {
            wp_send_json_error('خطا در ثبت مقدار');
        }
        $term_id = $result['term_id'];
    }

    $term = get_term($term_id, $taxonomy);
    wp_send_json_success([
        'id' => $term->term_id,
        'name' => $term->name
    ]);
});
