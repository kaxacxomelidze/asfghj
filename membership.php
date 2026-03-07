<?php
require __DIR__ . '/inc/bootstrap.php';

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

$success = (($_GET['submitted'] ?? '') === '1');

$pageTitle = 'საქართველოს სტუდენტური პარლამენტი და მთავრობა — გაწევრიანების განაცხადი';
$metaDescription = 'გაწევრიანების განაცხადი სტუდენტური პარლამენტისა და მთავრობისთვის.';
$metaKeywords = 'გაწევრიანება, განაცხადი, სტუდენტური პარლამენტი, სტუდენტური მთავრობა';

ensure_membership_applications_table();

$directionOptions = [
  'განათლება, მეცნიერება და ახალგაზრდობა',
  'იუსტიცია',
  'შერიგება',
  'შერიგება და სამოქალაქო თანასწორობის საკითხები',
  'გარემოს დაცვა და სოფლის მეურნეობა',
  'თავდაცვა',
  'კულტურა',
  'ფინანსები',
  'სპორტი',
  'ეკონომიკა და მდგრადი განვითარება',
  'საგარეო საქმეები',
  'ოკუპირებული ტერიტორიებიდან დევნილი, შრომა, ჯანმრთელობის და სოციალური დაცვა',
  'შინაგან საქმეთა მიმართულება',
];

$errors = [];
$data = [
  'full_name' => '',
  'phone' => '',
  'email' => '',
  'university_info' => '',
  'age' => '',
  'legal_address' => '',
  'desired_direction' => '',
  'motivation_text' => '',
];

function word_count_ka(string $text): int {
  $text = trim($text);
  if ($text === '') return 0;
  $parts = preg_split('/\s+/u', $text);
  return is_array($parts) ? count(array_filter($parts, fn($x) => $x !== '')) : 0;
}

/**
 * Always redirect to a clean SITE-ROOT URL, never a filesystem path.
 * Adjust these two if your public URL differs:
 */
