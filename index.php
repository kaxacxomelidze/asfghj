<?php

require __DIR__ . "/inc/bootstrap.php"; // <-- DB + helpers
$pageTitle = "საქართველოს სტუდენტური პარლამენტი და მთავრობა — სიახლეები";
include __DIR__ . "/header.php";

// ✅ Get posts from DB in SAME shape as your demo array:
// ["id","cat","date","title","text","img"]
$posts = get_news_posts(60);

// Escape
function h_local($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

// Tag colors (same as your file)
function tag_class($cat){
  switch ((string)$cat) {
    case 'განცხადება': return 'tagBlue';
    case 'ღონისძიება': return 'tagCyan';
    case 'ორგანიზაცია': return 'tagSlate';
    case 'ტრენინგი': return 'tagIndigo';
    default: return 'tagSlate';
  }
}

// Safe slice helper
function slice_safe(array $arr, int $offset, int $length): array {
  if ($offset < 0) $offset = 0;
  if ($length <= 0) return [];
  return array_slice($arr, $offset, $length);
}

// Category counts (sidebar)
$catCounts = [];
foreach ($posts as $p) {
  $c = (string)($p['cat'] ?? '');
  if ($c === '') continue;
  $catCounts[$c] = ($catCounts[$c] ?? 0) + 1;
}

// Layout slices (safe even if DB has few posts)
$heroSlides     = slice_safe($posts, 0, 4);
$featuredPosts  = slice_safe($posts, 1, 10);
$editorPicks    = slice_safe($posts, 4, 6);
$lastUpdates    = slice_safe($posts, 10, 12);
$compactList    = slice_safe($posts, 22, 8);
$latestMini     = slice_safe($posts, 1, 5);
?>

<style>
  *, *::before, *::after{ box-sizing:border-box; }
  html, body{ max-width:100%; overflow-x:hidden; }

  .wrap{ width:min(1500px,94%); margin:0 auto; }
  main{ padding: 0 0 76px; }

  section, aside, .panel, .featuredSlider, .heroSlider { min-width:0; }
  img{ max-width:100%; }

  .layout{
    display:grid;
    grid-template-columns: minmax(0, 1.95fr) minmax(0, 1fr);
    gap:18px;
    align-items:start;
  }

  .panel{
    background:#fff;
    border:1px solid var(--line);
    border-radius:22px;
    box-shadow: 0 18px 44px rgba(15,23,42,.06);
    overflow:hidden;
  }
  .pad{ padding:16px; }
  .row{ display:flex; justify-content:space-between; align-items:flex-end; gap:12px; flex-wrap:wrap; }
  .h2{ margin:0; font-size:18px; font-weight:900; letter-spacing:.15px; }
  .sub{ color:var(--muted); font-size:13.5px; }

  /* HERO */
  .heroFullBleed{ width: 100%; margin: 12px 0 18px; overflow: clip; }
  .heroSlider{ position:relative; max-width:1920px; margin:0 auto; padding:0 16px; }
  .heroViewport{ overflow:hidden; border-radius:26px; box-shadow:0 26px 70px rgba(15,23,42,.16); border:1px solid var(--line); }
  .heroTrack{ display:flex; transition: transform .65s ease; will-change: transform; }
  .heroSlide{ min-width:100%; }

  .heroNav{
    position:absolute; top:50%; transform: translateY(-50%);
    z-index:8; width:52px; height:52px; border-radius:16px;
    border:1px solid rgba(255,255,255,.22);
    background: rgba(15,23,42,.38);
    backdrop-filter: blur(10px);
    color:#fff; font-size:20px; font-weight:900;
    cursor:pointer; display:flex; align-items:center; justify-content:center;
    transition: transform .12s ease, background .12s ease;
  }
  .heroNav:hover{ transform: translateY(-50%) scale(1.03); background: rgba(15,23,42,.48); }
  .heroPrev{ left: 18px; }
  .heroNext{ right: 18px; }

  .heroDots{
    position:absolute; left:0; right:0; bottom:18px;
    display:flex; justify-content:center; gap:8px;
    z-index:8;
  }
  .heroDot{
    width:10px; height:10px; border-radius:999px;
    border:1px solid rgba(255,255,255,.35);
    background: rgba(255,255,255,.18);
    cursor:pointer;
    transition: transform .12s ease, background .12s ease;
  }
  .heroDot.active{ background: rgba(255,255,255,.92); transform: scale(1.10); }

  .hero{ position:relative; background:#0b1220; overflow:hidden; }
  .heroMedia{ height: clamp(320px, 72vh, 720px); position:relative; }
  .heroMedia img{ width:100%; height:100%; object-fit:cover; display:block; opacity:.92; transform:scale(1.02); }
  .heroOverlay{
    position:absolute; inset:0;
    background:
      linear-gradient(180deg, rgba(15,23,42,.05) 0%, rgba(15,23,42,.55) 52%, rgba(15,23,42,.94) 100%),
      radial-gradient(900px 420px at 10% 10%, rgba(37,99,235,.40), transparent 60%),
      radial-gradient(900px 420px at 90% 10%, rgba(14,165,233,.26), transparent 60%);
  }
  .heroBody{ position:absolute; left:0; right:0; bottom:0; padding:28px; color:#fff; }
  .heroTop{ display:flex; justify-content:space-between; gap:10px; flex-wrap:wrap; align-items:center; margin-bottom:10px; }
  .heroTitle{ margin:0 0 10px; font-size: clamp(20px, 2.1vw, 36px); font-weight:900; line-height:1.18; max-width:1100px; }
  .heroText{ margin:0 0 16px; max-width:980px; color:rgba(226,232,240,.93); font-size:15px; line-height:1.85; }

  .tag{
    display:inline-flex; align-items:center;
    padding:6px 12px; border-radius:999px;
    font-size:12px; font-weight:900;
    border:1px solid rgba(255,255,255,.18);
    background: rgba(255,255,255,.12);
    backdrop-filter: blur(10px);
  }
  .date{ font-size:12.5px; color: rgba(226,232,240,.88); }
  .heroActions{ display:flex; gap:10px; flex-wrap:wrap; }
  .ghost{
    display:inline-flex; align-items:center; justify-content:center;
    padding:12px 14px; border-radius:16px;
    border:1px solid rgba(255,255,255,.22);
    background: rgba(255,255,255,.10);
    color:#fff; font-weight:900; font-size:13.5px;
    text-decoration:none; transition: all .14s ease;
    white-space:nowrap;
  }
  .ghost:hover{ transform: translateY(-1px); background: rgba(255,255,255,.14); }

  /* TAG PILLS */
  .tagPill{
    display:inline-flex; align-items:center;
    padding:6px 10px;
    border-radius:999px;
    font-size:12px;
    font-weight:900;
    border:1px solid var(--line);
    background:#f8fafc;
  }
  .tagBlue{ border-color:rgba(37,99,235,.18); background:rgba(37,99,235,.08); color:#0b2a7a; }
  .tagCyan{ border-color:rgba(14,165,233,.22); background:rgba(14,165,233,.10); color:#075985; }
  .tagSlate{ border-color:rgba(100,116,139,.20); background:rgba(100,116,139,.08); color:#334155; }
  .tagIndigo{ border-color:rgba(99,102,241,.20); background:rgba(99,102,241,.10); color:#312e81; }

  /* FEATURED */
  .featuredSlider{ position:relative; margin-top:0; overflow:hidden; border-radius:22px; }
  .featuredTrack{
    display:flex; gap:14px;
    overflow-x:auto;
    scroll-snap-type: x mandatory;
    scroll-behavior: smooth;
    padding: 8px 32px 16px;
    -webkit-overflow-scrolling: touch;
    scrollbar-width: none;
  }
  .featuredTrack::-webkit-scrollbar{ display:none; }
  .feat{
    flex: 0 0 clamp(240px, 75vw, 360px);
    max-width:clamp(240px, 75vw, 360px);
    scroll-snap-align: start;
    border:1px solid var(--line);
    border-radius:20px;
    overflow:hidden;
    background:#fff;
    transition: transform .14s ease, box-shadow .14s ease;
    text-decoration:none;
    color:inherit;
    display:flex;
    flex-direction:column;
  }
  .feat:hover{ transform: translateY(-2px); box-shadow: 0 18px 46px rgba(15,23,42,.10); }
  .featImg{ height:150px; background:#f1f5f9; }
  .featImg img{ width:100%; height:100%; object-fit:cover; display:block; }
  .featBody{ padding:12px; display:flex; flex-direction:column; gap:8px; flex:1; }
  .featTitle{ margin:0; font-size:15.5px; font-weight:900; line-height:1.35; }
  .featText{ margin:0; color:var(--muted); font-size:13.7px; line-height:1.65; }
  .meta{
    display:flex; justify-content:space-between; gap:10px; flex-wrap:wrap;
    color:#94a3b8; font-size:12.5px; margin-top:auto;
    padding-top:8px; border-top:1px dashed rgba(148,163,184,.35);
  }

  .slideBtn{
    position:absolute; top:50%; transform: translateY(-50%);
    z-index:10;
    width:46px; height:46px;
    border-radius:14px;
    border:1px solid var(--line);
    background: rgba(255,255,255,.92);
    backdrop-filter: blur(8px);
    font-size:18px; font-weight:900;
    cursor:pointer;
    display:flex; align-items:center; justify-content:center;
    box-shadow: 0 12px 28px rgba(15,23,42,.10);
    transition: transform .12s ease, filter .12s ease;
  }
  .slideBtn:hover{ transform: translateY(-50%) scale(1.03); filter: brightness(.98); }
  .slideBtn:active{ transform: translateY(-50%) scale(.98); }
  .slideBtn.prev{ left:10px; }
  .slideBtn.next{ right:10px; }
  @media (max-width: 980px){ .slideBtn{ display:none; } }

  .featuredFade{ pointer-events:none; position:absolute; inset:0; border-radius: 22px; }
  .featuredFade:before, .featuredFade:after{ content:""; position:absolute; top:0; bottom:0; width:70px; }
  .featuredFade:before{ left:0; background: linear-gradient(90deg, rgba(248,250,252,1), rgba(248,250,252,0)); }
  .featuredFade:after{ right:0; background: linear-gradient(270deg, rgba(248,250,252,1), rgba(248,250,252,0)); }

  /* MAGAZINE */
  .mag{ margin-top:14px; display:grid; gap:12px; }
  .magItem{
    display:grid;
    grid-template-columns: 220px 1fr;
    gap:14px;
    border:1px solid var(--line);
    border-radius:20px;
    overflow:hidden;
    background:#fff;
    text-decoration:none;
    color:inherit;
    transition: transform .14s ease, box-shadow .14s ease;
    min-width:0;
  }
  .magItem:hover{ transform: translateY(-2px); box-shadow: 0 18px 46px rgba(15,23,42,.08); }
  .magImg{ height:100%; min-height:150px; background:#f1f5f9; }
  .magImg img{ width:100%; height:100%; object-fit:cover; display:block; }
  .magBody{ padding:12px; display:flex; flex-direction:column; gap:10px; min-width:0; }
  .magTitle{ margin:0; font-size:16px; font-weight:900; line-height:1.35; }
  .magText{ margin:0; color:var(--muted); font-size:14px; line-height:1.75; }

  /* GRID (2 columns) */
  .grid{
    margin-top:14px;
    display:grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap:14px;
    align-items: stretch;
  }
  .card{ min-width:0; border:1px solid var(--line); border-radius:20px; overflow:hidden; background:#fff; display:flex; flex-direction:column; transition: transform .14s ease, box-shadow .14s ease; }
  .card:hover{ transform: translateY(-2px); box-shadow: 0 18px 46px rgba(15,23,42,.10); }
  .thumb{ height:190px; background:#f1f5f9; }
  .thumb img{ width:100%; height:100%; object-fit:cover; display:block; }
  .body{ padding:14px; display:flex; flex-direction:column; gap:10px; flex:1; min-width:0; }
  .title{ margin:0; font-size:16.5px; font-weight:900; line-height:1.35; }
  .text{ margin:0; color:var(--muted); font-size:14.2px; line-height:1.75; }

  .btnMini{
    display:inline-flex; align-items:center; justify-content:center;
    padding:10px 12px;
    border-radius:14px;
    border:1px solid var(--line);
    background:#fff;
    font-size:13.5px;
    font-weight:900;
    color:var(--text);
    text-decoration:none;
    transition: all .12s ease;
    white-space:nowrap;
  }
  .btnMini:hover{ background:#f1f5f9; transform: translateY(-1px); }

  /* COMPACT */
  .compact{ margin-top:14px; }
  .compact a{
    display:flex;
    justify-content:space-between;
    gap:12px;
    padding:12px 0;
    border-bottom:1px dashed var(--line);
    text-decoration:none;
    color:inherit;
    align-items:flex-start;
    min-width:0;
  }
  .compact a:last-child{ border-bottom:none; }
  .cTitle{ margin:0; font-size:14.5px; font-weight:900; line-height:1.35; }
  .cText{ margin-top:6px; color:var(--muted); font-size:13.6px; line-height:1.6; max-width:680px; }
  .cDate{ color:#94a3b8; font-size:12.5px; white-space:nowrap; }

  /* SIDEBAR */
  .sidebar{ position: sticky; top:110px; }
  .sideTitle{ margin:0 0 10px; font-size:15px; font-weight:900; }
  .cat a{
    display:flex; justify-content:space-between; align-items:center;
    gap:10px; padding:12px 0;
    border-bottom:1px dashed var(--line);
    text-decoration:none; color:inherit;
  }
  .cat a:last-child{ border-bottom:none; }
  .cat small{ display:block; color:var(--muted); margin-top:2px; }
  .count{
    color:#64748b; font-size:12px;
    border:1px solid var(--line); background:#f8fafc;
    padding:4px 10px; border-radius:999px;
    height: fit-content;
    white-space:nowrap;
  }
  .mini{
    display:grid;
    grid-template-columns: 98px 1fr;
    gap:12px;
    padding:12px 0;
    border-bottom:1px dashed var(--line);
    text-decoration:none; color:inherit;
    min-width:0;
  }
  .mini:last-child{ border-bottom:none; }
  .miniThumb{ width:98px; height:64px; border-radius:14px; overflow:hidden; border:1px solid var(--line); background:#f1f5f9; }
  .miniThumb img{ width:100%; height:100%; object-fit:cover; display:block; }
  .miniT{ margin:0 0 6px; font-size:14px; font-weight:900; line-height:1.35; }
  .miniM{ font-size:12px; color:#94a3b8; }

  @media (max-width: 1200px){
    .layout{ grid-template-columns: minmax(0, 1.65fr) minmax(0, .85fr); }
    .heroSlider{ padding:0 12px; }
  }

  @media (max-width: 1024px){
    .layout{ grid-template-columns:1fr; }
    .sidebar{ position: static; top:auto; }
    .heroNav{ display:none; }
    .heroBody{ padding: 18px; }
    .heroText{ font-size:14px; }
    .featuredTrack{ padding: 8px 10px 16px; }
  }

  @media (max-width: 980px){
    .magItem{ grid-template-columns: 1fr; }
    .grid{ grid-template-columns: 1fr; }
  }
</style>

<main>

  <?php if(empty($posts)): ?>
    <div class="wrap" style="padding:22px 0">
      <div class="panel">
        <div class="pad">
          <h2 class="h2">სიახლეები ჯერ არ არის დამატებული</h2>
          <div class="sub">დამატება შესაძლებელია მხოლოდ ადმინისტრატორის პანელიდან.</div>
        </div>
      </div>
    </div>
  <?php else: ?>

  <!-- HERO -->
  <div class="heroFullBleed">
    <div class="heroSlider" id="heroSlider">
      <button class="heroNav heroPrev" type="button" aria-label="წინა">‹</button>

      <div class="heroViewport">
        <div class="heroTrack" id="heroTrack">
          <?php foreach($heroSlides as $p): ?>
            <div class="heroSlide">
              <article class="hero">
                <div class="heroMedia">
                  <img src="<?=h_local($p["img"])?>" alt="<?=h_local($p["title"])?>" />
                  <div class="heroOverlay"></div>
                </div>

                <div class="heroBody">
                  <div class="heroTop">
                    <span class="tag"><?=h_local($p["cat"])?></span>
                    <span class="date"><?=h_local($p["date"])?></span>
                  </div>

                  <h2 class="heroTitle"><?=h_local($p["title"])?></h2>
                  <p class="heroText"><?=h_local($p["text"])?></p>

                  <div class="heroActions">
                    <a class="ghost" href="<?=h(url('news-single?id=' . (int)$p["id"]))?>">დაწვრილებით</a>
                    <a class="ghost" href="<?=h(url('news?cat=' . urlencode($p["cat"])))?>">კატეგორია: <?=h_local($p["cat"])?></a>
                  </div>
                </div>
              </article>
            </div>
          <?php endforeach; ?>
        </div>
      </div>

      <button class="heroNav heroNext" type="button" aria-label="შემდეგი">›</button>
      <div class="heroDots" id="heroDots" aria-label="სლაიდერის მაჩვენებელი"></div>
    </div>
  </div>

  <!-- CONTENT -->
  <div class="wrap">
    <div class="layout">

      <section>

        <!-- FEATURED -->
        <?php if(!empty($featuredPosts)): ?>
        <div class="featuredSlider" id="featuredSlider">
          <button class="slideBtn prev" type="button" aria-label="წინა">‹</button>

          <div class="featuredTrack" id="featuredTrack">
            <?php foreach($featuredPosts as $p): ?>
              <a class="feat" href="<?=h(url('news-single?id=' . (int)$p["id"]))?>">
                <div class="featImg"><img src="<?=h_local($p["img"])?>" alt="<?=h_local($p["title"])?>"></div>
                <div class="featBody">
                  <div class="row" style="align-items:center">
                    <span class="tagPill <?=h_local(tag_class($p["cat"]))?>"><?=h_local($p["cat"])?></span>
                    <span style="color:#94a3b8;font-size:12.5px"><?=h_local($p["date"])?></span>
                  </div>
                  <h3 class="featTitle"><?=h_local($p["title"])?></h3>
                  <p class="featText"><?=h_local($p["text"])?></p>
                  <div class="meta"><span>საქართველოს სტუდენტური პარლამენტი და მთავრობა</span><span>კითხვის დრო: ~1 წთ</span></div>
                </div>
              </a>
            <?php endforeach; ?>
          </div>

          <button class="slideBtn next" type="button" aria-label="შემდეგი">›</button>
          <div class="featuredFade" aria-hidden="true"></div>
        </div>
        <?php endif; ?>

        <!-- MAGAZINE -->
        <?php if(!empty($editorPicks)): ?>
        <div class="panel" style="margin-top:14px">
          <div class="pad">
            <div class="row">
              <div>
                <h2 class="h2">რედაქტორის რჩევა</h2>
                <div class="sub">ჟურნალის სტილი — სწრაფი კითხვა და დეტალები</div>
              </div>
              <a class="btnMini" href="<?=h(url('news'))?>">არქივი</a>
            </div>

            <div class="mag">
              <?php foreach($editorPicks as $p): ?>
                <a class="magItem" href="<?=h(url('news-single?id=' . (int)$p["id"]))?>">
                  <div class="magImg"><img src="<?=h_local($p["img"])?>" alt="<?=h_local($p["title"])?>"></div>
                  <div class="magBody">
                    <div class="row" style="align-items:center">
                      <span class="tagPill <?=h_local(tag_class($p["cat"]))?>"><?=h_local($p["cat"])?></span>
                      <span style="color:#94a3b8;font-size:12.5px"><?=h_local($p["date"]) ?></span>
                    </div>
                    <h3 class="magTitle"><?=h_local($p["title"]) ?></h3>
                    <p class="magText"><?=h_local($p["text"]) ?></p>
                    <div class="row" style="align-items:center">
                      <span style="color:#94a3b8;font-size:12.5px">საქართველოს სტუდენტური პარლამენტი და მთავრობა</span>
                      <span class="btnMini">დაწვრილებით</span>
                    </div>
                  </div>
                </a>
              <?php endforeach; ?>
            </div>
          </div>
        </div>
        <?php endif; ?>

        <!-- GRID (2 columns) -->
        <?php if(!empty($lastUpdates)): ?>
        <div class="panel" style="margin-top:14px">
          <div class="pad">
            <div class="row">
              <div>
                <h2 class="h2">ბოლო განახლებები</h2>
                <div class="sub">კლასიკური ბარათები — როგორც სიახლეების პორტალებზე</div>
              </div>
              <a class="btnMini" href="<?=h(url('news'))?>">ყველა</a>
            </div>

            <div class="grid">
              <?php foreach($lastUpdates as $p): ?>
                <article class="card">
                  <div class="thumb"><img src="<?=h_local($p["img"])?>" alt="<?=h_local($p["title"])?>"></div>
                  <div class="body">
                    <div class="row" style="align-items:center">
                      <span class="tagPill <?=h_local(tag_class($p["cat"]))?>"><?=h_local($p["cat"]) ?></span>
                      <span style="color:#94a3b8;font-size:12.5px"><?=h_local($p["date"]) ?></span>
                    </div>
                    <h3 class="title"><?=h_local($p["title"]) ?></h3>
                    <p class="text"><?=h_local($p["text"]) ?></p>
                    <div class="row" style="align-items:center">
                      <span style="color:#94a3b8;font-size:12.5px">საქართველოს სტუდენტური პარლამენტი და მთავრობა</span>
                      <a class="btnMini" href="<?=h(url('news-single?id=' . (int)$p["id"]))?>">დაწვრილებით</a>
                    </div>
                  </div>
                </article>
              <?php endforeach; ?>
            </div>
          </div>
        </div>
        <?php endif; ?>

        <!-- COMPACT -->
        <?php if(!empty($compactList)): ?>
        <div class="panel" style="margin-top:14px">
          <div class="pad">
            <div class="row">
              <div>
                <h2 class="h2">მოკლე სია</h2>
                <div class="sub">კომპაქტური ფორმატი — სწრაფი გადახედვა</div>
              </div>
            </div>

            <div class="compact">
              <?php foreach($compactList as $p): ?>
                <a href="<?=h(url('news-single?id=' . (int)$p["id"]))?>">
                  <div>
                    <span class="tagPill <?=h_local(tag_class($p["cat"]))?>"><?=h_local($p["cat"]) ?></span>
                    <p class="cTitle" style="margin-top:8px"><?=h_local($p["title"]) ?></p>
                    <div class="cText"><?=h_local($p["text"]) ?></div>
                  </div>
                  <div class="cDate"><?=h_local($p["date"]) ?></div>
                </a>
              <?php endforeach; ?>
            </div>
          </div>
        </div>
        <?php endif; ?>

      </section>

      <!-- SIDEBAR -->
      <aside class="panel sidebar">
        <div class="pad">
          <h3 class="sideTitle">კატეგორიები</h3>
          <div class="cat">
            <?php
              $cats = [
                ["label"=>"განცხადებები", "hint"=>"ვადები, რეგისტრაცია, პირობები", "key"=>"განცხადება"],
                ["label"=>"ღონისძიებები", "hint"=>"კალენდარი, შეხვედრები", "key"=>"ღონისძიება"],
                ["label"=>"ორგანიზაცია",  "hint"=>"სტრუქტურა, დოკუმენტები", "key"=>"ორგანიზაცია"],
                ["label"=>"ტრენინგი",     "hint"=>"სესიები, მასალები", "key"=>"ტრენინგი"],
              ];
              foreach($cats as $c):
                $cnt = (int)($catCounts[$c["key"]] ?? 0);
            ?>
              <a href="<?=h(url('news?cat=' . urlencode($c["key"])))?>">
                <span><b><?=h_local($c["label"])?></b><small><?=h_local($c["hint"])?></small></span>
                <span class="count"><?=$cnt?></span>
              </a>
            <?php endforeach; ?>
          </div>

          <div style="margin-top:14px">
            <h3 class="sideTitle">ბოლო ჩანაწერები</h3>
            <?php foreach($latestMini as $p): ?>
              <a class="mini" href="<?=h(url('news-single?id=' . (int)$p["id"]))?>">
                <div class="miniThumb"><img src="<?=h_local($p["img"])?>" alt=""></div>
                <div>
                  <p class="miniT"><?=h_local($p["title"]) ?></p>
                  <div class="miniM"><?=h_local($p["date"]) ?></div>
                </div>
              </a>
            <?php endforeach; ?>
          </div>
        </div>
      </aside>

    </div>
  </div>

  <?php endif; // empty posts ?>
</main>

<script>
/* HERO autoplay slider: arrows + dots + swipe + pause hover */
(function(){
  const slider = document.getElementById('heroSlider');
  const track  = document.getElementById('heroTrack');
  const dotsEl = document.getElementById('heroDots');
  if(!slider || !track) return;

  const slides = Array.from(track.children);
  const prevBtn = slider.querySelector('.heroPrev');
  const nextBtn = slider.querySelector('.heroNext');

  let index = 0;
  let timer = null;
  const INTERVAL = 5200;

  function renderDots(){
    if(!dotsEl) return;
    dotsEl.innerHTML = slides.map((_,i)=>`<button class="heroDot ${i===0?'active':''}" type="button" aria-label="slide ${i+1}"></button>`).join('');
    Array.from(dotsEl.querySelectorAll('.heroDot')).forEach((b,i)=>{
      b.addEventListener('click', ()=> go(i, true));
    });
  }

  function update(){
    track.style.transform = `translateX(${-index * 100}%)`;
    if(dotsEl){
      Array.from(dotsEl.querySelectorAll('.heroDot')).forEach((d,i)=> d.classList.toggle('active', i===index));
    }
  }

  function go(i, user=false){
    index = (i + slides.length) % slides.length;
    update();
    if(user) restart();
  }
  function next(){ go(index + 1); }
  function prev(){ go(index - 1); }

  function start(){ stop(); timer = setInterval(next, INTERVAL); }
  function stop(){ if(timer){ clearInterval(timer); timer=null; } }
  function restart(){ stop(); start(); }

  prevBtn?.addEventListener('click', ()=>{ prev(); restart(); });
  nextBtn?.addEventListener('click', ()=>{ next(); restart(); });

  slider.addEventListener('mouseenter', stop);
  slider.addEventListener('mouseleave', start);

  // swipe / drag
  let isDown=false, startX=0, dx=0;
  const pointerX = e => (e.touches && e.touches[0]) ? e.touches[0].clientX : e.clientX;

  slider.addEventListener('mousedown', (e)=>{ isDown=true; dx=0; startX=pointerX(e); stop(); });
  slider.addEventListener('mousemove', (e)=>{ if(!isDown) return; dx = pointerX(e) - startX; });
  window.addEventListener('mouseup', ()=>{
    if(!isDown) return;
    isDown=false;
    if(Math.abs(dx) > 70) dx < 0 ? next() : prev();
    start();
  });

  slider.addEventListener('touchstart', (e)=>{ isDown=true; dx=0; startX=pointerX(e); stop(); }, {passive:true});
  slider.addEventListener('touchmove', (e)=>{ if(!isDown) return; dx = pointerX(e) - startX; }, {passive:true});
  slider.addEventListener('touchend', ()=>{
    if(!isDown) return;
    isDown=false;
    if(Math.abs(dx) > 50) dx < 0 ? next() : prev();
    start();
  });

  renderDots();
  update();
  start();
})();

/* Featured slider: arrows + drag + swipe + autoplay + pause hover */
(function(){
  const track = document.getElementById('featuredTrack');
  const wrap  = document.getElementById('featuredSlider');
  if(!track || !wrap) return;

  const prev = wrap.querySelector('.slideBtn.prev');
  const next = wrap.querySelector('.slideBtn.next');

  const getStep = () => {
    const card = track.querySelector('.feat');
    if(!card) return 380;
    const w = card.getBoundingClientRect().width;
    return w + 14;
  };

  prev && prev.addEventListener('click', () => track.scrollBy({ left: -getStep(), behavior: 'smooth' }));
  next && next.addEventListener('click', () => track.scrollBy({ left:  getStep(), behavior: 'smooth' }));

  let isDown = false, startX = 0, startScroll = 0, moved = false;

  track.addEventListener('mousedown', (e)=>{
    isDown = true; moved = false;
    startX = e.pageX; startScroll = track.scrollLeft;
    track.style.cursor = 'grabbing';
  });

  window.addEventListener('mouseup', ()=>{
    isDown = false;
    track.style.cursor = '';
  });

  track.addEventListener('mousemove', (e)=>{
    if(!isDown) return;
    const dx = e.pageX - startX;
    if(Math.abs(dx) > 4) moved = true;
    track.scrollLeft = startScroll - dx;
  });

  track.addEventListener('click', (e)=>{
    if(moved){ e.preventDefault(); e.stopPropagation(); moved = false; }
  }, true);

  // swipe
  let tStartX = 0, tDX = 0;
  track.addEventListener('touchstart', (e)=>{ tStartX = e.touches[0].clientX; tDX=0; }, {passive:true});
  track.addEventListener('touchmove', (e)=>{ tDX = e.touches[0].clientX - tStartX; }, {passive:true});
  track.addEventListener('touchend', ()=>{
    if(Math.abs(tDX) > 50){
      track.scrollBy({ left: tDX < 0 ? getStep() : -getStep(), behavior: 'smooth' });
    }
  });

  // autoplay
  let timer = null;
  function start(){ stop(); timer = setInterval(()=> track.scrollBy({ left: getStep(), behavior:'smooth' }), 4200); }
  function stop(){ if(timer){ clearInterval(timer); timer=null; } }
  wrap.addEventListener('mouseenter', stop);
  wrap.addEventListener('mouseleave', start);
  start();
})();
</script>

<?php include __DIR__ . "/footer.php"; ?>
