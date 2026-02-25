<?php
require __DIR__ . '/inc/bootstrap.php';
$pageTitle = 'საქართველოს სტუდენტური პარლამენტი და მთავრობა — User Dashboard';
ensure_users_table();

if (!is_user_logged_in()) {
  header('Location: ' . url('user-auth.php?tab=signin#dashboard'));
  exit;
}

$user = current_user();
if (!$user) {
  header('Location: ' . url('user-auth.php?tab=signin#dashboard'));
  exit;
}

seed_user_dashboard_data((int)$user['id']);
$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  csrf_verify();
  $action = (string)($_POST['action'] ?? '');

  if ($action === 'logout') {
    unset($_SESSION['user_id'], $_SESSION['user_name'], $_SESSION['user_email'], $_SESSION['user_lecturer_name']);
    session_regenerate_id(true);
    header('Location: ' . url('user-auth.php?tab=signin#dashboard'));
    exit;
  }

  if ($action === 'change_password') {
    $currentPassword = (string)($_POST['current_password'] ?? '');
    $newPassword = (string)($_POST['new_password'] ?? '');

    if ($currentPassword === '' || $newPassword === '') {
      $errors[] = 'შეავსეთ ორივე ველი პაროლის შესაცვლელად.';
    } elseif (!strong_password($newPassword)) {
      $errors[] = 'ახალი პაროლი უნდა იყოს მინიმუმ 8 სიმბოლო, დიდი/პატარა ასოებით და ციფრით.';
    }

    if (!$errors) {
      try {
        $stmt = db()->prepare('SELECT password_hash FROM users WHERE id=? LIMIT 1');
        $stmt->execute([(int)$user['id']]);
        $row = $stmt->fetch();
        if (!$row || !password_verify($currentPassword, (string)$row['password_hash'])) {
          $errors[] = 'მიმდინარე პაროლი არასწორია.';
        } else {
          $stmt = db()->prepare('UPDATE users SET password_hash=? WHERE id=?');
          $stmt->execute([password_hash($newPassword, PASSWORD_DEFAULT), (int)$user['id']]);
          $success = 'პაროლი წარმატებით განახლდა.';
        }
      } catch (Throwable $e) {
        $errors[] = 'პაროლის შეცვლა დროებით ვერ ხერხდება.';
      }
    }
  }

  if ($action === 'mark_notifications_read') {
    ensure_user_notifications_table();
    try {
      $stmt = db()->prepare('UPDATE user_notifications SET is_read=1 WHERE user_id=?');
      $stmt->execute([(int)$user['id']]);
      $success = 'შეტყობინებები მონიშნულია როგორც წაკითხული.';
    } catch (Throwable $e) {
      $errors[] = 'შეტყობინებების განახლება ვერ შესრულდა.';
    }
  }
}

$courses = get_user_courses((int)$user['id']);
$tasks = get_user_tasks((int)$user['id']);
$notifications = get_user_notifications((int)$user['id']);
$lecturers = get_user_lecturers((int)$user['id']);
$unreadCount = 0;
foreach ($notifications as $n) {
  if ((int)($n['is_read'] ?? 0) === 0) $unreadCount++;
}

