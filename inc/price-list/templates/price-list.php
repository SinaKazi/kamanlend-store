<?php
if (! defined('ABSPATH')) exit;
do_action('dokan_dashboard_wrap_start');
?>

<div class="dokan-dashboard-wrap">
    <?php dokan_get_template_part('global/dashboard-nav'); ?>
    <div class="dokan-dashboard-content dokan-price-list">
        <!-- 🔼 اضافه کردن بالای فرم -->
        <div class="kaman-import-box" style="margin-bottom: 20px;">
            <h2 class="entry-title">تغییرات اکسلی</h2>
            <span class="info-excel-form">شما می‌توانید با دانلود فایل نمونه اکسل، قیمت‌های محصولات خود را به‌روزرسانی کنید. لطفاً توجه داشته باشید که مواردی که به رنگ قرمز نمایش داده می‌شوند، قابل تغییر نبوده و تغییر آن‌ها ممنوع است.</span>
            <div class="content-form-excel">
            <a href="<?php echo admin_url('admin-ajax.php?action=download_price_excel'); ?>" class="button">نمونه اکسل محصولات</a>
            <form id="upload-price-excel-form" enctype="multipart/form-data" style="display:inline-block; margin-right: 10px;">
                <input type="file" name="price_excel" accept=".xlsx" required>
                <button type="submit" class="button button-primary">بارگذاری و بروزرسانی</button>
            </form>
            </div>
            <div id="price-import-progress" style="margin-top: 10px; color: #21759b; font-weight: bold;"></div>
        </div>

        <article class="dokan-dashboard-area">
            <header class="dokan-dashboard-header">
                <h2 class="entry-title">لیست قیمت</h2>
            </header>

            <div class="dokan-w12">
                <input type="text" id="search-products" placeholder="جست‌وجوی محصول..." class="dokan-form-control" style="margin-bottom: 15px; width: 100%;" />

                <form id="price-list-form">
                    <div class="kam-sticky-bar">
                        <button type="submit" class="button kam-save-button">ذخیره تغییرات</button>
                    </div>

                    <div id="product-table-wrap">
                        <div class="loading-spinner"></div>
                    </div>
                </form>
            </div>
        </article>
    </div>
</div>

<?php do_action('dokan_dashboard_wrap_end'); ?>

<style>
    #product-table-wrap table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 15px;
    }

    #product-table-wrap th,
    #product-table-wrap td {
        border: 1px solid #ddd;
        padding: 8px;
        text-align: center;
    }

    #product-table-wrap th {
        background-color: #f9f9f9;
        font-weight: bold;
    }

    .changed {
        border: 2px solid red !important;
        background-color: #fff0f0;
        transition: border 0.3s, background-color 0.3s;
    }

    .loading-spinner {
        margin: 30px auto;
        width: 40px;
        height: 40px;
        border: 4px solid #ccc;
        border-top: 4px solid #3498db;
        border-radius: 50%;
        animation: spin 1s linear infinite;
    }
    .kam-sticky-bar {
    background: #fff;
    z-index: 999;
    padding: 10px 15px;
    border-bottom: 1px solid #ddd;
    text-align: left;
}
.kam-save-button {
    background-color: #cc0000 !important;
    color: #fff !important;
    border: none;
    padding: 8px 18px;
    font-weight: bold;
    border-radius: 4px;
    cursor: pointer;
    font-size: 14px;
}

.kam-save-button:hover {
    background-color: #a80000 !important;
}
.pagination {
    margin-top: 15px;
}
.pagination-link.active {
    background-color: #cc0000;
    color: #fff;
    font-weight: bold;
    border-radius: 4px;
    padding: 4px 8px;
    text-decoration: none;
}
a.pagination-link {
    padding: 0 6px;
}
span.info-excel-form {
    display: block;
    color: #000;
}
.content-form-excel {
    display: flex;
    flex-wrap: nowrap;
    justify-content: space-between;
    align-items: center;
    margin-top: 10px;
}
.content-form-excel a, .content-form-excel button {
    background: #8BC34A;
    color: #000;
    border-radius: 5px;
}

    @keyframes spin {
        to {
            transform: rotate(360deg);
        }
    }
</style>