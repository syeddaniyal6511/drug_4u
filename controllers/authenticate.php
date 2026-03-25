<?php
declare(strict_types=1);

session_start();

require_once __DIR__ . '/../database/connect_db.php';

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    http_response_code(405);
    echo 'Method not allowed';
    exit;
}

$username = trim((string)($_POST['username'] ?? ''));
$password = (string)($_POST['password'] ?? '');

if ($username === '' || $password === '') {
    echo 'Username and password are required';
    exit;
}

try {
    $stmt = $objPdo->prepare('SELECT userID, username, pwd, role FROM user_ WHERE username = :username LIMIT 1');
    $stmt->execute([':username' => $username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        echo 'User not found';
        exit;
    }

    $stored = (string)($user['pwd'] ?? '');
    $ok = password_verify($password, $stored);

    // Backward compatibility if old rows stored plaintext passwords:
    if (!$ok && $stored !== '' && hash_equals($stored, $password)) {
        $ok = true;
        $newHash = password_hash($password, PASSWORD_DEFAULT);
        if ($newHash !== false) {
            $upd = $objPdo->prepare('UPDATE user_ SET pwd = :pwd WHERE userID = :userID');
            $upd->execute([':pwd' => $newHash, ':userID' => (int)$user['userID']]);
        }
    }

    if (!$ok) {
        echo 'Invalid password';
        exit;
    }

    session_regenerate_id(true);
    $_SESSION['user_id'] = (int)$user['userID'];
    $_SESSION['role'] = (string)($user['role'] ?? '');

    header('Location: ../pages/dashboard.php');
    exit;
} catch (Throwable $e) {
    http_response_code(500);
    echo 'Login failed';
    exit;
}