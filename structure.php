<?php
$pageTitle = "SPG Portal — სტრუქტურა";
require __DIR__ . "/inc/bootstrap.php";
require __DIR__ . "/inc/people_section.php";
include __DIR__ . "/header.php";

$people = [];
?>
<section class="section">
  <div class="container" style="max-width:1000px;padding:40px 0">
    <h2 style="margin-bottom:12px">სტრუქტურა</h2>
    <img src="<?=h(url('spg_logo2.png'))?>" alt="სტრუქტურა" style="width:100%;display:block;border-radius:18px">
  </div>
</section>
<?php render_people_section('Team', $people); ?>
<?php include __DIR__ . "/footer.php"; ?>
