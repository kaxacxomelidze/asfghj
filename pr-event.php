<?php
$pageTitle = "SPG Portal — PR & EVENT";
require __DIR__ . '/inc/bootstrap.php';
require __DIR__ . '/inc/people_section.php';
include __DIR__ . '/header.php';

$people = get_people_by_page('pr-event');
?>
<section class="section">
  <div class="container" style="max-width:1000px;padding:40px 0">
    <h2 style="margin-bottom:12px">PR &amp; EVENT</h2>
    <p style="line-height:1.9">გვერდზე აისახება PR &amp; EVENT გუნდის წევრები.</p>
  </div>
</section>
<?php render_people_section('PR & EVENT', $people); ?>
<?php include __DIR__ . '/footer.php'; ?>
