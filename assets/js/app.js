/* جاوااسکریپت عمومی فروشگاه و پنل */
function qtyStep(btn, delta) {
    var input = btn.parentElement.querySelector('input');
    if (!input) return;
    var step = parseFloat(input.step) || 0.5;
    var min = parseFloat(input.min) || 0;
    var max = parseFloat(input.max) || Infinity;
    var v = (parseFloat(input.value) || 0) + delta * step;
    v = Math.min(max, Math.max(min, v));
    input.value = (Math.round(v * 10) / 10).toString();
}

/* تایید فرم‌های حذف با data-confirm */
document.addEventListener('submit', function (e) {
    var f = e.target;
    if (f.getAttribute && f.getAttribute('data-confirm')) {
        if (!confirm(f.getAttribute('data-confirm'))) e.preventDefault();
    }
});
