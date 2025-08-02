jQuery(function ($) {
    function updateFeeOnGatewayChange() {
        // تأخیر کوچک برای اطمینان از ثبت درگاه در session ووکامرس
        setTimeout(function () {
            $('body').trigger('update_checkout');
        }, 100);
    }

    $(document.body).on('change', 'input[name="payment_method"]', updateFeeOnGatewayChange);
});
