<?php
declare(strict_types=1);
require __DIR__ . '/../../inc/bootstrap.php';
require __DIR__ . '/../_ui.php';
require_admin();
require_permission('admins.manage');
ensure_admin_permissions_table();

$errors = [];
$notice = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  csrf_verify();
  $action = (string)($_POST['action'] ?? '');

  if ($action === 'add') {
    $username = trim((string)($_POST['username'] ?? ''));
    $password = (string)($_POST['password'] ?? '');
    $perms = $_POST['perms'] ?? [];

    if ($username === '') $errors[] = 'Username is required';
    if ($password === '') $errors[] = 'Password is required';

    if (!$errors) {
      $stmt = db()->prepare('SELECT id FROM admins WHERE username=? LIMIT 1');
      $stmt->execute([$username]);
      if ($stmt->fetch()) {
        $errors[] = 'Username already exists';
      } else {
        $stmt = db()->prepare('INSERT INTO admins (username, password_hash) VALUES (?, ?)');
        $stmt->execute([$username, password_hash($password, PASSWORD_DEFAULT)]);
        $adminId = (int)db()->lastInsertId();

        if (is_array($perms) && $perms) {
          $stmt = db()->prepare('INSERT INTO admin_permissions (admin_id, permission) VALUES (?, ?)');
          foreach ($perms as $perm) $stmt->execute([$adminId, (string)$perm]);
        }
        $notice = 'Admin added.';
      }
    }
  }

  if ($action === 'update') {
    $adminId = (int)($_POST['admin_id'] ?? 0);
    $password = (string)($_POST['password'] ?? '');
    $perms = $_POST['perms'] ?? [];

    if ($adminId > 0) {
      if ($password !== '') {
        $stmt = db()->prepare('UPDATE admins SET password_hash=? WHERE id=?');
        $stmt->execute([password_hash($password, PASSWORD_DEFAULT), $adminId]);
      }

      $stmt = db()->prepare('DELETE FROM admin_permissions WHERE admin_id=?');
      $stmt->execute([$adminId]);

      if (is_array($perms) && $perms) {
        $stmt = db()->prepare('INSERT INTO admin_permissions (admin_id, permission) VALUES (?, ?)');
        foreach ($perms as $perm) $stmt->execute([$adminId, (string)$perm]);
      }
      $notice = 'Admin updated.';
    }
  }
}

$admins = db()->query('SELECT id, username FROM admins ORDER BY id ASC')->fetchAll();
$permissionsMap = [];
foreach ($admins as $admin) $permissionsMap[(int)$admin['id']] = admin_permissions((int)$admin['id']);
$availablePermissions = available_admin_permissions();
?>
<!doctype html>
<html lang="en">
<?php admin_head('Admin — Users'); ?>
<body class="admin-body">
  <div class="admin-wrap">
    <?php admin_topbar('Admins', [
      ['href' => url('admin/news/index.php'), 'label' => 'News Admin'],
      ['href' => url('admin/logout.php'), 'label' => 'Logout'],
    ]); ?>

    <?php if($errors): ?><div class="err"><?php foreach($errors as $e) echo '<div>'.h($e).'</div>'; ?></div><?php endif; ?>
    <?php if($notice): ?><div class="ok"><?=h($notice)?></div><?php endif; ?>

    <div class="admin-card">
      <h3 style="margin:0 0 10px">Add Admin</h3>
      <form method="post">
        <input type="hidden" name="_csrf" value="<?=h(csrf_token())?>">
        <input type="hidden" name="action" value="add">
        <div class="grid-2">
          <div><label>Username</label><input name="username" required></div>
          <div><label>Password</label><input name="password" type="password" required></div>
        </div>
        <div style="margin-top:12px">
          <label>Permissions</label>
          <?php foreach($availablePermissions as $key => $label): ?>
            <label style="display:block;margin:6px 0"><input type="checkbox" name="perms[]" value="<?=h($key)?>"> <?=h($label)?></label>
          <?php endforeach; ?>
        </div>
        <div style="margin-top:12px"><button class="btn" type="submit">Add Admin</button></div>
      </form>
    </div>

    <?php foreach($admins as $admin): $adminId=(int)$admin['id']; ?>
      <div class="admin-card">
        <h3 style="margin:0 0 10px">Edit <?=h((string)$admin['username'])?></h3>
        <form method="post">
          <input type="hidden" name="_csrf" value="<?=h(csrf_token())?>">
          <input type="hidden" name="action" value="update">
          <input type="hidden" name="admin_id" value="<?= $adminId ?>">

          <div class="grid-2">
            <div><label>Username</label><input value="<?=h((string)$admin['username'])?>" disabled></div>
            <div><label>New password (optional)</label><input name="password" type="password"></div>
          </div>
          <div style="margin-top:12px">
            <label>Permissions</label>
            <?php foreach($availablePermissions as $key => $label): ?>
              <label style="display:block;margin:6px 0"><input type="checkbox" name="perms[]" value="<?=h($key)?>" <?= in_array($key, $permissionsMap[$adminId] ?? [], true) ? 'checked' : '' ?>> <?=h($label)?></label>
            <?php endforeach; ?>
          </div>

          <div style="margin-top:12px"><button class="btn" type="submit">Save Changes</button></div>
        </form>
      </div>
    <?php endforeach; ?>
  </div>
</body>
</html>
