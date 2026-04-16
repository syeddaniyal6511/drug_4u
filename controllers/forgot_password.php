<?php


header('Content-Type: application/json');

require_once __DIR__ . '/../vendor/autoload.php'; // Composer / PHPMailer

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

/* ── DB config — replace with your shared config include ── */
define('DB_HOST', 'localhost');
define('DB_NAME', 'clinic_db');
define('DB_USER', 'root');
define('DB_PASS', 'Temitope123.');
define('DB_CHAR', 'utf8mb4');

/* ── Mail config — use your SMTP credentials ── */
define('MAIL_HOST',     'smtp.gmail.com');      // or your SMTP host
define('MAIL_PORT',     587);
define('MAIL_USERNAME', 'sdaniyal1971@gmail.com');
define('MAIL_PASSWORD', 'exqecifkhvmxfchk');   // Gmail: use App Password, not account password
define('MAIL_FROM',     'your@email.com');
define('MAIL_FROMNAME', 'drug_4u');

/* ── App base URL — used to build the reset link ── */
define('APP_URL', 'http://localhost/drug_4u');

define('TOKEN_EXPIRE_SECONDS', 3600); // 1 hour

/* ── Validate request ── */
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit(json_encode(['success' => false, 'message' => 'Method not allowed.']));
}

$body       = json_decode(file_get_contents('php://input'), true);
$identifier = trim($body['identifier'] ?? '');

if (!$identifier) {
    http_response_code(400);
    exit(json_encode(['success' => false, 'message' => 'Please enter your email or email.']));
}

/* ── Generic success message (sent regardless of whether user exists) ── */
$generic_ok = ['success' => true, 'message' => 'If that account exists, a reset link has been sent to the registered email address.'];

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

/* ── Look up user by email OR email ── */
$stmt = $pdo->prepare(
    'SELECT userID, email FROM user_ WHERE email = ? OR email = ? LIMIT 1'
);
$stmt->execute([$identifier, $identifier]);
$user = $stmt->fetch();

if (!$user || !$user['email']) {
    // User not found — still return generic OK to prevent enumeration
    exit(json_encode($generic_ok));
}

/* ── Generate token ── */
$raw_token  = bin2hex(random_bytes(32));            // 64-char hex, sent in email URL
$db_token   = hash('sha256', $raw_token);           // store only the hash in DB
$expires_at = date('Y-m-d H:i:s', time() + TOKEN_EXPIRE_SECONDS);

/* ── Delete any existing reset tokens for this user ── */
$pdo->prepare('DELETE FROM password_resets WHERE user_id = ?')->execute([$user['userID']]);

/* ── Insert new token ── */
$ins = $pdo->prepare(
    'INSERT INTO password_resets (user_id, token, expires_at) VALUES (?, ?, ?)'
);
$ins->execute([$user['userID'], $db_token, $expires_at]);

/* ── Build reset link ── */
$reset_link = APP_URL . '/pages/reset_password.html?token=' . urlencode($raw_token);

/* ── Send email via PHPMailer ── */
try {
    $mail = new PHPMailer(true);

    $mail->isSMTP();
    $mail->Host       = MAIL_HOST;
    $mail->SMTPAuth   = true;
    $mail->Username   = MAIL_USERNAME;
    $mail->Password   = MAIL_PASSWORD;
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = MAIL_PORT;

    $mail->setFrom(MAIL_FROM, MAIL_FROMNAME);
    $mail->addAddress($user['email']);

    $mail->isHTML(true);
    $mail->Subject = 'Reset your password — Pharmacy System';
    $mail->Body    = "
        <div style='font-family:sans-serif;max-width:480px;margin:auto;padding:32px;'>
            <h2 style='margin-bottom:8px;'>Password reset request</h2>
            <p style='color:#6b7280;margin-bottom:24px;'>
                We received a request to reset your password.
                Click the button below — this link expires in 1 hour.
            </p>
            <a href='{$reset_link}'
               style='display:inline-block;padding:12px 24px;background:#4f8ef7;
                      color:#fff;text-decoration:none;border-radius:8px;font-weight:500;'>
                Reset password
            </a>
            <p style='color:#6b7280;margin-top:24px;font-size:13px;'>
                If you did not request this, you can safely ignore this email.<br>
                <a href='{$reset_link}' style='color:#4f8ef7;'>{$reset_link}</a>
            </p>
        </div>
    ";
    $mail->AltBody = "Reset your password: {$reset_link}\n\nThis link expires in 1 hour. If you did not request this, ignore this email.";

    $mail->send();

} catch (Exception $e) {
    // Log internally but don't expose mail errors to the client
    error_log('PHPMailer error: ' . $mail->ErrorInfo);
    // Still return generic OK — token is saved, user can retry
}

echo json_encode($generic_ok);
