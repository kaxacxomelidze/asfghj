<?php
declare(strict_types=1);

require __DIR__ . '/../../inc/bootstrap.php';
require __DIR__ . '/../_ui.php';
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

/**
 * Absolute upload directory builder (no ../../ in final path)
 */
function news_upload_dir_abs(): string {
  // __DIR__ = /var/www/spg.edu.ge/public/admin/news
  // dirname(__DIR__, 2) = /var/www/spg.edu.ge/public
  return rtrim(dirname(__DIR__, 2), '/\\') . '/assets/news/uploads';
}

/**
 * Ensure directory exists and can REALLY be written by PHP.
 * IMPORTANT: we trust actual write test more than is_writable() because
 * some environments return false positives/negatives.
 */
function ensure_writable_upload_dir(string $dir, string $label = 'Uploads'): string {
  if (!is_dir($dir)) {
    if (!@mkdir($dir, 0775, true) && !is_dir($dir)) {
      throw new RuntimeException("Cannot create {$label} directory: {$dir}");
    }
  }

  $real = realpath($dir);
  if ($real === false) {
    throw new RuntimeException("Cannot resolve real path for {$label} directory: {$dir}");
  }

  // Real write test (source of truth)
  $testFile = $real . '/.__write_test_' . bin2hex(random_bytes(4)) . '.tmp';
  $writeOk = @file_put_contents($testFile, 'ok') !== false;
  $phpErr = error_get_last();

  if ($writeOk) {
    @unlink($testFile);
    return $real; // ✅ writable enough, don't fail because is_writable() says otherwise
  }

  // Extra diagnostics
  $perms = @substr(sprintf('%o', @fileperms($real)), -4);

  $ownerId = @fileowner($real);
  $groupId = @filegroup($real);

  $ownerName = ($ownerId !== false) ? (string)$ownerId : 'unknown';
  $groupName = ($groupId !== false) ? (string)$groupId : 'unknown';

  if (function_exists('posix_getpwuid') && $ownerId !== false) {
    $pw = @posix_getpwuid((int)$ownerId);
    if (is_array($pw) && isset($pw['name'])) {
      $ownerName = (string)$pw['name'] . " ({$ownerId})";
    }
  }
  if (function_exists('posix_getgrgid') && $groupId !== false) {
    $gr = @posix_getgrgid((int)$groupId);
    if (is_array($gr) && isset($gr['name'])) {
      $groupName = (string)$gr['name'] . " ({$groupId})";
    }
  }

  $extra = '';
  if (is_array($phpErr) && !empty($phpErr['message'])) {
    $extra = ' | php=' . (string)$phpErr['message'];
  }

  throw new RuntimeException(
    "{$label} directory is not writable: {$real}" .
    " (perms=" . ($perms ?: 'unknown') .
    ", owner={$ownerName}, group={$groupName})" .
    $extra .
    " | Hint: On CentOS/AlmaLinux this is often SELinux (set proper context: httpd_sys_rw_content_t)"
  );
}

/**
 * Convert stored path to DB value.
 * DB should store relative path (recommended), but absolute URLs still work.
 */
function image_path_to_db_value(string $path): string {
  $path = trim($path);
  if ($path === '') return '';

  // external URL -> keep
  if (preg_match('~^https?://~i', $path)) return $path;

  // project absolute URL (/sspm/assets/...) -> keep
  if (str_starts_with($path, '/')) return $path;

  // relative path -> normalize to assets/news/... when needed
  if (str_starts_with($path, 'assets/')) return $path;

  return 'assets/news/' . ltrim($path, '/');
}

/**
 * Upload one main image from field: image_file
 * Returns RELATIVE path: assets/news/uploads/xxx.jpg
 * Returns null if no file chosen
 * Throws RuntimeException on real upload errors
 */
