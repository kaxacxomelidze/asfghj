<?php
declare(strict_types=1);
require __DIR__ . '/../../inc/bootstrap.php';
require __DIR__ . '/../_ui.php';
require_admin();
require_permission('news.view');

$rows = db()->query('SELECT id, category, title, published_at, is_published FROM news_posts ORDER BY published_at DESC, id DESC')->fetchAll();
?>
<!doctype html>
<html lang="en">
<?php admin_head('Admin — News'); ?>
<body class="admin-body">
  <div class="admin-wrap">
    <?php
      $links = [];
      if (has_permission('news.create')) $links[] = ['href' => url('admin/news/create.php'), 'label' => '+ Add News'];
      if (has_permission('admins.manage')) $links[] = ['href' => url('admin/admins/index.php'), 'label' => 'Admins'];
      if (has_permission('contact.view')) $links[] = ['href' => url('admin/contact/index.php'), 'label' => 'Contact'];
      if (has_permission('people.manage')) $links[] = ['href' => url('admin/people/index.php'), 'label' => 'People'];
      $links[] = ['href' => url('admin/logout.php'), 'label' => 'Logout'];
      admin_topbar('News Admin', $links);
    ?>

    <div class="admin-card">
      <table class="admin-table">
        <thead>
          <tr><th>ID</th><th>Category</th><th>Title</th><th>Published</th><th>Status</th><th>Actions</th></tr>
        </thead>
        <tbody>
          <?php foreach($rows as $r): ?>
            <tr>
              <td><?= (int)$r['id'] ?></td>
              <td><?= h((string)$r['category']) ?></td>
              <td><?= h((string)$r['title']) ?></td>
              <td><?= h((string)$r['published_at']) ?></td>
              <td><?= (int)$r['is_published'] === 1 ? '<span class="pill">Published</span>' : '<span class="pill off">Hidden</span>' ?></td>
              <td>
                <?php if(has_permission('news.edit')): ?>
                  <a href="<?=h(url('admin/news/edit.php'))?>?id=<?= (int)$r['id'] ?>">Edit</a>
                <?php endif; ?>
                <?php if(has_permission('news.delete')): ?>
                  <form method="post" action="<?=h(url('admin/news/delete.php'))?>" style="display:inline;margin-left:8px">
                    <input type="hidden" name="_csrf" value="<?=h(csrf_token())?>">
                    <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                    <button class="btn secondary" type="submit" onclick="return confirm('Delete this post?')">Delete</button>
                  </form>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</body>
</html>
