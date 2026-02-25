<?php
require __DIR__ . '/inc/bootstrap.php';
$pageTitle = 'საქართველოს სტუდენტური პარლამენტი და მთავრობა — გაწევრიანება';
ensure_membership_applications_table();

$errors = [];
$success = false;
$data = [
  'first_name' => '',
  'last_name' => '',
  'personal_id' => '',
  'phone' => '',
  'university' => '',
  'faculty' => '',
  'email' => '',
  'additional_info' => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  csrf_verify();
  foreach ($data as $key => $_) {
    $data[$key] = trim((string)($_POST[$key] ?? ''));
  }

  foreach (['first_name','last_name','personal_id','phone','university','faculty'] as $req) {
    if ($data[$req] === '') {
      $errors[] = 'Please fill all required fields.';
      break;
    }
  }

  if (!$errors) {
    $stmt = db()->prepare('INSERT INTO membership_applications (first_name, last_name, personal_id, phone, university, faculty, email, additional_info, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)');
    $stmt->execute([
      $data['first_name'],
      $data['last_name'],
      $data['personal_id'],
      $data['phone'],
      $data['university'],
      $data['faculty'],
      $data['email'] !== '' ? $data['email'] : null,
      $data['additional_info'] !== '' ? $data['additional_info'] : null,
      date('Y-m-d H:i:s'),
    ]);
    $success = true;
    foreach ($data as $k => $_) $data[$k] = '';
  }
}

include __DIR__ . '/header.php';
?>
<section class="section">
  <div class="container" style="max-width:900px;padding:40px 0;">
    <h2 style="margin-bottom:8px">გაწევრიანების ფორმა</h2>
    <p style="color:var(--muted);margin-bottom:16px">შევსებული მონაცემები გადაეგზავნება ადმინისტრაციის პანელს.</p>

    <?php if($success): ?>
      <div style="color:#16a34a;margin-bottom:12px">განაცხადი წარმატებით გაიგზავნა.</div>
    <?php endif; ?>

    <?php if($errors): ?>
      <div style="color:#dc2626;margin-bottom:12px">
        <?php foreach($errors as $e) echo '<div>'.h($e).'</div>'; ?>
      </div>
    <?php endif; ?>

    <form method="post" style="display:grid;gap:12px">
      <input type="hidden" name="_csrf" value="<?=h(csrf_token())?>">

      <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
        <div>
          <label>სახელი *</label><br>
          <input name="first_name" value="<?=h($data['first_name'])?>" required style="width:100%;padding:12px;border-radius:12px;border:1px solid var(--line)">
        </div>
        <div>
          <label>გვარი *</label><br>
          <input name="last_name" value="<?=h($data['last_name'])?>" required style="width:100%;padding:12px;border-radius:12px;border:1px solid var(--line)">
        </div>
      </div>

      <div>
        <label>პირადი ნომერი *</label><br>
        <input name="personal_id" value="<?=h($data['personal_id'])?>" required style="width:100%;padding:12px;border-radius:12px;border:1px solid var(--line)">
      </div>

      <div>
        <label>ტელეფონი *</label><br>
        <input name="phone" value="<?=h($data['phone'])?>" required style="width:100%;padding:12px;border-radius:12px;border:1px solid var(--line)">
      </div>

      <div>
        <label>უნივერსიტეტი *</label><br>
        <input name="university" value="<?=h($data['university'])?>" required style="width:100%;padding:12px;border-radius:12px;border:1px solid var(--line)">
      </div>

      <div>
        <label>ფაკულტეტი *</label><br>
        <input name="faculty" value="<?=h($data['faculty'])?>" required style="width:100%;padding:12px;border-radius:12px;border:1px solid var(--line)">
      </div>

      <div>
        <label>ელ-ფოსტა</label><br>
        <input type="email" name="email" value="<?=h($data['email'])?>" style="width:100%;padding:12px;border-radius:12px;border:1px solid var(--line)">
      </div>

      <div>
        <label>სხვა ინფორმაცია</label><br>
        <textarea name="additional_info" style="width:100%;min-height:130px;padding:12px;border-radius:12px;border:1px solid var(--line)"><?=h($data['additional_info'])?></textarea>
      </div>

      <button type="submit" style="padding:12px 14px;border-radius:12px;border:0;background:#2563eb;color:#fff;font-weight:800;cursor:pointer">გაგზავნა</button>
    </form>
  </div>
</section>
<?php include __DIR__ . '/footer.php'; ?>
