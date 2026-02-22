<?php
declare(strict_types=1);

require __DIR__ . '/../../inc/bootstrap.php';
require __DIR__ . '/../_ui.php';

require_admin();
require_permission('people.manage');
ensure_people_profiles_table();

$pageLabels = people_page_labels();
$error = '';

/**
 * Absolute upload directory: /assets/people/uploads
 */
function people_upload_dir_abs(): string
{
    return dirname(__DIR__, 2)
        . DIRECTORY_SEPARATOR . 'assets'
        . DIRECTORY_SEPARATOR . 'people'
        . DIRECTORY_SEPARATOR . 'uploads';
}

/**
 * Stores uploaded image into /assets/people/uploads/
 * Returns relative path: assets/people/uploads/filename.ext
 */
function store_people_upload(array $file): ?string
{
    $tmpName  = (string)($file['tmp_name'] ?? '');
    $origName = (string)($file['name'] ?? '');

    if ($origName === '' || $tmpName === '' || !is_uploaded_file($tmpName)) {
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
    $destAbs  = $dir . DIRECTORY_SEPARATOR . $filename;

    if (!move_uploaded_file($tmpName, $destAbs)) {
        return null;
    }

    return 'assets/people/uploads/' . $filename;
}

/**
 * Delete old uploaded file safely (only if path is inside assets/people/uploads/)
 */
function delete_people_upload_if_local(string $imagePath): void
{
    $imagePath = trim($imagePath);
    if ($imagePath === '') return;

    // only allow deleting files in our uploads folder
    if (strpos($imagePath, 'assets/people/uploads/') !== 0) {
        return;
    }

    $abs = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $imagePath);

    if (is_file($abs)) {
        @unlink($abs);
    }
}

/**
 * Load one row by id
 */
function people_find(int $id): ?array
{
    $stmt = db()->prepare('SELECT * FROM people_profiles WHERE id = ? LIMIT 1');
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    return $row ?: null;
}

// --- Edit mode (GET ?edit=ID) ---
$editId = (int)($_GET['edit'] ?? 0);
$editRow = null;
if ($editId > 0) {
    $editRow = people_find($editId);
    if (!$editRow) {
        $editId = 0;
    }
}

