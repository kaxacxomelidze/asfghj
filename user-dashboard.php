<?php
require __DIR__ . '/inc/bootstrap.php';
$pageTitle = 'SPG Portal — User Dashboard';
ensure_users_table();

if (!is_user_logged_in()) {
  header('Location: ' . url('user-auth.php?tab=signin#dashboard'));
  exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  csrf_verify();
  if ((string)($_POST['action'] ?? '') === 'logout') {
    unset($_SESSION['user_id'], $_SESSION['user_name'], $_SESSION['user_email']);
    header('Location: ' . url('user-auth.php?tab=signin#dashboard'));
    exit;
  }
}

$user = current_user();
include __DIR__ . '/header.php';
?>
<section class="section" style="padding:36px 0 54px">
  <div class="container" style="max-width:980px;display:grid;gap:16px">
    <div style="background:#fff;border:1px solid var(--line);border-radius:18px;padding:20px;box-shadow:0 14px 32px rgba(15,23,42,.06)">
      <h2 style="margin:0 0 8px">მომხმარებლის Dashboard</h2>
      <p style="margin:0;color:var(--muted)">მოგესალმებით, <b><?=h((string)$user['name'])?></b> (<?=h((string)$user['email'])?>).</p>
      <div style="display:flex;gap:10px;flex-wrap:wrap;margin-top:14px">
        <a href="<?=h(url('membership.php'))?>" class="btn primary">გაწევრიანების ფორმა</a>
        <a href="<?=h(url('contact.php'))?>" class="btn">კონტაქტი</a>
      </div>
    </div>

    <div style="background:#fff;border:1px solid var(--line);border-radius:18px;padding:20px;">
      <h3 style="margin:0 0 10px">ანგარიშის მართვა</h3>
      <form method="post">
        <input type="hidden" name="_csrf" value="<?=h(csrf_token())?>">
        <input type="hidden" name="action" value="logout">
        <button type="submit" style="padding:12px 14px;border-radius:12px;border:0;background:#ef4444;color:#fff;font-weight:800;cursor:pointer">გასვლა</button>
      </form>
    </div>
  </div>
</section>
<?php include __DIR__ . '/footer.php'; ?>
