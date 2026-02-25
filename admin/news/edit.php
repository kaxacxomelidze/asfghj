<?php
declare(strict_types=1);
require __DIR__ . '/../../inc/bootstrap.php';
require __DIR__ . '/../_ui.php';
require_admin();
require_permission('news.edit');
ensure_news_gallery_table();

$id = (int)($_GET['id'] ?? 0);
$stmt = db()->prepare("SELECT * FROM news_posts WHERE id=? LIMIT 1");
$stmt->execute([$id]);
$row = $stmt->fetch();
if (!$row) { http_response_code(404); exit('Not found'); }

$data = [
  'category' => $row['category'],
  'title' => $row['title'],
  'excerpt' => $row['excerpt'],
  'content' => $row['content'] ?? '',
  'image_path' => $row['image_path'],
  'published_at' => date('Y-m-d\TH:i', strtotime($row['published_at'])),
  'is_published' => (int)$row['is_published'],
];

function handle_upload(): ?string {
  if (empty($_FILES['image_file']) || $_FILES['image_file']['error'] === UPLOAD_ERR_NO_FILE) return null;
  if ($_FILES['image_file']['error'] !== UPLOAD_ERR_OK) return null;

  $tmp = $_FILES['image_file']['tmp_name'];
  $name = $_FILES['image_file']['name'];
  $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
  if (!in_array($ext, ['jpg','jpeg','png','webp'], true)) return null;

  $dir = __DIR__ . '/../../assets/news/uploads';
  if (!is_dir($dir)) mkdir($dir, 0775, true);

  $new = 'news_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
  $dest = $dir . '/' . $new;

  if (!move_uploaded_file($tmp, $dest)) return null;
  return url('assets/news/uploads/' . $new);
}

function handle_gallery_uploads(): array {
  if (empty($_FILES['gallery_files']) || !is_array($_FILES['gallery_files']['name'])) return [];
  $files = $_FILES['gallery_files'];
  $items = [];
  foreach ($files['name'] as $i => $name) {
    if ($files['error'][$i] === UPLOAD_ERR_NO_FILE) continue;
    if ($files['error'][$i] !== UPLOAD_ERR_OK) continue;

    $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
    if (!in_array($ext, ['jpg','jpeg','png','webp'], true)) continue;

    $dir = __DIR__ . '/../../assets/news/uploads';
    if (!is_dir($dir)) mkdir($dir, 0775, true);

    $new = 'news_gallery_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
    $dest = $dir . '/' . $new;

    if (!move_uploaded_file($files['tmp_name'][$i], $dest)) continue;
    $items[] = url('assets/news/uploads/' . $new);
  }
  return $items;
}