// --- POST actions ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $action = (string)($_POST['action'] ?? '');

    // CREATE
    if ($action === 'create') {
        $pageKey   = (string)($_POST['page_key'] ?? '');
        $firstName = trim((string)($_POST['first_name'] ?? ''));
        $lastName  = trim((string)($_POST['last_name'] ?? ''));
        $roleTitle = trim((string)($_POST['role_title'] ?? ''));
        $sortOrder = (int)($_POST['sort_order'] ?? 0);
        $imagePath = trim((string)($_POST['image_path'] ?? ''));

        if (!isset($pageLabels[$pageKey])) {
            $error = 'Invalid page key';
        } elseif ($firstName === '' || $lastName === '') {
            $error = 'First name and last name are required';
        } else {
            // If file uploaded, it overrides image_path
            if (!empty($_FILES['image_file']['name'])) {
                $uploaded = store_people_upload($_FILES['image_file']);
                if ($uploaded === null) {
                    $error = 'Image upload failed. Allowed: jpg, jpeg, png, webp';
                } else {
                    $imagePath = $uploaded;
                }
            }

            if ($error === '') {
                $stmt = db()->prepare(
                    'INSERT INTO people_profiles
                        (page_key, first_name, last_name, role_title, image_path, sort_order, created_at)
                     VALUES
                        (?, ?, ?, ?, ?, ?, NOW())'
                );
                $stmt->execute([$pageKey, $firstName, $lastName, $roleTitle, $imagePath, $sortOrder]);

                header('Location: ' . url('admin/people/index.php'));
                exit;
            }
        }
    }

    // UPDATE
    if ($action === 'update') {
        $id        = (int)($_POST['id'] ?? 0);
        $pageKey   = (string)($_POST['page_key'] ?? '');
        $firstName = trim((string)($_POST['first_name'] ?? ''));
        $lastName  = trim((string)($_POST['last_name'] ?? ''));
        $roleTitle = trim((string)($_POST['role_title'] ?? ''));
        $sortOrder = (int)($_POST['sort_order'] ?? 0);
        $imagePath = trim((string)($_POST['image_path'] ?? ''));

        $current = ($id > 0) ? people_find($id) : null;
        if (!$current) {
            $error = 'Member not found';
        } elseif (!isset($pageLabels[$pageKey])) {
            $error = 'Invalid page key';
        } elseif ($firstName === '' || $lastName === '') {
            $error = 'First name and last name are required';
        } else {
            // default to old image if user didn't provide new
            $finalImage = (string)($current['image_path'] ?? '');

            // If user typed new image_path, use it
            if ($imagePath !== '') {
                $finalImage = $imagePath;
            }

            // If file uploaded, it overrides image_path
            if (!empty($_FILES['image_file']['name'])) {
                $uploaded = store_people_upload($_FILES['image_file']);
                if ($uploaded === null) {
                    $error = 'Image upload failed. Allowed: jpg, jpeg, png, webp';
                } else {
                    // delete old local upload (optional)
                    delete_people_upload_if_local((string)($current['image_path'] ?? ''));
                    $finalImage = $uploaded;
                }
            }

            if ($error === '') {
                $stmt = db()->prepare(
                    'UPDATE people_profiles
                     SET page_key = ?, first_name = ?, last_name = ?, role_title = ?, image_path = ?, sort_order = ?
                     WHERE id = ? LIMIT 1'
                );
                $stmt->execute([$pageKey, $firstName, $lastName, $roleTitle, $finalImage, $sortOrder, $id]);

                header('Location: ' . url('admin/people/index.php'));
                exit;
            } else {
                // keep edit mode if error
                $editId = $id;
                $editRow = $current;
            }
        }
    }

    // DELETE
    if ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            $current = people_find($id);
            if ($current) {
                // delete local file (optional)
                delete_people_upload_if_local((string)($current['image_path'] ?? ''));
            }

            $stmt = db()->prepare('DELETE FROM people_profiles WHERE id = ? LIMIT 1');
            $stmt->execute([$id]);
        }

        header('Location: ' . url('admin/people/index.php'));
        exit;
    }
}

// --- Table rows ---
$rows = db()->query(
    'SELECT id, page_key, first_name, last_name, role_title, image_path, sort_order
     FROM people_profiles
     ORDER BY page_key ASC, sort_order ASC, id ASC'
)->fetchAll();

