<?php
declare(strict_types=1);

require __DIR__ . '/../../inc/bootstrap.php';
require_admin();
require_permission('news.create');

$errors = [];
$data = [
  'category' => '',
  'title' => '',
  'excerpt' => '',
  'content' => '',
  'image_path' => '',
  'published_at' => date('Y-m-d\TH:i'),
  'is_published' => 1,
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

  $new  = 'news_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
  $dest = $dir . '/' . $new;

  if (!move_uploaded_file($tmp, $dest)) return null;

  // ✅ Correct for any project folder (/sspm or root)
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

    $new  = 'news_gallery_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
    $dest = $dir . '/' . $new;

    if (!move_uploaded_file($files['tmp_name'][$i], $dest)) continue;
    $items[] = url('assets/news/uploads/' . $new);
  }
  return $items;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  csrf_verify();

  foreach ($data as $k => $_) {
    if ($k === 'is_published') continue;
    $data[$k] = trim((string)($_POST[$k] ?? ''));
  }
  $data['is_published'] = isset($_POST['is_published']) ? 1 : 0;

  // If user entered image path manually, normalize it
  $data['image_path'] = normalize_image_path($data['image_path']);

  // If uploaded file exists, it overrides manual path
  $uploaded = handle_upload();
  if ($uploaded) $data['image_path'] = $uploaded;

  if ($data['category'] === '') $errors[] = 'Category is required';
  if ($data['title'] === '') $errors[] = 'Title is required';
  if ($data['excerpt'] === '') $errors[] = 'Short text (excerpt) is required';
  if ($data['image_path'] === '') $errors[] = 'Image path or upload is required';
  if ($data['published_at'] === '') $errors[] = 'Publish date is required';

  if (!$errors) {
    $stmt = db()->prepare("
      INSERT INTO news_posts (category, title, excerpt, content, image_path, published_at, is_published)
      VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([
      $data['category'],
      $data['title'],
      $data['excerpt'],
      $data['content'],
      $data['image_path'],
      date('Y-m-d H:i:s', strtotime($data['published_at'])),
      $data['is_published'],
    ]);
    $postId = (int)db()->lastInsertId();

    ensure_news_gallery_table();
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
      foreach ($galleryPaths as $i => $path) {
        $stmt->execute([$postId, $path, $i + 1]);
      }
    }

    // ✅ FIXED redirect (works in /sspm or root)
    header('Location: ' . url('admin/news/index.php'));
    exit;
  }
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Add News</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <style>
    body{font-family:system-ui;background:#0b1220;color:#e5e7eb;margin:0}
    .wrap{max-width:860px;margin:22px auto;padding:0 14px}
    input,textarea{width:100%;padding:12px;border-radius:12px;border:1px solid rgba(255,255,255,.18);background:#111a2e;color:#fff}
    textarea{min-height:120px}
    .row{display:grid;grid-template-columns:1fr 1fr;gap:12px}
    .btn{padding:12px 14px;border-radius:12px;border:0;background:#2563eb;color:#fff;font-weight:800;cursor:pointer}
    .card{background:#111a2e;border:1px solid rgba(255,255,255,.12);border-radius:16px;padding:16px}
    .err{color:#fca5a5;margin:10px 0}
    a{color:#93c5fd;text-decoration:none}
  </style>
</head>
<body>
  <div class="wrap">
    <h2 style="margin:0 0 12px">+ Add News</h2>
    <p><a href="<?=h(url('admin/news/index.php'))?>">← Back</a></p>

    <div class="card">
      <?php if($errors): ?>
        <div class="err">
          <?php foreach($errors as $e) echo '<div>'.h($e).'</div>'; ?>
        </div>
      <?php endif; ?>

      <form method="post" enctype="multipart/form-data">
        <input type="hidden" name="_csrf" value="<?=h(csrf_token())?>">

        <div class="row">
          <div>
            <label>Category (cat)</label>
            <input name="category" value="<?=h($data['category'])?>" placeholder="განცხადება / ღონისძიება / ორგანიზაცია / ტრენინგი">
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
          <label>Short text (this is your card 'text')</label>
          <textarea name="excerpt"><?=h($data['excerpt'])?></textarea>
        </div>

        <div style="margin-top:12px">
          <label>Full content (for single page)</label>
          <textarea name="content" style="min-height:180px"><?=h($data['content'])?></textarea>
        </div>

        <div style="margin-top:12px" class="row">
          <div>
            <label>Image path (examples: assets/news/news-1.jpg OR news-1.jpg)</label>
            <input name="image_path" value="<?=h($data['image_path'])?>">
            <div style="opacity:.75;font-size:12px;margin-top:6px">
              Tip: If you upload a file, it will be saved into <b>assets/news/uploads/</b> automatically.
            </div>
          </div>
          <div>
            <label>OR Upload image</label>
            <input type="file" name="image_file" accept=".jpg,.jpeg,.png,.webp">
          </div>
        </div>

        <div style="margin-top:12px">
          <label>Gallery image paths (one per line)</label>
          <textarea name="gallery_paths" placeholder="assets/news/gallery-1.jpg"></textarea>
        </div>

        <div style="margin-top:12px">
          <label>OR Upload gallery images</label>
          <input type="file" name="gallery_files[]" accept=".jpg,.jpeg,.png,.webp" multiple>
        </div>

        <div style="margin-top:12px">
          <label>
            <input type="checkbox" name="is_published" <?= $data['is_published'] ? 'checked' : '' ?>>
            Published (visible on site)
          </label>
        </div>

        <div style="margin-top:14px">
          <button class="btn" type="submit">Save</button>
        </div>
      </form>
    </div>
  </div>
</body>
</html>
