<?php

add_filter( 'dokan_get_dashboard_nav', 'kaman_hide_products_for_non_admins', 50 );

function kaman_hide_products_for_non_admins( $urls ) {
    if ( ! current_user_can('manage_options') ) {
        unset($urls['products']); // حذف آیتم «محصولات» از منو
    }
    return $urls;
}

add_filter( 'dokan_get_dashboard_nav', 'custom_add_price_list_menu', 99 );

function custom_add_price_list_menu( $urls ) {
    $urls['price-list'] = [
        'title' => __( 'لیست قیمت', 'your-textdomain' ),
        'icon'  => '<i class="fas fa-tags"></i>', // آیکون دلخواه فونت‌آوسام
        'url'   => dokan_get_navigation_url( 'price-list' ),
        'pos'   => 55, // موقعیت منو
    ];
    return $urls;
}
add_action( 'init', function () {
    add_rewrite_endpoint( 'price-list', EP_PAGES );
} );

add_action( 'dokan_load_custom_template', function( $query_vars ) {
    if ( isset( $query_vars['price-list'] ) ) {
        include plugin_dir_path( __FILE__ ) . 'templates/price-list.php';
    }
} );

