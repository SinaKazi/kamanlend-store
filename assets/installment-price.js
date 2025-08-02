document.addEventListener('DOMContentLoaded', function () {
    const pricesWrapper = document.getElementById('kam-installment-prices');
    if (!pricesWrapper) return;

    const prices = JSON.parse(pricesWrapper.dataset.prices || '{}');

    const cashPriceEl = document.getElementById('kam-cash-price');
    const installmentPriceEl = document.getElementById('kam-installment-price');
    const cashBox = document.querySelector('.kam-box-cash');
    const installmentBox = document.querySelector('.kam-box-installment');

    if (!cashPriceEl || !installmentPriceEl || !cashBox || !installmentBox) return;

    const cashRadio = cashBox.querySelector('input[type="radio"]');
    const installmentRadio = installmentBox.querySelector('input[type="radio"]');

    const enableBoxes = () => {
        [cashBox, installmentBox].forEach(box => {
            box.classList.add('enabled');
            box.querySelector('input[type="radio"]').disabled = false;
        });
    };

    const updatePrices = () => {
        const variationInput = document.querySelector('input.variation_id');
        const variationId = variationInput?.value;

        if (!variationId || !prices[variationId]) return;

        const priceData = prices[variationId];
        cashPriceEl.innerHTML = priceData.cash;
        installmentPriceEl.innerHTML = priceData.installment + '<br><span class="kam-payment-sub"><a href="https://app.kamanlend.ir/" target="_blank">توسط کمان‌لند</a></span>';
        enableBoxes();
    };

    // محصولات ساده
    if (prices.simple) {
        cashPriceEl.innerHTML = prices.simple.cash;
        installmentPriceEl.innerHTML = prices.simple.installment + '<br><span class="kam-payment-sub"><a href="https://app.kamanlend.ir/" target="_blank">توسط کمان‌لند</a></span>';
        enableBoxes();
    }

    // محصولات متغیر - تغییر انتخاب ویژگی‌ها
    jQuery(document).on('found_variation', 'form.variations_form', function () {
        updatePrices();
    });

    // انتخاب باکس پرداخت
    const boxes = document.querySelectorAll('.kam-payment-box');
    boxes.forEach(box => {
        box.addEventListener('click', () => {
            boxes.forEach(b => b.classList.remove('active'));
            box.classList.add('active');
            box.querySelector('input[type="radio"]').checked = true;
        });
    });

    cashBox.classList.add('active');
    cashRadio.checked = true;
});

// اطمینان از انتخاب صحیح نوع پرداخت هنگام ارسال فرم
const form = document.querySelector('form.cart');
if (form) {
    form.addEventListener('submit', () => {
        const selected = document.querySelector('.kam-payment-box input[type="radio"]:checked');
        if (!selected) {
            alert('لطفاً یکی از روش‌های پرداخت را انتخاب کنید.');
            event.preventDefault();
            return false;
        }

        // از نظر فنی لازم نیست کاری کنیم، چون input انتخاب شده با name="kam_payment_type" به درستی ارسال می‌شود.
    });
}

form.addEventListener('submit', (event) => {
    const selected = document.querySelector('.kam-payment-box input[type="radio"]:checked');
    if (!selected) {
        alert('لطفاً یکی از روش‌های پرداخت را انتخاب کنید.');
        event.preventDefault();
        return false;
    }
});

