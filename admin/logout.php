<?php
declare(strict_types=1);
require __DIR__ . '/../inc/bootstrap.php';

$_SESSION = [];
session_destroy();

header('Location: ' . url('admin/login.php'));
exit;
