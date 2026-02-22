<?php
$pageTitle = 'SPG Portal — Lecturer';
require __DIR__ . '/inc/bootstrap.php';

$lecturerName = trim((string)($_GET['name'] ?? ''));
$students = $lecturerName !== '' ? get_lecturer_students($lecturerName) : [];
include __DIR__ . '/header.php';
?>
<section class="section">
  <div class="container" style="max-width:1080px;padding:36px 0 54px;display:grid;gap:16px">
    <div style="background:#fff;border:1px solid var(--line);border-radius:18px;padding:20px;box-shadow:0 14px 32px rgba(15,23,42,.06)">
      <h2 style="margin:0 0 8px">ლექტორის გვერდი</h2>
      <p style="margin:0;color:var(--muted)">რეგისტრაციისას არჩეული სტუდენტები ავტომატურად ჩნდებიან ამ სიაში.</p>
      <form method="get" style="margin-top:12px;display:grid;grid-template-columns:1fr auto;gap:8px;align-items:end">
        <div>
          <label for="lecturer-name">ლექტორის სახელი</label>
          <input id="lecturer-name" name="name" value="<?=h($lecturerName)?>" placeholder="მაგ: Prof. N. Beridze" style="width:100%;padding:12px;border:1px solid var(--line);border-radius:12px">
        </div>
        <button class="btn primary" type="submit">ნახვა</button>
      </form>
    </div>

    <div style="background:#fff;border:1px solid var(--line);border-radius:18px;padding:20px;box-shadow:0 14px 32px rgba(15,23,42,.06)">
      <h3 style="margin:0 0 10px">სტუდენტების სრული ჩამონათვალი <?= $lecturerName!=='' ? '— '.h($lecturerName) : '' ?></h3>
      <table style="width:100%;border-collapse:collapse">
        <thead>
          <tr>
            <th style="text-align:left;padding:10px;border-bottom:1px solid var(--line)">#</th>
            <th style="text-align:left;padding:10px;border-bottom:1px solid var(--line)">სტუდენტი</th>
            <th style="text-align:left;padding:10px;border-bottom:1px solid var(--line)">ელ-ფოსტა</th>
            <th style="text-align:left;padding:10px;border-bottom:1px solid var(--line)">რეგისტრაცია</th>
          </tr>
        </thead>
        <tbody>
          <?php if(!$students): ?>
            <tr><td colspan="4" style="padding:12px;color:var(--muted)">მონაცემები არ მოიძებნა.</td></tr>
          <?php else: foreach($students as $i => $st): ?>
            <tr>
              <td style="padding:10px;border-bottom:1px solid var(--line)"><?= $i+1 ?></td>
              <td style="padding:10px;border-bottom:1px solid var(--line)"><?=h((string)$st['full_name'])?></td>
              <td style="padding:10px;border-bottom:1px solid var(--line)"><?=h((string)$st['email'])?></td>
              <td style="padding:10px;border-bottom:1px solid var(--line)"><?=h((string)$st['created_at'])?></td>
            </tr>
          <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</section>
<?php include __DIR__ . '/footer.php'; ?>
