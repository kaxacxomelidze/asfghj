<?php
$pageTitle = "საქართველოს სტუდენტური პარლამენტი და მთავრობა — სიახლე";
require __DIR__ . "/inc/bootstrap.php";
include __DIR__ . "/header.php";

$id = (int)($_GET['id'] ?? 0);
$post = $id ? get_one_news($id) : null;
$gallery = $post ? get_news_gallery((int)$post['id']) : [];

// Optional: de-duplicate by path in case DB returns duplicates
if ($gallery) {
  $seen = [];
  $gallery = array_values(array_filter($gallery, function ($g) use (&$seen) {
    $p = trim((string)($g['path'] ?? ''));
    if ($p === '' || isset($seen[$p])) return false;
    $seen[$p] = true;
    return true;
  }));
}

if (!$post) {
  http_response_code(404);
  echo "<div style='padding:40px;max-width:900px;margin:auto'>Not found</div>";
  include __DIR__ . "/footer.php";
  exit;
}
?>

<style>
  .newsWrap{
    max-width: 1040px;
    margin: 0 auto;
  }
  .backLink{
    display:inline-flex;
    align-items:center;
    gap:10px;
    text-decoration:none;
    margin-bottom: 14px;
    opacity:.9;
  }
  .backLink:hover{ opacity: 1; }

  .hero{
    border-radius: 18px;
    overflow:hidden;
    border:1px solid rgba(15,23,42,.12);
    box-shadow: 0 10px 25px rgba(2,6,23,.06);
    background: #fff;
  }
  .hero img{
    width:100%;
    display:block;
    max-height: 520px;
    object-fit: cover;
  }

  .metaRow{
    display:flex;
    flex-wrap: wrap;
    gap:10px;
    align-items:center;
    opacity:.85;
    margin-top: 16px;
  }
  .metaDot{ opacity:.55; }

  .newsTitle{
    margin: 10px 0 10px;
    line-height: 1.2;
    letter-spacing: -0.2px;
  }

  .newsLead{
    font-size: 18px;
    opacity: .92;
    line-height: 1.75;
    margin: 0 0 8px;
  }

  .richText{
    margin-top: 14px;
    line-height: 1.8;
    opacity: .96;
  }

  .galleryBlock{
    margin-top: 22px;
  }
  .galleryTitle{
    margin: 0 0 12px;
    font-size: 18px;
  }

  .galleryGrid{
    display:grid;
    grid-template-columns: repeat(12, 1fr);
    gap: 12px;
  }

  .gItem{
    grid-column: span 6;
    border-radius: 14px;
    overflow: hidden;
    border:1px solid rgba(15,23,42,.12);
    box-shadow: 0 8px 18px rgba(2,6,23,.06);
    background: #fff;
  }

  .gItem a{
    display:block;
    text-decoration:none;
    color: inherit;
  }

  .gItem img{
    width:100%;
    height: 320px;
    object-fit: cover;
    display:block;
    transition: transform .25s ease;
  }

  .gItem:hover img{
    transform: scale(1.02);
  }

  @media (max-width: 900px){
    .gItem{ grid-column: span 12; }
    .gItem img{ height: 260px; }
  }
</style>

<section class="section">
  <div class="container newsWrap">
    <a class="backLink" href="<?= h(url('news.php')) ?>">
      ← ყველა სიახლე
    </a>

    <?php if (!empty($post['img'])): ?>
      <div class="hero">
        <img src="<?= h($post['img']) ?>" alt="<?= h($post['title']) ?>">
      </div>
    <?php endif; ?>

    <div>
      <div class="metaRow">
        <span class="tag"><?= h($post['cat']) ?></span>
        <span class="metaDot">•</span>
        <span><?= h($post['date']) ?></span>
      </div>

      <h1 class="newsTitle"><?= h($post['title']) ?></h1>

      <?php if (trim((string)($post['text'] ?? '')) !== ''): ?>
        <p class="newsLead"><?= h($post['text']) ?></p>
      <?php endif; ?>

      <?php if (trim((string)($post['content'] ?? '')) !== ''): ?>
        <div class="richText">
          <?= nl2br(h($post['content'])) ?>
        </div>
      <?php endif; ?>

      <?php if (!empty($gallery)): ?>
        <div class="galleryBlock">
          <h3 class="galleryTitle">Gallery</h3>

          <div class="galleryGrid">
            <?php foreach ($gallery as $g): ?>
              <?php $path = trim((string)($g['path'] ?? '')); ?>
              <?php if ($path === '') continue; ?>

              <div class="gItem">
                <a href="<?= h($path) ?>" target="_blank" rel="noopener">
                  <img src="<?= h($path) ?>" alt="">
                </a>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
      <?php endif; ?>

    </div>
  </div>
</section>

<?php include __DIR__ . "/footer.php"; ?>