<?php
if (!defined('ABSPATH')) exit;

add_action('wp_ajax_get_price_list', function () {
    $current_user_id = get_current_user_id();
    $paged = max(1, intval($_GET['paged'] ?? 1));
    $query = sanitize_text_field($_GET['q'] ?? '');
    $per_page = 50;

    $args = [
        'post_type'      => 'product',
        'post_status'    => 'publish',
        'posts_per_page' => $per_page,
        'paged'          => $paged,
        'author'         => $current_user_id,
        's'              => $query,
    ];

    $query_obj = new WP_Query($args);
    $products = $query_obj->posts;
    $total_pages = $query_obj->max_num_pages;

    ob_start();
    if ($products) {
        echo '<table class="widefat dokan-table">';
        echo '<thead>
            <tr>
                <th>ردیف</th>
                <th>عنوان محصول</th>
                <th>قیمت عادی</th>
                <th>قیمت با تخفیف</th>
                <th>مدیریت موجودی</th>
                <th>موجودی</th>
            </tr>
        </thead><tbody>';

        foreach ($products as $index => $post) {
            $product = wc_get_product($post->ID);

            if ($product->is_type('variable')) {
                echo '<tr class="variable-parent">';
                echo '<td>' . (($paged - 1) * $per_page + $index + 1) . '</td>';
                echo '<td><a href="' . esc_url(get_permalink($product->get_id())) . '" target="_blank">' . esc_html($product->get_name()) . '</a></td>';
                echo '<td colspan="4" style="text-align:center;">محصول متغیر - فقط متغیرها قابل ویرایش هستند</td>';
                echo '</tr>';

                foreach ($product->get_children() as $child_id) {
                    $variation = wc_get_product($child_id);
                    if (!$variation) continue;

                    $manage_stock = $variation->get_manage_stock() ? 'checked' : '';
                    $disabled = $variation->get_manage_stock() ? '' : 'disabled';
                    $stock = $variation->get_stock_quantity();
                    $url = get_permalink($variation->get_id());

                    echo '<tr class="variation-row">';
                    echo '<td></td>';
                    echo '<td><a href="' . esc_url($url) . '" target="_blank">' . esc_html($variation->get_name()) . '</a></td>';
                    echo '<td><input type="text" class="price-input" name="regular_price[' . $variation->get_id() . ']" value="' . esc_attr($variation->get_regular_price()) . '"></td>';
                    echo '<td><input type="text" class="price-input" name="sale_price[' . $variation->get_id() . ']" value="' . esc_attr($variation->get_sale_price()) . '"></td>';
                    echo '<td><label><input type="checkbox" class="manage-stock-toggle" data-product="' . $variation->get_id() . '" name="manage_stock[' . $variation->get_id() . ']" ' . $manage_stock . '> فعال</label></td>';
                    echo '<td><input type="number" id="stock-' . $variation->get_id() . '" name="stock[' . $variation->get_id() . ']" value="' . esc_attr($stock) . '" ' . $disabled . '></td>';
                    echo '</tr>';
                }
            } else {
                $manage_stock = $product->get_manage_stock() ? 'checked' : '';
                $disabled = $product->get_manage_stock() ? '' : 'disabled';
                $stock = $product->get_stock_quantity();
                $url = get_permalink($product->get_id());

                echo '<tr>';
                echo '<td>' . (($paged - 1) * $per_page + $index + 1) . '</td>';
                echo '<td><a href="' . esc_url($url) . '" target="_blank">' . esc_html($product->get_name()) . '</a></td>';
                echo '<td><input type="text" class="price-input" name="regular_price[' . $product->get_id() . ']" value="' . esc_attr($product->get_regular_price()) . '"></td>';
                echo '<td><input type="text" class="price-input" name="sale_price[' . $product->get_id() . ']" value="' . esc_attr($product->get_sale_price()) . '"></td>';
                echo '<td><label><input type="checkbox" class="manage-stock-toggle" data-product="' . $product->get_id() . '" name="manage_stock[' . $product->get_id() . ']" ' . $manage_stock . '> فعال</label></td>';
                echo '<td><input type="number" id="stock-' . $product->get_id() . '" name="stock[' . $product->get_id() . ']" value="' . esc_attr($stock) . '" ' . $disabled . '></td>';
                echo '</tr>';
            }
        }

        echo '</tbody></table>';

        echo '<div style="text-align:center"><div class="pagination">';
        for ($i = 1; $i <= $total_pages; $i++) {
            $active_class = ($i === $paged) ? ' active' : '';
            echo '<a href="#" class="pagination-link' . $active_class . '" data-page="' . $i . '">' . $i . '</a> ';
        }

        echo '</div></div>';
    } else {
        echo '<div class="no-products">محصولی یافت نشد.</div>';
    }

    echo ob_get_clean();
    wp_die();
});

add_action('wp_ajax_save_price_list', function () {
    $current_user_id = get_current_user_id();
    parse_str($_POST['form'], $data);

    foreach ($data['regular_price'] as $product_id => $regular) {
        $product_id = absint($product_id);
        $product = wc_get_product($product_id);
        if (!$product || (int)get_post_field('post_author', $product_id) !== $current_user_id) continue;

        $reg_price = wc_format_decimal(str_replace(',', '', $regular));
        $sale_price = wc_format_decimal(str_replace(',', '', $data['sale_price'][$product_id]));
        $stock = absint($data['stock'][$product_id]);
        $manage = isset($data['manage_stock'][$product_id]);

        $product->set_regular_price($reg_price);
        $product->set_sale_price($sale_price);
        $product->set_price($sale_price !== '' ? $sale_price : $reg_price);
        $product->set_manage_stock($manage);
        $product->set_stock_quantity($stock);
        $product->save();
        error_log('Product ID: ' . $product_id);
        error_log('Regular Price: ' . $reg_price);
    }

    wp_send_json_success(['saved' => true]);
});
