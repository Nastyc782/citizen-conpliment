<?php if (!isset($_SESSION[\
user\]) || !isAdmin()) { header(\Location:
/login\); exit(); } require_once __DIR__ . \/../../includes/header.php\; ?>
