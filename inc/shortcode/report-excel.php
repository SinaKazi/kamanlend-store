<?php
function kam_report_shortcode() {
    ob_start();
    $today = gregorian_to_jalali(date('Y'), date('m'), date('d'));
    list($def_year, $def_month, $def_day) = $today;
    ?>
    <form method="get" action="<?php echo esc_url(home_url('/kam-report-download/')); ?>" style="display: flex; flex-wrap: wrap; gap: 10px; align-items: center; background: #f9f9f9; padding: 15px; border-radius: 8px; max-width: 600px;">
        <input type="hidden" name="kam_download_excel" value="1">
        <div>
            <label for="kam_year">سال:</label><br>
            <select name="kam_year" id="kam_year">
                <option value="1404 <?php selected($def_year, 1404); ?>">1404</option>
                <option value="1405<?php selected($def_year, 1405); ?>">1405</option>
            </select>
        </div>

        <div>
            <label for="kam_month">ماه:</label><br>
            <select name="kam_month" id="kam_month">
                <?php for ($i = 1; $i <= 12; $i++): ?>
                    <option value="<?php echo $i; ?>" <?php selected($def_month, $i); ?>>
                        <?php echo $i; ?>
                    </option>
                <?php endfor; ?>
            </select>
        </div>

        <div>
            <label for="kam_day">روز:</label><br>
            <select name="kam_day" id="kam_day">
                <?php for ($i = 1; $i <= 31; $i++): ?>
                    <option value="<?php echo $i; ?>" <?php selected($def_day, $i); ?>>
                        <?php echo $i; ?>
                    </option>
                <?php endfor; ?>
            </select>
        </div>

        <div style="margin-top: 20px;">
            <button type="submit" style="padding: 6px 14px; background: #0073aa; color: #fff; border: none; border-radius: 4px; cursor: pointer;">
                دریافت گزارش
            </button>
        </div>
    </form>
    <?php
    return ob_get_clean();
}
add_shortcode('kam_order_report', 'kam_report_shortcode');
