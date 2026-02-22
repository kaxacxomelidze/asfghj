<?php
declare(strict_types=1);
require __DIR__ . '/../inc/bootstrap.php';
require __DIR__ . '/_ui.php';

if (is_admin()) {
  header('Location: ' . url('admin/news/index.php'));
  exit;
}

$error = '';
$lockoutSeconds = 300;
$maxAttempts = 5;

if (!isset($_SESSION['login_attempts'])) $_SESSION['login_attempts'] = 0;
if (!isset($_SESSION['login_locked_until'])) $_SESSION['login_locked_until'] = 0;

function set_captcha(): void {
  $_SESSION['captcha_a'] = random_int(1, 9);
  $_SESSION['captcha_b'] = random_int(1, 9);
}
if (empty($_SESSION['captcha_a']) || empty($_SESSION['captcha_b'])) set_captcha();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  csrf_verify();
  $now = time();
  if ($_SESSION['login_locked_until'] > $now) {
    $remaining = (int)($_SESSION['login_locked_until'] - $now);
    $error = 'Too many attempts. Try again in ' . $remaining . ' seconds.';
    record_admin_login_log((string)($_POST['username'] ?? ''), null, 'blocked', 'temporary lockout');
  } else {
    $captcha = trim((string)($_POST['captcha'] ?? ''));
    $expected = (string)(($_SESSION['captcha_a'] ?? 0) + ($_SESSION['captcha_b'] ?? 0));

    $username = trim((string)($_POST['username'] ?? ''));
    $password = (string)($_POST['password'] ?? '');

    if ($captcha === '' || !hash_equals($expected, $captcha)) {
      $error = 'Captcha is incorrect';
      record_admin_login_log($username, null, 'failed', 'captcha incorrect');
    } else {
      $stmt = db()->prepare('SELECT id, password_hash FROM admins WHERE username=? LIMIT 1');
      $stmt->execute([$username]);
      $admin = $stmt->fetch();
      if ($admin && password_verify($password, (string)$admin['password_hash'])) {
        session_regenerate_id(true);
        $_SESSION['admin_id'] = (int)$admin['id'];
        $_SESSION['admin_username'] = $username;
        $_SESSION['login_attempts'] = 0;
        $_SESSION['login_locked_until'] = 0;
        record_admin_login_log($username, (int)$admin['id'], 'success', 'login successful');
        header('Location: ' . url('admin/news/index.php'));
        exit;
      }
      $error = 'Wrong username or password';
      record_admin_login_log($username, null, 'failed', 'wrong credentials');
    }
  }
  if ($error !== '') {
    $_SESSION['login_attempts'] = (int)$_SESSION['login_attempts'] + 1;
    if ($_SESSION['login_attempts'] >= $maxAttempts) $_SESSION['login_locked_until'] = time() + $lockoutSeconds;
    set_captcha();
  }
}
?>
<!doctype html>
<html lang="en">
<?php admin_head('Admin Login'); ?>
<body class="admin-body">
  <div class="admin-wrap" style="max-width:460px;min-height:100vh;display:flex;align-items:center;">
    <form class="admin-card" method="post" style="width:100%">
      <h2 style="margin:0 0 8px">Admin Login</h2>
      <p style="margin:0 0 14px;color:#9fb2cc">Secure panel access</p>

      <?php if($error): ?><div class="err"><?=h($error)?></div><?php endif; ?>
      <input type="hidden" name="_csrf" value="<?=h(csrf_token())?>">

      <div>
        <label>Username</label>
        <input name="username" autocomplete="username" required>
      </div>
      <div style="margin-top:10px">
        <label>Password</label>
        <input name="password" type="password" autocomplete="current-password" required>
      </div>
      <div style="margin-top:10px">
        <label>Captcha: <?=h((string)($_SESSION['captcha_a'] ?? 0))?> + <?=h((string)($_SESSION['captcha_b'] ?? 0))?></label>
        <input name="captcha" inputmode="numeric" autocomplete="off" required>
      </div>

      <button class="btn" style="width:100%;margin-top:14px" type="submit">Login</button>
      <p style="margin-top:12px;color:#9fb2cc;font-size:13px">
        If you cannot login, run <a href="<?=h(url('admin/setup_admin.php'))?>">setup_admin.php</a> once.
      </p>
    </form>
  </div>
</body>
</html>
