<?php
require __DIR__ . '/inc/bootstrap.php';
$pageTitle = 'SPG Portal — მომხმარებლის ავტორიზაცია';
ensure_users_table();
$lecturerOptions = list_available_lecturers();

$tab = (string)($_GET['tab'] ?? 'signin');
if (!in_array($tab, ['signin', 'signup'], true)) $tab = 'signin';
$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  csrf_verify();
  $action = (string)($_POST['action'] ?? '');

  if ($action === 'signup') {
    $fullName = trim((string)($_POST['full_name'] ?? ''));
    $email = mb_strtolower(trim((string)($_POST['email'] ?? '')));
    $password = (string)($_POST['password'] ?? '');
    $lecturerName = normalize_lecturer_name((string)($_POST['lecturer_name'] ?? ''));

    if ($fullName === '' || $email === '' || $password === '' || $lecturerName === '') $errors[] = 'გთხოვთ შეავსოთ ყველა ველი.';
    if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'ელ-ფოსტის ფორმატი არასწორია.';
    if ($password !== '' && !strong_password($password)) $errors[] = 'პაროლი უნდა იყოს მინიმუმ 8 სიმბოლო და შეიცავდეს დიდ/პატარა ასოს და ციფრს.';
    if ($lecturerName !== '' && $lecturerOptions && !in_array($lecturerName, $lecturerOptions, true)) {
      $errors[] = 'აირჩიეთ ლექტორი სიიდან.';
    }

    if (!$errors) {
      if (!user_login_allowed()) {
        $errors[] = 'ძალიან ბევრი მცდელობა. სცადეთ თავიდან ' . user_login_lock_remaining() . ' წამში.';
      }
    }

    if (!$errors) {
      try {
        $stmt = db()->prepare('SELECT id FROM users WHERE email=? LIMIT 1');
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
          $errors[] = 'ეს ელ-ფოსტა უკვე გამოყენებულია.';
        } else {
          $stmt = db()->prepare('INSERT INTO users (full_name, email, lecturer_name, password_hash, created_at) VALUES (?, ?, ?, ?, ?)');
          $stmt->execute([$fullName, $email, $lecturerName, password_hash($password, PASSWORD_DEFAULT), date('Y-m-d H:i:s')]);
          $userId = (int)db()->lastInsertId();
          session_regenerate_id(true);
          $_SESSION['user_id'] = $userId;
          $_SESSION['user_name'] = $fullName;
          $_SESSION['user_email'] = $email;
          $_SESSION['user_lecturer_name'] = $lecturerName;
          user_login_register_success();
          header('Location: ' . url('user-dashboard.php'));
          exit;
        }
        user_login_register_failure();
      } catch (Throwable $e) {
        $errors[] = 'რეგისტრაცია ვერ შესრულდა. სცადეთ მოგვიანებით.';
      }
    }
    $tab = 'signup';
  }

  if ($action === 'signin') {
    $email = mb_strtolower(trim((string)($_POST['email'] ?? '')));
    $password = (string)($_POST['password'] ?? '');
    if ($email === '' || $password === '') $errors[] = 'შეიყვანეთ ელ-ფოსტა და პაროლი.';

    if (!$errors) {
      if (!user_login_allowed()) {
        $errors[] = 'ძალიან ბევრი მცდელობა. სცადეთ თავიდან ' . user_login_lock_remaining() . ' წამში.';
      }
    }

    if (!$errors) {
      try {
        $stmt = db()->prepare('SELECT id, full_name, email, lecturer_name, password_hash FROM users WHERE email=? LIMIT 1');
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        if ($user && password_verify($password, (string)$user['password_hash'])) {
          session_regenerate_id(true);
          $_SESSION['user_id'] = (int)$user['id'];
          $_SESSION['user_name'] = (string)$user['full_name'];
          $_SESSION['user_email'] = (string)$user['email'];
          $_SESSION['user_lecturer_name'] = (string)($user['lecturer_name'] ?? '');
          user_login_register_success();
          header('Location: ' . url('user-dashboard.php'));
          exit;
        }
        user_login_register_failure();
      } catch (Throwable $e) {
        // ignore details
      }
      $errors[] = 'მომხმარებელი ან პაროლი არასწორია.';
    }
    $tab = 'signin';
  }
}

if (is_user_logged_in()) {
  header('Location: ' . url('user-dashboard.php'));
  exit;
}

