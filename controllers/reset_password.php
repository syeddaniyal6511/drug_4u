<?php
header('Content-Type: application/json');

/* ── DB config — replace with your shared config include ── */
define('DB_HOST', 'localhost');
define('DB_NAME', 'clinic_db');
define('DB_USER', 'root');
define('DB_PASS', 'Temitope123.');
define('DB_CHAR', 'utf8mb4');

/* ── Validate request ── */
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit(json_encode(['success' => false, 'message' => 'Method not allowed.']));
}

$body        = json_decode(file_get_contents('php://input'), true);
$raw_token   = trim($body['token']        ?? '');
$new_password = trim($body['new_password'] ?? '');

if (!$raw_token || !$new_password) {
    http_response_code(400);
    exit(json_encode(['success' => false, 'message' => 'Token and new password are required.']));
}

if (strlen($new_password) < 8) {
    http_response_code(400);
    exit(json_encode(['success' => false, 'message' => 'Password must be at least 8 characters.']));
}

/* ── DB connection ── */
try {
    $pdo = new PDO(
        'mysql:host='.DB_HOST.';dbname='.DB_NAME.';charset='.DB_CHAR,
        DB_USER, DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
    );
} catch (PDOException $e) {
    http_response_code(500);
    exit(json_encode(['success' => false, 'message' => 'Database connection failed.']));
}

/* ── Hash the raw token to look it up ── */
$db_token = hash('sha256', $raw_token);

/* ── Find a valid, unused, non-expired token ── */
$stmt = $pdo->prepare(
    'SELECT user_id FROM password_resets
     WHERE token = ? AND used = 0 AND expires_at > NOW()
     LIMIT 1'
);
$stmt->execute([$db_token]);
$row = $stmt->fetch();

if (!$row) {
    http_response_code(400);
    exit(json_encode([
        'success' => false,
        'message' => 'Invalid or expired reset link. Please request a new one.'
    ]));
}

/* ── Hash the new password with bcrypt ── */
$new_hash = password_hash($new_password, PASSWORD_BCRYPT, ['cost' => 12]);

/* ── Update the user's password ── */
$update = $pdo->prepare(
    'UPDATE user_
     SET pwd = :pwd
     WHERE userID = :userID'
);

$update->execute([
    ':pwd'    => $new_hash,
    ':userID' => (int)$row['user_id']
]);

/* Mark token as used */
$pdo->prepare(
    'UPDATE password_resets
     SET used = 1
     WHERE token = :token'
)->execute([':token' => $token]);

echo json_encode([
    'success' => true,
    'message' => 'Password updated successfully'
]);