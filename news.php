<?php
require __DIR__ . '/inc/bootstrap.php';
$pageTitle = 'საქართველოს სტუდენტური პარლამენტი და მთავრობა — სიახლეები';
include __DIR__ . '/header.php';

$loadError = false;
try {
  $posts = get_news_posts(200);
} catch (Throwable $e) {
  $posts = [];
  $loadError = true;
}
$featured = $posts[0] ?? null;
$rest = array_slice($posts, 1);
?>
<section class="section">
  <div class="container" style="padding:36px 0 54px">
    <div style="display:flex;justify-content:space-between;gap:14px;align-items:flex-end;flex-wrap:wrap;margin-bottom:18px">
      <div>
        <h1 style="font-size:clamp(28px,4vw,40px);line-height:1.15;margin:0">საქართველოს სტუდენტური პარლამენტი და მთავრობა — სიახლეები</h1>
        <p style="margin-top:8px;color:var(--muted)">ორგანიზაციის ყველა განცხადება, ღონისძიება და განახლება ერთ სივრცეში.</p>
      </div>
      <a href="<?=h(url('index.php#home'))?>" class="btn" style="padding:10px 14px">← მთავარ გვერდზე დაბრუნება</a>
    </div>

    <?php if ($loadError): ?>
      <div style="background:#fff;border:1px solid #fecaca;border-radius:18px;padding:28px;text-align:center">
        <h3 style="margin-bottom:8px;color:#b91c1c">სიახლეების ჩატვირთვა ვერ მოხერხდა</h3>
        <p style="color:#7f1d1d">დროებით ტექნიკური ხარვეზია. სცადეთ მოგვიანებით.</p>
      </div>
    <?php elseif (!$posts): ?>
      <div style="background:#fff;border:1px solid var(--line);border-radius:18px;padding:28px;text-align:center">
        <h3 style="margin-bottom:8px">სიახლეები ჯერ არ არის დამატებული</h3>
        <p style="color:var(--muted)">გთხოვთ სცადოთ მოგვიანებით.</p>
      </div>
    <?php else: ?>
      <?php if ($featured): ?>
        <article style="display:grid;grid-template-columns:minmax(320px,1.1fr) 1fr;gap:0;background:#fff;border:1px solid var(--line);border-radius:22px;overflow:hidden;box-shadow:0 20px 34px rgba(15,23,42,.08);margin-bottom:24px">
          <a href="<?=h(url('news-single?id=' . (int)$featured['id']))?>" style="display:block;background:#e2e8f0;min-height:280px">
            <img src="<?=h($featured['img'])?>" alt="<?=h($featured['title'])?>" style="width:100%;height:100%;object-fit:cover;display:block">
          </a>
          <div style="padding:24px 24px 20px">
            <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;margin-bottom:10px">
              <span class="tag"><?=h($featured['cat'])?></span>
              <span style="color:var(--muted)">• <?=h($featured['date'])?></span>
              <span style="margin-left:auto;background:#eff6ff;color:#1d4ed8;border:1px solid #bfdbfe;padding:4px 10px;border-radius:999px;font-size:12px;font-weight:700">მთავარი სიახლე</span>
            </div>
            <h2 style="margin:0 0 10px;font-size:clamp(22px,3vw,30px);line-height:1.2"><?=h($featured['title'])?></h2>
            <p style="color:var(--muted);margin-bottom:16px"><?=h($featured['text'])?></p>
            <a href="<?=h(url('news-single?id=' . (int)$featured['id']))?>" class="btn primary">სრულად ნახვა</a>
          </div>
        </article>
      <?php endif; ?>

      <?php if ($rest): ?>
        <div class="newsGrid" style="grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:16px;display:grid">
          <?php foreach($rest as $p): ?>
            <a class="newsCard" href="<?=h(url('news-single?id=' . (int)$p['id']))?>" style="display:flex;flex-direction:column;background:#fff;border:1px solid var(--line);border-radius:18px;overflow:hidden;box-shadow:0 10px 24px rgba(15,23,42,.06)">
              <div class="newsImg" style="aspect-ratio:16/10;background:#e2e8f0">
                <img src="<?=h($p['img'])?>" alt="<?=h($p['title'])?>" style="width:100%;height:100%;object-fit:cover;display:block">
              </div>
              <div class="newsBody" style="padding:14px 14px 16px;display:grid;gap:8px">
                <div class="newsMeta" style="display:flex;gap:8px;align-items:center;flex-wrap:wrap">
                  <span class="tag"><?=h($p['cat'])?></span>
                  <span class="dot" style="color:#94a3b8">•</span>
                  <span class="date" style="color:var(--muted)"><?=h($p['date'])?></span>
                </div>
                <div class="newsTitle" style="font-weight:800;line-height:1.35"><?=h($p['title'])?></div>
                <div class="newsText" style="color:var(--muted)"><?=h($p['text'])?></div>
              </div>
            </a>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    <?php endif; ?>
  </div>
</section>
<?php include __DIR__ . '/footer.php'; ?>
