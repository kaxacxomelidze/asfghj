<?php
$pageTitle = 'SPG Portal — ხედვა';
require __DIR__ . '/inc/bootstrap.php';
require __DIR__ . '/inc/people_section.php';
include __DIR__ . '/header.php';

$people = [];
?>
<section class="section">
  <div class="container" style="max-width:1080px;padding:40px 0 54px;display:grid;gap:16px;">
    <div style="background:#fff;border:1px solid var(--line);border-radius:20px;padding:22px;box-shadow:0 16px 34px rgba(15,23,42,.07)">
      <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:12px;flex-wrap:wrap">
        <div>
          <h2 style="margin:0 0 8px;font-size:clamp(24px,3vw,34px)">ხედვა • ღირებულებები</h2>
          <p style="margin:0;color:var(--muted)">ორგანიზაციის სამომავლო მიმართულება და ძირითადი პრინციპები.</p>
        </div>
        <span style="padding:6px 12px;border-radius:999px;background:#eff6ff;border:1px solid #bfdbfe;color:#1e40af;font-weight:700;font-size:13px">SPG Vision</span>
      </div>

      <div style="margin-top:16px;display:grid;gap:10px;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));">
        <div style="padding:14px;border:1px solid var(--line);border-radius:14px;background:#f8fafc">
          <h3 style="margin:0 0 6px;font-size:16px">ინკლუზიურობა</h3>
          <p style="margin:0;color:var(--muted)">ვქმნით მრავალფეროვან და ტოლერანტულ გარემოს, სადაც ყველა ხმა თანაბრად ფასობს.</p>
        </div>
        <div style="padding:14px;border:1px solid var(--line);border-radius:14px;background:#f8fafc">
          <h3 style="margin:0 0 6px;font-size:16px">გაძლიერება</h3>
          <p style="margin:0;color:var(--muted)">ახალგაზრდებს ვაძლევთ ცოდნის გაღრმავებისა და პრაქტიკული უნარების განვითარებას.</p>
        </div>
        <div style="padding:14px;border:1px solid var(--line);border-radius:14px;background:#f8fafc">
          <h3 style="margin:0 0 6px;font-size:16px">კოლაბორაცია</h3>
          <p style="margin:0;color:var(--muted)">გვჯერა, რომ გუნდური მუშაობა და პარტნიორობა წარმატების მიღწევის მთავარი გზაა.</p>
        </div>
      </div>
    </div>

    <div style="background:#fff;border:1px solid var(--line);border-radius:20px;overflow:hidden;box-shadow:0 16px 34px rgba(15,23,42,.07)">
      <img src="<?=h(url('e42c4f55fc66b25ec25290da10201f08.jpg'))?>" alt="SPG ხედვა" style="width:100%;display:block;max-height:560px;object-fit:cover">
    </div>
  </div>
</section>
<?php render_people_section('Team', $people); ?>
<?php include __DIR__ . '/footer.php'; ?>
