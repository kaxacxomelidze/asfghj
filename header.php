<?php /* header.php */ ?>
<!DOCTYPE html>
<html lang="ka">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <?php
    $orgName = "საქართველოს სტუდენტური პარლამენტი და მთავრობა";
    $metaTitle = isset($pageTitle) ? htmlspecialchars((string)$pageTitle, ENT_QUOTES, "UTF-8") : $orgName;
    $metaDescription = isset($metaDescription) ? htmlspecialchars((string)$metaDescription, ENT_QUOTES, "UTF-8") : "ოფიციალური ვებგვერდი — სიახლეები, პროექტები, სტუდენტური პარლამენტი და მთავრობა.";
    $canonicalUrl = isset($canonicalUrl) ? (string)$canonicalUrl : current_url();
  ?>
  <title><?= $metaTitle ?></title>
  <meta name="description" content="<?= $metaDescription ?>" />
  <meta name="robots" content="index,follow,max-image-preview:large" />
  <link rel="canonical" href="<?= h($canonicalUrl) ?>" />
  <meta property="og:type" content="website" />
  <meta property="og:site_name" content="<?= h($orgName) ?>" />
  <meta property="og:title" content="<?= $metaTitle ?>" />
  <meta property="og:description" content="<?= $metaDescription ?>" />
  <meta property="og:url" content="<?= h($canonicalUrl) ?>" />
  <link rel="sitemap" type="application/xml" title="Sitemap" href="<?= h(url('sitemap')) ?>" />

  <style>
    :root {
      color-scheme: light;
      --bg: #f8fafc;
      --card: #ffffff;
      --text: #0f172a;
      --muted: #64748b;
      --line: #e5e7eb;
      --brand: #2563eb;
      --brand2: #0ea5e9;
      --radius: 16px;
      --shadow: 0 14px 32px rgba(15, 23, 42, 0.08);
      --max: 1200px;
    }

    * { box-sizing: border-box; margin: 0; padding: 0; }
    body {
      font-family: system-ui, -apple-system, "Segoe UI", Roboto, Arial, sans-serif;
      background:
        radial-gradient(900px 420px at 10% 0%, rgba(37, 99, 235, 0.10), transparent 60%),
        radial-gradient(700px 380px at 90% 8%, rgba(14, 165, 233, 0.10), transparent 55%),
        var(--bg);
      color: var(--text);
      line-height: 1.6;
    }
    a { color: inherit; text-decoration: none; }
    .container { width: min(var(--max), 92%); margin: 0 auto; }


    /* Global mobile safety rules across all public pages */
    img{max-width:100%;height:auto}
    @media (max-width: 900px){
      .container{width:min(var(--max),95%)}
      table{display:block;width:100%;overflow-x:auto;-webkit-overflow-scrolling:touch}
      iframe, video{max-width:100%}

      /* common inline-grid patterns used across pages */
      [style*="grid-template-columns:1fr auto"],
      [style*="grid-template-columns:1fr 1fr"],
      [style*="grid-template-columns:minmax(320px,1.1fr) 1fr"],
      [style*="grid-template-columns: 1fr auto"],
      [style*="grid-template-columns: 1fr 1fr"]{
        grid-template-columns:1fr !important;
      }

      [style*="display:flex"][style*="justify-content:space-between"]{
        align-items:flex-start !important;
      }

      input, select, textarea, button{font-size:16px}
      .btn{max-width:100%}
    }

    /* HEADER */
    .header {
      position: sticky; top: 0; z-index: 60;
      background: rgba(255, 255, 255, 0.92);
      backdrop-filter: blur(10px);
      border-bottom: 1px solid var(--line);
    }
    .headerTopGlow {
      height: 4px;
      background: linear-gradient(90deg, var(--brand), var(--brand2));
      opacity: .65;
    }

    .topbar {
      background: rgba(248, 250, 252, 0.92);
      border-bottom: 1px solid var(--line);
    }
    .topbar__inner {
      height: 44px;
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 12px;
      font-size: 13px;
      color: var(--muted);
    }
    .topbar__left {
      display: flex;
      gap: 12px;
      align-items: center;
      flex-wrap: wrap;
    }
    .pill {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      padding: 7px 12px;
      border: 1px solid var(--line);
      background: #fff;
      border-radius: 999px;
      color: var(--muted);
      white-space: nowrap;
    }

    .lang { display: flex; gap: 8px; align-items: center; }
    .lang button {
      border: 1px solid var(--line);
      background: #fff;
      padding: 7px 10px;
      border-radius: 999px;
      cursor: pointer;
      font-size: 13px;
      color: var(--muted);
    }
    .lang button.active {
      color: var(--text);
      background: rgba(37, 99, 235, 0.10);
      border-color: rgba(37, 99, 235, 0.20);
      font-weight: 800;
    }

    .brandbar { padding: 22px 0; }
    .brandbar__inner {
      display: grid;
      grid-template-columns: 1fr auto 1fr;
      align-items: center;
      gap: 18px;
    }
    .leftActions { display: flex; justify-content: flex-start; gap: 10px; }
    .actions { display: flex; justify-content: flex-end; gap: 10px; }

    .brand { display: flex; align-items: center; justify-content: center; }
    .logoImg {
      height: 92px;
      width: auto;
      display: block;
      object-fit: contain;
      filter: drop-shadow(0 14px 18px rgba(37, 99, 235, .18));
    }

    .btn {
      border: 1px solid var(--line);
      background: var(--card);
      color: var(--text);
      padding: 13px 18px;
      border-radius: 16px;
      font-size: 14px;
      cursor: pointer;
      transition: all .15s ease;
      display: inline-flex;
      align-items: center;
      gap: 10px;
      white-space: nowrap;
    }
    .btn:hover { transform: translateY(-1px); filter: brightness(.985); }
    .btn.primary {
      border-color: rgba(37,99,235,.28);
      background: linear-gradient(135deg, rgba(37,99,235,.18), rgba(14,165,233,.10));
      color: #0b2a7a;
      font-weight: 900;
    }
    .btn.signin { font-weight: 800; background:#fff; }
    .btn.burger { display: none; width: 52px; height: 52px; justify-content: center; }

    .navbar {
      border-top: 1px solid var(--line);
      background: rgba(255,255,255,.92);
    }
    .navbar__inner {
      height: 68px;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 10px;
    }

    .nav {
      display: flex;
      gap: 10px;
      align-items: center;
      flex-wrap: wrap;
      justify-content: center;
    }
    .nav > a, .drop > button {
      font-size: 15px;
      color: var(--muted);
      padding: 12px 16px;
      border-radius: 999px;
      border: 1px solid transparent;
      background: transparent;
      cursor: pointer;
      display: inline-flex;
      align-items: center;
      gap: 8px;
      transition: all .15s ease;
      white-space: nowrap;
    }
    .nav > a:hover, .drop > button:hover {
      color: var(--text);
      background: #f1f5f9;
      border-color: #eef2f7;
    }
    .nav > a.active, .drop > button.active {
      color: var(--text);
      background: rgba(37, 99, 235, .10);
      border-color: rgba(37, 99, 235, .20);
      font-weight: 900;
    }
    .caret { font-size: 12px; opacity: .75; }

    .drop { position: relative; }
    .menu {
      position: absolute;
      top: 58px;
      left: 50%;
      transform: translateX(-50%);
      width: 340px;
      background: var(--card);
      border: 1px solid var(--line);
      box-shadow: var(--shadow);
      border-radius: 18px;
      padding: 8px;
      display: none;
      overflow: hidden;
    }
    .drop.open .menu { display: block; }
    .menu a {
      display: block;
      padding: 12px 12px;
      border-radius: 14px;
      color: var(--muted);
      font-size: 14px;
      transition: all .12s ease;
    }
    .menu a:hover {
      background: #f1f5f9;
      color: var(--text);
      transform: translateX(2px);
    }

    /* Mobile */
    .mobile {
      display: none;
      border-top: 1px solid var(--line);
      padding: 12px 0 16px;
      background: rgba(255,255,255,.96);
    }
    .mobile a {
      display: block;
      padding: 12px 12px;
      border-radius: 14px;
      color: var(--muted);
    }
    .mobile a:hover { background:#f1f5f9; color:var(--text); }
    .mobile.open { display: block; }

    /* ✅ Mobile improvements ONLY (desktop stays exact) */
    @media (max-width: 980px) {
      .nav { display: none; }
      .btn.burger { display: inline-flex; }

      /* Better layout on phone/tablet */
      .brandbar__inner{
        grid-template-columns: 1fr auto 1fr;
        gap: 10px;
      }
      .logoImg{height:72px}
      .btn{padding:12px 14px;border-radius:14px}
      .btn.burger{width:48px;height:48px}

      /* make topbar wrap nicely */
      .topbar__inner{
        height:auto;
        padding:10px 0;
        align-items:flex-start;
      }
      .topbar__left{gap:8px}
      .pill{padding:6px 10px;font-size:12.5px}

      /* mobile menu full width feel */
      .mobile{
        padding:10px 0 14px;
      }
      .mobile a{
        padding:12px 14px;
        margin:0 6px;
      }
    }

    @media (max-width: 560px){
      /* on very small phones */
      .brandbar{padding:14px 0}
      .logoImg{height:58px}
      .leftActions .btn.signin{padding:10px 11px;font-size:13px}
      .actions .btn.primary{padding:10px 11px;font-size:13px}
      .topbar__left .pill:nth-child(3){display:none}
      .topbar__inner{gap:8px}
      .lang button{padding:6px 8px;font-size:12px}
      .mobile a{font-size:14px}
    }
  </style>


  <script type="application/ld+json">
  {
    "@context": "https://schema.org",
    "@type": "Organization",
    "name": "საქართველოს სტუდენტური პარლამენტი და მთავრობა",
    "url": "<?= h(rtrim((preg_replace('~/(index|sitemap)(\.php)?$~i', '', current_url()) ?: current_url()), '/')) ?>/",
    "logo": "<?= h(url('spg_logo2.png')) ?>",
    "contactPoint": [{
      "@type": "ContactPoint",
      "telephone": "+995591037047",
      "contactType": "customer support",
      "areaServed": "GE"
    }]
  }
  </script>

</head>

<body>

  <header class="header" id="siteHeader">
    <div class="headerTopGlow"></div>

    <div class="topbar">
      <div class="container topbar__inner">
        <div class="topbar__left">
          <span class="pill" data-i18n="topbar.phone">📞 +995 591 037 047</span>
          <a class="pill" href="mailto:info@spg.ge" data-i18n="topbar.email">✉️ info@spg.ge</a>
          <span class="pill" data-i18n="topbar.address">📍 ჟიულ შარტავას 35-37, თბილისი</span>
        </div>

        <div class="lang" aria-label="Language">
          <button class="active" type="button" data-lang="ka">KA</button>
          <button type="button" data-lang="en">EN</button>
        </div>
      </div>
    </div>

    <div class="brandbar">
      <div class="container brandbar__inner">
        <div class="leftActions">
          <a class="btn signin" href="<?=h(url('user-auth.php#dashboard'))?>" data-i18n="header.signin">🔐 Sign in</a>
        </div>

        <a class="brand" href="<?=h(url('#home'))?>" aria-label="Home">
          <img class="logoImg" src="cropped-cropped-IMG_9728.png" alt="SPG Logo" />
        </a>

        <div class="actions">
          <a class="btn primary" href="<?=h(url('membership.php'))?>" data-i18n="header.registerCta">გაწევრიანება</a>
          <button class="btn burger" id="burger" type="button" aria-label="Open menu">☰</button>
        </div>
      </div>
    </div>

    <div class="navbar">
      <div class="container navbar__inner">
        <nav class="nav" aria-label="Main">
          <a id="nav-home" href="<?=h(url('#home'))?>" data-i18n="nav.home">მთავარი</a>
          <a id="nav-news" href="<?=h(url('news.php'))?>" data-i18n="nav.news">სიახლეები</a>

          <div class="drop" data-drop="about" id="nav-about">
            <button type="button" aria-haspopup="true" aria-expanded="false">
              <span data-i18n="nav.about">ჩვენს შესახებ</span> <span class="caret">▾</span>
            </button>
            <div class="menu" role="menu">
              <a href="<?=h(url('history.php'))?>" data-i18n="nav.aboutHistory">ისტორია</a>
              <a href="<?=h(url('mission.php'))?>" data-i18n="nav.aboutMission">მისია</a>
              <a href="<?=h(url('vision.php'))?>" data-i18n="nav.aboutVision">ხედვა</a>
              <a href="<?=h(url('structure.php'))?>" data-i18n="nav.aboutStructure">სტრუქტურა</a>
              <a href="<?=h(url('#about-career'))?>" data-i18n="nav.aboutCareer">კარიერული განვითარების გეგმა</a>
              <a href="<?=h(url('message.php'))?>" data-i18n="nav.aboutMessage">ხელმძღვანელის მიმართვა</a>
            </div>
          </div>

          <div class="drop" data-drop="team" id="nav-team">
            <button type="button" aria-haspopup="true" aria-expanded="false">
              <span data-i18n="nav.team">გუნდი</span> <span class="caret">▾</span>
            </button>
            <div class="menu" role="menu">
              <a href="<?=h(url('pr-event.php'))?>" data-i18n="nav.teamPr">PR &amp; EVENT</a>
              <a href="<?=h(url('aparati.php'))?>" data-i18n="nav.teamAparati">აპარატი</a>
              <a href="<?=h(url('parlament.php'))?>" data-i18n="nav.teamParlament">სტუდენტური პარლამენტი</a>
              <a href="<?=h(url('gov.php'))?>" data-i18n="nav.teamGov">სტუდენტური მთავრობა</a>
            </div>
          </div>

          <a id="nav-contact" href="<?=h(url('contact.php'))?>" data-i18n="nav.contact">კონტაქტი</a>
        </nav>
      </div>

      <div class="container mobile" id="mobile">
        <a href="<?=h(url('#home'))?>" data-i18n="nav.home">მთავარი</a>
        <a href="<?=h(url('news.php'))?>" data-i18n="nav.news">სიახლეები</a>
        <a href="<?=h(url('history.php'))?>" data-i18n="nav.about">ჩვენს შესახებ</a>
        <a href="<?=h(url('pr-event.php'))?>" data-i18n="nav.team">გუნდი</a>
        <a href="<?=h(url('contact.php'))?>" data-i18n="nav.contact">კონტაქტი</a>
        <a href="<?=h(url('membership.php'))?>" data-i18n="header.registerCta">რეგისტრაცია</a>
        <a href="<?=h(url('user-auth.php#dashboard'))?>" data-i18n="header.signin">Sign in</a>
      </div>
    </div>
  </header>

  <script>
    const $ = (s, el=document) => el.querySelector(s);
    const $$ = (s, el=document) => Array.from(el.querySelectorAll(s));

    // burger mobile
    const burger = $("#burger");
    const mobile = $("#mobile");
    burger?.addEventListener("click", () => mobile.classList.toggle("open"));

    // dropdowns
    const drops = $$(".drop");
    drops.forEach(drop => {
      const btn = $("button", drop);
      btn.addEventListener("click", (e) => {
        e.stopPropagation();
        drops.forEach(d => { if (d !== drop) d.classList.remove("open"); });
        drop.classList.toggle("open");
        btn.setAttribute("aria-expanded", drop.classList.contains("open") ? "true" : "false");
      });
    });

    // close on outside click
    document.addEventListener("click", () => drops.forEach(d => d.classList.remove("open")));

    // language buttons (UI only)
    const langBtns = $$(".lang button");
    langBtns.forEach(btn=>{
      btn.addEventListener("click", ()=>{
        langBtns.forEach(b=>b.classList.toggle("active", b===btn));
      });
    });

    // dynamic active nav pill
    const navHome = $("#nav-home");
    const navNews = $("#nav-news");
    const navContact = $("#nav-contact");
    const navAboutBtn = $("#nav-about > button");
    const navTeamBtn = $("#nav-team > button");

    function clearActive(){
      [navHome, navNews, navContact, navAboutBtn, navTeamBtn].forEach(el => el?.classList.remove("active"));
    }

    function normPath(path){
      if(!path) return '/';
      let p = path.toLowerCase().replace(/\/+$|\/$/g, '');
      if(p === '') p = '/';
      p = p.replace(/\.php$/,'');
      if(p === '/index') p = '/';
      return p;
    }

    function setActiveNavByLocation(){
      clearActive();
      const path = normPath(location.pathname);
      const hash = (location.hash || '').toLowerCase();

      const aboutPaths = ['/history','/mission','/vision','/structure','/message'];
      const teamPaths = ['/pr-event','/aparati','/parlament','/gov'];

      if (hash === '#about-career' || aboutPaths.includes(path)) {
        navAboutBtn?.classList.add('active');
        return;
      }
      if (teamPaths.includes(path)) {
        navTeamBtn?.classList.add('active');
        return;
      }
      if (path === '/news') {
        navNews?.classList.add('active');
        return;
      }
      if (path === '/contact') {
        navContact?.classList.add('active');
        return;
      }
      navHome?.classList.add('active');
    }

    setActiveNavByLocation();
    window.addEventListener('hashchange', setActiveNavByLocation);

    [navHome, navNews, navContact].forEach(el => {
      el?.addEventListener('click', () => {
        clearActive();
        el.classList.add('active');
      });
    });
    navAboutBtn?.addEventListener('click', () => { clearActive(); navAboutBtn.classList.add('active'); });
    navTeamBtn?.addEventListener('click', () => { clearActive(); navTeamBtn.classList.add('active'); });
  </script>