function handle_upload(): ?string {
  if (!isset($_FILES['image_file'])) {
    return null;
  }

  $f = $_FILES['image_file'];
  if (!is_array($f)) {
    throw new RuntimeException('Invalid upload payload for image_file');
  }

  $err = (int)($f['error'] ?? UPLOAD_ERR_NO_FILE);
  if ($err === UPLOAD_ERR_NO_FILE) {
    return null;
  }

  if ($err !== UPLOAD_ERR_OK) {
    $map = [
      UPLOAD_ERR_INI_SIZE   => 'Main image exceeds server upload_max_filesize',
      UPLOAD_ERR_FORM_SIZE  => 'Main image exceeds form MAX_FILE_SIZE',
      UPLOAD_ERR_PARTIAL    => 'Main image upload was partial',
      UPLOAD_ERR_NO_TMP_DIR => 'Temporary upload directory is missing',
      UPLOAD_ERR_CANT_WRITE => 'Server could not write uploaded image (temp/upload dir issue)',
      UPLOAD_ERR_EXTENSION  => 'Upload blocked by PHP extension',
    ];
    throw new RuntimeException($map[$err] ?? ('Main image upload failed (code ' . $err . ')'));
  }

  $tmp  = (string)($f['tmp_name'] ?? '');
  $size = (int)($f['size'] ?? 0);

  if ($tmp === '' || !is_uploaded_file($tmp)) {
    throw new RuntimeException('Invalid uploaded main image (tmp file missing)');
  }

  if ($size <= 0) {
    throw new RuntimeException('Uploaded main image is empty');
  }

  if ($size > 10 * 1024 * 1024) {
    throw new RuntimeException('Main image is too large (max 10 MB)');
  }

  $imgInfo = @getimagesize($tmp);
  if ($imgInfo === false) {
    throw new RuntimeException('Uploaded main file is not a valid image');
  }

  $mime = (string)($imgInfo['mime'] ?? '');
  $extMap = [
    'image/jpeg' => 'jpg',
    'image/png'  => 'png',
    'image/webp' => 'webp',
    'image/gif'  => 'gif',
  ];

  if (!isset($extMap[$mime])) {
    throw new RuntimeException('Unsupported main image format. Allowed: JPG, PNG, WEBP, GIF');
  }

  $ext = $extMap[$mime];
  $dir = ensure_writable_upload_dir(news_upload_dir_abs(), 'Uploads');

  $new  = 'news_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
  $dest = $dir . '/' . $new;

  if (!@move_uploaded_file($tmp, $dest)) {
    $last = error_get_last();
    throw new RuntimeException(
      'Failed to move uploaded main image' .
      ($last && !empty($last['message']) ? ': ' . (string)$last['message'] : '')
    );
  }

  @chmod($dest, 0664);

  // Store relative path in DB
  return 'assets/news/uploads/' . $new;
}

/**
 * Upload multiple gallery images from field: gallery_files[]
 * Returns array of RELATIVE paths
 * Individual invalid files are skipped.
 */
