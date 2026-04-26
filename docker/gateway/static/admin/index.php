<?php
/**
 * NetworkMemories — MGO2 Admin Panel
 * Access: http://YOUR_SERVER_IP/admin/
 * Auth via env: ADMIN_USER / ADMIN_PASSWORD
 */
session_start();

$admin_user = getenv('ADMIN_USER') ?: 'admin';
$admin_pass = getenv('ADMIN_PASSWORD') ?: '';
$db_host    = getenv('MYSQL_HOST') ?: 'nomad-mysql';
$db_name    = getenv('MYSQL_DATABASE') ?: 'nomad';
$db_user    = getenv('MYSQL_USER') ?: '';
$db_pass    = getenv('MYSQL_PASSWORD') ?: '';
$server_ip  = getenv('SERVER_IP') ?: '—';

if (isset($_POST['logout'])) { session_destroy(); header('Location: /admin/'); exit; }

if (!isset($_SESSION['auth'])) {
    if (isset($_POST['user'], $_POST['pass'])
        && $_POST['user'] === $admin_user
        && hash_equals(hash('sha256', $admin_pass), hash('sha256', $_POST['pass']))) {
        $_SESSION['auth'] = true;
        header('Location: /admin/');
        exit;
    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $error = 'Invalid credentials.';
    }
    include __DIR__ . '/views/login.php'; exit;
}

try {
    $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8mb4",
        $db_user, $db_pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
} catch (PDOException $e) { $db_error = $e->getMessage(); $pdo = null; }

// --- Actions ---
$message = '';
if ($pdo && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['account_id'])) {
    $aid = (int)$_POST['account_id'];
    switch ($_POST['action']) {
        case 'kick':
            $pdo->prepare("DELETE FROM sessions WHERE account_id=?")->execute([$aid]);
            $message = "Account #$aid kicked (session cleared)."; break;
        case 'ban':
            $pdo->prepare("UPDATE accounts SET banned=1 WHERE id=?")->execute([$aid]);
            $pdo->prepare("DELETE FROM sessions WHERE account_id=?")->execute([$aid]);
            $message = "Account #$aid banned."; break;
        case 'unban':
            $pdo->prepare("UPDATE accounts SET banned=0 WHERE id=?")->execute([$aid]);
            $message = "Account #$aid unbanned."; break;
        case 'delete':
            $pdo->prepare("DELETE FROM sessions WHERE account_id=?")->execute([$aid]);
            $pdo->prepare("DELETE FROM accounts WHERE id=?")->execute([$aid]);
            $message = "Account #$aid deleted."; break;
    }
}

// --- Stats ---
$stats = [];
if ($pdo) {
    foreach (['accounts','sessions'] as $t) {
        try { $stats[$t] = $pdo->query("SELECT COUNT(*) FROM `$t`")->fetchColumn(); }
        catch (Exception $e) { $stats[$t] = 'N/A'; }
    }
}

// --- Players ---
$players = [];
if ($pdo) {
    try {
        $players = $pdo->query(
            "SELECT a.id, a.username, a.created_at, a.last_login, a.banned,
                    CASE WHEN s.token IS NOT NULL THEN 1 ELSE 0 END as online
             FROM accounts a
             LEFT JOIN sessions s ON s.account_id=a.id AND s.expires_at > NOW()
             ORDER BY a.id DESC LIMIT 100"
        )->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {}
}

include __DIR__ . '/views/dashboard.php';
