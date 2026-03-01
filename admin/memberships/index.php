<?php
declare(strict_types=1);
require __DIR__ . '/../../inc/bootstrap.php';
require __DIR__ . '/../_ui.php';
require_admin();
require_permission('membership.view');
ensure_membership_applications_table();

try {
  $rows = db()->query('SELECT id, full_name, phone, email, university_info, age, legal_address, desired_direction, motivation_text, created_at FROM membership_applications ORDER BY created_at DESC, id DESC')->fetchAll();
} catch (Throwable $e) {
  // Fallback for older schema deployments
  $legacyRows = db()->query('SELECT id, first_name, last_name, personal_id, phone, university, faculty, email, additional_info, created_at FROM membership_applications ORDER BY created_at DESC, id DESC')->fetchAll();
  $rows = [];
  foreach ($legacyRows as $r) {
    $rows[] = [
      'id' => $r['id'],
      'full_name' => $r['first_name'] ?? '',
      'phone' => $r['phone'] ?? '',
      'email' => $r['email'] ?? '',
      'university_info' => $r['university'] ?? '',
      'age' => $r['last_name'] ?? '',
      'legal_address' => $r['personal_id'] ?? '',
      'desired_direction' => $r['faculty'] ?? '',
      'motivation_text' => $r['additional_info'] ?? '',
      'created_at' => $r['created_at'] ?? '',
    ];
  }
}

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

    <div class="grid-3">
      <div class="admin-card"><label>Total</label><div style="font-size:30px;font-weight:900"><?= count($rows) ?></div></div>
      <?php $todayCount = 0; foreach($rows as $r){ if (str_starts_with((string)$r['created_at'], date('Y-m-d'))) $todayCount++; } ?>
      <div class="admin-card"><label>Today</label><div style="font-size:30px;font-weight:900;color:#93c5fd"><?= $todayCount ?></div></div>
      <div class="admin-card"><label>Latest</label><div style="font-size:15px;font-weight:700;color:#cbd5e1"><?= $rows ? h((string)$rows[0]['created_at']) : '—' ?></div></div>
    </div>

    <div class="admin-card" style="margin-top:14px">
      <div style="margin-bottom:10px;color:#9fb2cc;font-size:13px">Updated membership form fields: full profile, direction, and motivation (max 50 words).</div>
      <table class="admin-table">
        <thead>
          <tr>
            <th>ID</th>
            <th>სახელი გვარი</th>
            <th>ტელეფონი</th>
            <th>ელ. ფოსტა</th>
            <th>უნივერსიტეტი/ფაკულტეტი/კურსი</th>
            <th>ასაკი</th>
            <th>იურიდიული მისამართი</th>
            <th>სასურველი მიმართულება</th>
            <th>მოტივაცია</th>
            <th>თარიღი</th>
          </tr>
        </thead>
        <tbody>
          <?php if(!$rows): ?>
            <tr><td colspan="10">No membership applications yet.</td></tr>
          <?php else: foreach($rows as $r): ?>
            <tr>
              <td><?= (int)$r['id'] ?></td>
              <td><?= h((string)($r['full_name'] ?? '')) ?></td>
              <td><?= h((string)($r['phone'] ?? '')) ?></td>
              <td><?= h((string)($r['email'] ?? '')) ?></td>
              <td><?= h((string)($r['university_info'] ?? '')) ?></td>
              <td><?= h((string)($r['age'] ?? '')) ?></td>
              <td><?= h((string)($r['legal_address'] ?? '')) ?></td>
              <td><?= h((string)($r['desired_direction'] ?? '')) ?></td>
              <td style="min-width:260px"><?= nl2br(h((string)($r['motivation_text'] ?? ''))) ?></td>
              <td><?= h((string)$r['created_at']) ?></td>
            </tr>
          <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</body>
</html>
