<?php
declare(strict_types=1);
require __DIR__ . '/../../inc/bootstrap.php';
require_admin();
require_permission('news.delete');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405);
  exit('Method not allowed');
}
csrf_verify();

$id = (int)($_POST['id'] ?? 0);
if ($id > 0) {
  $stmt = db()->prepare("DELETE FROM news_posts WHERE id=?");
  $stmt->execute([$id]);
  safe_record_admin_activity('delete', 'news_post', $id, 'Deleted news post');
}
header('Location: ' . url('admin/news/index.php'));
exit;
