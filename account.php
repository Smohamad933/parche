<?php
require_once __DIR__ . '/app/init.php';
require_login();
$u = current_user();
$tab = $_GET['tab'] ?? 'dashboard';

/* ---------- پروفایل ---------- */
if ($tab === 'profile' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $name = trim($_POST['name'] ?? '');
    $phone = en_num(trim($_POST['phone'] ?? ''));
    $email = trim($_POST['email'] ?? '');
    $pass = (string)($_POST['new_password'] ?? '');
    $errs = [];
    if (mb_strlen($name) < 3) $errs[] = 'نام را کامل وارد کنید.';
    if ($phone && !preg_match('/^09\d{9}$/', $phone)) $errs[] = 'موبایل معتبر نیست.';
    if ($pass && strlen($pass) < 6) $errs[] = 'رمز جدید حداقل ۶ کاراکتر.';
    if ($pass && !password_verify($_POST['old_password'] ?? '', qv("SELECT password FROM users WHERE id = ?", [$u['id']]))) $errs[] = 'رمز فعلی اشتباه است.';
    if (!$errs) {
        q("UPDATE users SET name = ?, phone = ?, email = ? WHERE id = ?", [$name, $phone ?: null, $email ?: null, $u['id']]);
        if ($pass) q("UPDATE users SET password = ? WHERE id = ?", [password_hash($pass, PASSWORD_DEFAULT), $u['id']]);
        $_SESSION['user']['name'] = $name;
        flash_set('s', 'پروفایل ذخیره شد.');
        redirect('account.php?tab=profile');
    }
    foreach ($errs as $er) flash_set('e', $er);
}

/* ---------- پیام‌ها ---------- */
if ($tab === 'messages' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $subject = trim($_POST['subject'] ?? '');
    $body = trim($_POST['body'] ?? '');
    $toId = (int)($_POST['to'] ?? 0);
    $replyTo = (int)($_POST['reply_to'] ?? 0);
    if (!$toId) $toId = (int)(qv("SELECT id FROM users WHERE role = 'admin' LIMIT 1") ?: 0);
    if (mb_strlen($subject) < 2 || mb_strlen($body) < 2) {
        flash_set('e', 'موضوع و متن پیام را کامل بنویسید.');
    } elseif (!$toId) {
        flash_set('e', 'گیرنده پیدا نشد.');
    } else {
        q("INSERT INTO messages (sender_id, receiver_id, subject, body, is_read, created_at) VALUES (?,?,?,?,0,?)",
          [$u['id'], $toId, $subject, $body, now()]);
        if ($replyTo) q("UPDATE messages SET is_read = 1 WHERE id = ? AND receiver_id = ?", [$replyTo, $u['id']]);
        flash_set('s', 'پیام شما ارسال شد ✅');
        redirect('account.php?tab=messages');
    }
}
if ($tab === 'message' && isset($_GET['id'])) {
    $mid = (int)$_GET['id'];
    q("UPDATE messages SET is_read = 1 WHERE id = ? AND receiver_id = ?", [$mid, $u['id']]);
}

/* ---------- داده‌های تب‌ها ---------- */
$orders = $tab === 'orders' ? q("SELECT * FROM orders WHERE user_id = ? ORDER BY id DESC", [$u['id']])->fetchAll() : [];
$orderId = (int)($_GET['order_id'] ?? 0);
$order = ($tab === 'order' && $orderId) ? q1("SELECT * FROM orders WHERE id = ? AND user_id = ?", [$orderId, $u['id']]) : null;
$orderItems = $order ? q("SELECT * FROM order_items WHERE order_id = ?", [$order['id']])->fetchAll() : [];
$orderHist = $order ? q("SELECT * FROM order_history WHERE order_id = ? ORDER BY id", [$order['id']])->fetchAll() : [];
$orderInvs = $order ? q("SELECT * FROM invoices WHERE order_id = ?", [$order['id']])->fetchAll() : [];

