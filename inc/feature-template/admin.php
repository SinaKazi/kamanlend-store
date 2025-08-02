<?php
// متاباکس محصول
add_action('add_meta_boxes', function () {
    add_meta_box(
        'kam_quick_feature_entry',
        'ورود سریع ویژگی',
        'kam_render_quick_feature_box',
        'product',
        'normal',
        'default'
    );
});

function kam_render_quick_feature_box($post) {
    $feature_groups = get_posts([
        'post_type' => 'kam_feature_group',
        'posts_per_page' => -1
    ]);
    ?>
    <label for="kam_feature_group_select">انتخاب دسته ویژگی:</label>
    <select id="kam_feature_group_select" style="min-width: 200px">
        <option value="">انتخاب کنید</option>
        <?php foreach ($feature_groups as $group): ?>
            <option value="<?= esc_attr($group->ID) ?>"><?= esc_html($group->post_title) ?></option>
        <?php endforeach; ?>
    </select>

    <div id="kam_feature_fields" style="margin-top: 20px;"></div>
    <div id="kam_feature_hidden_inputs"></div>

    <script>
    jQuery(document).ready(function($) {
        $('#kam_feature_group_select').on('change', function () {
            let groupId = $(this).val();
            if (!groupId) return;

            $.post(ajaxurl, {
                action: 'kam_load_feature_fields',
                group_id: groupId,
                product_id: <?= $post->ID ?>
            }, function (response) {
                $('#kam_feature_fields').html(response);
                $('.kam-select2').select2({ width: 'resolve' });
            });
        });

        // افزودن term جدید از input به select
        $('#kam_feature_fields').on('click', '.kam-add-feature', function () {
            const attrSlug = $(this).data('attr');
            const $input = $(`input[name="kam_features_custom[${attrSlug}]"]`);
            const valuesRaw = $input.val().trim();
            if (!valuesRaw) return;

            const values = valuesRaw.split('|').map(v => v.trim()).filter(Boolean);
            const $select = $(`select[name="kam_features[${attrSlug}][]"]`);

            values.forEach(function (value) {
                if ($select.find(`option[value="${value}"]`).length) {
                    $select.find(`option[value="${value}"]`).prop('selected', true);
                    return;
                }

                $.post(ajaxurl, {
                    action: 'kam_add_custom_feature_term',
                    attr_slug: attrSlug,
                    term_name: value
                }, function (res) {
                    if (res.success) {
                        const $option = $('<option>')
                            .val(res.data.name)
                            .text(res.data.name)
                            .prop('selected', true);
                        $select.append($option).trigger('change');
                    } else {
                        alert('خطا: ' + res.data);
                    }
                });
            });

            $input.val('');
        });

        // انتقال به hidden برای ارسال فرم
        $('#post').on('submit', function () {
            const hiddenInputsContainer = $('#kam_feature_hidden_inputs');
            hiddenInputsContainer.html('');

            $('#kam_feature_fields').find('select, input[type="text"]').each(function () {
                const name = $(this).attr('name');
                const value = $(this).val();
                if (name && value) {
                    hiddenInputsContainer.append(
                        $('<input>').attr('type', 'hidden').attr('name', name).val(value)
                    );
                }
            });
        });
    });
    </script>
    <?php
}

// ثبت post type دسته ویژگی
add_action('init', function () {
    register_post_type('kam_feature_group', [
        'labels' => [
            'name' => 'دسته‌های ویژگی',
            'singular_name' => 'دسته ویژگی',
            'add_new' => 'افزودن دسته جدید',
            'add_new_item' => 'افزودن دسته ویژگی جدید',
            'edit_item' => 'ویرایش دسته ویژگی',
            'new_item' => 'دسته ویژگی جدید',
            'view_item' => 'مشاهده دسته ویژگی',
            'search_items' => 'جستجو دسته ویژگی',
        ],
        'public' => false,
        'show_ui' => true,
        'menu_icon' => 'dashicons-tag',
        'supports' => ['title'],
        'show_in_menu' => true,
    ]);
});

// متاباکس دسته ویژگی
add_action('add_meta_boxes', function () {
    add_meta_box(
        'kam_feature_group_meta',
        'انتخاب ویژگی‌ها',
        'kam_render_feature_group_meta',
        'kam_feature_group',
        'normal',
        'default'
    );
});

function kam_render_feature_group_meta($post) {
    $selected = get_post_meta($post->ID, 'kam_feature_terms', true) ?: [];

    global $wpdb;
    $attributes = $wpdb->get_results("SELECT attribute_name, attribute_label FROM {$wpdb->prefix}woocommerce_attribute_taxonomies");

    echo '<select name="kam_feature_terms[]" multiple class="kam-select2" style="width: 100%;">';
    foreach ($attributes as $attr) {
        $slug = $attr->attribute_name;
        $label = $attr->attribute_label ?: $slug;
        $is_selected = in_array($slug, $selected) ? 'selected' : '';
        echo "<option value='" . esc_attr($slug) . "' $is_selected>" . esc_html($label) . "</option>";
    }
    echo '</select>';
}

// استایل و اسکریپت select2

add_action('admin_enqueue_scripts', function ($hook) {
    wp_enqueue_style('select2-css', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css');
    wp_enqueue_script('select2-js', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js', ['jquery'], null, true);
    wp_add_inline_script('select2-js', 'jQuery(function($){ $(".kam-select2").select2({width:"resolve"}); });');
});

// ذخیره دسته ویژگی
add_action('save_post_kam_feature_group', function ($post_id) {
    if (isset($_POST['kam_feature_terms'])) {
        update_post_meta($post_id, 'kam_feature_terms', array_map('sanitize_text_field', $_POST['kam_feature_terms']));
    } else {
        delete_post_meta($post_id, 'kam_feature_terms');
    }
});

// ذخیره ویژگی در محصول
add_action('save_post', function ($post_id) {
    if (get_post_type($post_id) !== 'product') return;
    if (!isset($_POST['kam_features']) || !is_array($_POST['kam_features'])) return;

    $existing_attributes = get_post_meta($post_id, '_product_attributes', true) ?: [];

    foreach ($_POST['kam_features'] as $attr_slug => $selected_values) {
        $taxonomy = 'pa_' . sanitize_title($attr_slug);
        $custom_value = $_POST['kam_features_custom'][$attr_slug] ?? '';

        $final_values = is_array($selected_values) ? $selected_values : [$selected_values];
        if ($custom_value) {
            $final_values[] = $custom_value;
            if (!term_exists($custom_value, $taxonomy)) {
                wp_insert_term($custom_value, $taxonomy, ['slug' => sanitize_title($custom_value)]);
            }
        }

        $existing_terms = wp_get_object_terms($post_id, $taxonomy, ['fields' => 'names']);
        $merged_terms = array_unique(array_merge($existing_terms, $final_values));

        wp_set_object_terms($post_id, $merged_terms, $taxonomy, false);

        if (!isset($existing_attributes[$taxonomy])) {
            $existing_attributes[$taxonomy] = [
                'name' => $taxonomy,
                'value' => '',
                'position' => 0,
                'is_visible' => 1,
                'is_variation' => 0,
                'is_taxonomy' => 1
            ];
        }
    }

    update_post_meta($post_id, '_product_attributes', $existing_attributes);
});
