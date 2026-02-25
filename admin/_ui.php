<?php
declare(strict_types=1);

function admin_head($title) {
  ?>
  <head>
    <meta charset="utf-8">
    <title><?=h($title)?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" type="image/png" href="<?=h(url('spg_logo2.png'))?>">
    <meta name="theme-color" content="#2563eb">
    <link rel="stylesheet" href="<?=h(url('admin/assets_admin.css'))?>">
  </head>
  <?php
}

function admin_topbar($title, $links = []) {
  static $rendered = false;
  if ($rendered) {
    return;
  }
  $rendered = true;
  ?>
  <div class="admin-top" role="navigation" aria-label="Admin quick menu">
    <div class="admin-top__head">
      <div class="admin-brand-mini">
        <img src="<?=h(url('spg_logo2.png'))?>" alt="SPG" />
        <h2 class="admin-title"><?=h($title)?></h2>
      </div>
    </div>

    <?php if (!empty($links)): ?>
      <div class="admin-links" aria-label="Admin pages">
        <?php foreach ($links as $item): ?>
          <a class="admin-link" href="<?=h((string)$item['href'])?>"><?=h((string)$item['label'])?></a>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
  <?php
}
