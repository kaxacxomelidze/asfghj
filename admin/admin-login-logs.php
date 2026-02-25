<?php
require __DIR__ . '/../inc/bootstrap.php';
require __DIR__ . '/_ui.php';
require_admin();
require_permission('admin.logs.view');
ensure_admin_login_logs_table();
ensure_admin_activity_logs_table();

$rows = [];
try {
  $rows = db()->query('SELECT id, username, admin_id, ip_address, user_agent, status, reason, created_at FROM admin_login_logs ORDER BY id DESC LIMIT 500')->fetchAll();

} catch (Throwable $e) {
  $rows = [];
}

$activityRows = [];
try {
  $activityRows = db()->query('SELECT id, admin_id, username, action, entity_type, entity_id, details, ip_address, created_at FROM admin_activity_logs ORDER BY id DESC LIMIT 500')->fetchAll();
} catch (Throwable $e) {
  $activityRows = [];
}
?>
<!doctype html>
<html lang="en">
<?php admin_head('Admin — Login Logs'); ?>
<body class="admin-body">
  <div class="admin-wrap">
    <?php admin_topbar('Admin Logs', [
      ['href' => url('admin/news/index.php'), 'label' => 'News Admin'],
      ['href' => url('admin/partners/index.php'), 'label' => 'Partners'],
      ['href' => url('admin/university.php'), 'label' => 'University System'],
      ['href' => url('admin/logout.php'), 'label' => 'Logout'],
    ]); ?>

    <div class="grid-3">
      <div class="admin-card"><label>Total records</label><div style="font-size:30px;font-weight:900"><?=count($rows)?></div></div>
      <?php $successCount = 0; foreach($rows as $r){ if((string)$r['status']==='success') $successCount++; } $failedCount = count($rows) - $successCount; ?>
      <div class="admin-card"><label>Success</label><div style="font-size:30px;font-weight:900;color:#86efac"><?= $successCount ?></div></div>
      <div class="admin-card"><label>Failed/Blocked</label><div style="font-size:30px;font-weight:900;color:#fca5a5"><?= $failedCount ?></div></div>
    </div>

    <div class="admin-card" style="margin-top:14px">
      <table class="admin-table">
        <thead><tr><th>ID</th><th>Time</th><th>User</th><th>Status</th><th>Reason</th><th>IP</th><th>User Agent</th></tr></thead>
        <tbody>
          <?php if(!$rows): ?>
            <tr><td colspan="7">No login logs found.</td></tr>
          <?php else: foreach($rows as $r): ?>
            <tr>
              <td><?= (int)$r['id'] ?></td>
              <td><?= h((string)$r['created_at']) ?></td>
              <td><?= h((string)$r['username']) ?><?php if(!empty($r['admin_id'])): ?> (#<?= (int)$r['admin_id'] ?>)<?php endif; ?></td>
              <td><?= h((string)$r['status']) ?></td>
              <td><?= h((string)($r['reason'] ?? '')) ?></td>
              <td><?= h((string)($r['ip_address'] ?? '')) ?></td>
              <td style="max-width:360px;word-break:break-word"><?= h((string)($r['user_agent'] ?? '')) ?></td>
            </tr>
          <?php endforeach; endif; ?>
        </tbody>
      </table>
    

    <div class="grid-3" style="margin-top:14px">
      <div class="admin-card"><label>Activity records</label><div style="font-size:30px;font-weight:900"><?=count($activityRows)?></div></div>
      <?php $creates = 0; foreach($activityRows as $r){ if((string)$r['action']==='create') $creates++; } ?>
      <div class="admin-card"><label>Creates</label><div style="font-size:30px;font-weight:900;color:#86efac"><?= $creates ?></div></div>
      <div class="admin-card"><label>Other actions</label><div style="font-size:30px;font-weight:900;color:#93c5fd"><?= count($activityRows)-$creates ?></div></div>
    </div>

    <div class="admin-card" style="margin-top:14px">
      <h3 style="margin:0 0 10px">Admin Activity Logs (Create / Update / Delete / etc.)</h3>
      <table class="admin-table">
        <thead><tr><th>ID</th><th>Time</th><th>Admin</th><th>Action</th><th>Entity</th><th>Details</th><th>IP</th></tr></thead>
        <tbody>
          <?php if(!$activityRows): ?>
            <tr><td colspan="7">No activity logs found yet.</td></tr>
          <?php else: foreach($activityRows as $r): ?>
            <tr>
              <td><?= (int)$r['id'] ?></td>
              <td><?= h((string)$r['created_at']) ?></td>
              <td><?= h((string)$r['username']) ?> (#<?= (int)$r['admin_id'] ?>)</td>
              <td><?= h((string)$r['action']) ?></td>
              <td><?= h((string)$r['entity_type']) ?><?php if(!empty($r['entity_id'])): ?> #<?= (int)$r['entity_id'] ?><?php endif; ?></td>
              <td style="max-width:420px;word-break:break-word"><?= h((string)($r['details'] ?? '')) ?></td>
              <td><?= h((string)($r['ip_address'] ?? '')) ?></td>
            </tr>
          <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>

  </div>
</body>
</html>
