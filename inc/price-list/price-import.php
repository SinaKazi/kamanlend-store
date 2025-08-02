<?php
if (!defined('ABSPATH')) exit;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\IOFactory;

require_once __DIR__ . '/../shortcode/vendor/autoload.php'; // اگر autoload در جای دیگر است، مسیر را اصلاح کن

// 1. تولید فایل اکسل نمونه برای فروشنده
add_action('wp_ajax_download_price_excel', function () {
    $user_id = get_current_user_id();

    if (!$user_id) wp_die('دسترسی غیرمجاز');

    $products = wc_get_products([
        'author' => $user_id,
        'limit'  => -1,
        'status' => 'publish',
        'orderby' => 'date',
        'order'   => 'DESC',
    ]);

    //$rows = [['product_id', 'parent_id', 'product_name', 'is_variable', 'regular_price', 'sale_price', 'stock_quantity', 'manage_stock']];
    $rows = [
        ['شناسه محصول', 'شناسه والد', 'نام محصول', 'متغیر بودن', 'قیمت عادی', 'قیمت با تخفیف', 'موجودی', 'مدیریت موجودی']
    ];
    foreach ($products as $product) {
        if ($product->is_type('variable')) {
            foreach ($product->get_children() as $child_id) {
                $variation = wc_get_product($child_id);
                if (!$variation) continue;
                $rows[] = [
                    $variation->get_id(),
                    $product->get_id(),
                    $variation->get_name(),
                    1,
                    $variation->get_regular_price(),
                    $variation->get_sale_price(),
                    $variation->get_stock_quantity(),
                    1,
                ];
            }
        } else {
            $rows[] = [
                $product->get_id(),
                '',
                $product->get_name(),
                0,
                $product->get_regular_price(),
                $product->get_sale_price(),
                $product->get_stock_quantity(),
                1,
            ];
        }
    }

    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    foreach ($rows as $i => $row) {
        $sheet->fromArray($row, null, 'A' . ($i + 1));
        // فقط عنوان‌های خاص را قرمز می‌کنیم
        if ($i === 0) {
            // ستون‌های خاصی که نمی‌خواهیم تغییر کنند: `product_id`, `parent_id`, `is_variable`, `manage_stock`
            $sheet->getStyle("A1")->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID);
            $sheet->getStyle("A1")->getFill()->getStartColor()->setARGB('FFFF0000'); // قرمز

            $sheet->getStyle("B1")->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID);
            $sheet->getStyle("B1")->getFill()->getStartColor()->setARGB('FFFF0000'); // قرمز

            $sheet->getStyle("C1")->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID);
            $sheet->getStyle("C1")->getFill()->getStartColor()->setARGB('FFFF0000'); // قرمز

            $sheet->getStyle("D1")->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID);
            $sheet->getStyle("D1")->getFill()->getStartColor()->setARGB('FFFF0000'); // قرمز

            $sheet->getStyle("H1")->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID);
            $sheet->getStyle("H1")->getFill()->getStartColor()->setARGB('FFFF0000'); // قرمز
        }
    }

    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename="price-list.xlsx"');
    header('Cache-Control: max-age=0');
    $writer = new Xlsx($spreadsheet);
    $writer->save('php://output');
    exit;
});

