<?php
$pageTitle = "SPG Portal — კონტაქტი";
require __DIR__ . "/inc/bootstrap.php";
include __DIR__ . "/header.php";

ensure_contact_messages_table();
$errors = [];
$success = false;
$data = [
  'name' => '',
  'email' => '',
  'phone' => '',
  'message' => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  csrf_verify();
  foreach ($data as $key => $_) {
    $data[$key] = trim((string)($_POST[$key] ?? ''));
  }

  if ($data['name'] === '') $errors[] = 'Name is required';
  if ($data['email'] === '') $errors[] = 'Email is required';
  if ($data['message'] === '') $errors[] = 'Message is required';

  if (!$errors) {
    $stmt = db()->prepare("INSERT INTO contact_messages (name, email, phone, message, created_at) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([
      $data['name'],
      $data['email'],
      $data['phone'] !== '' ? $data['phone'] : null,
      $data['message'],
      date('Y-m-d H:i:s'),
    ]);
    $success = true;
    $data = ['name' => '', 'email' => '', 'phone' => '', 'message' => ''];
  }
}
?>
<section class="section">
  <div class="container" style="max-width:1000px;padding:40px 0">
    <h2 style="margin-bottom:12px">კონტაქტი</h2>

    <div style="margin-bottom:18px;line-height:1.8">
      ოფისი<br>
      ჟიულ შარტავას 35-37 თბილისი<br><br>
      საკონტაქტო ინფორმაცია<br>
      Phone:<br>
      +995 591 037 047<br>
      Email:<br>
      info@spg.ge
    </div>

    <?php if($success): ?>
      <div style="color:#16a34a;margin-bottom:12px">Your message has been sent.</div>
    <?php endif; ?>
    <?php if($errors): ?>
      <div style="color:#dc2626;margin-bottom:12px">
        <?php foreach($errors as $e) echo '<div>'.h($e).'</div>'; ?>
      </div>
    <?php endif; ?>

    <form method="post" style="display:grid;gap:12px">
      <input type="hidden" name="_csrf" value="<?=h(csrf_token())?>">

      <div>
        <label>Name</label><br>
        <input name="name" value="<?=h($data['name'])?>" required style="width:100%;padding:12px;border-radius:12px;border:1px solid var(--line)">
      </div>
      <div>
        <label>Email</label><br>
        <input type="email" name="email" value="<?=h($data['email'])?>" required style="width:100%;padding:12px;border-radius:12px;border:1px solid var(--line)">
      </div>
      <div>
        <label>Phone</label><br>
        <input name="phone" value="<?=h($data['phone'])?>" style="width:100%;padding:12px;border-radius:12px;border:1px solid var(--line)">
      </div>
      <div>
        <label>Message</label><br>
        <textarea name="message" required style="width:100%;min-height:140px;padding:12px;border-radius:12px;border:1px solid var(--line)"><?=h($data['message'])?></textarea>
      </div>
      <button type="submit" style="padding:12px 14px;border-radius:12px;border:0;background:#2563eb;color:#fff;font-weight:800;cursor:pointer">Send</button>
    </form>
  </div>
</section>
<?php include __DIR__ . "/footer.php"; ?>
