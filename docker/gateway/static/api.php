<?php
/**
 * NetworkMemories — MGO2 Account API
 * Called by: Nomad Java server + direct PS3 web requests
 *
 * Routes:
 *   POST /account/create-mgo2  → create account
 *   POST /account/login        → authenticate, return session token
 *   GET  /account/check        → validate session token
 *   POST /account/delete       → delete account
 *
 * Account constraints (enforced by MGO2 client — cannot change):
 *   Username: 8-32 chars, lowercase + digits only
 *   Password: 4-16 chars, digits only
 */

header('Content-Type: application/json');

$db_host = getenv('MYSQL_HOST') ?: 'nomad-mysql';
$db_name = getenv('MYSQL_DATABASE') ?: 'nomad';
$db_user = getenv('MYSQL_USER') ?: '';
$db_pass = getenv('MYSQL_PASSWORD') ?: '';

try {
    $pdo = new PDO(
        "mysql:host=$db_host;dbname=$db_name;charset=utf8mb4",
        $db_user, $db_pass,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (PDOException $e) {
    http_response_code(503);
    echo json_encode(['error' => 'DB unavailable']);
    exit;
}

$path   = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$method = $_SERVER['REQUEST_METHOD'];
$body   = json_decode(file_get_contents('php://input'), true) ?: [];
$post   = array_merge($_POST, $body);

// --- Router ---
switch (true) {
    case $method === 'POST' && str_ends_with($path, '/account/create-mgo2'):
        account_create($pdo, $post); break;
    case $method === 'POST' && str_ends_with($path, '/account/login'):
        account_login($pdo, $post); break;
    case $method === 'GET'  && str_ends_with($path, '/account/check'):
        account_check($pdo); break;
    case $method === 'POST' && str_ends_with($path, '/account/delete'):
        account_delete($pdo, $post); break;
    default:
        http_response_code(404);
        echo json_encode(['error' => 'Not found']);
}

// ---------------------------------------------------------------------------

function validate_username(string $u): bool {
    // MGO2 constraint: 8-32 chars, lowercase + digits only
    return (bool)preg_match('/^[a-z0-9]{8,32}$/', $u);
}

function validate_password(string $p): bool {
    // MGO2 constraint: 4-16 chars, digits only
    return (bool)preg_match('/^[0-9]{4,16}$/', $p);
}

function account_create(PDO $pdo, array $post): void {
    $username = trim($post['username'] ?? '');
    $password = trim($post['password'] ?? '');

    if (!validate_username($username)) {
        echo json_encode(['success' => false,
            'error' => 'Username must be 8-32 characters, lowercase and digits only']);
        return;
    }
    if (!validate_password($password)) {
        echo json_encode(['success' => false,
            'error' => 'Password must be 4-16 digits only']);
        return;
    }

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM accounts WHERE username = ?");
    $stmt->execute([$username]);
    if ($stmt->fetchColumn() > 0) {
        echo json_encode(['success' => false, 'error' => 'Username already taken']);
        return;
    }

    $hash = password_hash($password, PASSWORD_BCRYPT);
    $pdo->prepare("INSERT INTO accounts (username, password_hash, created_at) VALUES (?, ?, NOW())")
        ->execute([$username, $hash]);

    error_log("[nomad-api] Created account: $username");
    echo json_encode(['success' => true]);
}

function account_login(PDO $pdo, array $post): void {
    $username = trim($post['username'] ?? '');
    $password = trim($post['password'] ?? '');

    $stmt = $pdo->prepare("SELECT id, password_hash, banned FROM accounts WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user || !password_verify($password, $user['password_hash'])) {
        echo json_encode(['success' => false, 'error' => 'Invalid credentials']);
        return;
    }
    if ($user['banned']) {
        echo json_encode(['success' => false, 'error' => 'Account banned']);
        return;
    }

    // Generate session token
    $token = bin2hex(random_bytes(24));
    $expires = date('Y-m-d H:i:s', strtotime('+24 hours'));
    $pdo->prepare("INSERT INTO sessions (account_id, token, expires_at) VALUES (?, ?, ?)
                   ON DUPLICATE KEY UPDATE token=VALUES(token), expires_at=VALUES(expires_at)")
        ->execute([$user['id'], $token, $expires]);
    $pdo->prepare("UPDATE accounts SET last_login=NOW() WHERE id=?")->execute([$user['id']]);

    echo json_encode(['success' => true, 'token' => $token]);
}

function account_check(PDO $pdo): void {
    $token = $_SERVER['HTTP_X_SESSION_TOKEN'] ?? $_GET['token'] ?? '';
    if (!$token) {
        echo json_encode(['valid' => false]);
        return;
    }

    $stmt = $pdo->prepare(
        "SELECT a.username, a.banned FROM sessions s
         JOIN accounts a ON a.id = s.account_id
         WHERE s.token = ? AND s.expires_at > NOW()"
    );
    $stmt->execute([$token]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row || $row['banned']) {
        echo json_encode(['valid' => false]);
        return;
    }
    echo json_encode(['valid' => true, 'username' => $row['username']]);
}

function account_delete(PDO $pdo, array $post): void {
    $username = trim($post['username'] ?? '');
    $password = trim($post['password'] ?? '');

    $stmt = $pdo->prepare("SELECT id, password_hash FROM accounts WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user || !password_verify($password, $user['password_hash'])) {
        echo json_encode(['success' => false, 'error' => 'Invalid credentials']);
        return;
    }

    $pdo->prepare("DELETE FROM sessions WHERE account_id = ?")->execute([$user['id']]);
    $pdo->prepare("DELETE FROM accounts WHERE id = ?")->execute([$user['id']]);
    error_log("[nomad-api] Deleted account: $username");
    echo json_encode(['success' => true]);
}
