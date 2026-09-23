<?php
require_once __DIR__ . '/../app/init.php';
$pageTitle = 'پیام‌رسان داخلی';
$u = current_user();
$isAdmin = is_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'send') {
    csrf_verify();
    $subject = trim($_POST['subject'] ?? '');
    $body = trim($_POST['body'] ?? '');
    $to = (int)($_POST['to'] ?? 0);
    $replyTo = (int)($_POST['reply_to'] ?? 0);
    if (mb_strlen($subject) < 2 || mb_strlen($body) < 2) {
        flash_set('e', 'موضوع و متن پیام لازم است.');
    } elseif (!$to) {
        flash_set('e', 'گیرنده را انتخاب کنید.');
    } else {
        q("INSERT INTO messages (sender_id, receiver_id, subject, body, is_read, created_at) VALUES (?,?,?,?,0,?)", [$u['id'], $to, $subject, $body, now()]);
        if ($replyTo) q("UPDATE messages SET is_read = 1 WHERE id = ?", [$replyTo]);
        flash_set('s', 'پیام ارسال شد.');
        redirect('messages.php');
    }
}

/* ارسال همگانی (فقط مدیر) */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'broadcast') {
    csrf_verify();
    if (!$isAdmin) { flash_set('e', 'فقط مدیر.'); redirect('messages.php'); }
    $subject = trim($_POST['subject'] ?? '');
    $body = trim($_POST['body'] ?? '');
    $role = ($_POST['role'] ?? 'all') === 'customer' ? 'customer' : (($_POST['role'] ?? '') === 'seller' ? 'seller' : null);
    if (mb_strlen($subject) < 2 || mb_strlen($body) < 2) {
        flash_set('e', 'موضوع و متن لازم است.');
    } else {
        $sql = "SELECT id FROM users WHERE role IN ('customer','seller','admin') AND id != ?";
        $params = [$u['id']];
        if ($role) { $sql .= ' AND role = ?'; $params[] = $role; }
        $n = 0;
        foreach (q($sql, $params)->fetchAll() as $r) {
            q("INSERT INTO messages (sender_id, receiver_id, subject, body, is_read, created_at) VALUES (?,?,?,?,0,?)", [$u['id'], $r['id'], $subject, $body, now()]);
            $n++;
        }
        flash_set('s', "پیام برای " . fa_num($n) . " کاربر ارسال شد.");
        redirect('messages.php');
    }
}

if (isset($_GET['read'])) {
    q("UPDATE messages SET is_read = 1 WHERE id = ? AND receiver_id = ?", [(int)$_GET['read'], $u['id']]);
    redirect('messages.php?view=' . (int)$_GET['read']);
}

