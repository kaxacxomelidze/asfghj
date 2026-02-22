<?php
require __DIR__ . '/../inc/bootstrap.php';
require __DIR__ . '/_ui.php';
require_admin();
require_permission('university.manage');

ensure_users_table();
ensure_user_courses_table();
ensure_user_tasks_table();
ensure_user_lecturers_table();

$msg = '';
$err = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  csrf_verify();
  $action = (string)($_POST['action'] ?? '');
  $userId = (int)($_POST['user_id'] ?? 0);
  if ($userId <= 0) {
    $err = 'Select a valid user.';
  } else {
    try {
      if ($action === 'add_course') {
        $title = trim((string)($_POST['course_title'] ?? ''));
        if ($title === '') throw new RuntimeException('Course title is required.');
        $stmt = db()->prepare('INSERT INTO user_courses (user_id, course_title, instructor, schedule_text, status, created_at) VALUES (?, ?, ?, ?, ?, ?)');
        $stmt->execute([$userId, $title, trim((string)($_POST['instructor'] ?? '')), trim((string)($_POST['schedule_text'] ?? '')), 'active', date('Y-m-d H:i:s')]);
        $msg = 'Course added.';
      } elseif ($action === 'add_task') {
        $title = trim((string)($_POST['task_title'] ?? ''));
        if ($title === '') throw new RuntimeException('Task title is required.');
        $due = trim((string)($_POST['due_at'] ?? ''));
        $dueAt = $due !== '' ? date('Y-m-d H:i:s', strtotime($due)) : null;
        $stmt = db()->prepare('INSERT INTO user_tasks (user_id, task_title, due_at, status, created_at) VALUES (?, ?, ?, ?, ?)');
        $stmt->execute([$userId, $title, $dueAt, 'todo', date('Y-m-d H:i:s')]);
        $msg = 'Task added.';
      } elseif ($action === 'add_lecturer') {
        $name = trim((string)($_POST['lecturer_name'] ?? ''));
        if ($name === '') throw new RuntimeException('Lecturer name is required.');
        $stmt = db()->prepare('INSERT INTO user_lecturers (user_id, lecturer_name, department, email, office_room, office_hours, created_at) VALUES (?, ?, ?, ?, ?, ?, ?)');
        $stmt->execute([$userId, $name, trim((string)($_POST['department'] ?? '')), trim((string)($_POST['email'] ?? '')), trim((string)($_POST['office_room'] ?? '')), trim((string)($_POST['office_hours'] ?? '')), date('Y-m-d H:i:s')]);
        $msg = 'Lecturer added.';
      }
    } catch (Throwable $e) {
      $err = $e->getMessage() ?: 'Action failed.';
    }
  }
}