include __DIR__ . '/header.php';
?>
<section class="section" id="dashboard" style="scroll-margin-top:120px;padding:36px 0 54px">
  <div class="container" style="max-width:1120px;display:grid;gap:16px">
    <div style="background:#fff;border:1px solid var(--line);border-radius:18px;padding:20px;box-shadow:0 14px 32px rgba(15,23,42,.06)">
      <div style="display:flex;justify-content:space-between;gap:12px;flex-wrap:wrap;align-items:flex-start">
        <div>
          <h2 style="margin:0 0 8px">უსაფრთხო სტუდენტის Dashboard</h2>
          <p style="margin:0;color:var(--muted)">მოგესალმებით, <b><?=h((string)$user['name'])?></b> (<?=h((string)$user['email'])?>).</p>
          <p style="margin:6px 0 0;color:var(--muted)">თქვენი ლექტორი: <b><?=h((string)($user['lecturer_name'] ?? ''))?></b></p>
        </div>
        <span style="padding:6px 12px;border-radius:999px;background:#eff6ff;border:1px solid #bfdbfe;color:#1e40af;font-weight:700;font-size:13px">Secure Portal</span>
      </div>
      <div style="display:flex;gap:10px;flex-wrap:wrap;margin-top:14px">
        <a href="#dashboard" class="btn primary">📊 Dashboard</a>
        <a href="#university-system" class="btn">🎓 უნივერსიტეტის სისტემა</a>
        <a href="#account-security" class="btn">🔒 უსაფრთხოება</a>
        <a href="#lecturers" class="btn">👨‍🏫 ლექტორები</a>
        <?php if(!empty($user['lecturer_name'])): ?><a href="<?=h(url('lecturer.php?name=' . urlencode((string)$user['lecturer_name'])))?>" class="btn">📋 ჩემი ლექტორის გვერდი</a><?php endif; ?>
        <a href="<?=h(url('membership.php'))?>" class="btn">🧾 გაწევრიანება</a>
      </div>
    </div>

    <?php if($success): ?><div class="ok"><?=h($success)?></div><?php endif; ?>
    <?php if($errors): ?><div class="err"><?php foreach($errors as $e) echo '<div>'.h($e).'</div>'; ?></div><?php endif; ?>

    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:12px">
      <div style="background:#fff;border:1px solid var(--line);border-radius:14px;padding:14px"><div style="color:var(--muted);font-size:13px">აქტიური კურსები</div><div style="font-size:22px;font-weight:800;margin-top:4px"><?=count($courses)?></div></div>
      <div style="background:#fff;border:1px solid var(--line);border-radius:14px;padding:14px"><div style="color:var(--muted);font-size:13px">მიმდინარე დავალებები</div><div style="font-size:22px;font-weight:800;margin-top:4px"><?=count($tasks)?></div></div>
      <div style="background:#fff;border:1px solid var(--line);border-radius:14px;padding:14px"><div style="color:var(--muted);font-size:13px">შეტყობინებები</div><div style="font-size:22px;font-weight:800;margin-top:4px"><?=$unreadCount?> unread</div></div>
      <div style="background:#fff;border:1px solid var(--line);border-radius:14px;padding:14px"><div style="color:var(--muted);font-size:13px">ბოლო შესვლა</div><div style="font-size:22px;font-weight:800;margin-top:4px"><?=h(date('d.m.Y H:i'))?></div></div>
    </div>

    <div id="university-system" style="scroll-margin-top:120px;background:#fff;border:1px solid var(--line);border-radius:18px;padding:20px;box-shadow:0 14px 32px rgba(15,23,42,.06)">
      <h3 style="margin:0 0 8px">უნივერსიტეტის სრული სისტემა</h3>
      <p style="margin:0 0 14px;color:var(--muted)">კურსები, დავალებები და შეტყობინებები ერთ სივრცეში.</p>
      <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));gap:12px;align-items:start">
        <div style="border:1px solid var(--line);border-radius:12px;padding:12px;background:#f8fafc">
          <b>📚 კურსები</b>
          <ul style="margin:8px 0 0 18px;padding:0">
            <?php foreach($courses as $c): ?>
              <li><b><?=h((string)$c['course_title'])?></b> — <?=h((string)($c['schedule_text'] ?? ''))?></li>
            <?php endforeach; ?>
          </ul>
        </div>
        <div style="border:1px solid var(--line);border-radius:12px;padding:12px;background:#f8fafc">
          <b>📝 დავალებები</b>
          <ul style="margin:8px 0 0 18px;padding:0">
            <?php foreach($tasks as $t): ?>
              <li><?=h((string)$t['task_title'])?><?php if(!empty($t['due_at'])): ?> — <?=h(fmt_date_dmY((string)$t['due_at']))?><?php endif; ?></li>
            <?php endforeach; ?>
          </ul>
        </div>
        <div style="border:1px solid var(--line);border-radius:12px;padding:12px;background:#f8fafc">
          <div style="display:flex;justify-content:space-between;align-items:center;gap:8px;flex-wrap:wrap">
            <b>🔔 შეტყობინებები</b>
            <form method="post" style="margin:0">
              <input type="hidden" name="_csrf" value="<?=h(csrf_token())?>">
              <input type="hidden" name="action" value="mark_notifications_read">
              <button type="submit" class="btn" style="padding:6px 10px">read all</button>
            </form>
          </div>
          <ul style="margin:8px 0 0 18px;padding:0">
            <?php foreach($notifications as $n): ?>
              <li><?=h((string)$n['message'])?> <?php if((int)$n['is_read']===0): ?><small style="color:#ef4444">(new)</small><?php endif; ?></li>
            <?php endforeach; ?>
          </ul>
        </div>
      </div>
    </div>


    <div id="lecturers" style="scroll-margin-top:120px;background:#fff;border:1px solid var(--line);border-radius:18px;padding:20px;box-shadow:0 14px 32px rgba(15,23,42,.06)">
      <h3 style="margin:0 0 8px">👨‍🏫 ლექტორების სისტემა</h3>
      <p style="margin:0 0 12px;color:var(--muted)">საგნების ლექტორები, საკონტაქტო ელფოსტა და საკონსულტაციო საათები.</p>
      <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));gap:12px;align-items:start">
        <?php foreach($lecturers as $l): ?>
          <div style="border:1px solid var(--line);border-radius:12px;padding:12px;background:#f8fafc">
            <b><?=h((string)$l['lecturer_name'])?></b>
            <div style="color:var(--muted);font-size:13px;margin-top:4px"><?=h((string)($l['department'] ?? ''))?></div>
            <div style="font-size:13px;margin-top:6px">📧 <?=h((string)($l['email'] ?? '-'))?></div>
            <div style="font-size:13px">🏢 <?=h((string)($l['office_room'] ?? '-'))?></div>
            <div style="font-size:13px">🕒 <?=h((string)($l['office_hours'] ?? '-'))?></div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>

    <div id="account-security" style="scroll-margin-top:120px;background:#fff;border:1px solid var(--line);border-radius:18px;padding:20px;display:grid;gap:14px">
      <h3 style="margin:0">🔒 ანგარიშის უსაფრთხოება</h3>
      <form method="post" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:10px;align-items:end">
        <input type="hidden" name="_csrf" value="<?=h(csrf_token())?>">
        <input type="hidden" name="action" value="change_password">
        <div>
          <label for="cur-pass">მიმდინარე პაროლი</label>
          <input id="cur-pass" type="password" name="current_password" required style="width:100%;padding:12px;border-radius:12px;border:1px solid var(--line)">
        </div>
        <div>
          <label for="new-pass">ახალი პაროლი</label>
          <input id="new-pass" type="password" name="new_password" required style="width:100%;padding:12px;border-radius:12px;border:1px solid var(--line)">
        </div>
        <button type="submit" style="padding:12px 14px;border-radius:12px;border:0;background:#0ea5e9;color:#fff;font-weight:800;cursor:pointer">პაროლის შეცვლა</button>
      </form>
      <form method="post">
        <input type="hidden" name="_csrf" value="<?=h(csrf_token())?>">
        <input type="hidden" name="action" value="logout">
        <button type="submit" style="padding:12px 14px;border-radius:12px;border:0;background:#ef4444;color:#fff;font-weight:800;cursor:pointer">გასვლა</button>
      </form>
    </div>
  </div>
</section>
<?php include __DIR__ . '/footer.php'; ?>
