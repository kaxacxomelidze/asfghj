<?php
$pageTitle = "SPG Portal — ისტორია";
require __DIR__ . "/inc/bootstrap.php";
require __DIR__ . "/inc/people_section.php";
include __DIR__ . "/header.php";
$people = [];
?>
<section class="section">
  <div class="container" style="max-width:1080px;padding:40px 0 56px;display:grid;gap:16px;">
    <div style="background:#fff;border:1px solid var(--line);border-radius:20px;padding:22px;box-shadow:0 16px 34px rgba(15,23,42,.07)">
      <h2 style="margin:0 0 10px">ორგანიზაციის ისტორია</h2>
      <p style="margin:0;color:var(--muted)">დაფუძნებიდან დღემდე — გზის ქრონოლოგია და მიღწევები.</p>
      <div style="margin-top:14px;display:grid;gap:10px">
        <div style="padding:14px;border:1px solid var(--line);border-radius:12px;background:#f8fafc"><b>2016</b> — დაფუძნდა ა(ა)იპ „საქართველოს დამოუკიდებელ ბავშვთა და სტუდენტთა გაერთიანება“.</div>
        <div style="padding:14px;border:1px solid var(--line);border-radius:12px;background:#f8fafc"><b>2016-2018</b> — განხორციელდა პროექტები: „აღმოაჩინე საქართველო“, „ზამთრის ლიდერთა სკოლა“, „განავითარე შენი რეგიონი/ქალაქი“.</div>
        <div style="padding:14px;border:1px solid var(--line);border-radius:12px;background:#f8fafc"><b>2018</b> — შეიქმნა სტუდენტური პარლამენტის მოდელი (15 კომიტეტი, 200-მდე აქტიური წევრი), დაიწყო „თოვლის ქალაქი — ბაკურიანი“.</div>
        <div style="padding:14px;border:1px solid var(--line);border-radius:12px;background:#f8fafc"><b>2024</b> — რებრენდინგის შედეგად ორგანიზაციას ეწოდა „საქართველოს სტუდენტური პარლამენტი და მთავრობა“.</div>
      </div>
    </div>

    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:10px">
      <div style="background:#fff;border:1px solid var(--line);border-radius:14px;padding:14px"><div style="color:var(--muted);font-size:13px">პროგრამები</div><div style="font-size:26px;font-weight:900">5+</div></div>
      <div style="background:#fff;border:1px solid var(--line);border-radius:14px;padding:14px"><div style="color:var(--muted);font-size:13px">საერთაშორისო ღონისძიება</div><div style="font-size:26px;font-weight:900">3</div></div>
      <div style="background:#fff;border:1px solid var(--line);border-radius:14px;padding:14px"><div style="color:var(--muted);font-size:13px">პროექტი</div><div style="font-size:26px;font-weight:900">30+</div></div>
      <div style="background:#fff;border:1px solid var(--line);border-radius:14px;padding:14px"><div style="color:var(--muted);font-size:13px">ბენეფიციარი</div><div style="font-size:26px;font-weight:900">47500+</div></div>
    </div>
  </div>
</section>
<?php render_people_section('Team', $people); ?>
<?php include __DIR__ . "/footer.php"; ?>
