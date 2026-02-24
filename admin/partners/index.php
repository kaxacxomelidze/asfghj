<?php
declare(strict_types=1);
require __DIR__ . '/../../inc/bootstrap.php';
require __DIR__ . '/../_ui.php';

require_admin();
require_permission('partners.manage');
ensure_partner_logos_table();

$msg = '';
$err = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  csrf_verify();
  $action = (string)($_POST['action'] ?? '');

  try {
    if ($action === 'add') {
      $path = resolve_image_input('image_file', 'image_path', 'assets/partners');
      $path = trim((string)$path);
      if ($path === '') {
        throw new RuntimeException('Partner logo image is required.');
      }
      $sortOrder = (int)($_POST['sort_order'] ?? 0);
      $active = isset($_POST['is_active']) ? 1 : 0;

      $stmt = db()->prepare('INSERT INTO partner_logos (image_path, sort_order, is_active, created_at) VALUES (?, ?, ?, ?)');
      $stmt->execute([$path, $sortOrder, $active, date('Y-m-d H:i:s')]);
      $msg = 'Partner logo added.';
    }

    if ($action === 'delete') {
      $id = (int)($_POST['id'] ?? 0);
      if ($id <= 0) throw new RuntimeException('Invalid partner ID.');
      $stmt = db()->prepare('DELETE FROM partner_logos WHERE id=?');
      $stmt->execute([$id]);
      $msg = 'Partner logo removed.';
    }

    if ($action === 'toggle') {
      $id = (int)($_POST['id'] ?? 0);
      if ($id <= 0) throw new RuntimeException('Invalid partner ID.');
      $stmt = db()->prepare('UPDATE partner_logos SET is_active = CASE WHEN is_active=1 THEN 0 ELSE 1 END WHERE id=?');
      $stmt->execute([$id]);
      $msg = 'Partner logo status updated.';
    }
  } catch (Throwable $e) {
    $err = $e->getMessage() ?: 'Action failed.';
  }
}

$rows = [];
try {
  $rows = db()->query('SELECT id, image_path, sort_order, is_active, created_at FROM partner_logos ORDER BY sort_order ASC, id DESC')->fetchAll();
} catch (Throwable $e) {
  $rows = [];
}
?>
<!doctype html>
<html lang="en">
<?php admin_head('Admin — Partners'); ?>
<body class="admin-body">
  <div class="admin-wrap">
    <?php admin_topbar('Partners Logos Manager', [
      ['href' => url('admin/news/index.php'), 'label' => 'News Admin'],
      ['href' => url('admin/logout.php'), 'label' => 'Logout'],
    ]); ?>

    <?php if($msg): ?><div class="ok"><?=h($msg)?></div><?php endif; ?>
    <?php if($err): ?><div class="err"><?=h($err)?></div><?php endif; ?>

    <div class="admin-card">
      <h3 style="margin:0 0 12px">Add partner logo</h3>
      <form method="post" enctype="multipart/form-data" class="grid-2">
        <input type="hidden" name="_csrf" value="<?=h(csrf_token())?>">
        <input type="hidden" name="action" value="add">

        <div>
          <label>Upload logo image</label>
          <input type="file" name="image_file" accept="image/*">
        </div>

        <div>
          <label>Or image path / URL</label>
          <input type="text" name="image_path" placeholder="assets/partners/logo.png or https://...">
        </div>

        <div>
          <label>Sort order</label>
          <input type="number" name="sort_order" value="0">
        </div>

        <div>
          <label><input type="checkbox" name="is_active" checked> Active on homepage</label>
        </div>

        <div style="grid-column:1/-1">
          <button class="btn" type="submit">Save logo</button>
        </div>
      </form>
    </div>

    <div class="admin-card" style="margin-top:14px">
      <table class="admin-table">
        <thead><tr><th>ID</th><th>Logo</th><th>Path</th><th>Sort</th><th>Status</th><th>Actions</th></tr></thead>
        <tbody>
          <?php if(!$rows): ?>
            <tr><td colspan="6">No partner logos added yet.</td></tr>
          <?php else: foreach($rows as $r): ?>
            <tr>
              <td><?= (int)$r['id'] ?></td>
              <td><img src="<?=h(normalize_image_path((string)$r['image_path']))?>" alt="partner" style="width:90px;height:46px;object-fit:contain;background:#fff;border:1px solid #dbe2ea;border-radius:8px;padding:6px"></td>
              <td><?= h((string)$r['image_path']) ?></td>
              <td><?= (int)$r['sort_order'] ?></td>
              <td><?= (int)$r['is_active'] === 1 ? '<span class="pill">Active</span>' : '<span class="pill off">Hidden</span>' ?></td>
              <td style="display:flex;gap:8px;align-items:center">
                <form method="post" style="display:inline">
                  <input type="hidden" name="_csrf" value="<?=h(csrf_token())?>">
                  <input type="hidden" name="action" value="toggle">
                  <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                  <button class="btn secondary" type="submit">Toggle</button>
                </form>
                <form method="post" style="display:inline" onsubmit="return confirm('Delete this logo?')">
                  <input type="hidden" name="_csrf" value="<?=h(csrf_token())?>">
                  <input type="hidden" name="action" value="delete">
                  <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                  <button class="btn secondary" type="submit">Delete</button>
                </form>
              </td>
            </tr>
          <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</body>
</html>