$pageTotals = [];
foreach ($pageLabels as $k => $_l) $pageTotals[$k] = 0;
foreach ($rows as $r) {
    $k = (string)$r['page_key'];
    if (isset($pageTotals[$k])) $pageTotals[$k]++;
}

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

    <div class="grid-3">
      <div class="admin-card"><label>Total team members</label><div style="font-size:30px;font-weight:900"><?= count($rows) ?></div></div>
      <div class="admin-card"><label>Pages covered</label><div style="font-size:30px;font-weight:900;color:#93c5fd"><?= count(array_filter($pageTotals, fn($n)=>$n>0)) ?></div></div>
      <div class="admin-card"><label>Largest section</label><div style="font-size:15px;font-weight:700;color:#cbd5e1"><?php $mx=0;$mk='—'; foreach($pageTotals as $k=>$n){ if($n>$mx){$mx=$n;$mk=$pageLabels[$k];}} echo h((string)$mk); ?></div></div>
    </div>

    <?php if ($editId > 0 && $editRow): ?>
        <!-- EDIT FORM -->
        <div class="admin-card">
            <h3 style="margin:0 0 10px">Edit Team Member #<?= (int)$editRow['id'] ?></h3>

            <?php if ($error !== ''): ?>
                <div class="err"><?= h($error) ?></div>
            <?php endif; ?>

            <form method="post" enctype="multipart/form-data">
                <input type="hidden" name="_csrf" value="<?= h(csrf_token()) ?>">
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="id" value="<?= (int)$editRow['id'] ?>">

                <div class="grid" style="display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:10px">
                    <div>
                        <label>Page</label>
                        <select name="page_key" required>
                            <?php foreach ($pageLabels as $k => $label): ?>
                                <option
                                    value="<?= h((string)$k) ?>"
                                    <?= ((string)$editRow['page_key'] === (string)$k) ? 'selected' : '' ?>
                                >
                                    <?= h((string)$label) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label>First name</label>
                        <input name="first_name" required value="<?= h((string)$editRow['first_name']) ?>">
                    </div>

                    <div>
                        <label>Last name</label>
                        <input name="last_name" required value="<?= h((string)$editRow['last_name']) ?>">
                    </div>

                    <div>
                        <label>Role/Position</label>
                        <input name="role_title" value="<?= h((string)$editRow['role_title']) ?>">
                    </div>

                    <div>
                        <label>Sort order</label>
                        <input type="number" name="sort_order" value="<?= (int)$editRow['sort_order'] ?>">
                    </div>

                    <div>
                        <label>Image upload (replace)</label>
                        <input type="file" name="image_file" accept=".jpg,.jpeg,.png,.webp">
                    </div>
                </div>

                <div style="margin-top:10px">
                    <label>Or image path / URL (replace)</label>
                    <input name="image_path" placeholder="assets/people/uploads/pic.jpg or https://..." value="">
                    <div style="opacity:.8;margin-top:6px;font-size:13px">
                        Current image:
                        <?php if ((string)$editRow['image_path'] !== ''): ?>
                            <a href="<?= h(normalize_image_path((string)$editRow['image_path'])) ?>" target="_blank">
                                <?= h((string)$editRow['image_path']) ?>
                            </a>
                        <?php else: ?>
                            <span>—</span>
                        <?php endif; ?>
                    </div>
                </div>

                <div style="margin-top:12px;display:flex;gap:10px;flex-wrap:wrap">
                    <button class="btn" type="submit">Save Changes</button>
                    <a class="btn secondary" href="<?= h(url('admin/people/index.php')) ?>">Cancel</a>
                </div>
            </form>
        </div>

    <?php else: ?>
        <!-- CREATE FORM -->
        <div class="admin-card">
            <h3 style="margin:0 0 10px">Add Team Member</h3>

            <?php if ($error !== ''): ?>
                <div class="err"><?= h($error) ?></div>
            <?php endif; ?>

            <form method="post" enctype="multipart/form-data">
                <input type="hidden" name="_csrf" value="<?= h(csrf_token()) ?>">
                <input type="hidden" name="action" value="create">

                <div class="grid" style="display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:10px">
                    <div>
                        <label>Page</label>
                        <select name="page_key" required>
                            <?php foreach ($pageLabels as $k => $label): ?>
                                <option value="<?= h((string)$k) ?>"><?= h((string)$label) ?></option>
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
    <?php endif; ?>

    <!-- TABLE -->
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
                <th style="width:180px">Action</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($rows as $r): ?>
                <tr>
                    <td>
                        <?php if ((string)$r['image_path'] !== ''): ?>
                            <img
                                src="<?= h(normalize_image_path((string)$r['image_path'])) ?>"
                                alt=""
                                style="width:54px;height:54px;border-radius:10px;object-fit:cover"
                            >
                        <?php endif; ?>
                    </td>

                    <td><?= h($pageLabels[(string)$r['page_key']] ?? (string)$r['page_key']) ?></td>
                    <td><?= h(trim(((string)$r['first_name']) . ' ' . ((string)$r['last_name']))) ?></td>
                    <td><?= h((string)$r['role_title']) ?></td>
                    <td><?= (int)$r['sort_order'] ?></td>

                    <td style="display:flex;gap:8px;align-items:center;flex-wrap:wrap">
                        <a class="btn secondary" href="<?= h(url('admin/people/index.php')) ?>?edit=<?= (int)$r['id'] ?>">
                            Edit
                        </a>

                        <form method="post" onsubmit="return confirm('Delete member?')" style="margin:0">
                            <input type="hidden" name="_csrf" value="<?= h(csrf_token()) ?>">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                            <button class="btn secondary" type="submit">Delete</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>

            <?php if (empty($rows)): ?>
                <tr>
                    <td colspan="6" style="opacity:.8">No members yet.</td>
                </tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>

</div>
</body>
</html>
