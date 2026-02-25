<?php
declare(strict_types=1);

require __DIR__ . '/../inc/bootstrap.php';
require __DIR__ . '/_ui.php';

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
        csrf_verify();

        $now = time();

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
                    $pdo = db();
                    $stmt = $pdo->prepare('SELECT id, username, password_hash FROM admins WHERE username = ? LIMIT 1');
                    $stmt->execute([$username]);
                    $admin = $stmt->fetch(PDO::FETCH_ASSOC);

                    if ($admin && isset($admin['password_hash']) && password_verify($password, (string)$admin['password_hash'])) {
                        session_regenerate_id(true);

                        $_SESSION['admin_id'] = (int)$admin['id'];
                        $_SESSION['admin_username'] = (string)$admin['username'];

                        $_SESSION['login_attempts'] = 0;
                        $_SESSION['login_locked_until'] = 0;

                        safe_record_admin_login_log($username, (int)$admin['id'], 'success', 'login successful');

                        header('Location: ' . url('admin/news/index.php'));
                        exit;
                    }

                    $error = 'Wrong username or password';
                    safe_record_admin_login_log($username, null, 'failed', 'wrong credentials');
                } catch (Throwable $e) {
                    $error = 'Temporary server/database error. Please try again.';
                    safe_record_admin_login_log($username, null, 'failed', 'db unavailable');
                    error_log('Admin login DB error: ' . $e->getMessage());
                }
            }
        }
    } catch (Throwable $e) {
        $error = 'Request validation failed. Please refresh and try again.';
        error_log('Admin login request error: ' . $e->getMessage());
        safe_record_admin_login_log((string)($_POST['username'] ?? ''), null, 'failed', 'request validation failed');
    }

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
<body class="admin-body admin-login-page">
  <main class="admin-login-shell">
    <section class="admin-login-brand" aria-hidden="true">
      <img src="<?= h(url('spg_logo2.png')) ?>" alt="SPG Logo" width="88" height="88">
      <h1>Admin Panel</h1>
      <p>Secure access for content and management tools.</p>
    </section>

    <form class="admin-card admin-login-card" method="post" novalidate>
      <h2>Admin Login</h2>
      <p class="admin-login-subtitle">Sign in to manage news, teams, and settings.</p>

      <?php if ($error !== ''): ?>
        <div class="err"><?= h($error) ?></div>
      <?php endif; ?>

      <input type="hidden" name="_csrf" value="<?= h(csrf_token()) ?>">

      <div class="admin-field">
        <label for="username">Username</label>
        <input id="username" name="username" autocomplete="username" required
               value="<?= h((string)($_POST['username'] ?? '')) ?>">
      </div>

      <div class="admin-field">
        <label for="password">Password</label>
        <input id="password" name="password" type="password" autocomplete="current-password" required>
      </div>

      <div class="admin-field">
        <label for="captcha">
          Captcha: <?= h((string)($_SESSION['captcha_a'] ?? 0)) ?> + <?= h((string)($_SESSION['captcha_b'] ?? 0)) ?>
        </label>
        <input id="captcha" name="captcha" inputmode="numeric" autocomplete="off" required>
      </div>

      <button class="btn admin-login-btn" type="submit">Login</button>

      <p class="admin-login-note">
        If you cannot login, run <a href="<?= h(url('admin/setup_admin.php')) ?>">setup_admin.php</a> once.
      </p>
    </form>
  </main>
</body>
</html>
