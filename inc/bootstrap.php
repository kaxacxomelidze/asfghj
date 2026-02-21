<?php
declare(strict_types=1);

// IMPORTANT: no spaces/BOM before <?php

$config = require __DIR__ . '/config.php';

/**
 * Detect base URL automatically:
 * - If project is in C:\xampp\htdocs\sspm  -> base_url = /sspm
 * - If project is in C:\xampp\htdocs      -> base_url = (empty)
 */
function detect_base_url(): string {
  $docRoot = realpath($_SERVER['DOCUMENT_ROOT'] ?? '') ?: '';
  $projectRoot = realpath(__DIR__ . '/..') ?: '';

  $docRoot = str_replace('\\', '/', $docRoot);
  $projectRoot = str_replace('\\', '/', $projectRoot);

  if ($docRoot !== '' && $projectRoot !== '' && str_starts_with($projectRoot, $docRoot)) {
    $rel = substr($projectRoot, strlen($docRoot)); // like "/sspm"
    $rel = str_replace('\\', '/', $rel);
    $rel = rtrim($rel, '/');
    return $rel; // "" or "/sspm"
  }
  return ''; // fallback
}

define('BASE_URL', rtrim((string)($config['app']['base_url'] ?? ''), '/'));
if (BASE_URL === '') {
  define('AUTO_BASE_URL', detect_base_url());
} else {
  define('AUTO_BASE_URL', BASE_URL);
}

/** Build absolute URL within this project */
function url(string $path = ''): string {
  $base = AUTO_BASE_URL;
  $path = '/' . ltrim($path, '/');
  if ($base === '' || $base === '/') return $path;
  return $base . $path;
}

/** Escape HTML */
function h(string $s): string { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }

/** Normalize user-provided image paths */
function normalize_image_path(string $path): string {
  $path = trim($path);
  if ($path === '') return '';

  if (preg_match('~^https?://~i', $path)) return $path;
  if (str_starts_with($path, '/')) return $path;

  if (str_starts_with($path, 'assets/')) {
    return url($path);
  }

  return url('assets/news/' . ltrim($path, '/'));
}

/** CSRF */
function csrf_token(): string {
  if (empty($_SESSION['_csrf'])) {
    $_SESSION['_csrf'] = bin2hex(random_bytes(16));
  }
  return $_SESSION['_csrf'];
}
function csrf_verify(): void {
  $ok = isset($_POST['_csrf'], $_SESSION['_csrf']) && hash_equals($_SESSION['_csrf'], (string)$_POST['_csrf']);
  if (!$ok) {
    http_response_code(419);
    exit('CSRF token mismatch');
  }
}

/** Start session BEFORE any output */
if (session_status() !== PHP_SESSION_ACTIVE) {
  if (headers_sent($file, $line)) {
    http_response_code(500);
    exit("Headers already sent in: {$file} on line {$line}. Make sure every page loads bootstrap.php BEFORE header.php.");
  }
  session_name($config['app']['session_name'] ?? 'SPGSESSID');
  session_start();
}

/** DB */
function db(): PDO {
  static $pdo = null;
  if ($pdo) return $pdo;

  $cfg = require __DIR__ . '/config.php';
  $db = $cfg['db'];

  $dsn = "mysql:host={$db['host']};dbname={$db['name']};charset={$db['charset']}";
  $pdo = new PDO($dsn, $db['user'], $db['pass'], [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
  ]);
  return $pdo;
}

/** Admin auth */
function is_admin(): bool {
  return !empty($_SESSION['admin_id']);
}
function require_admin(): void {
  if (!is_admin()) {
    header('Location: ' . url('admin/login.php'));
    exit;
  }
}

