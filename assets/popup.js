(function ($) {
    // این کد برای اطمینان از بارگذاری صحیح درگاه‌ها با حالت AJAX است
    $(document).on('change', 'input[name="payment_method"]', function() {
        var selectedGateway = $(this).val(); // دریافت ID درگاه انتخاب شده
        console.log('Selected Gateway: ', selectedGateway); // بررسی ID انتخابی

        // بررسی اینکه پاپ‌آپ برای این درگاه فعال است یا نه
        if (popupGatewayData[selectedGateway] && popupGatewayData[selectedGateway].is_popup_enabled) {
            var popupText = popupGatewayData[selectedGateway].popup_text;

            if (popupText) {
                // نمایش پاپ‌آپ
                var popupHTML = '<div id="popup-overlay">' +
                                    '<div id="popup-content">' +
                                        '<p>' + popupText + '</p>' +
                                        '<button id="popup-close">متوجه شدم</button>' +
                                    '</div>' +
                                '</div>';

                $('body').append(popupHTML);
                $('#popup-overlay').fadeIn();

                // بستن پاپ‌آپ
                $('#popup-close').click(function() {
                    $('#popup-overlay').fadeOut(function() {
                        $('#popup-overlay').remove();
                    });
                });
            }
        } else {
            console.log('پاپ‌آپ برای این درگاه فعال نیست یا متنی وجود ندارد.');
        }
    });

    // این بخش برای اطمینان از اجرای کدها بعد از بارگذاری مجدد صفحه در حالت AJAX است
    $(document.body).on('updated_checkout', function() {
        console.log('صفحه تسویه‌حساب به روز رسانی شد');
        // می‌توانید اینجا بررسی کنید که آیا درگاه پرداخت تغییر کرده است یا نه
    });

})(jQuery);