$inbox = $tab === 'messages' ? q("SELECT m.*, s.name AS sname FROM messages m LEFT JOIN users s ON s.id = m.sender_id
                                  WHERE m.receiver_id = ? ORDER BY m.id DESC LIMIT 50", [$u['id']])->fetchAll() : [];
$sent = $tab === 'messages' ? q("SELECT m.*, r.name AS rname FROM messages m LEFT JOIN users r ON r.id = m.receiver_id
                                 WHERE m.sender_id = ? ORDER BY m.id DESC LIMIT 50", [$u['id']])->fetchAll() : [];
$viewMsg = ($tab === 'message' && isset($_GET['id'])) ? q1("SELECT m.*, s.name AS sname FROM messages m LEFT JOIN users s ON s.id = m.sender_id
                                  WHERE m.id = ? AND (m.receiver_id = ? OR m.sender_id = ?)", [(int)$_GET['id'], $u['id'], $u['id']]) : null;

$stats = [
    'orders' => (int)qv("SELECT COUNT(*) FROM orders WHERE user_id = ?", [$u['id']]),
    'unread' => unread_messages_count($u['id']),
    'spent' => (float)(qv("SELECT COALESCE(SUM(total),0) FROM orders WHERE user_id = ? AND status != 'cancelled'", [$u['id']])),
];
$pageTitle = 'حساب کاربری';
include __DIR__ . '/inc/header.php';
?>
<div class="account">
    <aside class="acc-side">
        <div class="acc-user">
            <div class="avatar"><?= e(mb_substr($u['name'], 0, 1)) ?></div>
            <div><b><?= e($u['name']) ?></b><br><small class="muted"><?= e($u['email'] ?: $u['phone']) ?></small></div>
        </div>
        <?php foreach ([
            'dashboard' => '📊 پیشخوان',
            'orders' => '📦 سفارش‌های من',
            'messages' => '💬 پیام‌ها' . ($stats['unread'] ? ' <b class="badge-msg">' . fa_num($stats['unread']) . '</b>' : ''),
            'profile' => '⚙️ پروفایل',
        ] as $k => $lbl): ?>
            <a class="acc-link <?= $tab === $k || ($k === 'orders' && $tab === 'order') ? 'on' : '' ?>" href="account.php?tab=<?= $k ?>"><?= $lbl ?></a>
        <?php endforeach; ?>
    </aside>

    <section class="acc-body">
    <?php if ($tab === 'dashboard'): ?>
        <h1>سلام <?= e($u['name']) ?> 👋</h1>
        <div class="acc-cards">
            <div class="acc-card"><b><?= fa_num($stats['orders']) ?></b><span>سفارش</span></div>
            <div class="acc-card"><b><?= fa_num($stats['unread']) ?></b><span>پیام خوانده‌نشده</span></div>
            <div class="acc-card"><b><?= price($stats['spent']) ?></b><span>مجموع خرید</span></div>
        </div>
        <?php $last = q1("SELECT * FROM orders WHERE user_id = ? ORDER BY id DESC LIMIT 1", [$u['id']]); ?>
        <?php if ($last): ?>
        <h3>آخرین سفارش</h3>
        <div class="order-row">
            <a href="account.php?tab=order&order_id=<?= (int)$last['id'] ?>"><b dir="ltr"><?= e($last['code']) ?></b></a>
            <span class="tag tag-<?= order_status_class($last['status']) ?>"><?= order_status_label($last['status']) ?></span>
            <span><?= price($last['total']) ?></span>
            <small class="muted"><?= jdate_human($last['created_at']) ?></small>
        </div>
        <?php endif; ?>

    <?php elseif ($tab === 'orders'): ?>
        <h1>سفارش‌های من</h1>
        <?php if (!$orders): ?><div class="empty">هنوز سفارشی ثبت نکرده‌اید. <a href="category.php">شروع خرید ←</a></div><?php else: ?>
        <table class="cart-table">
            <thead><tr><th>کد</th><th>تاریخ</th><th>مبلغ</th><th>وضعیت</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($orders as $o): ?>
                <tr>
                    <td><b dir="ltr"><?= e($o['code']) ?></b></td>
                    <td><?= jdate_human($o['created_at']) ?></td>
                    <td><?= price($o['total']) ?></td>
                    <td><span class="tag tag-<?= order_status_class($o['status']) ?>"><?= order_status_label($o['status']) ?></span></td>
                    <td><a class="btn btn-sm btn-ghost" href="account.php?tab=order&order_id=<?= (int)$o['id'] ?>">جزئیات</a></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>

    <?php elseif ($tab === 'order' && $order): ?>
        <h1>سفارش <span dir="ltr"><?= e($order['code']) ?></span></h1>
        <p><span class="tag tag-<?= order_status_class($order['status']) ?>"><?= order_status_label($order['status']) ?></span> <small class="muted">ثبت: <?= jdate_human($order['created_at']) ?></small></p>
        <table class="cart-table">
            <thead><tr><th>کالا</th><th>متراژ</th><th>قیمت واحد</th><th>جمع</th></tr></thead>
            <tbody>
            <?php foreach ($orderItems as $it): ?>
                <tr>
                    <td><?= e($it['product_name']) ?></td>
                    <td><?= fa_num(rtrim(rtrim((string)$it['qty'], '0'), '.')) ?></td>
                    <td><?= fa_num(number_format((float)$it['unit_price'])) ?></td>
                    <td><?= price($it['total']) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <div class="sumrow"><span>جمع کالاها:</span><b><?= price($order['subtotal']) ?></b></div>
        <?php if ((float)$order['discount'] > 0): ?><div class="sumrow ok"><span>تخفیف:</span><b>−<?= price($order['discount']) ?></b></div><?php endif; ?>
        <div class="sumrow"><span>ارسال:</span><b><?= (float)$order['shipping_cost'] ? price($order['shipping_cost']) : 'رایگان' ?></b></div>
        <div class="sumrow total"><span>مبلغ کل:</span><b><?= price($order['total']) ?></b></div>

        <?php if ($orderInvs): ?>
        <h3>فاکتورها</h3>
        <ul class="inv-links">
        <?php foreach ($orderInvs as $inv): ?>
            <li><a href="invoice.php?code=<?= e($inv['code']) ?>" target="_blank">🧾 <?= e($inv['code']) ?> — <?= price($inv['total']) ?> (<?= invoice_status_label($inv['status']) ?>)</a></li>
        <?php endforeach; ?>
        </ul>
        <?php endif; ?>

        <h3>رویدادها</h3>
        <ul class="history">
            <?php foreach ($orderHist as $h): ?><li><b><?= order_status_label($h['status']) ?></b> — <?= e($h['note']) ?> <small class="muted">(<?= jdate_human($h['created_at']) ?>)</small></li><?php endforeach; ?>
        </ul>

    <?php elseif ($tab === 'message' && $viewMsg): ?>
        <h1>💬 <?= e($viewMsg['subject']) ?></h1>
        <div class="msg-view">
            <div class="msg-bubble <?= $viewMsg['sender_id'] == $u['id'] ? 'mine' : '' ?>">
                <div class="msg-meta"><?= $viewMsg['sender_id'] == $u['id'] ? 'شما' : e($viewMsg['sname'] ?: 'پشتیبانی') ?> · <?= jdate_human($viewMsg['created_at']) ?></div>
                <p><?= nl2br(e($viewMsg['body'])) ?></p>
            </div>
        </div>
        <form method="post" class="msg-form">
            <?= csrf_field() ?>
            <input type="hidden" name="tab" value="messages">
            <input type="hidden" name="to" value="<?= (int)($viewMsg['sender_id'] ?: 1) ?>">
            <input type="hidden" name="reply_to" value="<?= (int)$viewMsg['id'] ?>">
            <label>پاسخ:</label>
            <input name="subject" value="پاسخ: <?= e($viewMsg['subject']) ?>" required>
            <textarea name="body" rows="3" required placeholder="پاسخ خود را بنویسید…"></textarea>
            <button class="btn btn-primary" type="submit">ارسال پاسخ</button>
        </form>

    <?php elseif ($tab === 'messages'): ?>
        <h1>💬 پیام‌ها</h1>
        <div class="msg-cols">
            <div>
                <h3>دریافتی</h3>
                <?php if (!$inbox): ?><p class="muted">پیامی ندارید.</p><?php endif; ?>
                <?php foreach ($inbox as $m): ?>
                    <a class="msg-row <?= (int)$m['is_read'] ? '' : 'unread' ?>" href="account.php?tab=message&id=<?= (int)$m['id'] ?>">
                        <b><?= e($m['subject']) ?></b>
                        <small class="muted"><?= e($m['sname'] ?: 'پشتیبانی') ?> · <?= jdate_human($m['created_at']) ?></small>
                    </a>
                <?php endforeach; ?>
            </div>
            <div>
                <h3>ارسالی</h3>
                <?php if (!$sent): ?><p class="muted">پیامی ارسال نکرده‌اید.</p><?php endif; ?>
                <?php foreach ($sent as $m): ?>
                    <a class="msg-row" href="account.php?tab=message&id=<?= (int)$m['id'] ?>">
                        <b><?= e($m['subject']) ?></b>
                        <small class="muted">به <?= e($m['rname']) ?> · <?= jdate_human($m['created_at']) ?></small>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
        <h3>پیام جدید به پشتیبانی</h3>
        <form method="post" class="msg-form">
            <?= csrf_field() ?>
            <input type="hidden" name="tab" value="messages">
            <label>موضوع</label>
            <input name="subject" required>
            <label>متن پیام</label>
            <textarea name="body" rows="3" required></textarea>
            <button class="btn btn-primary" type="submit">ارسال</button>
        </form>

    <?php elseif ($tab === 'profile'): ?>
        <h1>⚙️ پروفایل</h1>
        <form method="post" class="profile-form">
            <?= csrf_field() ?>
            <div class="row2">
                <label>نام و نام خانوادگی<input name="name" value="<?= e($u['name']) ?>" required></label>
                <label>موبایل<input name="phone" dir="ltr" value="<?= e($u['phone']) ?>"></label>
            </div>
            <label>ایمیل<input name="email" type="email" dir="ltr" value="<?= e($u['email']) ?>"></label>
            <hr>
            <div class="row2">
                <label>رمز فعلی (برای تغییر رمز)<input name="old_password" type="password"></label>
                <label>رمز جدید<input name="new_password" type="password"></label>
            </div>
            <button class="btn btn-primary" type="submit">ذخیره تغییرات</button>
        </form>
    <?php else: ?>
        <div class="empty">صفحه یافت نشد.</div>
    <?php endif; ?>
    </section>
</div>
<?php include __DIR__ . '/inc/footer.php'; ?>
