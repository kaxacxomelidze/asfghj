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
  $secureCookie = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
  session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'domain' => '',
    'secure' => $secureCookie,
    'httponly' => true,
    'samesite' => 'Lax',
  ]);
  session_start();
}

/** DB */
function db(): PDO {
  static $pdo = null;
  if ($pdo) return $pdo;

  $cfg = require __DIR__ . '/config.php';
  $db = $cfg['db'];
  $options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
  ];

  $dsn = "mysql:host={$db['host']};dbname={$db['name']};charset={$db['charset']}";
  try {
    $pdo = new PDO($dsn, $db['user'], $db['pass'], $options);
    return $pdo;
  } catch (PDOException $e) {
    $msg = (string)$e->getMessage();
    $isUnknownDb = str_contains($msg, '1049') || stripos($msg, 'Unknown database') !== false;
    if (!$isUnknownDb) {
      throw $e;
    }

    $serverDsn = "mysql:host={$db['host']};charset={$db['charset']}";
    $serverPdo = new PDO($serverDsn, $db['user'], $db['pass'], $options);
    $dbName = str_replace('`', '', (string)$db['name']);
    $charset = (string)$db['charset'];
    $serverPdo->exec("CREATE DATABASE IF NOT EXISTS `{$dbName}` CHARACTER SET {$charset}");

    $pdo = new PDO($dsn, $db['user'], $db['pass'], $options);
    return $pdo;
  }
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
    'university.manage' => 'Manage university system data',
    'admin.logs.view' => 'View admin login logs',
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



