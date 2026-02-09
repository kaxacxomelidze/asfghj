<?php
declare(strict_types=1);

function render_people_section(string $heading, array $people): void {
  if (!$people) {
    return;
  }
  ?>
  <section class="section">
    <div class="container" style="max-width:1000px;padding:40px 0">
      <h2 style="margin-bottom:12px"><?=h($heading)?></h2>
      <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:16px">
        <?php foreach ($people as $person): ?>
          <div style="border:1px solid var(--line);border-radius:18px;overflow:hidden;background:#fff">
            <div style="aspect-ratio:4/3;overflow:hidden;background:#f1f5f9">
              <img src="<?=h((string)($person['image'] ?? ''))?>" alt="<?=h((string)($person['name'] ?? ''))?>" style="width:100%;height:100%;object-fit:cover;display:block">
            </div>
            <div style="padding:12px">
              <div style="font-weight:900"><?=h((string)($person['name'] ?? ''))?></div>
              <div style="color:var(--muted);margin-top:6px"><?=h((string)($person['position'] ?? ''))?></div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </section>
  <?php
}
