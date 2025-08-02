<?php
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\Color;

require_once plugin_dir_path(__FILE__) . '/vendor/autoload.php';
require_once ABSPATH . 'wp-load.php';

if (!function_exists('jalali_to_gregorian') || !function_exists('gregorian_to_jalali')) {
    wp_die('تبدیل تاریخ شمسی/میلادی در دسترس نیست.');
}

$year  = intval($_GET['kam_year']);
$month = intval($_GET['kam_month']);
$day   = intval($_GET['kam_day']);

$gdate = jalali_to_gregorian($year, $month, $day);
$date_start = sprintf('%04d-%02d-%02d 00:00:00', $gdate[0], $gdate[1], $gdate[2]);
$date_end   = sprintf('%04d-%02d-%02d 23:59:59', $gdate[0], $gdate[1], $gdate[2]);

$args = [
    'limit'        => -1,
    'status'       => ['processing', 'completed'],
    'date_created' => $date_start . '...' . $date_end,
];
$orders = wc_get_orders($args);

$order_rows = [];

foreach ($orders as $order) {
    $order_id = $order->get_id();
    $full_name = $order->get_billing_first_name() . ' ' . $order->get_billing_last_name();
    $national_code = $order->get_meta('_billing_national_code');
    $invoice_number = $order->get_meta('_kam_invoice_number');

    // تبدیل تاریخ به شمسی
    list($gy, $gm, $gd) = explode('-', $order->get_date_created()->date('Y-m-d'));
    list($jy, $jm, $jd) = gregorian_to_jalali($gy, $gm, $gd);
    $invoice_date = sprintf('%04d/%02d/%02d', $jy, $jm, $jd);

    $payment_type = $order->get_payment_method_title();
    $base_url = home_url('/');
    $invoice_pdf_link = add_query_arg([
        'view_invoice_pdf' => 1,
        'order_id' => $order_id
    ], $base_url);
    foreach ($order->get_items() as $item) {
        $product = $item->get_product();
        if (!$product) continue;

        $product_name = $item->get_name();
        $product_sku = $product->get_sku();
        $total_price = $item->get_total();
        $seller_id = get_post_field('post_author', $item->get_product_id());
        $seller_data = dokan_get_store_info($seller_id);
        $seller_name = isset($seller_data['store_name']) ? $seller_data['store_name'] : 'بدون فروشنده';

        $order_rows[] = [
            'نام مشتری'        => $full_name,
            'کد ملی'           => $national_code,
            'کد کالا'          => $product_sku,
            'شرح کالا'         => $product_name,
            'تامین کننده'      => $seller_name,
            'مبلغ فاکتور'      => $total_price,
            'شماره فاکتور'     => $invoice_number,
            'تاریخ فاکتور'     => $invoice_date,
            'نوع خرید'         => $payment_type,
            'لینک فاکتور PDF'  => ['text' => 'مشاهده فاکتور', 'url' => $invoice_pdf_link],
        ];
    }
}

if (!empty($order_rows)) {
    if (ob_get_length()) ob_end_clean();

    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();

    $headers = array_keys($order_rows[0]);
    $sheet->fromArray($headers, NULL, 'A1');

    $rowIndex = 2;
    foreach ($order_rows as $row) {
        $colIndex = 1;
        foreach ($row as $key => $cell) {
            $cellRef = Coordinate::stringFromColumnIndex($colIndex) . $rowIndex;

            if ($key === 'لینک فاکتور PDF' && is_array($cell)) {
                $sheet->setCellValue($cellRef, $cell['text']);
                $sheet->getCell($cellRef)->getHyperlink()->setUrl($cell['url']);
                $sheet->getStyle($cellRef)->getFont()->getColor()->setARGB(Color::COLOR_BLUE);
                $sheet->getStyle($cellRef)->getFont()->setUnderline(true);
            } else {
                $value = is_array($cell) ? json_encode($cell) : $cell;
                $sheet->setCellValue($cellRef, $value);
            }

            $colIndex++;
        }
        $rowIndex++;
    }

    $filename = 'order-report-' . $year . '-' . $month . '-' . $day . '.xlsx';
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header("Content-Disposition: attachment;filename=\"{$filename}\"");
    header('Cache-Control: max-age=0');

    $writer = new Xlsx($spreadsheet);
    $writer->save('php://output');
    exit;
} else {
    wp_die('سفارشی در این تاریخ یافت نشد.');
}
