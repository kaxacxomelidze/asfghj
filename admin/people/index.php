<?php
declare(strict_types=1);

require __DIR__ . '/../../inc/bootstrap.php';
require __DIR__ . '/../_ui.php';

require __DIR__ . '/../../inc/bootstrap.php';
require __DIR__ . '/../_ui.php';
require_admin();
require_permission('people.manage');
ensure_people_profiles_table();

$pageLabels = people_page_labels();
$error = '';

function people_upload_dir_abs(): string {
  return dirname(__DIR__, 2)
    . DIRECTORY_SEPARATOR . 'assets'
    . DIRECTORY_SEPARATOR . 'people'
    . DIRECTORY_SEPARATOR . 'uploads';
}

function store_people_upload(array $file): ?string {
  $tmpName = (string)($file['tmp_name'] ?? '');
  $origName = (string)($file['name'] ?? '');

  if ($origName === '' || !is_uploaded_file($tmpName)) {
    return null;
  }

  $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
  if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp'], true)) {
    return null;
  }

  $dir = people_upload_dir_abs();
  if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
    return null;
  }

  $filename = 'person_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
  $destAbs = $dir . DIRECTORY_SEPARATOR . $filename;

  if (!move_uploaded_file($tmpName, $destAbs)) {
    return null;
  }
  return dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'people' . DIRECTORY_SEPARATOR . 'uploads';
}

