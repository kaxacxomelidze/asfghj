<?php
declare(strict_types=1);
require __DIR__ . '/../../inc/bootstrap.php';
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
      $stmt = db()->prepare("SELECT id FROM admins WHERE username=? LIMIT 1");
      $stmt->execute([$username]);
      if ($stmt->fetch()) {
        $errors[] = 'Username already exists';
      } else {
        $stmt = db()->prepare("INSERT INTO admins (username, password_hash) VALUES (?, ?)");
        $stmt->execute([$username, password_hash($password, PASSWORD_DEFAULT)]);
        $adminId = (int)db()->lastInsertId();

        if (is_array($perms) && $perms) {
          $stmt = db()->prepare("INSERT INTO admin_permissions (admin_id, permission) VALUES (?, ?)");
          foreach ($perms as $perm) {
            $stmt->execute([$adminId, (string)$perm]);
          }
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
        $stmt = db()->prepare("UPDATE admins SET password_hash=? WHERE id=?");
        $stmt->execute([password_hash($password, PASSWORD_DEFAULT), $adminId]);
      }

      $stmt = db()->prepare("DELETE FROM admin_permissions WHERE admin_id=?");
      $stmt->execute([$adminId]);

      if (is_array($perms) && $perms) {
        $stmt = db()->prepare("INSERT INTO admin_permissions (admin_id, permission) VALUES (?, ?)");
        foreach ($perms as $perm) {
          $stmt->execute([$adminId, (string)$perm]);
        }
      }
      $notice = 'Admin updated.';
    }
  }
}

$admins = db()->query("SELECT id, username FROM admins ORDER BY id ASC")->fetchAll();
$permissionsMap = [];
foreach ($admins as $admin) {
  $permissionsMap[(int)$admin['id']] = admin_permissions((int)$admin['id']);
}
$availablePermissions = available_admin_permissions();
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Admin — Users</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <style>
    body{font-family:system-ui;background:#0b1220;color:#e5e7eb;margin:0}
    .wrap{max-width:1100px;margin:22px auto;padding:0 14px}
    a{color:#93c5fd;text-decoration:none}
    .card{background:#111a2e;border:1px solid rgba(255,255,255,.12);border-radius:16px;padding:16px;margin-top:16px}
    input{padding:10px;border-radius:10px;border:1px solid rgba(255,255,255,.18);background:#0b1220;color:#fff}
    .err{color:#fca5a5;margin:10px 0}
    .ok{color:#86efac;margin:10px 0}
  </style>
</head>
<body>
  <div class="wrap">
    <div style="display:flex;justify-content:space-between;align-items:center;gap:12px">
      <h2 style="margin:0">Admins</h2>
      <div>
        <a href="<?=h(url('admin/news/index.php'))?>">← News Admin</a>
      </div>
    </div>

    <?php if($errors): ?>
      <div class="err"><?php foreach($errors as $e) echo '<div>'.h($e).'</div>'; ?></div>
    <?php endif; ?>
    <?php if($notice): ?>
      <div class="ok"><?=h($notice)?></div>
    <?php endif; ?>

    <div class="card">
      <h3 style="margin:0 0 10px">Add Admin</h3>
      <form method="post">
        <input type="hidden" name="_csrf" value="<?=h(csrf_token())?>">
        <input type="hidden" name="action" value="add">

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
          <div>
            <label>Username</label><br>
            <input name="username" required>
          </div>
          <div>
            <label>Password</label><br>
            <input name="password" type="password" required>
          </div>
        </div>

        <div style="margin-top:12px">
          <label>Permissions</label>
          <div style="margin-top:8px">
            <?php foreach($availablePermissions as $key => $label): ?>
              <label style="display:block;margin-bottom:6px">
                <input type="checkbox" name="perms[]" value="<?=h($key)?>">
                <?=h($label)?>
              </label>
            <?php endforeach; ?>
          </div>
        </div>

        <div style="margin-top:12px">
          <button type="submit">Add Admin</button>
        </div>
      </form>
    </div>

    <?php foreach($admins as $admin): ?>
      <?php $adminId = (int)$admin['id']; ?>
      <div class="card">
        <h3 style="margin:0 0 10px">Edit <?=h((string)$admin['username'])?></h3>
        <form method="post">
          <input type="hidden" name="_csrf" value="<?=h(csrf_token())?>">
          <input type="hidden" name="action" value="update">
          <input type="hidden" name="admin_id" value="<?= $adminId ?>">

          <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
            <div>
              <label>Username</label><br>
              <input value="<?=h((string)$admin['username'])?>" disabled>
            </div>
            <div>
              <label>New password (optional)</label><br>
              <input name="password" type="password">
            </div>
          </div>

          <div style="margin-top:12px">
            <label>Permissions</label>
            <div style="margin-top:8px">
              <?php foreach($availablePermissions as $key => $label): ?>
                <label style="display:block;margin-bottom:6px">
                  <input type="checkbox" name="perms[]" value="<?=h($key)?>" <?= in_array($key, $permissionsMap[$adminId] ?? [], true) ? 'checked' : '' ?>>
                  <?=h($label)?>
                </label>
              <?php endforeach; ?>
            </div>
          </div>

          <div style="margin-top:12px">
            <button type="submit">Save Changes</button>
          </div>
        </form>
      </div>
    <?php endforeach; ?>
  </div>
</body>
</html>
