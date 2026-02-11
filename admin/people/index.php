<?php
declare(strict_types=1);

require __DIR__ . '/../../inc/bootstrap.php';
require_once __DIR__ . '/../_ui.php';

require_admin();
require_permission('people.manage');
ensure_people_profiles_table();

$pageLabels = people_page_labels();
$error = '';

function people_upload_dir_abs() {
  return dirname(__DIR__, 2)
    . DIRECTORY_SEPARATOR . 'assets'
    . DIRECTORY_SEPARATOR . 'people'
    . DIRECTORY_SEPARATOR . 'uploads';
}

function store_people_upload($file) {
  $tmpName = isset($file['tmp_name']) ? (string)$file['tmp_name'] : '';
  $origName = isset($file['name']) ? (string)$file['name'] : '';

  if ($origName === '' || !is_uploaded_file($tmpName)) {
    return null;
  }

  $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
  if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp'], true)) {
    return null;
  }

  $dir = people_upload_dir_abs();
  if (!is_dir($dir)) {
    if (!mkdir($dir, 0775, true) && !is_dir($dir)) {
      return null;
    }
  }

  $filename = 'person_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
  $destAbs = $dir . DIRECTORY_SEPARATOR . $filename;

  if (!move_uploaded_file($tmpName, $destAbs)) {
    return null;
  }

  return 'assets/people/uploads/' . $filename;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  csrf_verify();
  $action = isset($_POST['action']) ? (string)$_POST['action'] : '';

  if ($action === 'create') {
    $pageKey = isset($_POST['page_key']) ? (string)$_POST['page_key'] : '';
    $firstName = trim((string)($_POST['first_name'] ?? ''));
    $lastName = trim((string)($_POST['last_name'] ?? ''));
    $roleTitle = trim((string)($_POST['role_title'] ?? ''));
    $sortOrder = (int)($_POST['sort_order'] ?? 0);

    if (!isset($pageLabels[$pageKey])) {
      $error = 'Invalid page key';
    } elseif ($firstName === '' || $lastName === '') {
      $error = 'First name and last name are required';
    } else {
      $imagePath = trim((string)($_POST['image_path'] ?? ''));

      if (!empty($_FILES['image_file']['name'])) {
        $uploaded = store_people_upload($_FILES['image_file']);
        if ($uploaded === null) {
          $error = 'Image upload failed. Allowed: jpg, jpeg, png, webp';
        } else {
          $imagePath = $uploaded;
        }
      }

      if ($error === '') {
        $stmt = db()->prepare('INSERT INTO people_profiles (page_key, first_name, last_name, role_title, image_path, sort_order, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())');
        $stmt->execute([$pageKey, $firstName, $lastName, $roleTitle, $imagePath, $sortOrder]);
        header('Location: ' . url('admin/people/index.php'));
        exit;
      }
    }
  }

  if ($action === 'delete') {
    $id = (int)($_POST['id'] ?? 0);
    if ($id > 0) {
      $stmt = db()->prepare('DELETE FROM people_profiles WHERE id=? LIMIT 1');
      $stmt->execute([$id]);
    }
    header('Location: ' . url('admin/people/index.php'));
    exit;
  }
}

$rows = db()->query('SELECT id, page_key, first_name, last_name, role_title, image_path, sort_order FROM people_profiles ORDER BY page_key ASC, sort_order ASC, id ASC')->fetchAll();
?>
<!doctype html>
<html lang="en">
<?php admin_head('Admin — People'); ?>
<body class="admin-body">
  <div class="admin-wrap">
    <?php admin_topbar('People Admin', [
      ['href' => url('admin/news/index.php'), 'label' => 'News Admin'],
      ['href' => url('admin/logout.php'), 'label' => 'Logout'],
    ]); ?>

    <div class="admin-card">
      <h3 style="margin:0 0 10px">Add Team Member</h3>
      <?php if ($error !== ''): ?>
        <div class="err"><?=h($error)?></div>
      <?php endif; ?>

      <form method="post" enctype="multipart/form-data">
        <input type="hidden" name="_csrf" value="<?=h(csrf_token())?>">
        <input type="hidden" name="action" value="create">

        <div class="grid-3">
          <div><label>Page</label><select name="page_key" required><?php foreach ($pageLabels as $k => $label) { ?><option value="<?=h($k)?>"><?=h($label)?></option><?php } ?></select></div>
          <div><label>First name</label><input name="first_name" required></div>
          <div><label>Last name</label><input name="last_name" required></div>
          <div><label>Role/Position</label><input name="role_title"></div>
          <div><label>Sort order</label><input type="number" name="sort_order" value="0"></div>
          <div><label>Image upload</label><input type="file" name="image_file" accept=".jpg,.jpeg,.png,.webp"></div>
        </div>

        <div style="margin-top:10px">
          <label>Or image path / URL</label>
          <input name="image_path" placeholder="assets/people/uploads/pic.jpg or https://...">
        </div>

        <div style="margin-top:12px"><button class="btn" type="submit">Add Member</button></div>
      </form>
    </div>

    <div class="admin-card">
      <h3 style="margin:0 0 6px">Members</h3>
      <table class="admin-table">
        <thead>
          <tr><th>Photo</th><th>Page</th><th>Name</th><th>Position</th><th>Sort</th><th>Action</th></tr>
        </thead>
        <tbody>
          <?php foreach ($rows as $r) { ?>
            <tr>
              <td><?php if ((string)$r['image_path'] !== '') { ?><img src="<?=h(normalize_image_path((string)$r['image_path']))?>" alt="" style="width:54px;height:54px;border-radius:10px;object-fit:cover"><?php } ?></td>
              <td><?=h($pageLabels[(string)$r['page_key']] ?? (string)$r['page_key'])?></td>
              <td><?=h(trim(((string)$r['first_name']) . ' ' . ((string)$r['last_name'])))?></td>
              <td><?=h((string)$r['role_title'])?></td>
              <td><?= (int)$r['sort_order'] ?></td>
              <td>
                <form method="post" onsubmit="return confirm('Delete member?')">
                  <input type="hidden" name="_csrf" value="<?=h(csrf_token())?>">
                  <input type="hidden" name="action" value="delete">
                  <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                  <button class="btn secondary" type="submit">Delete</button>
                </form>
              </td>
            </tr>
          <?php } ?>
        </tbody>
      </table>
    </div>
  </div>
</body>
</html>