function store_people_upload(array $file): ?string {
  if (empty($file['name']) || !is_uploaded_file((string)($file['tmp_name'] ?? ''))) return null;
  $ext = strtolower(pathinfo((string)$file['name'], PATHINFO_EXTENSION));
  if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp'], true)) return null;

  $dir = people_upload_dir_abs();
  if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) return null;

  $filename = 'person_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
  $destAbs = $dir . DIRECTORY_SEPARATOR . $filename;
  if (!move_uploaded_file((string)$file['tmp_name'], $destAbs)) return null;

  return 'assets/people/uploads/' . $filename;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  csrf_verify();
  $action = (string)($_POST['action'] ?? '');

  if ($action === 'create') {
    $pageKey = (string)($_POST['page_key'] ?? '');
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

      if (!empty($_FILES['image_file']['name']) && is_uploaded_file($_FILES['image_file']['tmp_name'])) {
        $tmp = (string)$_FILES['image_file']['tmp_name'];
        $ext = strtolower(pathinfo((string)$_FILES['image_file']['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp'], true)) {
          $error = 'Allowed image types: jpg, jpeg, png, webp';
        } else {
          $filename = 'person_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
          $destRel = 'assets/people/uploads/' . $filename;
          $destAbs = __DIR__ . '/../../' . $destRel;
          if (!move_uploaded_file($tmp, $destAbs)) {
            $error = 'Failed to upload image';
          } else {
            $imagePath = $destRel;
          }
        }
      }

      if ($error === '') {
        $stmt = db()->prepare(
          'INSERT INTO people_profiles (page_key, first_name, last_name, role_title, image_path, sort_order, created_at)
           VALUES (?, ?, ?, ?, ?, ?, NOW())'
        );
        $stmt->execute([$pageKey, $firstName, $lastName, $roleTitle, $imagePath, $sortOrder]);

        $stmt = db()->prepare('INSERT INTO people_profiles (page_key, first_name, last_name, role_title, image_path, sort_order, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())');
        $stmt = db()->prepare("INSERT INTO people_profiles (page_key, first_name, last_name, role_title, image_path, sort_order, created_at)
          VALUES (?, ?, ?, ?, ?, ?, NOW())");
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

$rows = db()->query(
  'SELECT id, page_key, first_name, last_name, role_title, image_path, sort_order
   FROM people_profiles
   ORDER BY page_key ASC, sort_order ASC, id ASC'
)->fetchAll();
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

$rows = db()->query("SELECT id, page_key, first_name, last_name, role_title, image_path, sort_order
  FROM people_profiles
  ORDER BY page_key ASC, sort_order ASC, id ASC")->fetchAll();
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Admin — People</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <style>
    body{font-family:system-ui;background:#0b1220;color:#e5e7eb;margin:0}
    .wrap{max-width:1180px;margin:22px auto;padding:0 14px}
    a{color:#93c5fd;text-decoration:none}
    .top{display:flex;justify-content:space-between;align-items:center;gap:12px}
    .card{background:#111a2e;border:1px solid rgba(255,255,255,.12);border-radius:16px;padding:16px;margin-top:14px}
    input,select{width:100%;padding:10px;border-radius:10px;border:1px solid rgba(255,255,255,.18);background:#0b1220;color:#fff}
    button{padding:10px 14px;border-radius:10px;border:0;background:#2563eb;color:#fff;font-weight:700;cursor:pointer}
    table{width:100%;border-collapse:collapse;margin-top:10px}
    th,td{padding:10px;border-bottom:1px solid rgba(255,255,255,.10);text-align:left;font-size:14px}
    .grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:10px}
    .err{color:#fca5a5;margin-bottom:10px}
    img{width:54px;height:54px;object-fit:cover;border-radius:10px;border:1px solid rgba(255,255,255,.15)}
  </style>
</head>
<body>
  <div class="wrap">
    <div class="top">
      <h2 style="margin:0">People Admin</h2>
      <div>
        <a href="<?=h(url('admin/news/index.php'))?>">News Admin</a>
        <a style="margin-left:10px" href="<?=h(url('admin/logout.php'))?>">Logout</a>
      </div>
    </div>

    <div class="card">
      <h3 style="margin:0 0 10px">Add Team Member</h3>
      <?php if($error !== ''): ?><div class="err"><?=h($error)?></div><?php endif; ?>
      <form method="post" enctype="multipart/form-data">
        <input type="hidden" name="_csrf" value="<?=h(csrf_token())?>">
        <input type="hidden" name="action" value="create">

        <div class="grid-3">
          <div>
            <label>Page</label>
            <select name="page_key" required>
              <?php foreach ($pageLabels as $k => $label): ?>
          <div><label>Page</label><select name="page_key" required><?php foreach($pageLabels as $k=>$label): ?><option value="<?=h($k)?>"><?=h($label)?></option><?php endforeach; ?></select></div>
          <div><label>First name</label><input name="first_name" required></div>
          <div><label>Last name</label><input name="last_name" required></div>
          <div><label>Role/Position</label><input name="role_title"></div>
          <div><label>Sort order</label><input type="number" name="sort_order" value="0"></div>
          <div><label>Image upload</label><input type="file" name="image_file" accept=".jpg,.jpeg,.png,.webp"></div>
        </div>

        <div style="margin-top:10px"><label>Or image path / URL</label><input name="image_path" placeholder="assets/people/uploads/pic.jpg or https://..."></div>
        <div style="margin-top:12px"><button class="btn" type="submit">Add Member</button></div>
      </form>
    </div>

    <div class="admin-card">
      <h3 style="margin:0 0 6px">Members</h3>
      <table class="admin-table">
        <thead><tr><th>Photo</th><th>Page</th><th>Name</th><th>Position</th><th>Sort</th><th>Action</th></tr></thead>
        <tbody>
          <?php foreach($rows as $r): ?>
            <tr>
              <td><?php if((string)$r['image_path'] !== ''): ?><img src="<?=h(normalize_image_path((string)$r['image_path']))?>" alt="" style="width:54px;height:54px;border-radius:10px;object-fit:cover"><?php endif; ?></td>
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
          <?php endforeach; ?>
        <div class="grid">
          <div>
            <label>Page</label>
            <select name="page_key" required>
              <?php foreach($pageLabels as $k => $label): ?>
                <option value="<?=h($k)?>"><?=h($label)?></option>
              <?php endforeach; ?>
            </select>
          </div>

          <div>
            <label>First name</label>
            <input name="first_name" required>
          </div>

          <div>
            <label>Last name</label>
            <input name="last_name" required>
          </div>

          <div>
            <label>Role/Position</label>
            <input name="role_title">
          </div>

          <div>
            <label>Sort order</label>
            <input type="number" name="sort_order" value="0">
          </div>

          <div>
            <label>Image upload</label>
            <input type="file" name="image_file" accept=".jpg,.jpeg,.png,.webp">
          </div>
        </div>

        <div style="margin-top:10px">
          <label>Or image path / URL</label>
          <input name="image_path" placeholder="assets/people/uploads/pic.jpg or https://...">
        </div>

        <div style="margin-top:12px">
          <button class="btn" type="submit">Add Member</button>
        </div>
      </form>
    </div>

    <div class="admin-card">
      <h3 style="margin:0 0 6px">Members</h3>

      <table class="admin-table">
        <thead>
          <tr>
            <th>Photo</th>
            <th>Page</th>
            <th>Name</th>
            <th>Position</th>
            <th>Sort</th>
            <th>Action</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($rows as $r): ?>
            <tr>
              <td>
                <?php if ((string)$r['image_path'] !== ''): ?>
                  <img
                    src="<?=h(normalize_image_path((string)$r['image_path']))?>"
                    alt=""
                    style="width:54px;height:54px;border-radius:10px;object-fit:cover"
                  >
                <?php endif; ?>
              </td>

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
          <?php endforeach; ?>
        <div style="margin-top:12px"><button type="submit">Add Member</button></div>
      </form>
    </div>

    <div class="card">
      <h3 style="margin:0 0 6px">Members</h3>
      <table>
        <thead>
          <tr>
            <th>Photo</th><th>Page</th><th>Name</th><th>Position</th><th>Sort</th><th>Action</th>
          </tr>
        </thead>
        <tbody>
        <?php foreach($rows as $r): ?>
          <tr>
            <td>
              <?php if((string)$r['image_path'] !== ''): ?>
                <img src="<?=h(normalize_image_path((string)$r['image_path']))?>" alt="">
              <?php endif; ?>
            </td>
            <td><?=h($pageLabels[(string)$r['page_key']] ?? (string)$r['page_key'])?></td>
            <td><?=h(trim(((string)$r['first_name']) . ' ' . ((string)$r['last_name'])))?></td>
            <td><?=h((string)$r['role_title'])?></td>
            <td><?= (int)$r['sort_order'] ?></td>
            <td>
              <form method="post" onsubmit="return confirm('Delete member?')">
                <input type="hidden" name="_csrf" value="<?=h(csrf_token())?>">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                <button type="submit">Delete</button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</body>
</html>
