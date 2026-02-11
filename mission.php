<?php
$pageTitle = "SPG Portal — მისია";
require __DIR__ . "/inc/bootstrap.php";
require __DIR__ . "/inc/people_section.php";
include __DIR__ . "/header.php";

$people = [];
?>
<section class="section">
  <div class="container" style="max-width:1000px;padding:40px 0">
    <h2 style="margin-bottom:12px">მისია</h2>
    <p style="line-height:1.9">
      საქართველოს სტუდენტური პარლამენტისა და მთავრობის მისიას წარმოადგენს სტუდენტებისათვის სამოქალაქო ჩართულობის ხელშეწყობა, ლიდერობის უნარების განვითარება და მართვის პრაქტიკული გამოცდილების მიწოდება, რაც მათ საშუალებას აძლევს, მონაწილეობა მიიღონ პოზიტიური ცვლილებების განხორციელებაში,  კარგი მმართველობის პრინციპებისა და სოციალური სამართლიანობის დანერგვაში.
    </p>
  </div>
</section>
<?php render_people_section('Team', $people); ?>
<?php include __DIR__ . "/footer.php"; ?>