$FORM_PATH = '/membership';                 // URL where this page is reachable
$SUCCESS_URL = $FORM_PATH . '?submitted=1'; // success redirect

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  csrf_verify();

  foreach ($data as $key => $_) {
    $data[$key] = trim((string)($_POST[$key] ?? ''));
  }

  $requiredLabels = [
    'full_name' => 'სახელი გვარი',
    'phone' => 'ტელეფონის ნომერი',
    'email' => 'ელ. ფოსტის მისამართი',
    'university_info' => 'უნივერსიტეტი, ფაკულტეტი, კურსი',
    'age' => 'ასაკი',
    'legal_address' => 'იურიდიული მისამართი',
    'desired_direction' => 'სასურველი მიმართულება',
    'motivation_text' => 'მოტივაცია',
  ];

  $missing = [];
  foreach ($requiredLabels as $key => $label) {
    if ($data[$key] === '') {
      $missing[] = $label;
    }
  }
  if ($missing) {
    $errors[] = 'გთხოვთ, შეავსოთ სავალდებულო ველები: ' . implode(', ', $missing) . '.';
  }

  if ($data['email'] !== '' && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'ელ. ფოსტის მისამართი არასწორია.';
  }

  if ($data['desired_direction'] === '' || !in_array($data['desired_direction'], $directionOptions, true)) {
    $errors[] = 'აირჩიეთ სასურველი მიმართულება ჩამონათვალიდან.';
  }

  $data['motivation_text'] = preg_replace('/\r\n?/', "\n", $data['motivation_text']) ?? $data['motivation_text'];
  $motivationWords = word_count_ka($data['motivation_text']);
  if ($motivationWords > 50) {
    $errors[] = 'მოტივაცია უნდა იყოს მაქსიმუმ 50 სიტყვა.';
  }

  if (!$errors) {
    try {
      // Preferred schema with detailed fields
      $stmt = db()->prepare('
        INSERT INTO membership_applications
          (first_name, last_name, personal_id, phone, university, faculty, email, additional_info,
           full_name, university_info, age, legal_address, desired_direction, motivation_text, created_at)
        VALUES
          (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
      ');
      $stmt->execute([
        $data['full_name'],
        '-',
        '-',
        $data['phone'],
        $data['university_info'],
        '-',
        $data['email'],
        $data['motivation_text'],
        $data['full_name'],
        $data['university_info'],
        $data['age'],
        $data['legal_address'],
        $data['desired_direction'],
        $data['motivation_text'],
        date('Y-m-d H:i:s'),
      ]);
    } catch (Throwable $e) {
      // Backward compatibility for older schema
      $stmt = db()->prepare('
        INSERT INTO membership_applications
          (first_name, last_name, personal_id, phone, university, faculty, email, additional_info, created_at)
        VALUES
          (?, ?, ?, ?, ?, ?, ?, ?, ?)
      ');
      $stmt->execute([
        $data['full_name'],
        $data['age'],
        $data['legal_address'],
        $data['phone'],
        $data['university_info'],
        $data['desired_direction'],
        $data['email'],
        $data['motivation_text'],
        date('Y-m-d H:i:s'),
      ]);
    }

    // ✅ IMPORTANT FIX: redirect to clean root URL (not relative, not php filename)
    header('Location: ' . $SUCCESS_URL, true, 303);
    exit;
  }
}

include __DIR__ . '/header.php';
?>
<section class="section">
  <div class="container" style="max-width:900px;padding:40px 0 56px;display:grid;gap:14px">
    <div style="background:#fff;border:1px solid var(--line);border-radius:18px;padding:20px">
    </div>

    <div style="background:linear-gradient(180deg,#ffffff,#f8fbff);border:1px solid var(--line);border-radius:18px;padding:20px;display:grid;gap:10px;line-height:1.65">
      <h3 style="margin:0;color:#0f172a">საქართველოს სტუდენტური პარლამენტი და მთავრობა აცხადებს წევრების მიღებას!</h3>
      <p style="margin:0">🌟 შეუერთდი ლიდერთა მომავალ თაობას! 🌟</p>
      <p style="margin:0">📢 გაქვთ რეალური ცვლილებების შექმნაში მონაწილეობის სურვილი? მზად ხართ, გააძლიეროთ ქართველი ახალგაზრდების ხმა და ხელი შეუწყოთ პოზიტიურ ცვლილებებს?</p>
      <p style="margin:0">🔹 ეს შენი შანსია!</p>
      <p style="margin:0">🔰 საქართველოს სტუდენტური პარლამენტი და მთავრობა აცხადებს წევრების მიღებას!</p>
      <p style="margin:0">➡️ ჩვენი ორგანიზაციის ინიციატივები უფრო მეტია, ვიდრე უბრალოდ აქტივობები — ეს არის შესაძლებლობა, მიიღოთ პრაქტიკული გამოცდილება ლიდერობაში, ჩაერთოთ მნიშვნელოვან პროექტებში და წარმოადგინოთ სტუდენტების ინტერესები მთელი საქართველოს მასშტაბით.</p>
      <div>
        <p style="margin:0 0 6px"><strong>🚀 რას მიიღებთ:</strong></p>
        <ul style="margin:0;padding-left:20px;display:grid;gap:6px">
          <li>ლიდერობის უნარები და პროექტების მართვის გამოცდილება</li>
          <li>ბანაკებში, ტრენინგებში, ექსკურსიებში და ერთობლივ პროექტებში მონაწილეობის შესაძლებლობა</li>
          <li>სრულად დაფინანსებული მონაწილეობა გაცვლით პროექტებში</li>
          <li>აქტიური მონაწილეობა სამოქალაქო და სოციალურ ინიციატივებში</li>
          <li>ძლიერი პლატფორმა იდეების გასახმოვანებლად და საქართველოს მომავალზე გავლენის მოსახდენად</li>
        </ul>
      </div>
      <p style="margin:0">💡 თუ ხარ განვითარებისადმი და საზოგადოებრივი აქტივობებისადმი ერთგული სტუდენტი, ჩვენ გვსურს, რომ იყო ჩვენთან ერთად! არ გაუშვა ხელიდან ეს შანსი.</p>
      <p style="margin:0">✨ ერთად გავაგრძელოთ სწავლა და განვითარება ✨</p>
    </div>

    <?php if ($success): ?>
      <div class="ok">განაცხადი წარმატებით გაიგზავნა.</div>
    <?php endif; ?>

    <?php if ($errors): ?>
      <div class="err">
        <?php foreach ($errors as $e): ?>
          <div><?= h($e) ?></div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

    <!-- ✅ IMPORTANT FIX: force POST to the clean URL -->
    <form method="post" action="<?= h($FORM_PATH) ?>" style="background:#fff;border:1px solid var(--line);border-radius:18px;padding:20px;display:grid;gap:12px">
      <input type="hidden" name="_csrf" value="<?= h(csrf_token()) ?>">

      <div>
        <label>სახელი გვარი *</label>
        <input name="full_name" required maxlength="190" value="<?= h($data['full_name']) ?>" style="width:100%;padding:12px;border-radius:12px;border:1px solid var(--line)">
      </div>

      <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
        <div>
          <label>ტელეფონის ნომერი *</label>
          <input name="phone" required maxlength="50" value="<?= h($data['phone']) ?>" style="width:100%;padding:12px;border-radius:12px;border:1px solid var(--line)">
        </div>
        <div>
          <label>ელ. ფოსტის მისამართი *</label>
          <input type="email" name="email" required maxlength="190" value="<?= h($data['email']) ?>" style="width:100%;padding:12px;border-radius:12px;border:1px solid var(--line)">
        </div>
      </div>

      <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
        <div>
          <label>უნივერსიტეტი, ფაკულტეტი, კურსი. *</label>
          <input name="university_info" required maxlength="255" value="<?= h($data['university_info']) ?>" style="width:100%;padding:12px;border-radius:12px;border:1px solid var(--line)">
        </div>
        <div>
          <label>ასაკი *</label>
          <input name="age" required maxlength="20" value="<?= h($data['age']) ?>" style="width:100%;padding:12px;border-radius:12px;border:1px solid var(--line)">
        </div>
      </div>

      <div>
        <label>იურიდიული მისამართი *</label>
        <input name="legal_address" required maxlength="255" value="<?= h($data['legal_address']) ?>" style="width:100%;padding:12px;border-radius:12px;border:1px solid var(--line)">
      </div>

      <div>
        <label>აირჩიე სასურველი მიმართულება *</label>
        <select name="desired_direction" required style="width:100%;padding:12px;border-radius:12px;border:1px solid var(--line);background:#fff">
          <option value="">— აირჩიეთ —</option>
          <?php foreach ($directionOptions as $opt): ?>
            <option value="<?= h($opt) ?>" <?= $data['desired_direction'] === $opt ? 'selected' : '' ?>>
              <?= h($opt) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div>
        <label>რა არის თქვენი მოტივაცია? (50 სიტყვა მაქსიმუმ) *</label>
        <textarea name="motivation_text" required style="width:100%;min-height:140px;padding:12px;border-radius:12px;border:1px solid var(--line)" placeholder="მოკლედ აღწერეთ თქვენი მოტივაცია (მაქსიმუმ 50 სიტყვა)"><?= h($data['motivation_text']) ?></textarea>
      </div>

      <button type="submit" class="btn primary" style="justify-self:start">გაგზავნა</button>
    </form>
  </div>
</section>
<?php include __DIR__ . '/footer.php'; ?>