// 2. آپلود فایل و بررسی اعتبار
add_action('wp_ajax_upload_price_excel', function () {
    $user_id = get_current_user_id();
    if (!$user_id || !current_user_can('edit_products')) wp_send_json_error(['message' => 'دسترسی غیرمجاز']);

    if (!isset($_FILES['price_excel']) || $_FILES['price_excel']['error'] !== UPLOAD_ERR_OK) {
        wp_send_json_error(['message' => 'آپلود فایل ناموفق بود.']);
    }

    $file = $_FILES['price_excel']['tmp_name'];
    try {
        $spreadsheet = IOFactory::load($file);
        $data = $spreadsheet->getActiveSheet()->toArray();
    } catch (Exception $e) {
        wp_send_json_error(['message' => 'فایل اکسل معتبر نیست.']);
    }

    $headers = array_map('trim', $data[0]);
    $expected = ['product_id', 'parent_id', 'product_name', 'is_variable', 'regular_price', 'sale_price', 'stock_quantity', 'manage_stock'];

    if ($headers !== $expected) {
        wp_send_json_error(['message' => 'ساختار فایل اکسل تغییر کرده و معتبر نیست.']);
    }

    $updates = [];
    for ($i = 1; $i < count($data); $i++) {
        $row = array_combine($headers, $data[$i]);
        $product_id = intval($row['product_id']);

        // فقط ستون‌های قابل ویرایش را بگیر
        $updates[] = [
            'product_id'      => $product_id,
            'regular_price'   => wc_format_decimal($row['regular_price']),
            'sale_price'      => wc_format_decimal($row['sale_price']),
            'stock_quantity'  => intval($row['stock_quantity']),
        ];
    }

    // ذخیره در transient
    set_transient("kam_price_update_queue_user_{$user_id}", [
        'queue' => $updates,
        'progress' => 0,
        'total' => count($updates),
    ], 60 * 60); // یک ساعت اعتبار

    wp_send_json_success(['message' => 'فایل با موفقیت ثبت شد. پردازش آغاز خواهد شد.']);
    error_log(print_r($updates, true));
});

// 3. پردازش مرحله‌ای آپدیت قیمت‌ها (هر بار 10 محصول)
add_action('wp_ajax_process_price_excel_batch', function () {
    $user_id = get_current_user_id();
    if (!$user_id) wp_send_json_error(['message' => 'دسترسی غیرمجاز']);

    $key = "kam_price_update_queue_user_{$user_id}";
    $data = get_transient($key);

    if (!$data || empty($data['queue'])) {
        delete_transient($key);
        wp_send_json_success(['done' => true, 'percent' => 100]);
    }

    $processed = 0;
    $batch_size = 10;

    while ($processed < $batch_size && !empty($data['queue'])) {
        $item = array_shift($data['queue']);
        $product_id = absint($item['product_id']);
        $product = wc_get_product($product_id);

        // امنیت: فقط محصولات فروشنده جاری
        if (!$product || (int) get_post_field('post_author', $product_id) !== $user_id) continue;

        // $product->set_regular_price($item['regular_price']);
        // $product->set_sale_price($item['sale_price']);
        // $product->set_price($item['sale_price'] ?: $item['regular_price']);
        // $product->set_manage_stock(true);
        // $product->set_stock_quantity($item['stock_quantity']);
        // $product->save();

        // فقط مقادیری که تغییر کرده‌اند رو آپدیت می‌کنیم
        $is_updated = false;

        // بررسی تغییرات قیمت عادی
        if ($product->get_regular_price() != $item['regular_price']) {
            $product->set_regular_price($item['regular_price']);
            $is_updated = true;
        }

        // بررسی تغییرات قیمت تخفیف
        if ($product->get_sale_price() != $item['sale_price']) {
            $product->set_sale_price($item['sale_price']);
            $is_updated = true;
        }

        // بررسی تغییرات موجودی
        if ($product->get_stock_quantity() != $item['stock_quantity']) {
            $product->set_stock_quantity($item['stock_quantity']);
            $is_updated = true;
        }

        // اگر تغییری انجام شده، ذخیره می‌کنیم
        if ($is_updated) {
            $product->set_price($item['sale_price'] !== '' ? $item['sale_price'] : $item['regular_price']);
            $product->save();
        }

        $processed++;
        $data['progress']++;
    }

    // بازنویسی صف جدید
    set_transient($key, $data, 60 * 60);

    $percent = round(($data['progress'] / $data['total']) * 100);

    wp_send_json_success([
        'done' => false,
        'percent' => min(100, $percent),
    ]);
});