function handle_gallery_uploads(): array {
  if (empty($_FILES['gallery_files']) || !is_array($_FILES['gallery_files']['name'])) {
    return [];
  }

  $files = $_FILES['gallery_files'];
  $items = [];
  $dir = ensure_writable_upload_dir(news_upload_dir_abs(), 'Gallery uploads');

  foreach ((array)$files['name'] as $i => $origName) {
    $err = (int)($files['error'][$i] ?? UPLOAD_ERR_NO_FILE);
    if ($err === UPLOAD_ERR_NO_FILE) continue;
    if ($err !== UPLOAD_ERR_OK) continue;

    $tmp  = (string)($files['tmp_name'][$i] ?? '');
    $size = (int)($files['size'][$i] ?? 0);

    if ($tmp === '' || !is_uploaded_file($tmp)) continue;
    if ($size <= 0 || $size > 10 * 1024 * 1024) continue;

    $imgInfo = @getimagesize($tmp);
    if ($imgInfo === false) continue;

    $mime = (string)($imgInfo['mime'] ?? '');
    $extMap = [
      'image/jpeg' => 'jpg',
      'image/png'  => 'png',
      'image/webp' => 'webp',
      'image/gif'  => 'gif',
    ];
    if (!isset($extMap[$mime])) continue;

    $ext = $extMap[$mime];
    $new  = 'news_gallery_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '_' . $i . '.' . $ext;
    $dest = $dir . '/' . $new;

    if (!@move_uploaded_file($tmp, $dest)) continue;
    @chmod($dest, 0664);

    $items[] = 'assets/news/uploads/' . $new;
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

  // Manual path (store relative path if user typed relative)
  $manualImagePath = trim((string)$data['image_path']);
  $data['image_path'] = $manualImagePath !== '' ? image_path_to_db_value($manualImagePath) : '';

  // Uploaded file overrides manual path
  try {
    $uploaded = handle_upload();
    if ($uploaded !== null && $uploaded !== '') {
      $data['image_path'] = $uploaded;
    }
  } catch (Throwable $e) {
    $errors[] = $e->getMessage();
  }

  if ($data['category'] === '') $errors[] = 'Category is required';
  if ($data['title'] === '') $errors[] = 'Title is required';
  if ($data['excerpt'] === '') $errors[] = 'Short text (excerpt) is required';

  // Prevent duplicate generic message when upload already failed with a specific reason
  $hasUploadRelatedError = false;
  foreach ($errors as $e) {
    if (
      stripos($e, 'upload') !== false ||
      stripos($e, 'writable') !== false ||
      stripos($e, 'directory') !== false ||
      stripos($e, 'tmp') !== false
    ) {
      $hasUploadRelatedError = true;
      break;
    }
  }

  if ($data['image_path'] === '' && !$hasUploadRelatedError) {
    $errors[] = 'Image path or upload is required';
  }

  if ($data['published_at'] === '') $errors[] = 'Publish date is required';

  $publishedTs = strtotime($data['published_at']);
  if ($data['published_at'] !== '' && $publishedTs === false) {
    $errors[] = 'Invalid publish date';
  }

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
      date('Y-m-d H:i:s', $publishedTs ?: time()),
      $data['is_published'],
    ]);
    $postId = (int)db()->lastInsertId();

    ensure_news_gallery_table();
    $galleryPaths = [];

    // Manual gallery paths
    $manualGallery = trim((string)($_POST['gallery_paths'] ?? ''));
    if ($manualGallery !== '') {
      foreach (preg_split('/\r?\n/', $manualGallery) as $line) {
        $line = trim((string)$line);
        if ($line === '') continue;
        $path = image_path_to_db_value($line);
        if ($path !== '') $galleryPaths[] = $path;
      }
    }

    // Uploaded gallery images
    try {
      $galleryPaths = array_merge($galleryPaths, handle_gallery_uploads());
    } catch (Throwable $e) {
      // Keep post saved; show warning
      $errors[] = $e->getMessage();
    }

    if ($galleryPaths) {
      $gstmt = db()->prepare("INSERT INTO news_gallery (post_id, image_path, sort_order) VALUES (?, ?, ?)");
      foreach ($galleryPaths as $i => $path) {
        $gstmt->execute([$postId, $path, $i + 1]);
      }
    }

    if (!$errors) {
      header('Location: ' . url('admin/news/index.php'));
      exit;
    }
  }
}
?>
<!doctype html>
<html lang="en">
<?php admin_head('Add News'); ?>
<body class="admin-body">
  <div class="admin-wrap" style="max-width:900px">
    <?php admin_topbar('Add News', [['href' => url('admin/news/index.php'), 'label' => '← Back to News']]); ?>

    <div class="admin-card">
      <?php if ($errors): ?>
        <div class="err">
          <?php foreach ($errors as $e) echo '<div>' . h($e) . '</div>'; ?>
        </div>
      <?php endif; ?>

      <form method="post" enctype="multipart/form-data">
        <input type="hidden" name="_csrf" value="<?= h(csrf_token()) ?>">

        <div class="grid-2">
          <div>
            <label>Category (cat)</label>
            <input name="category" value="<?= h($data['category']) ?>" placeholder="განცხადება / ღონისძიება / ორგანიზაცია / ტრენინგი">
          </div>
          <div>
            <label>Publish date</label>
            <input type="datetime-local" name="published_at" value="<?= h($data['published_at']) ?>">
          </div>
        </div>

        <div style="margin-top:12px">
          <label>Title</label>
          <input name="title" value="<?= h($data['title']) ?>">
        </div>

        <div style="margin-top:12px">
          <label>Short text (this is your card 'text')</label>
          <textarea name="excerpt"><?= h($data['excerpt']) ?></textarea>
        </div>

        <div style="margin-top:12px">
          <label>Full content (for single page)</label>
          <textarea name="content" style="min-height:180px"><?= h($data['content']) ?></textarea>
        </div>

        <div style="margin-top:12px" class="grid-2">
          <div>
            <label>Image path (examples: assets/news/news-1.jpg OR news-1.jpg)</label>
            <input name="image_path" value="<?= h($data['image_path']) ?>">
            <div style="opacity:.75;font-size:12px;margin-top:6px">
              Tip: If you upload a file, it will be saved into <b>assets/news/uploads/</b> automatically.
            </div>
          </div>
          <div>
            <label>OR Upload image</label>
            <input type="file" name="image_file" accept=".jpg,.jpeg,.png,.webp,.gif,image/jpeg,image/png,image/webp,image/gif">
          </div>
        </div>

        <div style="margin-top:12px">
          <label>Gallery image paths (one per line)</label>
          <textarea name="gallery_paths" placeholder="assets/news/gallery-1.jpg"></textarea>
        </div>

        <div style="margin-top:12px">
          <label>OR Upload gallery images</label>
          <input type="file" name="gallery_files[]" accept=".jpg,.jpeg,.png,.webp,.gif,image/jpeg,image/png,image/webp,image/gif" multiple>
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