function ensure_admin_login_logs_table(): void {
  static $done = false;
  if ($done) return;
  $done = true;
  try {
    db()->exec("CREATE TABLE IF NOT EXISTS admin_login_logs (
      id INT AUTO_INCREMENT PRIMARY KEY,
      username VARCHAR(120) NOT NULL,
      admin_id INT DEFAULT NULL,
      ip_address VARCHAR(64) DEFAULT NULL,
      user_agent VARCHAR(255) DEFAULT NULL,
      status VARCHAR(24) NOT NULL,
      reason VARCHAR(190) DEFAULT NULL,
      created_at DATETIME NOT NULL,
      INDEX idx_admin_login_logs_created_at (created_at),
      INDEX idx_admin_login_logs_status (status)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
  } catch (Throwable $e) {
    // ignore if DB is unavailable
  }
}

function record_admin_login_log(string $username, ?int $adminId, string $status, string $reason = ''): void {
  ensure_admin_login_logs_table();
  $ip = (string)($_SERVER['REMOTE_ADDR'] ?? '');
  $ua = substr((string)($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255);
  try {
    $stmt = db()->prepare('INSERT INTO admin_login_logs (username, admin_id, ip_address, user_agent, status, reason, created_at) VALUES (?, ?, ?, ?, ?, ?, ?)');
    $stmt->execute([
      $username,
      $adminId,
      $ip !== '' ? $ip : null,
      $ua !== '' ? $ua : null,
      $status,
      $reason !== '' ? $reason : null,
      date('Y-m-d H:i:s')
    ]);
  } catch (Throwable $e) {
    // ignore if DB is unavailable
  }
}

function ensure_users_table(): void {
  static $done = false;
  if ($done) return;
  $done = true;
  try {
    db()->exec("CREATE TABLE IF NOT EXISTS users (
      id INT AUTO_INCREMENT PRIMARY KEY,
      full_name VARCHAR(190) NOT NULL,
      email VARCHAR(190) NOT NULL UNIQUE,
      lecturer_name VARCHAR(190) DEFAULT NULL,
      password_hash VARCHAR(255) NOT NULL,
      created_at DATETIME NOT NULL,
      INDEX (created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    try {
      db()->exec("ALTER TABLE users ADD COLUMN lecturer_name VARCHAR(190) DEFAULT NULL AFTER email");
    } catch (Throwable $e2) {
      // already exists
    }
    try {
      db()->exec("ALTER TABLE users ADD INDEX idx_users_lecturer_name (lecturer_name)");
    } catch (Throwable $e3) {
      // already exists
    }
  } catch (Throwable $e) {
    // ignore if DB is unavailable
  }
}

function is_user_logged_in(): bool {
  return !empty($_SESSION['user_id']);
}

function user_login_allowed(): bool {
  $lockUntil = (int)($_SESSION['user_login_lock_until'] ?? 0);
  return $lockUntil <= time();
}

function user_login_lock_remaining(): int {
  $lockUntil = (int)($_SESSION['user_login_lock_until'] ?? 0);
  return max(0, $lockUntil - time());
}

function user_login_register_failure(): void {
  $fails = (int)($_SESSION['user_login_failures'] ?? 0) + 1;
  $_SESSION['user_login_failures'] = $fails;
  if ($fails >= 5) {
    $_SESSION['user_login_lock_until'] = time() + 300;
    $_SESSION['user_login_failures'] = 0;
  }
}

function user_login_register_success(): void {
  unset($_SESSION['user_login_failures'], $_SESSION['user_login_lock_until']);
}

function strong_password(string $password): bool {
  if (strlen($password) < 8) return false;
  if (!preg_match('/[A-Z]/', $password)) return false;
  if (!preg_match('/[a-z]/', $password)) return false;
  if (!preg_match('/\d/', $password)) return false;
  return true;
}

function current_user(): ?array {
  if (!is_user_logged_in()) return null;
  return [
    'id' => (int)($_SESSION['user_id'] ?? 0),
    'name' => (string)($_SESSION['user_name'] ?? ''),
    'email' => (string)($_SESSION['user_email'] ?? ''),
    'lecturer_name' => (string)($_SESSION['user_lecturer_name'] ?? ''),
  ];
}

function ensure_user_courses_table(): void {
  static $done = false;
  if ($done) return;
  $done = true;
  try {
    db()->exec("CREATE TABLE IF NOT EXISTS user_courses (
      id INT AUTO_INCREMENT PRIMARY KEY,
      user_id INT NOT NULL,
      course_title VARCHAR(190) NOT NULL,
      instructor VARCHAR(190) DEFAULT NULL,
      schedule_text VARCHAR(190) DEFAULT NULL,
      status VARCHAR(32) NOT NULL DEFAULT 'active',
      created_at DATETIME NOT NULL,
      INDEX idx_user_courses_user_id (user_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
  } catch (Throwable $e) {
    // ignore if DB is unavailable
  }
}

function ensure_user_tasks_table(): void {
  static $done = false;
  if ($done) return;
  $done = true;
  try {
    db()->exec("CREATE TABLE IF NOT EXISTS user_tasks (
      id INT AUTO_INCREMENT PRIMARY KEY,
      user_id INT NOT NULL,
      task_title VARCHAR(190) NOT NULL,
      due_at DATETIME DEFAULT NULL,
      status VARCHAR(32) NOT NULL DEFAULT 'todo',
      created_at DATETIME NOT NULL,
      INDEX idx_user_tasks_user_id (user_id),
      INDEX idx_user_tasks_due_at (due_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
  } catch (Throwable $e) {
    // ignore if DB is unavailable
  }
}

function ensure_user_notifications_table(): void {
  static $done = false;
  if ($done) return;
  $done = true;
  try {
    db()->exec("CREATE TABLE IF NOT EXISTS user_notifications (
      id INT AUTO_INCREMENT PRIMARY KEY,
      user_id INT NOT NULL,
      message VARCHAR(255) NOT NULL,
      level VARCHAR(32) NOT NULL DEFAULT 'info',
      is_read TINYINT(1) NOT NULL DEFAULT 0,
      created_at DATETIME NOT NULL,
      INDEX idx_user_notifications_user_id (user_id),
      INDEX idx_user_notifications_is_read (is_read)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
  } catch (Throwable $e) {
    // ignore if DB is unavailable
  }
}


function ensure_user_lecturers_table(): void {
  static $done = false;
  if ($done) return;
  $done = true;
  try {
    db()->exec("CREATE TABLE IF NOT EXISTS user_lecturers (
      id INT AUTO_INCREMENT PRIMARY KEY,
      user_id INT NOT NULL,
      lecturer_name VARCHAR(190) NOT NULL,
      department VARCHAR(190) DEFAULT NULL,
      email VARCHAR(190) DEFAULT NULL,
      office_room VARCHAR(64) DEFAULT NULL,
      office_hours VARCHAR(190) DEFAULT NULL,
      created_at DATETIME NOT NULL,
      INDEX idx_user_lecturers_user_id (user_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
  } catch (Throwable $e) {
    // ignore if DB is unavailable
  }
}

function seed_user_dashboard_data(int $userId): void {
  ensure_user_courses_table();
  ensure_user_tasks_table();
  ensure_user_notifications_table();
  ensure_user_lecturers_table();
  try {
    $stmt = db()->prepare('SELECT COUNT(*) FROM user_courses WHERE user_id=?');
    $stmt->execute([$userId]);
    $hasCourses = (int)$stmt->fetchColumn() > 0;
    if (!$hasCourses) {
      $now = date('Y-m-d H:i:s');
      $courseStmt = db()->prepare('INSERT INTO user_courses (user_id, course_title, instructor, schedule_text, status, created_at) VALUES (?, ?, ?, ?, ?, ?)');
      $courseStmt->execute([$userId, 'Academic Writing', 'Prof. N. Beridze', 'Mon / Wed 10:00', 'active', $now]);
      $courseStmt->execute([$userId, 'Computer Science Basics', 'Prof. G. Gogelia', 'Tue / Thu 13:00', 'active', $now]);

      $taskStmt = db()->prepare('INSERT INTO user_tasks (user_id, task_title, due_at, status, created_at) VALUES (?, ?, ?, ?, ?)');
      $taskStmt->execute([$userId, 'Submit assignment #2', date('Y-m-d H:i:s', strtotime('+4 days')), 'todo', $now]);
      $taskStmt->execute([$userId, 'Prepare lab report', date('Y-m-d H:i:s', strtotime('+7 days')), 'todo', $now]);

      $notifStmt = db()->prepare('INSERT INTO user_notifications (user_id, message, level, is_read, created_at) VALUES (?, ?, ?, 0, ?)');
      $notifStmt->execute([$userId, 'Welcome to the secure student dashboard.', 'success', $now]);
      $notifStmt->execute([$userId, 'Remember to complete your profile information.', 'info', $now]);

      $lecturerStmt = db()->prepare('INSERT INTO user_lecturers (user_id, lecturer_name, department, email, office_room, office_hours, created_at) VALUES (?, ?, ?, ?, ?, ?, ?)');
      $lecturerStmt->execute([$userId, 'Prof. N. Beridze', 'Humanities', 'n.beridze@spg.local', 'B-204', 'Mon 12:00-14:00', $now]);
      $lecturerStmt->execute([$userId, 'Assoc. Prof. G. Gogelia', 'Computer Science', 'g.gogelia@spg.local', 'C-310', 'Thu 11:00-13:00', $now]);
    }
  } catch (Throwable $e) {
    // ignore if DB is unavailable
  }
}

function get_user_courses(int $userId): array {
  ensure_user_courses_table();
  try {
    $stmt = db()->prepare('SELECT course_title, instructor, schedule_text, status FROM user_courses WHERE user_id=? ORDER BY id DESC');
    $stmt->execute([$userId]);
    return $stmt->fetchAll();
  } catch (Throwable $e) {
    return [];
  }
}

function get_user_tasks(int $userId): array {
  ensure_user_tasks_table();
  try {
    $stmt = db()->prepare('SELECT task_title, due_at, status FROM user_tasks WHERE user_id=? ORDER BY (due_at IS NULL), due_at ASC, id DESC');
    $stmt->execute([$userId]);
    return $stmt->fetchAll();
  } catch (Throwable $e) {
    return [];
  }
}

function get_user_notifications(int $userId): array {
  ensure_user_notifications_table();
  try {
    $stmt = db()->prepare('SELECT id, message, level, is_read, created_at FROM user_notifications WHERE user_id=? ORDER BY created_at DESC, id DESC LIMIT 8');
    $stmt->execute([$userId]);
    return $stmt->fetchAll();
  } catch (Throwable $e) {
    return [];
  }
}


function get_user_lecturers(int $userId): array {
  ensure_user_lecturers_table();
  try {
    $stmt = db()->prepare('SELECT lecturer_name, department, email, office_room, office_hours FROM user_lecturers WHERE user_id=? ORDER BY id DESC');
    $stmt->execute([$userId]);
    return $stmt->fetchAll();
  } catch (Throwable $e) {
    return [];
  }
}



function normalize_lecturer_name(string $name): string {
  $name = trim(preg_replace('/\s+/', ' ', $name) ?? '');
  return mb_substr($name, 0, 190);
}

function list_available_lecturers(): array {
  ensure_user_lecturers_table();
  try {
    $rows = db()->query("SELECT DISTINCT lecturer_name FROM user_lecturers WHERE lecturer_name<>'' ORDER BY lecturer_name ASC")->fetchAll();
    return array_values(array_filter(array_map(fn($r) => trim((string)($r['lecturer_name'] ?? '')), $rows)));
  } catch (Throwable $e) {
    return [];
  }
}

function get_lecturer_students(string $lecturerName): array {
  ensure_users_table();
  $lecturerName = trim($lecturerName);
  if ($lecturerName === '') return [];
  try {
    $stmt = db()->prepare('SELECT id, full_name, email, created_at FROM users WHERE lecturer_name=? ORDER BY full_name ASC, id DESC');
    $stmt->execute([$lecturerName]);
    return $stmt->fetchAll();
  } catch (Throwable $e) {
    return [];
  }
}

/** Helpers for news */
function fmt_date_dmY(string $datetime): string {
  $t = strtotime($datetime);
  return $t ? date('d.m.Y', $t) : '';
}

function get_news_posts(int $limit = 50): array {
  try {
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
  } catch (Throwable $e) {
    return [];
  }

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
  try {
    $stmt = db()->prepare("SELECT * FROM news_posts WHERE id=? AND is_published=1 LIMIT 1");
    $stmt->execute([$id]);
    $r = $stmt->fetch();
  } catch (Throwable $e) {
    return null;
  }
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
