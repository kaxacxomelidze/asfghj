<?php
$pageTitle = "საქართველოს სტუდენტური პარლამენტი და მთავრობა — ხელმძღვანელის მიმართვა";
require __DIR__ . "/inc/bootstrap.php";
require __DIR__ . "/inc/people_section.php";
include __DIR__ . "/header.php";
$people = [];
?>
<section class="section">
  <div class="container" style="max-width:1080px;padding:40px 0 56px;display:grid;gap:16px;">
    <div style="background:#fff;border:1px solid var(--line);border-radius:20px;padding:22px;box-shadow:0 16px 34px rgba(15,23,42,.07)">
      <div style="display:flex;justify-content:space-between;gap:10px;flex-wrap:wrap;align-items:flex-start">
        <div>
          <h2 style="margin:0 0 8px">ხელმძღვანელის მიმართვა</h2>
          <p style="margin:0;color:var(--muted)">ჩვენი ხედვა სტუდენტების გაძლიერებაზე, ლიდერობაზე და ეროვნულ განვითარებაზე.</p>
        </div>
        <span style="padding:6px 12px;border-radius:999px;background:#eff6ff;border:1px solid #bfdbfe;color:#1e40af;font-weight:700;font-size:13px">Leader Message</span>
      </div>
      <div style="margin-top:16px;border-radius:16px;overflow:hidden;border:1px solid var(--line)">
        <img src="<?=h(url('cropped-cropped-IMG_9728.png'))?>" alt="ხელმძღვანელის მიმართვა" style="width:100%;display:block;max-height:420px;object-fit:cover">
      </div>
      <div style="margin-top:14px;line-height:1.9;color:#0f172a;white-space:pre-line">
ძვირფასო მეგობრებო,

მოხარული ვარ, რომ ეწვიეთ საქართველოს სტუდენტური პარლამენტისა და მთავრობის ოფიციალურ ვებგვერდს. ჩვენი ორგანიზაცია წარმოადგენს საქართველოში არსებულ, ახალგაზრდებზე ორიენტირებულ, ერთ-ერთ ყველაზე დიდ პლატფორმას, სადაც თითოეულ მონაწილეს ეძლევა შესაძლებლობა, განავითაროს კრიტიკული უნარები და აქტიურად ჩაერთოს პოზიტიური დღის წესრიგის პროცესების ფორმირებაში.

ჩვენი მიზანია, შევქმნათ ინკლუზიური და ინოვაციური სივრცე, რომელიც ხელს შეუწყობს ახალგაზრდების განათლებას, თანამონაწილეობას და სამოქალაქო პასუხისმგებლობის განვითარებას.

მადლობას გიხდით ნდობისა და მხარდაჭერისთვის. ერთად შეგვიძლია, შევქმნათ ძლიერი სტუდენტური საზოგადოება, რომელიც აქტიურად იმოქმედებს ქვეყნის უკეთესი მომავლისთვის.
      </div>
      <div style="margin-top:12px;padding:12px;border:1px solid var(--line);border-radius:12px;background:#f8fafc">
        <b>დავით კელენჯერიძე</b><br>
        <span style="color:var(--muted)">საქართველოს სტუდენტური პარლამენტი და მთავრობა — ორგანიზაციის ხელმძღვანელი</span>
      </div>
    </div>
  </div>
</section>
<?php render_people_section('Team', $people); ?>
<?php include __DIR__ . "/footer.php"; ?>
