<?php
declare(strict_types=1);
require __DIR__ . '/inc/bootstrap.php';

header('Content-Type: application/xml; charset=UTF-8');

$base = rtrim(current_url(), '/');
$base = preg_replace('~/(sitemap(\.php)?)$~i', '', (string)$base) ?: $base;

$staticPages = [
  ['', 'daily'],
  ['news', 'daily'],
  ['history', 'weekly'],
  ['mission', 'weekly'],
  ['vision', 'weekly'],
  ['structure', 'weekly'],
  ['message', 'weekly'],
  ['pr-event', 'weekly'],
  ['aparati', 'weekly'],
  ['parlament', 'weekly'],
  ['gov', 'weekly'],
  ['contact', 'monthly'],
  ['membership', 'weekly'],
];

$urls = [];
$today = date('Y-m-d');
foreach ($staticPages as [$path, $freq]) {
  $loc = $path === '' ? $base . '/' : $base . url($path);
  $urls[] = ['loc' => $loc, 'lastmod' => $today, 'changefreq' => $freq, 'priority' => $path === '' ? '1.0' : '0.8'];
}

try {
  $stmt = db()->query("SELECT id, published_at FROM news_posts WHERE is_published=1 ORDER BY published_at DESC, id DESC");
  $rows = $stmt->fetchAll();
  foreach ($rows as $row) {
    $urls[] = [
      'loc' => $base . url('news-single?id=' . (int)$row['id']),
      'lastmod' => date('Y-m-d', strtotime((string)$row['published_at']) ?: time()),
      'changefreq' => 'monthly',
      'priority' => '0.7',
    ];
  }
} catch (Throwable $e) {
  // keep sitemap available even if DB is down
}

echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
echo "<urlset xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\">\n";
foreach ($urls as $u) {
  echo "  <url>\n";
  echo '    <loc>' . h((string)$u['loc']) . "</loc>\n";
  echo '    <lastmod>' . h((string)$u['lastmod']) . "</lastmod>\n";
  echo '    <changefreq>' . h((string)$u['changefreq']) . "</changefreq>\n";
  echo '    <priority>' . h((string)$u['priority']) . "</priority>\n";
  echo "  </url>\n";
}
echo "</urlset>\n";