$inbox = q("SELECT m.*, s.name AS sname FROM messages m LEFT JOIN users s ON s.id = m.sender_id
            WHERE m.receiver_id = ? ORDER BY m.id DESC LIMIT 60", [$u['id']])->fetchAll();
$sent = q("SELECT m.*, r.name AS rname FROM messages m LEFT JOIN users r ON r.id = m.receiver_id
           WHERE m.sender_id = ? ORDER BY m.id DESC LIMIT 60", [$u['id']])->fetchAll();
$view = !empty($_GET['view']) ? q1("SELECT m.*, s.name AS sname FROM messages m LEFT JOIN users s ON s.id = m.sender_id
            WHERE m.id = ? AND (m.receiver_id = ? OR m.sender_id = ?)", [(int)$_GET['view'], $u['id'], $u['id']]) : null;
$people = q("SELECT id, name, role, shop_name FROM users WHERE id != ? ORDER BY role, name", [$u['id']])->fetchAll();

include __DIR__ . '/inc/header.php';
?>
<div class="cols-2">
    <div class="p-box">
        <div class="box-head"><h3><?= icon('inbox') ?> صندوق ورودی</h3></div>
        <?php if (!$inbox): ?><p class="muted">پیامی نیست.</p><?php endif; ?>
        <?php foreach ($inbox as $m): ?>
            <a class="msg-row <?= (int)$m['is_read'] ? '' : 'unread' ?>" href="messages.php?view=<?= (int)$m['id'] ?>">
                <b><?= e($m['subject']) ?></b>
                <small class="muted">از <?= e($m['sname'] ?: 'سیستم') ?> · <?= jdate_human($m['created_at']) ?></small>
            </a>
        <?php endforeach; ?>
        <div class="box-head" style="margin-top:18px"><h3><?= icon('send') ?> ارسالی</h3></div>
        <?php if (!$sent): ?><p class="muted">پیامی ارسال نکرده‌اید.</p><?php endif; ?>
        <?php foreach ($sent as $m): ?>
            <a class="msg-row" href="messages.php?view=<?= (int)$m['id'] ?>">
                <b><?= e($m['subject']) ?></b>
                <small class="muted">به <?= e($m['rname']) ?> · <?= jdate_human($m['created_at']) ?></small>
            </a>
        <?php endforeach; ?>
    </div>

    <div>
        <div class="p-box">
            <h3><?= icon('mail') ?> پیام جدید</h3>
            <form method="post" class="p-form">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="send">
                <label>گیرنده
                    <select name="to" required>
                        <option value="">— انتخاب گیرنده —</option>
                        <?php foreach ($people as $pp): ?>
                            <option value="<?= (int)$pp['id'] ?>"><?= e($pp['name']) ?> — <?= ['admin' => 'مدیر', 'seller' => $pp['shop_name'] ?: 'فروشنده', 'customer' => 'مشتری'][$pp['role']] ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label>موضوع<input name="subject" required></label>
                <label>متن پیام<textarea name="body" rows="4" required></textarea></label>
                <button class="btn btn-primary" type="submit">ارسال</button>
            </form>
        </div>

        <?php if ($isAdmin): ?>
        <div class="p-box">
            <h3><?= icon('megaphone') ?> پیام همگانی</h3>
            <form method="post" class="p-form">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="broadcast">
                <label>گروه گیرندگان
                    <select name="role">
                        <option value="all">همه (مشتریان + فروشندگان)</option>
                        <option value="customer">فقط مشتریان</option>
                        <option value="seller">فقط فروشندگان</option>
                    </select>
                </label>
                <label>موضوع<input name="subject" required></label>
                <label>متن<textarea name="body" rows="3" required></textarea></label>
                <button class="btn btn-gold" type="submit" onclick="return confirm('برای همه اعضای گروه ارسال شود؟')">ارسال همگانی</button>
            </form>
        </div>
        <?php endif; ?>

        <?php if ($view): ?>
        <div class="p-box">
            <div class="box-head"><h3><?= icon('message-square') ?> <?= e($view['subject']) ?></h3></div>
            <div class="msg-bubble <?= (int)$view['sender_id'] === (int)$u['id'] ? 'mine' : '' ?>">
                <div class="msg-meta"><?= (int)$view['sender_id'] === (int)$u['id'] ? 'شما' : e($view['sname'] ?: 'سیستم') ?> · <?= jdate_human($view['created_at']) ?></div>
                <p><?= nl2br(e($view['body'])) ?></p>
            </div>
            <?php if ((int)$view['receiver_id'] === (int)$u['id'] && $view['sender_id']): ?>
            <form method="post" class="p-form" style="margin-top:10px">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="send">
                <input type="hidden" name="to" value="<?= (int)$view['sender_id'] ?>">
                <input type="hidden" name="reply_to" value="<?= (int)$view['id'] ?>">
                <label>پاسخ سریع<input name="subject" value="پاسخ: <?= e($view['subject']) ?>" required></label>
                <textarea name="body" rows="2" required placeholder="پاسخ…"></textarea>
                <button class="btn btn-primary btn-sm" type="submit">پاسخ</button>
            </form>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>
</div>
<?php include __DIR__ . '/inc/footer.php'; ?>
