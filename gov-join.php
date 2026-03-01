<?php
declare(strict_types=1);
require __DIR__ . '/inc/bootstrap.php';

$pageTitle = 'საქართველოს სტუდენტური პარლამენტი და მთავრობა — მთავრობაში დამატება';
$metaDescription = 'სტუდენტური მთავრობის წევრის დამატების გვერდი — ატვირთეთ ფოტო, სახელი, გვარი და როლი.';
$metaKeywords = 'სტუდენტური მთავრობა, წევრის დამატება, ახალგაზრდობა, ახალგაზრდები, ტრენინგი, შეხვედრა, ბანაკი';

ensure_people_profiles_table();

$ok = '';
$errors = [];
$data = [
  'first_name' => '',
  'last_name' => '',
  'role_title' => '',
  'sort_order' => 500,
  'image_path' => '',
  'hp' => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  csrf_verify();

  foreach ($data as $k => $_) {
    $data[$k] = trim((string)($_POST[$k] ?? $data[$k]));
  }

  // Honeypot anti-spam: must stay empty
  if ($data['hp'] !== '') {
    $errors[] = 'Spam request blocked.';
  }

  if ($data['first_name'] === '' || $data['last_name'] === '') {
    $errors[] = 'სახელი და გვარი სავალდებულოა.';
  }

  if (mb_strlen($data['first_name']) > 120 || mb_strlen($data['last_name']) > 120) {
    $errors[] = 'სახელი/გვარი ძალიან გრძელია.';
  }

  if (mb_strlen($data['role_title']) > 180) {
    $errors[] = 'როლი ძალიან გრძელია.';
  }

  $sortOrder = is_numeric($data['sort_order']) ? (int)$data['sort_order'] : 500;

  $imagePath = $data['image_path'];
  try {
    $uploaded = handle_image_upload('image_file', 'assets/people/uploads');
    if ($uploaded !== '') {
      $imagePath = $uploaded;
    }
  } catch (Throwable $e) {
    $errors[] = $e->getMessage();
  }

  if ($imagePath === '') {
    $errors[] = 'ფოტო აუცილებელია (ატვირთვა ან ბმული).';
  }

  if (!$errors) {
    try {
      $stmt = db()->prepare('INSERT INTO people_profiles (page_key, first_name, last_name, role_title, image_path, sort_order, created_at) VALUES (?, ?, ?, ?, ?, ?, ?)');
      $stmt->execute([
        'gov',
        $data['first_name'],
        $data['last_name'],
        $data['role_title'],
        $imagePath,
        $sortOrder,
        date('Y-m-d H:i:s'),
      ]);
      $ok = 'თქვენი პროფილი წარმატებით დაემატა სტუდენტური მთავრობის გვერდს.';
      $data = ['first_name'=>'','last_name'=>'','role_title'=>'','sort_order'=>500,'image_path'=>'','hp'=>''];
    } catch (Throwable $e) {
      $errors[] = 'პროფილის დამატება ვერ მოხერხდა. სცადეთ მოგვიანებით.';
    }
  }
}

include __DIR__ . '/header.php';
?>
<section class="section">
  <div class="container" style="max-width:860px;padding:36px 0 58px;display:grid;gap:14px">
    <div style="background:#fff;border:1px solid var(--line);border-radius:18px;padding:20px">
      <h1 style="margin:0 0 8px">სტუდენტური მთავრობის წევრის დამატება</h1>
      <p style="margin:0;color:var(--muted)">ამ გვერდზე ნებისმიერ წევრს შეუძლია დაამატოს საკუთარი თავი (ფოტო, სახელი, გვარი და როლი). წაშლის ფუნქცია აქ არ არის.</p>
      <div style="margin-top:12px"><a class="btn" href="<?=h(url('gov'))?>">← მთავრობის გვერდზე დაბრუნება</a></div>
    </div>

    <?php if($ok): ?>
      <div class="ok"><?=h($ok)?></div>
    <?php endif; ?>

    <?php if($errors): ?>
      <div class="err">
        <?php foreach($errors as $e): ?>
          <div><?=h((string)$e)?></div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

    <form method="post" enctype="multipart/form-data" style="background:#fff;border:1px solid var(--line);border-radius:18px;padding:20px;display:grid;gap:12px">
      <input type="hidden" name="_csrf" value="<?=h(csrf_token())?>">
      <input type="text" name="hp" value="" autocomplete="off" tabindex="-1" style="position:absolute;left:-9999px;opacity:0" aria-hidden="true">

      <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px">
        <div>
          <label>სახელი *</label>
          <input name="first_name" required maxlength="120" value="<?=h((string)$data['first_name'])?>">
        </div>
        <div>
          <label>გვარი *</label>
          <input name="last_name" required maxlength="120" value="<?=h((string)$data['last_name'])?>">
        </div>
      </div>

      <div>
        <label>როლი (მაგ: კომიტეტის წევრი)</label>
        <input name="role_title" maxlength="180" value="<?=h((string)$data['role_title'])?>">
      </div>

      <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px">
        <div>
          <label>ფოტო ატვირთვა *</label>
          <input type="file" name="image_file" accept="image/*">
        </div>
        <div>
          <label>ან ფოტო ბმული/მისამართი *</label>
          <input name="image_path" placeholder="assets/people/uploads/.. ან https://..." value="<?=h((string)$data['image_path'])?>">
        </div>
      </div>

      <div>
        <label>რიგითობა (Sort order)</label>
        <input type="number" name="sort_order" value="<?=h((string)$data['sort_order'])?>">
      </div>

      <button class="btn primary" type="submit">დამატება</button>
    </form>
  </div>
</section>
<?php include __DIR__ . '/footer.php'; ?>