$users = db()->query('SELECT id, full_name, email FROM users ORDER BY id DESC')->fetchAll();
$selectedUserId = (int)($_GET['user_id'] ?? ($_POST['user_id'] ?? 0));
$courses = $selectedUserId > 0 ? get_user_courses($selectedUserId) : [];
$tasks = $selectedUserId > 0 ? get_user_tasks($selectedUserId) : [];
$lecturers = $selectedUserId > 0 ? get_user_lecturers($selectedUserId) : [];
$selectedLecturerName = trim((string)($_GET['lecturer_name'] ?? ''));
$lecturerStudents = $selectedLecturerName !== '' ? get_lecturer_students($selectedLecturerName) : [];
?>
<!doctype html>
<html lang="en">
<?php admin_head('Admin — University System'); ?>
<body class="admin-body">
  <div class="admin-wrap">
    <?php admin_topbar('University System Manager', [
      ['href' => url('admin/news/index.php'), 'label' => 'News Admin'],
      ['href' => url('admin/admin-login-logs.php'), 'label' => 'Login Logs'],
      ['href' => url('admin/logout.php'), 'label' => 'Logout'],
    ]); ?>

    <?php if($msg): ?><div class="ok"><?=h($msg)?></div><?php endif; ?>
    <?php if($err): ?><div class="err"><?=h($err)?></div><?php endif; ?>

    <div class="admin-card">
      <form method="get" class="grid-2">
        <div>
          <label>Select user</label>
          <select name="user_id" required>
            <option value="">Choose user</option>
            <?php foreach($users as $u): ?>
              <option value="<?= (int)$u['id'] ?>" <?= $selectedUserId === (int)$u['id'] ? 'selected' : '' ?>><?=h((string)$u['full_name'])?> (<?=h((string)$u['email'])?>)</option>
            <?php endforeach; ?>
          </select>
        </div>
        <div style="align-self:end"><button class="btn" type="submit">Open user system</button></div>
      </form>
    </div>



    <div class="admin-card" style="margin-top:14px">
      <form method="get" class="grid-2">
        <div>
          <label>Lecturer student list</label>
          <input name="lecturer_name" value="<?=h($selectedLecturerName)?>" placeholder="Prof. N. Beridze">
        </div>
        <div style="align-self:end"><button class="btn" type="submit">Show students</button></div>
      </form>
      <?php if($selectedLecturerName !== ''): ?>
        <div style="margin-top:10px;overflow:auto">
          <table class="admin-table">
            <thead><tr><th>#</th><th>Student</th><th>Email</th><th>Registered</th></tr></thead>
            <tbody>
              <?php if(!$lecturerStudents): ?>
                <tr><td colspan="4">No students for this lecturer yet.</td></tr>
              <?php else: foreach($lecturerStudents as $i=>$st): ?>
                <tr>
                  <td><?= $i+1 ?></td>
                  <td><?= h((string)$st['full_name']) ?></td>
                  <td><?= h((string)$st['email']) ?></td>
                  <td><?= h((string)$st['created_at']) ?></td>
                </tr>
              <?php endforeach; endif; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>

    <?php if($selectedUserId > 0): ?>
      <div class="grid-3" style="margin-top:14px">
        <div class="admin-card"><label>Courses</label><div style="font-size:28px;font-weight:900"><?=count($courses)?></div></div>
        <div class="admin-card"><label>Tasks</label><div style="font-size:28px;font-weight:900"><?=count($tasks)?></div></div>
        <div class="admin-card"><label>Lecturers</label><div style="font-size:28px;font-weight:900"><?=count($lecturers)?></div></div>
      </div>

      <div class="grid-3" style="margin-top:14px">
        <form class="admin-card" method="post">
          <input type="hidden" name="_csrf" value="<?=h(csrf_token())?>"><input type="hidden" name="action" value="add_course"><input type="hidden" name="user_id" value="<?= $selectedUserId ?>">
          <h3>Add course</h3>
          <label>Title</label><input name="course_title" required>
          <label>Instructor</label><input name="instructor">
          <label>Schedule</label><input name="schedule_text">
          <button class="btn" style="margin-top:10px">Save</button>
        </form>
        <form class="admin-card" method="post">
          <input type="hidden" name="_csrf" value="<?=h(csrf_token())?>"><input type="hidden" name="action" value="add_task"><input type="hidden" name="user_id" value="<?= $selectedUserId ?>">
          <h3>Add task</h3>
          <label>Title</label><input name="task_title" required>
          <label>Due date/time</label><input type="datetime-local" name="due_at">
          <button class="btn" style="margin-top:10px">Save</button>
        </form>
        <form class="admin-card" method="post">
          <input type="hidden" name="_csrf" value="<?=h(csrf_token())?>"><input type="hidden" name="action" value="add_lecturer"><input type="hidden" name="user_id" value="<?= $selectedUserId ?>">
          <h3>Add lecturer</h3>
          <label>Name</label><input name="lecturer_name" required>
          <label>Department</label><input name="department">
          <label>Email</label><input type="email" name="email">
          <label>Office room</label><input name="office_room">
          <label>Office hours</label><input name="office_hours">
          <button class="btn" style="margin-top:10px">Save</button>
        </form>
      </div>
    <?php endif; ?>
  </div>
</body>
</html>
