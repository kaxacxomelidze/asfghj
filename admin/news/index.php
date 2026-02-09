<?php
declare(strict_types=1);
require __DIR__ . '/../../inc/bootstrap.php';
require_admin();
require_permission('news.view');

$rows = db()->query("SELECT id, category, title, published_at, is_published FROM news_posts ORDER BY published_at DESC, id DESC")->fetchAll();
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Admin — News</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <style>
    body{font-family:system-ui;background:#0b1220;color:#e5e7eb;margin:0}
    .wrap{max-width:1100px;margin:22px auto;padding:0 14px}
    a{color:#93c5fd;text-decoration:none}
    .top{display:flex;justify-content:space-between;align-items:center;gap:12px}
    .btn{display:inline-block;padding:10px 14px;border-radius:12px;background:#2563eb;color:#fff;font-weight:700}
    table{width:100%;border-collapse:collapse;margin-top:16px;background:#111a2e;border:1px solid rgba(255,255,255,.12);border-radius:16px;overflow:hidden}
    th,td{padding:12px;border-bottom:1px solid rgba(255,255,255,.10);text-align:left;font-size:14px}
    th{opacity:.85}
    .pill{display:inline-block;padding:3px 10px;border-radius:999px;background:rgba(34,197,94,.18);border:1px solid rgba(34,197,94,.35)}
    .pill.off{background:rgba(244,63,94,.14);border-color:rgba(244,63,94,.35)}
    .actions a{margin-right:10px}
  </style>
</head>
<body>
  <div class="wrap">
    <div class="top">
      <h2 style="margin:0">News Admin</h2>
      <div>
        <?php if(has_permission('news.create')): ?>
          <a class="btn" href="<?=h(url('admin/news/create.php'))?>">+ Add News</a>
        <?php endif; ?>
        <?php if(has_permission('admins.manage')): ?>
          <a style="margin-left:10px" href="<?=h(url('admin/admins/index.php'))?>">Admins</a>
        <?php endif; ?>
        <a style="margin-left:10px" href="<?=h(url('admin/logout.php'))?>">Logout</a>
      </div>
    </div>

    <table>
      <thead>
        <tr>
          <th>ID</th><th>Category</th><th>Title</th><th>Published At</th><th>Status</th><th>Actions</th>
        </tr>
      </thead>
      <tbody>
      <?php foreach($rows as $r): ?>
        <tr>
          <td><?= (int)$r['id'] ?></td>
          <td><?= h($r['category']) ?></td>
          <td><?= h($r['title']) ?></td>
          <td><?= h($r['published_at']) ?></td>
          <td>
            <?php if((int)$r['is_published']===1): ?>
              <span class="pill">Published</span>
            <?php else: ?>
              <span class="pill off">Hidden</span>
            <?php endif; ?>
          </td>
          <td class="actions">
            <?php if(has_permission('news.edit')): ?>
              <a href="<?=h(url('admin/news/edit.php'))?>?id=<?= (int)$r['id'] ?>">Edit</a>
            <?php endif; ?>
            <?php if(has_permission('news.delete')): ?>
              <form method="post" action="<?=h(url('admin/news/delete.php'))?>" style="display:inline">
                <input type="hidden" name="_csrf" value="<?=h(csrf_token())?>">
                <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                <button type="submit" onclick="return confirm('Delete this post?')">Delete</button>
              </form>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</body>
</html>
