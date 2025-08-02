<?php

$order_id = absint($_GET['order_id']);
$order = wc_get_order($order_id);

if (!$order || !in_array($order->get_status(), ['processing', 'completed'])) {
    wp_die('فاکتور فقط برای سفارش‌های معتبر در دسترس است.');
}
require_once __DIR__ . '/vendor/autoload.php';

use Mpdf\Config\FontVariables;
use Mpdf\Config\ConfigVariables;

function load_font() {
    $defaultConfig = (new ConfigVariables())->getDefaults();
    $fontDirs = is_array($defaultConfig['fontDir']) ? $defaultConfig['fontDir'] : [];

    $defaultFontConfig = (new FontVariables())->getDefaults();
    $fontData = is_array($defaultFontConfig['fontdata']) ? $defaultFontConfig['fontdata'] : [];

    return [
        'fontDir' => array_merge($fontDirs, [__DIR__ . '/fonts']),
        'mode' => 'utf-8',
        'format' => 'A4-L',
        'fontdata' => $fontData + [
            'shabnam' => [
                'R' => 'shabnam.ttf',
                'B' => 'shabnam-bold.ttf',
                'useOTL' => 0xFF,
                'useKashida' => 75,
            ],
        ],
        'default_font' => 'shabnam',
    ];
}


$font = load_font();
$mpdf = new \Mpdf\Mpdf($font);

$mpdf->SetDirectionality('rtl');
$mpdf->SetDisplayMode('fullpage');
$mpdf->autoScriptToLang = true;
//$mpdf->autoLangToFont = true;
$mpdf->useSubstitutions = false;

function is_mobile_device() {
    $agent = strtolower($_SERVER['HTTP_USER_AGENT'] ?? '');
    return preg_match('/android|iphone|ipad|ipod|windows phone|mobile/i', $agent);
}

function kam_format_price($amount) {
    return number_format($amount * 10);
}

function kam_get_persian_date($datetime) {
    if (! $datetime) return '';
    $formatter = new IntlDateFormatter(
        'fa_IR@calendar=persian',
        IntlDateFormatter::LONG,
        IntlDateFormatter::NONE,
        'Asia/Tehran',
        IntlDateFormatter::TRADITIONAL,
        'yyyy/MM/dd'
    );
    return $formatter->format($datetime->getTimestamp());
}

// جایگذاری مقادیر در HTML
$template_path = __DIR__ . '/templates/factor-template.html';
$html = file_get_contents($template_path);

$replacements = [
    '{{seller_name}}' => get_option('kam_invoice_seller_name'),
    '{{seller_register_code}}' => get_option('kam_invoice_register_code'),
    '{{seller_economic_code}}' => get_option('kam_invoice_economic_code'),
    '{{seller_national_id}}' => get_option('kam_invoice_national_id'),
    '{{seller_phone}}' => get_option('kam_invoice_phone'),
    '{{seller_address}}' => get_option('kam_invoice_address'),
    '{{seller_postal_code}}' => get_option('kam_invoice_postal_code'),
    '{{seller_state}}' => get_option('kam_invoice_state'),
    '{{seller_city}}' => get_option('kam_invoice_city'),

    '{{buyer_name}}' => $order->get_billing_first_name() . ' ' . $order->get_billing_last_name(),
    '{{buyer_postal_code}}' => $order->get_billing_postcode(),
    '{{buyer_phone}}' => $order->get_billing_phone(),
    '{{buyer_state}}' => WC()->countries->get_states('IR')[$order->get_billing_state()] ?? $order->get_billing_state(),
    '{{buyer_city}}' => $order->get_billing_city(),
    '{{buyer_address}}' => $order->get_billing_address_1(),
    '{{buyer_national_code}}' => $order->get_meta('_billing_national_code'),
    '{{order_date}}' => kam_get_persian_date($order->get_date_created()),
    '{{invoice_number}}' => $order->get_meta('_kam_invoice_number'),
];

// جدول آیتم‌ها
$i = 1;
$rows = '';
foreach ($order->get_items() as $item) {
    $product = $item->get_product();
    $quantity = $item->get_quantity();
    $subtotal = $item->get_subtotal();
    $final_price = $item->get_total();
    $discount = max(0, $subtotal - $final_price);
    $seller_id = $product ? get_post_field('post_author', $product->get_id()) : 0;
    $store_info = function_exists('dokan_get_store_info') ? dokan_get_store_info($seller_id) : [];
    $seller_name = $store_info['store_name'] ?? get_option('kam_invoice_seller_name');

    $rows .= '<tr>';
    $rows .= '<td>' . $i++ . '</td>';
    $rows .= '<td>' . ($product ? $product->get_sku() : '') . '</td>';
    $rows .= '<td>' . $item->get_name() . '</td>';
    $rows .= '<td>' . esc_html($seller_name) . '</td>';
    $rows .= '<td>' . $quantity . '</td>';
    $rows .= '<td>' . kam_format_price($subtotal) . '</td>';
    $rows .= '<td>' . kam_format_price($discount) . '</td>';
    $rows .= '<td>' . kam_format_price($final_price) . '</td>';
    $rows .= '<td>0</td>';
    $rows .= '<td>' . kam_format_price($final_price) . '</td>';
    $rows .= '</tr>';
}
$replacements['{{order_items_table}}'] = $rows;
$replacements['{{order_total}}'] = kam_format_price($order->get_total()) . ' ریال';

foreach ($replacements as $key => $value) {
    $html = str_replace($key, $value, $html);
}

$filename = "invoice-{$order_id}.pdf";
$mpdf->WriteHTML($html);
if (is_mobile_device()) {
    $mpdf->Output($filename, 'D');
} else {
    $mpdf->Output($filename, 'I');
}
exit;
