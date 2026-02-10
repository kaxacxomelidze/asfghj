<?php
declare(strict_types=1);
require __DIR__ . '/../../inc/bootstrap.php';
require __DIR__ . '/../_ui.php';
require_admin();
require_permission('contact.view');
ensure_contact_messages_table();

$rows = db()->query('SELECT id, name, email, phone, message, created_at FROM contact_messages ORDER BY created_at DESC, id DESC')->fetchAll();
?>
<!doctype html>
<html lang="en">
<?php admin_head('Admin — Contact Messages'); ?>
<body class="admin-body">
  <div class="admin-wrap">
    <?php admin_topbar('Contact Messages', [
      ['href' => url('admin/news/index.php'), 'label' => 'News Admin'],
      ['href' => url('admin/logout.php'), 'label' => 'Logout'],
    ]); ?>

    <div class="admin-card">
      <table class="admin-table">
        <thead>
          <tr><th>ID</th><th>Name</th><th>Email</th><th>Phone</th><th>Message</th><th>Date</th></tr>
        </thead>
        <tbody>
          <?php if(!$rows): ?>
            <tr><td colspan="6">No messages yet.</td></tr>
          <?php else: foreach($rows as $r): ?>
            <tr>
              <td><?= (int)$r['id'] ?></td>
              <td><?= h((string)$r['name']) ?></td>
              <td><?= h((string)$r['email']) ?></td>
              <td><?= h((string)($r['phone'] ?? '')) ?></td>
              <td><?= nl2br(h((string)$r['message'])) ?></td>
              <td><?= h((string)$r['created_at']) ?></td>
            </tr>
          <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</body>
</html>