include __DIR__ . '/header.php';
?>
<section class="section" id="dashboard" style="scroll-margin-top:120px;">
  <div class="container" style="max-width:1000px;padding:34px 0 48px;display:grid;gap:16px;">
    <div style="background:#fff;border:1px solid var(--line);border-radius:18px;padding:18px;box-shadow:0 14px 32px rgba(15,23,42,.06)">
      <h2 style="margin:0 0 8px">მომხმარებლის ავტორიზაცია</h2>
      <p style="margin:0;color:var(--muted)">სისტემაში შესვლის შემდეგ გადახვალთ ცალკე მომხმარებლის dashboard გვერდზე.</p>
    </div>

    <?php if($success): ?><div class="ok"><?=h($success)?></div><?php endif; ?>
    <?php if($errors): ?><div class="err"><?php foreach($errors as $e) echo '<div>'.h($e).'</div>'; ?></div><?php endif; ?>

    <div style="display:flex;gap:10px;flex-wrap:wrap">
      <a href="<?=h(url('user-auth.php?tab=signin#dashboard'))?>" class="btn <?= $tab==='signin' ? 'primary' : '' ?>">შესვლა</a>
      <a href="<?=h(url('user-auth.php?tab=signup#dashboard'))?>" class="btn <?= $tab==='signup' ? 'primary' : '' ?>">რეგისტრაცია</a>
    </div>

    <?php if($tab === 'signin'): ?>
      <div style="background:#fff;border:1px solid var(--line);border-radius:16px;padding:20px;max-width:560px;">
        <h3 style="margin-bottom:12px">მომხმარებლის შესვლა</h3>
        <form method="post" style="display:grid;gap:12px">
          <input type="hidden" name="_csrf" value="<?=h(csrf_token())?>">
          <input type="hidden" name="action" value="signin">
          <div>
            <label for="signin-email">ელ-ფოსტა</label><br>
            <input id="signin-email" type="email" name="email" required style="width:100%;padding:12px;border-radius:12px;border:1px solid var(--line)">
          </div>
          <div>
            <label for="signin-pass">პაროლი</label><br>
            <input id="signin-pass" type="password" name="password" required style="width:100%;padding:12px;border-radius:12px;border:1px solid var(--line)">
          </div>
          <button type="submit" style="padding:12px 14px;border-radius:12px;border:0;background:#2563eb;color:#fff;font-weight:800;cursor:pointer">შესვლა</button>
        </form>
      </div>
    <?php else: ?>
      <div style="background:#fff;border:1px solid var(--line);border-radius:16px;padding:20px;max-width:560px;">
        <h3 style="margin-bottom:12px">მომხმარებლის რეგისტრაცია</h3>
        <form method="post" style="display:grid;gap:12px">
          <input type="hidden" name="_csrf" value="<?=h(csrf_token())?>">
          <input type="hidden" name="action" value="signup">
          <div>
            <label for="signup-name">სახელი და გვარი</label><br>
            <input id="signup-name" name="full_name" value="<?=h((string)($_POST['full_name'] ?? ''))?>" required style="width:100%;padding:12px;border-radius:12px;border:1px solid var(--line)">
          </div>
          <div>
            <label for="signup-email">ელ-ფოსტა</label><br>
            <input id="signup-email" type="email" name="email" value="<?=h((string)($_POST['email'] ?? ''))?>" required style="width:100%;padding:12px;border-radius:12px;border:1px solid var(--line)">
          </div>
          <div>
            <label for="signup-lecturer">თქვენი ლექტორი</label><br>
            <?php if($lecturerOptions): ?>
              <select id="signup-lecturer" name="lecturer_name" required style="width:100%;padding:12px;border-radius:12px;border:1px solid var(--line)">
                <option value="">აირჩიეთ ლექტორი</option>
                <?php foreach($lecturerOptions as $lectName): ?>
                  <option value="<?=h((string)$lectName)?>" <?= ((string)($_POST['lecturer_name'] ?? '') === (string)$lectName) ? 'selected' : '' ?>><?=h((string)$lectName)?></option>
                <?php endforeach; ?>
              </select>
            <?php else: ?>
              <input id="signup-lecturer" name="lecturer_name" value="<?=h((string)($_POST['lecturer_name'] ?? ''))?>" required placeholder="ლექტორის სახელი" style="width:100%;padding:12px;border-radius:12px;border:1px solid var(--line)">
            <?php endif; ?>
          </div>

          <div>
            <label for="signup-pass">პაროლი</label><br>
            <input id="signup-pass" type="password" name="password" required style="width:100%;padding:12px;border-radius:12px;border:1px solid var(--line)">
            <small style="color:var(--muted)">მინიმუმ 8 სიმბოლო, დიდი/პატარა ასო და ციფრი.</small>
          </div>
          <button type="submit" style="padding:12px 14px;border-radius:12px;border:0;background:#0ea5e9;color:#fff;font-weight:800;cursor:pointer">რეგისტრაცია</button>
        </form>
      </div>
    <?php endif; ?>
  </div>
</section>
<?php include __DIR__ . '/footer.php'; ?>