$errors = [];
$galleryRows = [];
try {
  $stmt = db()->prepare("SELECT id, image_path FROM news_gallery WHERE post_id=? ORDER BY sort_order ASC, id ASC");
  $stmt->execute([$id]);
  $galleryRows = $stmt->fetchAll();
} catch (Throwable $e) {
  $galleryRows = [];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  csrf_verify();

  $data['category'] = trim((string)($_POST['category'] ?? ''));
  $data['title'] = trim((string)($_POST['title'] ?? ''));
  $data['excerpt'] = trim((string)($_POST['excerpt'] ?? ''));
  $data['content'] = trim((string)($_POST['content'] ?? ''));
  $data['image_path'] = normalize_image_path((string)($_POST['image_path'] ?? ''));
  $data['published_at'] = trim((string)($_POST['published_at'] ?? ''));
  $data['is_published'] = isset($_POST['is_published']) ? 1 : 0;

  $uploaded = handle_upload();
  if ($uploaded) $data['image_path'] = $uploaded;

  if ($data['category'] === '') $errors[] = 'Category is required';
  if ($data['title'] === '') $errors[] = 'Title is required';
  if ($data['excerpt'] === '') $errors[] = 'Short text (excerpt) is required';
  if ($data['image_path'] === '') $errors[] = 'Image path or upload is required';
  if ($data['published_at'] === '') $errors[] = 'Publish date is required';

  if (!$errors) {
    $stmt = db()->prepare("
      UPDATE news_posts
      SET category=?, title=?, excerpt=?, content=?, image_path=?, published_at=?, is_published=?
      WHERE id=?
    ");
    $stmt->execute([
      $data['category'],
      $data['title'],
      $data['excerpt'],
      $data['content'],
      $data['image_path'],
      date('Y-m-d H:i:s', strtotime($data['published_at'])),
      $data['is_published'],
      $id,
    ]);

    $removeIds = $_POST['remove_gallery'] ?? [];
    if (is_array($removeIds) && $removeIds) {
      $placeholders = implode(',', array_fill(0, count($removeIds), '?'));
      $params = array_map('intval', $removeIds);
      $stmt = db()->prepare("DELETE FROM news_gallery WHERE id IN ($placeholders) AND post_id=?");
      $params[] = $id;
      $stmt->execute($params);
    }

    $galleryPaths = [];
    $manualGallery = trim((string)($_POST['gallery_paths'] ?? ''));
    if ($manualGallery !== '') {
      foreach (preg_split('/\\r?\\n/', $manualGallery) as $line) {
        $path = normalize_image_path($line);
        if ($path !== '') $galleryPaths[] = $path;
      }
    }
    $galleryPaths = array_merge($galleryPaths, handle_gallery_uploads());
    if ($galleryPaths) {
      $stmt = db()->prepare("INSERT INTO news_gallery (post_id, image_path, sort_order) VALUES (?, ?, ?)");
      $start = (int)db()->query("SELECT COALESCE(MAX(sort_order), 0) FROM news_gallery WHERE post_id=" . (int)$id)->fetchColumn();
      foreach ($galleryPaths as $i => $path) {
        $stmt->execute([$id, $path, $start + $i + 1]);
      }
    }

    safe_record_admin_activity('update', 'news_post', $id, 'Updated news post: ' . $data['title']);
    header('Location: ' . url('admin/news/index.php'));
    exit;
  }
}
?>
<!doctype html>
<html lang="en">
<?php admin_head('Edit News'); ?>
<body class="admin-body">
  <div class="admin-wrap" style="max-width:900px">
    <?php admin_topbar('Edit News #'.(int)$id, [['href' => url('admin/news/index.php'), 'label' => '← Back to News']]); ?>

    <div class="admin-card">
      <?php if($errors): ?>
        <div class="err"><?php foreach($errors as $e) echo '<div>'.h($e).'</div>'; ?></div>
      <?php endif; ?>

      <form method="post" enctype="multipart/form-data">
        <input type="hidden" name="_csrf" value="<?=h(csrf_token())?>">

        <div class="grid-2">
          <div>
            <label>Category</label>
            <input name="category" value="<?=h($data['category'])?>">
          </div>
          <div>
            <label>Publish date</label>
            <input type="datetime-local" name="published_at" value="<?=h($data['published_at'])?>">
          </div>
        </div>

        <div style="margin-top:12px">
          <label>Title</label>
          <input name="title" value="<?=h($data['title'])?>">
        </div>

        <div style="margin-top:12px">
          <label>Short text (card text)</label>
          <textarea name="excerpt"><?=h($data['excerpt'])?></textarea>
        </div>

        <div style="margin-top:12px">
          <label>Full content</label>
          <textarea name="content" style="min-height:180px"><?=h($data['content'])?></textarea>
        </div>

        <div style="margin-top:12px" class="grid-2">
          <div>
            <label>Image path</label>
            <input name="image_path" value="<?=h($data['image_path'])?>">
          </div>
          <div>
            <label>OR Upload new image</label>
            <input type="file" name="image_file" accept=".jpg,.jpeg,.png,.webp">
          </div>
        </div>

        <?php if($galleryRows): ?>
          <div style="margin-top:14px">
            <label>Current gallery (check to remove)</label>
            <div style="margin-top:8px">
              <?php foreach($galleryRows as $g): ?>
                <label style="display:block;margin-bottom:6px">
                  <input type="checkbox" name="remove_gallery[]" value="<?= (int)$g['id'] ?>">
                  <?=h(normalize_image_path((string)$g['image_path']))?>
                </label>
              <?php endforeach; ?>
            </div>
          </div>
        <?php endif; ?>

        <div style="margin-top:12px">
          <label>Add gallery image paths (one per line)</label>
          <textarea name="gallery_paths" placeholder="assets/news/gallery-1.jpg"></textarea>
        </div>

        <div style="margin-top:12px">
          <label>OR Upload gallery images</label>
          <input type="file" name="gallery_files[]" accept=".jpg,.jpeg,.png,.webp" multiple>
        </div>

        <div style="margin-top:12px">
          <label>
            <input type="checkbox" name="is_published" <?= $data['is_published'] ? 'checked' : '' ?>>
            Published
          </label>
        </div>

        <div style="margin-top:14px">
          <button class="btn" type="submit">Update</button>
        </div>
      </form>
    </div>
  </div>
</body>
</html>
