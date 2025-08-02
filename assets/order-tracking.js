jQuery(document).ready(function($) {
    $('.kam-add-tracking-btn').on('click', function () {
        const orderId = $(this).data('order-id');
        $('#kam_order_id').val(orderId);
        $('#kam-tracking-modal').show();
    });

    $('#tracking_provider').on('change', function () {
        if ($(this).val() === 'custom') {
            $('#custom_name_field').show();
        } else {
            $('#custom_name_field').hide();
        }
    });

    $('#kam-tracking-form').on('submit', function (e) {
        e.preventDefault();
        const data = $(this).serialize();
        $.post(ajaxurl, {
            action: 'kam_save_tracking_code',
            ...Object.fromEntries(new URLSearchParams(data))
        }, function (res) {
            if (res.success) {
                alert('ذخیره شد!');
                location.reload();
            } else {
                alert('خطا: ' + res.data);
            }
        });
    });
});
