<?php
$pageTitle = 'საქართველოს სტუდენტური პარლამენტი და მთავრობა — კონტაქტი';
require __DIR__ . '/inc/bootstrap.php';
include __DIR__ . '/header.php';

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

  if ($data['name'] === '') $errors[] = 'სახელი აუცილებელია.';
  if ($data['email'] === '' || !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) $errors[] = 'სწორი ელ-ფოსტა აუცილებელია.';
  if ($data['message'] === '' || mb_strlen($data['message']) < 8) $errors[] = 'მესიჯი უნდა იყოს მინიმუმ 8 სიმბოლო.';
  if ($data['phone'] !== '' && !preg_match('/^[0-9+\-\s]{6,20}$/', $data['phone'])) $errors[] = 'ტელეფონის ფორმატი არასწორია.';

  if (!$errors) {
    try {
      $stmt = db()->prepare('INSERT INTO contact_messages (name, email, phone, message, created_at) VALUES (?, ?, ?, ?, ?)');
      $stmt->execute([
        $data['name'],
        $data['email'],
        $data['phone'] !== '' ? $data['phone'] : null,
        $data['message'],
        date('Y-m-d H:i:s'),
      ]);
      $success = true;
      $data = ['name' => '', 'email' => '', 'phone' => '', 'message' => ''];
    } catch (Throwable $e) {
      $errors[] = 'გაგზავნა ვერ მოხერხდა. სცადეთ მოგვიანებით.';
    }
  }
}
?>
<section class="section">
  <div class="container" style="max-width:1060px;padding:40px 0;display:grid;gap:16px;grid-template-columns:repeat(auto-fit,minmax(300px,1fr));">

    <div style="background:#fff;border:1px solid var(--line);border-radius:18px;padding:20px;box-shadow:0 14px 30px rgba(15,23,42,.06)">
      <h2 style="margin:0 0 10px">კონტაქტი</h2>
      <p style="color:var(--muted);margin:0 0 16px">დაგვიკავშირდით კითხვების, თანამშრომლობის ან წევრობის საკითხებზე.</p>

      <div style="display:grid;gap:10px;line-height:1.6">
        <div><b>ოფისი:</b> ჟიულ შარტავას 35-37, თბილისი</div>
        <div><b>ტელეფონი:</b> <a href="tel:+995591037047">+995 591 037 047</a></div>
        <div><b>ელ-ფოსტა:</b> <a href="mailto:info@spg.ge">info@spg.ge</a></div>
        <div><b>სამუშაო დრო:</b> ორშ–პარ, 10:00–18:00</div>
      </div>

      <div style="margin-top:14px;padding:12px;border-radius:12px;background:#eff6ff;border:1px solid #bfdbfe;color:#1e40af;font-size:14px">
        სწრაფი რჩევა: თქვენი შეტყობინება პირდაპირ გადაიგზავნება ადმინისტრაციის პანელში.
      </div>
    </div>

    <div style="background:#fff;border:1px solid var(--line);border-radius:18px;padding:20px;box-shadow:0 14px 30px rgba(15,23,42,.06)">
      <h3 style="margin:0 0 10px">მოგვწერეთ</h3>

      <?php if($success): ?>
        <div style="color:#166534;background:#dcfce7;border:1px solid #86efac;border-radius:10px;padding:10px;margin-bottom:12px">თქვენი შეტყობინება წარმატებით გაიგზავნა.</div>
      <?php endif; ?>
      <?php if($errors): ?>
        <div style="color:#991b1b;background:#fee2e2;border:1px solid #fca5a5;border-radius:10px;padding:10px;margin-bottom:12px">
          <?php foreach($errors as $e) echo '<div>'.h($e).'</div>'; ?>
        </div>
      <?php endif; ?>

      <form method="post" style="display:grid;gap:12px">
        <input type="hidden" name="_csrf" value="<?=h(csrf_token())?>">

        <div>
          <label>სახელი *</label><br>
          <input name="name" value="<?=h($data['name'])?>" required style="width:100%;padding:12px;border-radius:12px;border:1px solid var(--line)">
        </div>
        <div>
          <label>ელ-ფოსტა *</label><br>
          <input type="email" name="email" value="<?=h($data['email'])?>" required style="width:100%;padding:12px;border-radius:12px;border:1px solid var(--line)">
        </div>
        <div>
          <label>ტელეფონი</label><br>
          <input name="phone" value="<?=h($data['phone'])?>" style="width:100%;padding:12px;border-radius:12px;border:1px solid var(--line)">
        </div>
        <div>
          <label>შეტყობინება *</label><br>
          <textarea name="message" required style="width:100%;min-height:140px;padding:12px;border-radius:12px;border:1px solid var(--line)"><?=h($data['message'])?></textarea>
        </div>
        <button type="submit" style="padding:12px 14px;border-radius:12px;border:0;background:#2563eb;color:#fff;font-weight:800;cursor:pointer">გაგზავნა</button>
      </form>
    </div>
  </div>
</section>
<?php include __DIR__ . '/footer.php'; ?>
