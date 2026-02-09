<?php
declare(strict_types=1);
require __DIR__ . '/../../inc/bootstrap.php';
require_admin();
require_permission('contact.view');
ensure_contact_messages_table();

$rows = db()->query("SELECT id, name, email, phone, message, created_at FROM contact_messages ORDER BY created_at DESC, id DESC")->fetchAll();
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Admin — Contact Messages</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <style>
    body{font-family:system-ui;background:#0b1220;color:#e5e7eb;margin:0}
    .wrap{max-width:1100px;margin:22px auto;padding:0 14px}
    a{color:#93c5fd;text-decoration:none}
    table{width:100%;border-collapse:collapse;margin-top:16px;background:#111a2e;border:1px solid rgba(255,255,255,.12);border-radius:16px;overflow:hidden}
    th,td{padding:12px;border-bottom:1px solid rgba(255,255,255,.10);text-align:left;font-size:14px;vertical-align:top}
    th{opacity:.85}
  </style>
</head>
<body>
  <div class="wrap">
    <div style="display:flex;justify-content:space-between;align-items:center;gap:12px">
      <h2 style="margin:0">Contact Messages</h2>
      <a href="<?=h(url('admin/news/index.php'))?>">← News Admin</a>
    </div>

    <table>
      <thead>
        <tr>
          <th>ID</th>
          <th>Name</th>
          <th>Email</th>
          <th>Phone</th>
          <th>Message</th>
          <th>Date</th>
        </tr>
      </thead>
      <tbody>
      <?php if(!$rows): ?>
        <tr><td colspan="6">No messages yet.</td></tr>
      <?php else: ?>
        <?php foreach($rows as $r): ?>
          <tr>
            <td><?= (int)$r['id'] ?></td>
            <td><?= h((string)$r['name']) ?></td>
            <td><?= h((string)$r['email']) ?></td>
            <td><?= h((string)($r['phone'] ?? '')) ?></td>
            <td><?= nl2br(h((string)$r['message'])) ?></td>
            <td><?= h((string)$r['created_at']) ?></td>
          </tr>
        <?php endforeach; ?>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
</body>
</html>
