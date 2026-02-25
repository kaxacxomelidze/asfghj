<?php
$pageTitle = "საქართველოს სტუდენტური პარლამენტი და მთავრობა — მისია";
require __DIR__ . "/inc/bootstrap.php";
require __DIR__ . "/inc/people_section.php";
include __DIR__ . "/header.php";
$people = [];
?>
<section class="section">
  <div class="container" style="max-width:1080px;padding:40px 0 56px;display:grid;gap:16px;">
    <div style="background:#fff;border:1px solid var(--line);border-radius:20px;padding:22px;box-shadow:0 16px 34px rgba(15,23,42,.07)">
      <h2 style="margin:0 0 10px">მისია</h2>
      <p style="line-height:1.9;margin:0 0 12px">
        საქართველოს სტუდენტური პარლამენტისა და მთავრობის მისიაა სტუდენტებისთვის სამოქალაქო ჩართულობის ხელშეწყობა,
        ლიდერობის უნარების განვითარება და მართვის პრაქტიკული გამოცდილების მიწოდება, რათა მათ შეძლონ პოზიტიური ცვლილებების განხორციელება.
      </p>
      <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:10px;margin-top:10px">
        <div style="padding:14px;border:1px solid var(--line);border-radius:12px;background:#f8fafc"><b>სამოქალაქო ჩართულობა</b><div style="color:var(--muted);margin-top:6px">სტუდენტების აქტიური მონაწილეობა საზოგადოებრივ პროცესებში.</div></div>
        <div style="padding:14px;border:1px solid var(--line);border-radius:12px;background:#f8fafc"><b>ლიდერობა</b><div style="color:var(--muted);margin-top:6px">ახალგაზრდების ლიდერული პოტენციალის განვითარება.</div></div>
        <div style="padding:14px;border:1px solid var(--line);border-radius:12px;background:#f8fafc"><b>კარგი მმართველობა</b><div style="color:var(--muted);margin-top:6px">გამჭვირვალე და სამართლიან მმართველობით პრინციპებზე დაფუძნება.</div></div>
      </div>
    </div>
  </div>
</section>
<?php render_people_section('Team', $people); ?>
<?php include __DIR__ . "/footer.php"; ?>
