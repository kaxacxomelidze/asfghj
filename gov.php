<?php
$pageTitle = "საქართველოს სტუდენტური პარლამენტი და მთავრობა — სტუდენტური მთავრობა";
require __DIR__ . '/inc/bootstrap.php';
require __DIR__ . '/inc/people_section.php';
include __DIR__ . '/header.php';

$people = get_people_by_page('gov');
?>
<section class="section">
  <div class="container" style="max-width:1000px;padding:40px 0">
    <h2 style="margin-bottom:12px">სტუდენტური მთავრობა</h2>
    <p style="line-height:1.9">გვერდზე აისახება სტუდენტური მთავრობის წევრები.</p>
  </div>
</section>
<?php render_people_section('სტუდენტური მთავრობა', $people); ?>
<?php include __DIR__ . '/footer.php'; ?>
