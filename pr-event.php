<?php
$pageTitle = "საქართველოს სტუდენტური პარლამენტი და მთავრობა — PR & EVENT";
require __DIR__ . '/inc/bootstrap.php';
require __DIR__ . '/inc/people_section.php';
include __DIR__ . '/header.php';

$people = get_people_by_page('pr-event');
?>
<section class="section">
  <div class="container" style="max-width:1000px;padding:40px 0">

  </div>
</section>
<?php render_people_section('PR & EVENT', $people); ?>
<?php include __DIR__ . '/footer.php'; ?>
