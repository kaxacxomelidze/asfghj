<?php
declare(strict_types=1);

require __DIR__ . '/../inc/bootstrap.php';
require __DIR__ . '/_ui.php';

// --- Optional: turn on local debug temporarily (DO NOT leave in production) ---
// ini_set('display_errors', '1');
// ini_set('display_startup_errors', '1');
// error_reporting(E_ALL);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

/**
 * Safe wrapper so logging failures never break login flow.
 */
function safe_record_admin_login_log(string $username, ?int $adminId, string $status, string $message): void
{
    try {
        if (function_exists('record_admin_login_log')) {
            record_admin_login_log($username, $adminId, $status, $message);
        }
    } catch (Throwable $e) {
        // Never break login page because log table/function failed
        error_log('record_admin_login_log failed: ' . $e->getMessage());
    }
}

/**
 * Reset / set captcha numbers
 */
function set_captcha(): void
{
    $_SESSION['captcha_a'] = random_int(1, 9);
    $_SESSION['captcha_b'] = random_int(1, 9);
}

/**
 * Small helper for session keys
 */
function init_login_session_state(): void
{
    if (!isset($_SESSION['login_attempts']) || !is_int($_SESSION['login_attempts'])) {
        $_SESSION['login_attempts'] = 0;
    }
    if (!isset($_SESSION['login_locked_until']) || !is_int($_SESSION['login_locked_until'])) {
        $_SESSION['login_locked_until'] = 0;
    }
}

init_login_session_state();

if (empty($_SESSION['captcha_a']) || empty($_SESSION['captcha_b'])) {
    set_captcha();
}

// If already logged in, redirect
if (function_exists('is_admin') && is_admin()) {
    header('Location: ' . url('admin/news/index.php'));
    exit;
}

$error = '';
$lockoutSeconds = 300; // 5 minutes
$maxAttempts = 5;

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    try {
        // CSRF verification can throw
        csrf_verify();

        $now = time();

        // Check lockout
        if ((int)$_SESSION['login_locked_until'] > $now) {
            $remaining = (int)$_SESSION['login_locked_until'] - $now;
            $error = 'Too many attempts. Try again in ' . $remaining . ' seconds.';
            safe_record_admin_login_log((string)($_POST['username'] ?? ''), null, 'blocked', 'temporary lockout');
        } else {
            $captcha  = trim((string)($_POST['captcha'] ?? ''));
            $expected = (string)(((int)($_SESSION['captcha_a'] ?? 0)) + ((int)($_SESSION['captcha_b'] ?? 0)));

            $username = trim((string)($_POST['username'] ?? ''));
            $password = (string)($_POST['password'] ?? '');

            if ($username === '' || $password === '') {
                $error = 'Username and password are required.';
                safe_record_admin_login_log($username, null, 'failed', 'empty username/password');
            } elseif ($captcha === '' || !hash_equals($expected, $captcha)) {
                $error = 'Captcha is incorrect';
                safe_record_admin_login_log($username, null, 'failed', 'captcha incorrect');
            } else {
                try {
                    $pdo = db(); // must return PDO
                    $stmt = $pdo->prepare('SELECT id, username, password_hash FROM admins WHERE username = ? LIMIT 1');
                    $stmt->execute([$username]);
                    $admin = $stmt->fetch(PDO::FETCH_ASSOC);

                    if ($admin && isset($admin['password_hash']) && password_verify($password, (string)$admin['password_hash'])) {
                        session_regenerate_id(true);

                        $_SESSION['admin_id'] = (int)$admin['id'];
                        $_SESSION['admin_username'] = (string)$admin['username'];

                        // Reset login protection state
                        $_SESSION['login_attempts'] = 0;
                        $_SESSION['login_locked_until'] = 0;

                        safe_record_admin_login_log($username, (int)$admin['id'], 'success', 'login successful');

                        header('Location: ' . url('admin/news/index.php'));
                        exit;
                    }

                    $error = 'Wrong username or password';
                    safe_record_admin_login_log($username, null, 'failed', 'wrong credentials');
                } catch (Throwable $e) {
                    // DB query failure
                    $error = 'Temporary server/database error. Please try again.';
                    safe_record_admin_login_log($username, null, 'failed', 'db unavailable');
                    error_log('Admin login DB error: ' . $e->getMessage());
                }
            }
        }
    } catch (Throwable $e) {
        // CSRF/helper fatal -> user-friendly message
        $error = 'Request validation failed. Please refresh and try again.';
        error_log('Admin login request error: ' . $e->getMessage());
        safe_record_admin_login_log((string)($_POST['username'] ?? ''), null, 'failed', 'request validation failed');
    }

    // If any error happened -> count attempt and refresh captcha
    if ($error !== '') {
        $_SESSION['login_attempts'] = ((int)$_SESSION['login_attempts']) + 1;

        if ((int)$_SESSION['login_attempts'] >= $maxAttempts) {
            $_SESSION['login_locked_until'] = time() + $lockoutSeconds;
        }

        set_captcha();
    }
}
?>
<!doctype html>
<html lang="en">
<?php admin_head('Admin Login'); ?>
<body class="admin-body">
  <div class="admin-wrap" style="max-width:460px;min-height:100vh;display:flex;align-items:center;">
    <form class="admin-card" method="post" style="width:100%" novalidate>
      <h2 style="margin:0 0 8px">Admin Login</h2>
      <p style="margin:0 0 14px;color:#9fb2cc">Secure panel access</p>

      <?php if ($error !== ''): ?>
        <div class="err"><?= h($error) ?></div>
      <?php endif; ?>

      <input type="hidden" name="_csrf" value="<?= h(csrf_token()) ?>">

      <div>
        <label for="username">Username</label>
        <input id="username" name="username" autocomplete="username" required
               value="<?= h((string)($_POST['username'] ?? '')) ?>">
      </div>

      <div style="margin-top:10px">
        <label for="password">Password</label>
        <input id="password" name="password" type="password" autocomplete="current-password" required>
      </div>

      <div style="margin-top:10px">
        <label for="captcha">
          Captcha: <?= h((string)($_SESSION['captcha_a'] ?? 0)) ?> + <?= h((string)($_SESSION['captcha_b'] ?? 0)) ?>
        </label>
        <input id="captcha" name="captcha" inputmode="numeric" autocomplete="off" required>
      </div>

      <button class="btn" style="width:100%;margin-top:14px" type="submit">Login</button>

      <p style="margin-top:12px;color:#9fb2cc;font-size:13px">
        If you cannot login, run <a href="<?= h(url('admin/setup_admin.php')) ?>">setup_admin.php</a> once.
      </p>
    </form>
  </div>
</body>
</html>