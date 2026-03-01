<?php
require __DIR__ . '/inc/bootstrap.php';
$pageTitle = 'საქართველოს სტუდენტური პარლამენტი და მთავრობა — გაწევრიანების განაცხადი';
$metaDescription = 'შეავსეთ გაწევრიანების განაცხადი და მოგვწერეთ სამოტივაციო წერილი.';
$metaKeywords = 'გაწევრიანება, სამოტივაციო წერილი, სტუდენტური პარლამენტი, სტუდენტური მთავრობა';
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
  'additional_info' => '', // stored as motivational letter
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  csrf_verify();
  foreach ($data as $key => $_) {
    $data[$key] = trim((string)($_POST[$key] ?? ''));
  }

  foreach (['first_name','last_name','personal_id','phone','university','faculty','additional_info'] as $req) {
    if ($data[$req] === '') {
      $errors[] = 'გთხოვთ, შეავსოთ ყველა სავალდებულო ველი.';
      break;
    }
  }

  if ($data['email'] !== '' && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'ელ-ფოსტის ფორმატი არასწორია.';
  }

  if ($data['additional_info'] !== '' && mb_strlen($data['additional_info']) < 80) {
    $errors[] = 'სამოტივაციო წერილი უნდა იყოს მინიმუმ 80 სიმბოლო.';
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
      $data['additional_info'],
      date('Y-m-d H:i:s'),
    ]);
    $success = true;
    foreach ($data as $k => $_) $data[$k] = '';
  }
}

include __DIR__ . '/header.php';
?>
<section class="section">
  <div class="container" style="max-width:860px;padding:40px 0 56px;display:grid;gap:14px">
    <div style="background:#fff;border:1px solid var(--line);border-radius:18px;padding:20px">
      <h2 style="margin:0 0 8px">გაწევრიანების განაცხადი</h2>
      <p style="color:var(--muted);margin:0">გთხოვთ, შეავსოთ ფორმა და მოგვწეროთ <b>სამოტივაციო წერილი</b>. განაცხადი გადმოგვეცემა ადმინ პანელში.</p>
    </div>

    <?php if($success): ?>
      <div class="ok">განაცხადი წარმატებით გაიგზავნა. მადლობა ინტერესისთვის!</div>
    <?php endif; ?>

    <?php if($errors): ?>
      <div class="err">
        <?php foreach($errors as $e): ?>
          <div><?= h($e) ?></div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

    <form method="post" style="background:#fff;border:1px solid var(--line);border-radius:18px;padding:20px;display:grid;gap:12px">
      <input type="hidden" name="_csrf" value="<?=h(csrf_token())?>">

      <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
        <div>
          <label>სახელი *</label>
          <input name="first_name" value="<?=h($data['first_name'])?>" required maxlength="120" style="width:100%;padding:12px;border-radius:12px;border:1px solid var(--line)">
        </div>
        <div>
          <label>გვარი *</label>
          <input name="last_name" value="<?=h($data['last_name'])?>" required maxlength="120" style="width:100%;padding:12px;border-radius:12px;border:1px solid var(--line)">
        </div>
      </div>

      <div>
        <label>პირადი ნომერი *</label>
        <input name="personal_id" value="<?=h($data['personal_id'])?>" required maxlength="30" style="width:100%;padding:12px;border-radius:12px;border:1px solid var(--line)">
      </div>

      <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
        <div>
          <label>ტელეფონი *</label>
          <input name="phone" value="<?=h($data['phone'])?>" required maxlength="50" style="width:100%;padding:12px;border-radius:12px;border:1px solid var(--line)">
        </div>
        <div>
          <label>ელ-ფოსტა</label>
          <input type="email" name="email" value="<?=h($data['email'])?>" style="width:100%;padding:12px;border-radius:12px;border:1px solid var(--line)">
        </div>
      </div>

      <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
        <div>
          <label>უნივერსიტეტი *</label>
          <input name="university" value="<?=h($data['university'])?>" required maxlength="190" style="width:100%;padding:12px;border-radius:12px;border:1px solid var(--line)">
        </div>
        <div>
          <label>ფაკულტეტი *</label>
          <input name="faculty" value="<?=h($data['faculty'])?>" required maxlength="190" style="width:100%;padding:12px;border-radius:12px;border:1px solid var(--line)">
        </div>
      </div>

      <div>
        <label>სამოტივაციო წერილი * (მინ. 80 სიმბოლო)</label>
        <textarea name="additional_info" required minlength="80" style="width:100%;min-height:170px;padding:12px;border-radius:12px;border:1px solid var(--line)" placeholder="მოგვწერეთ რატომ გსურთ გაწევრიანება, რა გამოცდილება გაქვთ და რისი გაკეთება შეგიძლიათ ორგანიზაციაში."><?=h($data['additional_info'])?></textarea>
      </div>

      <button type="submit" class="btn primary" style="justify-self:start">გაგზავნა</button>
    </form>
  </div>
</section>
<?php include __DIR__ . '/footer.php'; ?>
