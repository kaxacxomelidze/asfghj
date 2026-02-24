<?php
declare(strict_types=1);

/**
 * People section (Front-end)
 * Each $person supports:
 *  - image, name, position
 *  - facebook, twitter, linkedin, instagram (optional URLs)
 */

function render_people_section($heading, $people)
{
    if (empty($people)) return;
    ?>
    <style>
        /* ===== People grid (3 in a row) ===== */
        .people-wrap{max-width:1200px;margin:0 auto;padding:30px 18px;}
        .people-title{margin:0 0 18px;font-weight:900;font-size:28px;line-height:1.1;}
        .people-grid{
            display:grid;
            grid-template-columns:repeat(3, minmax(0, 1fr));
            gap:28px;
            align-items:start;
        }
        @media (max-width: 980px){
            .people-grid{grid-template-columns:repeat(2, minmax(0, 1fr));}
        }
        @media (max-width: 640px){
            .people-grid{grid-template-columns:1fr;}
        }

        /* card without border */
        .person-card{background:transparent;}

        /* photo: moderate size, rounded */
        .person-photo{
            border-radius:18px;
            overflow:hidden;
            background:#0b1220;
            box-shadow:0 10px 24px rgba(0,0,0,.12);
        }
        .person-photo img{
            width:100% !important;
            height:500px !important; /* ზომიერი */
            object-fit:cover !important;
            display:block;
        }

        /* text + socials */
        .person-meta{
            margin-top:14px;
            display:grid;
            grid-template-columns:1fr auto;
            gap:14px;
            align-items:start;
        }
        .person-name{
            margin:0;
            font-weight:800;
            font-size:22px;
            line-height:1.15;
            letter-spacing:-0.2px;
        }
        .person-role{
            margin-top:6px;
            color:#7b8596;
            font-size:14px;
            line-height:1.35;
            max-width:340px;
        }

        /* socials */
        .person-social{
            display:flex;
            flex-direction:column;
            gap:10px;
            margin-top:2px;
        }
        .soc{
            width:40px;height:40px;
            display:inline-flex;
            align-items:center;justify-content:center;
            border-radius:999px;
            background:#fff;
            border:2px solid rgba(37,99,235,.45);
            text-decoration:none;
            box-shadow:0 8px 18px rgba(0,0,0,.10);
            transition:transform .12s ease, box-shadow .12s ease;
        }
        .soc:hover{transform:translateY(-1px);box-shadow:0 12px 24px rgba(0,0,0,.14);}
        .soc svg{width:16px;height:16px;fill:#2563eb;}
        .soc.ig{border-color:rgba(236,72,153,.45)}
        .soc.ig svg{fill:#ec4899}

        /* ===== IMPORTANT: override your theme/elementor rule ===== */
        .team-item .team-thumb img{
            height:320px !important;
            width:100% !important;
            object-fit:cover !important;
        }
    </style>

    <section class="section people-section">
        <div class="people-wrap">
            <h2 class="people-title"><?= h($heading) ?></h2>

            <div class="people-grid">
                <?php foreach ($people as $p): ?>
                    <?php
                    $img  = trim((string)($p['image'] ?? ''));
                    $name = trim((string)($p['name'] ?? ''));
                    $pos  = trim((string)($p['position'] ?? ''));

                    $fb = trim((string)($p['facebook'] ?? ''));
                    $tw = trim((string)($p['twitter'] ?? ''));
                    $in = trim((string)($p['linkedin'] ?? ''));
                    $ig = trim((string)($p['instagram'] ?? ''));
                    ?>
                    <article class="person-card">
                        <div class="person-photo">
                            <img src="<?= h($img) ?>" alt="<?= h($name) ?>">
                        </div>

                        <div class="person-meta">
                            <div>
                                <h3 class="person-name"><?= h($name) ?></h3>
                                <div class="person-role"><?= h($pos) ?></div>
                            </div>

                            <div class="person-social">
                                <?php if ($fb !== ''): ?>
                                    <a class="soc fb" href="<?= h($fb) ?>" target="_blank" rel="noopener" aria-label="Facebook">
                                        <svg viewBox="0 0 24 24" aria-hidden="true">
                                            <path d="M22 12a10 10 0 1 0-11.5 9.9v-7H8v-3h2.5V9.8c0-2.5 1.5-3.9 3.8-3.9 1.1 0 2.2.2 2.2.2v2.4h-1.2c-1.2 0-1.6.7-1.6 1.5V12H18l-.5 3h-2.6v7A10 10 0 0 0 22 12z"/>
                                        </svg>
                                    </a>
                                <?php endif; ?>

                                <?php if ($tw !== ''): ?>
                                    <a class="soc tw" href="<?= h($tw) ?>" target="_blank" rel="noopener" aria-label="Twitter/X">
                                        <svg viewBox="0 0 24 24" aria-hidden="true">
                                            <path d="M22 5.9c-.7.3-1.5.5-2.3.6.8-.5 1.4-1.2 1.7-2.1-.8.5-1.6.8-2.5 1A3.9 3.9 0 0 0 12 8a11 11 0 0 1-8-4 3.9 3.9 0 0 0 1.2 5.2c-.6 0-1.2-.2-1.7-.5v.1c0 1.9 1.4 3.6 3.2 4-.3.1-.7.1-1 .1-.2 0-.5 0-.7-.1.5 1.7 2.1 2.9 4 2.9A7.8 7.8 0 0 1 2 17.6 11 11 0 0 0 8 19.4c7.2 0 11.2-6.1 11.2-11.4v-.5c.8-.6 1.4-1.2 1.8-2z"/>
                                        </svg>
                                    </a>
                                <?php endif; ?>

                                <?php if ($in !== ''): ?>
                                    <a class="soc in" href="<?= h($in) ?>" target="_blank" rel="noopener" aria-label="LinkedIn">
                                        <svg viewBox="0 0 24 24" aria-hidden="true">
                                            <path d="M4.98 3.5C4.98 4.9 3.9 6 2.5 6S0 4.9 0 3.5 1.1 1 2.5 1 4.98 2.1 4.98 3.5zM.5 23.5h4V7.9h-4v15.6zM8 7.9h3.8v2.1h.1c.5-1 1.9-2.1 3.9-2.1 4.2 0 5 2.8 5 6.4v9.2h-4v-8.2c0-2 0-4.5-2.7-4.5s-3.2 2.1-3.2 4.4v8.3H8V7.9z"/>
                                        </svg>
                                    </a>
                                <?php endif; ?>

                                <?php if ($ig !== ''): ?>
                                    <a class="soc ig" href="<?= h($ig) ?>" target="_blank" rel="noopener" aria-label="Instagram">
                                        <svg viewBox="0 0 24 24" aria-hidden="true">
                                            <path d="M7 2h10a5 5 0 0 1 5 5v10a5 5 0 0 1-5 5H7a5 5 0 0 1-5-5V7a5 5 0 0 1 5-5zm10 2H7a3 3 0 0 0-3 3v10a3 3 0 0 0 3 3h10a3 3 0 0 0 3-3V7a3 3 0 0 0-3-3zm-5 4.5A5.5 5.5 0 1 1 6.5 14 5.5 5.5 0 0 1 12 8.5zm0 2A3.5 3.5 0 1 0 15.5 14 3.5 3.5 0 0 0 12 10.5zM18 6.8a1.2 1.2 0 1 1-1.2 1.2A1.2 1.2 0 0 1 18 6.8z"/>
                                        </svg>
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <?php
}