function ensure_admin_permissions_table(): void {
  static $done = false;
  if ($done) return;
  $done = true;
  try {
    db()->exec("CREATE TABLE IF NOT EXISTS admin_permissions (
      admin_id INT NOT NULL,
      permission VARCHAR(64) NOT NULL,
      PRIMARY KEY (admin_id, permission)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
  } catch (Throwable $e) {
    // ignore if DB user lacks permissions
  }
}

function ensure_news_gallery_table(): void {
  static $done = false;
  if ($done) return;
  $done = true;
  try {
    db()->exec("CREATE TABLE IF NOT EXISTS news_gallery (
      id INT AUTO_INCREMENT PRIMARY KEY,
      post_id INT NOT NULL,
      image_path VARCHAR(255) NOT NULL,
      sort_order INT NOT NULL DEFAULT 0,
      INDEX (post_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
  } catch (Throwable $e) {
    // ignore if DB user lacks permissions
  }
}

function available_admin_permissions(): array {
  return [
    'news.view' => 'View news list',
    'news.create' => 'Create news',
    'news.edit' => 'Edit news',
    'news.delete' => 'Delete news',
    'people.manage' => 'Manage team members',
    'contact.view' => 'View contact submissions',
    'membership.view' => 'View membership applications',
    'admins.manage' => 'Manage admins',
  ];
}

function admin_permissions_total_count(): int {
  static $count = null;
  if ($count !== null) return $count;
  ensure_admin_permissions_table();
  try {
    $stmt = db()->query("SELECT COUNT(*) AS c FROM admin_permissions");
    $count = (int)$stmt->fetchColumn();
  } catch (Throwable $e) {
    $count = 0;
  }
  return $count;
}

function admin_permissions(int $adminId): array {
  ensure_admin_permissions_table();
  try {
    $stmt = db()->prepare("SELECT permission FROM admin_permissions WHERE admin_id=?");
    $stmt->execute([$adminId]);
    return array_map('strval', $stmt->fetchAll(PDO::FETCH_COLUMN));
  } catch (Throwable $e) {
    return [];
  }
}

function has_permission(string $perm): bool {
  if (!is_admin()) return false;
  $adminId = (int)($_SESSION['admin_id'] ?? 0);
  $perms = admin_permissions($adminId);
  if (!$perms && admin_permissions_total_count() === 0) return true;
  return in_array($perm, $perms, true);
}

function require_permission(string $perm): void {
  if (!has_permission($perm)) {
    http_response_code(403);
    exit('Access denied');
  }
}

/** Helpers for news */
function fmt_date_dmY(string $datetime): string {
  $t = strtotime($datetime);
  return $t ? date('d.m.Y', $t) : '';
}

function get_news_posts(int $limit = 50): array {
  $stmt = db()->prepare("
    SELECT id, category, title, excerpt, image_path, published_at
    FROM news_posts
    WHERE is_published=1
    ORDER BY published_at DESC, id DESC
    LIMIT :lim
  ");
  $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
  $stmt->execute();

  $rows = $stmt->fetchAll();
  $out = [];
  foreach ($rows as $r) {
    $out[] = [
      'id' => (int)$r['id'],
      'cat' => (string)$r['category'],
      'date' => fmt_date_dmY((string)$r['published_at']),
      'title' => (string)$r['title'],
      'text' => (string)$r['excerpt'],
      'img' => normalize_image_path((string)$r['image_path']),
    ];
  }
  return $out;
}

function get_one_news(int $id): ?array {
  $stmt = db()->prepare("SELECT * FROM news_posts WHERE id=? AND is_published=1 LIMIT 1");
  $stmt->execute([$id]);
  $r = $stmt->fetch();
  if (!$r) return null;

  return [
    'id' => (int)$r['id'],
    'cat' => (string)$r['category'],
    'date' => fmt_date_dmY((string)$r['published_at']),
    'title' => (string)$r['title'],
    'text' => (string)$r['excerpt'],
    'content' => (string)($r['content'] ?? ''),
    'img' => normalize_image_path((string)$r['image_path']),
    'published_at' => (string)$r['published_at'],
  ];
}

function get_news_gallery(int $postId): array {
  ensure_news_gallery_table();
  try {
    $stmt = db()->prepare("SELECT id, image_path FROM news_gallery WHERE post_id=? ORDER BY sort_order ASC, id ASC");
    $stmt->execute([$postId]);
    $rows = $stmt->fetchAll();
  } catch (Throwable $e) {
    return [];
  }

  $out = [];
  foreach ($rows as $row) {
    $out[] = [
      'id' => (int)$row['id'],
      'path' => normalize_image_path((string)$row['image_path']),
    ];
  }
  return $out;
}

function ensure_contact_messages_table(): void {
  static $done = false;
  if ($done) return;
  $done = true;
  try {
    db()->exec("CREATE TABLE IF NOT EXISTS contact_messages (
      id INT AUTO_INCREMENT PRIMARY KEY,
      name VARCHAR(120) NOT NULL,
      email VARCHAR(190) NOT NULL,
      phone VARCHAR(50) DEFAULT NULL,
      message TEXT NOT NULL,
      created_at DATETIME NOT NULL,
      INDEX (created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
  } catch (Throwable $e) {
    // ignore if DB user lacks permissions
  }
}

function ensure_membership_applications_table(): void {
  static $done = false;
  if ($done) return;
  $done = true;
  try {
    db()->exec("CREATE TABLE IF NOT EXISTS membership_applications (
      id INT AUTO_INCREMENT PRIMARY KEY,
      first_name VARCHAR(120) NOT NULL,
      last_name VARCHAR(120) NOT NULL,
      personal_id VARCHAR(30) NOT NULL,
      phone VARCHAR(50) NOT NULL,
      university VARCHAR(190) NOT NULL,
      faculty VARCHAR(190) NOT NULL,
      email VARCHAR(190) DEFAULT NULL,
      additional_info TEXT DEFAULT NULL,
      created_at DATETIME NOT NULL,
      INDEX (created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
  } catch (Throwable $e) {
    // ignore if DB user lacks permissions
  }
}

function ensure_people_profiles_table(): void {
  static $done = false;
  if ($done) return;
  $done = true;
  try {
    db()->exec("CREATE TABLE IF NOT EXISTS people_profiles (
      id INT AUTO_INCREMENT PRIMARY KEY,
      page_key VARCHAR(64) NOT NULL,
      first_name VARCHAR(120) NOT NULL,
      last_name VARCHAR(120) NOT NULL,
      role_title VARCHAR(180) DEFAULT NULL,
      image_path VARCHAR(255) DEFAULT NULL,
      sort_order INT NOT NULL DEFAULT 0,
      created_at DATETIME NOT NULL,
      INDEX (page_key),
      INDEX (sort_order)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
  } catch (Throwable $e) {
    // ignore if DB user lacks permissions
  }
}

function people_page_labels(): array {
  return [
    'pr-event' => 'PR & EVENT',
    'aparati' => 'აპარატი',
    'parlament' => 'სტუდენტური პარლამენტი',
    'gov' => 'სტუდენტური მთავრობა',
  ];
}

function get_people_by_page(string $pageKey): array {
  ensure_people_profiles_table();
  try {
    $stmt = db()->prepare("SELECT first_name, last_name, role_title, image_path
      FROM people_profiles
      WHERE page_key=?
      ORDER BY sort_order ASC, id ASC");
    $stmt->execute([$pageKey]);
    $rows = $stmt->fetchAll();
  } catch (Throwable $e) {
    return [];
  }

  $out = [];
  foreach ($rows as $row) {
    $fullName = trim(((string)$row['first_name']) . ' ' . ((string)$row['last_name']));
    $out[] = [
      'image' => normalize_image_path((string)($row['image_path'] ?? '')),
      'name' => $fullName,
      'position' => (string)($row['role_title'] ?? ''),
    ];
  }
  return $out;
}
