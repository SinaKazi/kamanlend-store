console.log('jQuery وجود دارد؟', typeof jQuery);

(function ($) {
    function formatPrice(input) {
        let val = input.value.replace(/,/g, '').replace(/[^0-9]/g, '');
        if (!val) return input.value = '';
        input.value = val.replace(/\B(?=(\d{3})+(?!\d))/g, ",");
    }

    function loadingAnimation() {
        return '<div class="kaman-loading"><div class="loader"></div> در حال بارگذاری...</div>';
    }

    function loadProducts(page = 1, query = '') {
        const wrap = $('#product-table-wrap');
        wrap.html(loadingAnimation());

        $.get(ajaxurlObject.ajaxurl, {
            action: 'get_price_list',
            paged: page,
            q: query
        }, function (response) {
            wrap.html('<table class="kaman-price-table">' + response + '</table>');

            $('.price-input').each(function () {
                formatPrice(this);
                $(this).on('input', function () {
                    formatPrice(this);
                    $(this).addClass('changed');
                });
            });

            $('.manage-stock-toggle').on('change', function () {
                const productId = $(this).data('product');
                $('#stock-' + productId).prop('disabled', !this.checked);
                $(this).addClass('changed');
            });

            $('.pagination-link').on('click', function (e) {
                e.preventDefault();
                // اگر فیلدی تغییر کرده بود، هشدار بده
                if ($('.changed').length > 0) {
                    const go = confirm('شما تغییراتی ایجاد کرده‌اید. آیا می‌خواهید بدون ذخیره به صفحه بعد بروید؟');
                    if (!go) return;
                }
                loadProducts($(this).data('page'), $('#search-products').val());
            });
        });
    }

    $('#search-products').on('input', function () {
        loadProducts(1, this.value.trim());
    });

    // ✅ فقط عدد صحیح برای قیمت‌ها و موجودی
    $(document).on('input', '.price-input[name^="regular_price"], .price-input[name^="sale_price"]', function () {
        const val = this.value;
        if (!/^\d*$/.test(val)) {
            $(this).addClass('error');
            $(this).attr('title', 'لطفاً فقط عدد صحیح وارد کنید');
        } else {
            $(this).removeClass('error');
            $(this).removeAttr('title');
        }
    });

    $(document).on('input', 'input[name^="stock"]', function () {
        const val = this.value;
        if (!/^\d*$/.test(val)) {
            $(this).addClass('error');
            $(this).attr('title', 'لطفاً فقط عدد صحیح وارد کنید');
        } else {
            $(this).removeClass('error');
            $(this).removeAttr('title');
        }
    });

    $('#price-list-form').on('submit', function (e) {
        e.preventDefault();

        let hasInvalidPrice = false;

        $('.price-input[name^="regular_price"]').each(function () {
            const raw = this.value.replace(/,/g, '').trim();
            const num = parseInt(raw);

            // if (!raw || isNaN(num) || num < 1000000) {
            //     $(this).addClass('error');
            //     hasInvalidPrice = true;
            // } else {
            //     $(this).removeClass('error');
            // }
        });

        if (hasInvalidPrice) {
            alert('قیمت عادی محصولات را بررسی کنید');
            return;
        }

        $('.price-input').each(function () {
            this.value = this.value.replace(/,/g, '');
        });

        $.post(ajaxurlObject.ajaxurl, {
            action: 'save_price_list',
            form: $(this).serialize()
        }, function (response) {
            if (response.success) {
                alert('تغییرات با موفقیت ذخیره شد.');
                $('.changed').removeClass('changed');
                loadProducts();
            } else {
                alert('خطایی در ذخیره‌سازی رخ داد.');
            }
        });
    });

    $(document).ready(function () {
        loadProducts();
    });
})(jQuery);

// ✅ آپلود فایل اکسل و پردازش مرحله‌ای
jQuery(function ($) {
    $('#upload-price-excel-form').on('submit', function (e) {
        e.preventDefault();

        const formData = new FormData(this);
        formData.append('action', 'upload_price_excel');

        $.ajax({
            url: ajaxurlObject.ajaxurl,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function (res) {
                console.log('فایل ارسال شد، پاسخ دریافتی:', res);
                if (res.success) {
                    $('#price-import-progress').text('✅ آپلود موفق. پردازش شروع می‌شود...');
                    processBatch();
                } else {
                    $('#price-import-progress').text('❌ ' + res.data.message);
                }
            },
            error: function (err) {
                console.log('❌ خطای AJAX:', err);
                $('#price-import-progress').text('❌ خطا در آپلود.');
            }
        });
    });

    function processBatch() {
        $.post(ajaxurlObject.ajaxurl, { action: 'process_price_excel_batch' }, function (res) {
            if (!res.success) {
                $('#price-import-progress').text('❌ خطا در پردازش');
                return;
            }

            $('#price-import-progress').text(`درصد انجام شده: ${res.data.percent}%`);

            if (!res.data.done) {
                setTimeout(processBatch, 3000);
            } else {
                $('#price-import-progress').append(' ✅ تمام شد.');
                loadProducts();
            }
        });
    }
});
