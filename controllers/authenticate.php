<?php
declare(strict_types=1);

session_start();

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    http_response_code(405);
    echo 'Method not allowed';
    exit;
}

$email = trim((string)($_POST['email'] ?? ''));
$password = (string)($_POST['password'] ?? '');

if ($email === '' || $password === '') {
    header('Location: ../pages/login.html?error=required');
    exit;
}

try {
    require_once __DIR__ . '/../database/connect_db.php';

    // Support both 'email' and 'username' column names
    $cols = [];
    $colStmt = $objPdo->query("SHOW COLUMNS FROM user_");
    foreach ($colStmt->fetchAll(PDO::FETCH_COLUMN) as $col) {
        $cols[] = $col;
    }
    $emailCol = in_array('email', $cols, true) ? 'email' : 'username';

    $stmt = $objPdo->prepare(
        "SELECT userID, {$emailCol}, pwd, role FROM user_ WHERE {$emailCol} = :email LIMIT 1"
    );
    $stmt->execute([':email' => $email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        header('Location: ../pages/login.html?error=invalid');
        exit;
    }

    $stored = (string)($user['pwd'] ?? '');
    $ok = password_verify($password, $stored);

    // Backward compatibility: plaintext passwords
    if (!$ok && $stored !== '' && hash_equals($stored, $password)) {
        $ok = true;
        $newHash = password_hash($password, PASSWORD_DEFAULT);
        if ($newHash !== false) {
            $upd = $objPdo->prepare('UPDATE user_ SET pwd = :pwd WHERE userID = :userID');
            $upd->execute([':pwd' => $newHash, ':userID' => (int)$user['userID']]);
        }
    }

    if (!$ok) {
        header('Location: ../pages/login.html?error=invalid');
        exit;
    }

    session_regenerate_id(true);
    $_SESSION['user_id'] = (int)$user['userID'];
    $_SESSION['role']    = (string)($user['role'] ?? '');

    header('Location: ../pages/dashboard.php');
    exit;

} catch (Throwable $e) {
    error_log('authenticate.php error: ' . $e->getMessage());
    header('Location: ../pages/login.html?error=server');
    exit;
}