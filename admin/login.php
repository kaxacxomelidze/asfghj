<?php
declare(strict_types=1);
require __DIR__ . '/../inc/bootstrap.php';

if (is_admin()) {
  header('Location: ' . url('admin/news/index.php'));
  exit;
}

$error = '';
$lockoutSeconds = 300;
$maxAttempts = 5;

if (!isset($_SESSION['login_attempts'])) {
  $_SESSION['login_attempts'] = 0;
}
if (!isset($_SESSION['login_locked_until'])) {
  $_SESSION['login_locked_until'] = 0;
}

function set_captcha(): void {
  $_SESSION['captcha_a'] = random_int(1, 9);
  $_SESSION['captcha_b'] = random_int(1, 9);
}

if (empty($_SESSION['captcha_a']) || empty($_SESSION['captcha_b'])) {
  set_captcha();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  csrf_verify();
  $now = time();
  if ($_SESSION['login_locked_until'] > $now) {
    $remaining = (int)($_SESSION['login_locked_until'] - $now);
    $error = 'Too many attempts. Try again in ' . $remaining . ' seconds.';
  } else {
    $captcha = trim((string)($_POST['captcha'] ?? ''));
    $expected = (string)(($_SESSION['captcha_a'] ?? 0) + ($_SESSION['captcha_b'] ?? 0));

    $username = trim((string)($_POST['username'] ?? ''));
    $password = (string)($_POST['password'] ?? '');

    if ($captcha === '' || !hash_equals($expected, $captcha)) {
      $error = 'Captcha is incorrect';
    } else {
      $stmt = db()->prepare("SELECT id, password_hash FROM admins WHERE username=? LIMIT 1");
      $stmt->execute([$username]);
      $admin = $stmt->fetch();

      if ($admin && password_verify($password, (string)$admin['password_hash'])) {
        session_regenerate_id(true);
        $_SESSION['admin_id'] = (int)$admin['id'];
        $_SESSION['admin_username'] = $username;
        $_SESSION['login_attempts'] = 0;
        $_SESSION['login_locked_until'] = 0;

        header('Location: ' . url('admin/news/index.php'));
        exit;
      }

      $error = 'Wrong username or password';
    }
  }
  if ($error !== '') {
    $_SESSION['login_attempts'] = (int)$_SESSION['login_attempts'] + 1;
    if ($_SESSION['login_attempts'] >= $maxAttempts) {
      $_SESSION['login_locked_until'] = time() + $lockoutSeconds;
    }
    set_captcha();
  }
}
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Admin Login</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <style>
    body{font-family:system-ui;background:#0b1220;color:#e5e7eb;display:flex;min-height:100vh;align-items:center;justify-content:center;}
    .card{width:min(420px,92vw);background:#111a2e;border:1px solid rgba(255,255,255,.12);border-radius:16px;padding:22px}
    input{width:100%;padding:12px;border-radius:12px;border:1px solid rgba(255,255,255,.18);background:#0b1220;color:#fff;margin-top:8px}
    button{width:100%;padding:12px;border-radius:12px;border:0;background:#2563eb;color:#fff;font-weight:700;margin-top:14px;cursor:pointer}
    .err{color:#fca5a5;margin:10px 0}
    a{color:#93c5fd;text-decoration:none}
  </style>
</head>
<body>
  <form class="card" method="post">
    <h2 style="margin:0 0 10px">Admin Login</h2>

    <?php if($error): ?><div class="err"><?=h($error)?></div><?php endif; ?>

    <input type="hidden" name="_csrf" value="<?=h(csrf_token())?>">

    <label>Username</label>
    <input name="username" autocomplete="username" required>

    <label style="display:block;margin-top:10px">Password</label>
    <input name="password" type="password" autocomplete="current-password" required>

    <label style="display:block;margin-top:10px">Captcha: <?=h((string)($_SESSION['captcha_a'] ?? 0))?> + <?=h((string)($_SESSION['captcha_b'] ?? 0))?></label>
    <input name="captcha" inputmode="numeric" autocomplete="off" required>

    <button type="submit">Login</button>

    <p style="opacity:.75;margin-top:12px;font-size:13px">
      If you can't login, run <a href="<?=h(url('admin/setup_admin.php'))?>">setup_admin.php</a> once.
    </p>
  </form>
</body>
</html>
