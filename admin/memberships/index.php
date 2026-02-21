<?php
declare(strict_types=1);
require __DIR__ . '/../../inc/bootstrap.php';
require __DIR__ . '/../_ui.php';
require_admin();
require_permission('membership.view');
ensure_membership_applications_table();

$rows = db()->query('SELECT id, first_name, last_name, personal_id, phone, university, faculty, email, additional_info, created_at FROM membership_applications ORDER BY created_at DESC, id DESC')->fetchAll();
?>
<!doctype html>
<html lang="en">
<?php admin_head('Admin — Membership Applications'); ?>
<body class="admin-body">
  <div class="admin-wrap">
    <?php admin_topbar('Membership Applications', [
      ['href' => url('admin/news/index.php'), 'label' => 'News Admin'],
      ['href' => url('admin/logout.php'), 'label' => 'Logout'],
    ]); ?>

    <div class="admin-card">
      <table class="admin-table">
        <thead>
          <tr>
            <th>ID</th><th>Name</th><th>Personal ID</th><th>Phone</th><th>University</th><th>Faculty</th><th>Email</th><th>Other Info</th><th>Date</th>
          </tr>
        </thead>
        <tbody>
          <?php if(!$rows): ?>
            <tr><td colspan="9">No membership applications yet.</td></tr>
          <?php else: foreach($rows as $r): ?>
            <tr>
              <td><?= (int)$r['id'] ?></td>
              <td><?= h((string)$r['first_name'] . ' ' . (string)$r['last_name']) ?></td>
              <td><?= h((string)$r['personal_id']) ?></td>
              <td><?= h((string)$r['phone']) ?></td>
              <td><?= h((string)$r['university']) ?></td>
              <td><?= h((string)$r['faculty']) ?></td>
              <td><?= h((string)($r['email'] ?? '')) ?></td>
              <td><?= nl2br(h((string)($r['additional_info'] ?? ''))) ?></td>
              <td><?= h((string)$r['created_at']) ?></td>
            </tr>
          <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</body>
</html>
