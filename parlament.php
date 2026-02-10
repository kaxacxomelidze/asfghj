<?php
$pageTitle = "SPG Portal — სტუდენტური პარლამენტი";
require __DIR__ . '/inc/bootstrap.php';
require __DIR__ . '/inc/people_section.php';
include __DIR__ . '/header.php';

$people = get_people_by_page('parlament');
?>
<section class="section">
  <div class="container" style="max-width:1000px;padding:40px 0">
    <h2 style="margin-bottom:12px">სტუდენტური პარლამენტი</h2>
    <p style="line-height:1.9">გვერდზე აისახება სტუდენტური პარლამენტის წევრები.</p>
  </div>
</section>
<?php render_people_section('სტუდენტური პარლამენტი', $people); ?>
<?php include __DIR__ . '/footer.php'; ?>
