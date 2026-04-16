<?php
declare(strict_types=1);

/**
 * Seed a default admin user into `user_`.
 *
 * Safe to re-run: it will not create duplicates (checks by email).
 *
 * Usage (browser): http://localhost/drug_4u/database/seed_admin.php
 * Usage (CLI): php database/seed_admin.php
 */

require_once __DIR__ . '/connect_db.php';

$defaultAdmin = [
    'firstname' => 'System',
    'lastname'  => 'Administrator',
    'dob'       => '1990-01-01',
    'email'  => 'admin@gmail.com',
    // Change this after first login.
    'password'  => 'password',
    'role'      => 'admin',
];

try {
    // Ensure `user_` exists (connect_db.php runs schema).

    $stmt = $objPdo->prepare('SELECT userID, role FROM user_ WHERE email = :email LIMIT 1');
    $stmt->execute([':email' => $defaultAdmin['email']]);
    $existing = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($existing) {
        // Keep it idempotent: do not overwrite password on re-run.
        // But do ensure role is admin (helpful if it was created as another role).
        if (($existing['role'] ?? null) !== 'admin') {
            $upd = $objPdo->prepare('UPDATE user_ SET role = :role WHERE userID = :userID');
            $upd->execute([
                ':role' => 'admin',
                ':userID' => (int)$existing['userID'],
            ]);
        }

        echo "Default admin already exists (email: {$defaultAdmin['email']}).\n";
        exit(0);
    }

    $pwdHash = password_hash($defaultAdmin['password'], PASSWORD_DEFAULT);
    if ($pwdHash === false) {
        throw new RuntimeException('Failed to hash password.');
    }

    $ins = $objPdo->prepare('
        INSERT INTO user_ (firstname, lastname, dob, email, pwd, role)
        VALUES (:firstname, :lastname, :dob, :email, :pwd, :role)
    ');
    $ins->execute([
        ':firstname' => $defaultAdmin['firstname'],
        ':lastname'  => $defaultAdmin['lastname'],
        ':dob'       => $defaultAdmin['dob'],
        ':email'  => $defaultAdmin['email'],
        ':pwd'       => $pwdHash,
        ':role'      => $defaultAdmin['role'],
    ]);

    echo "Seeded default admin user.\n";
    echo "Username: {$defaultAdmin['email']}\n";
    echo "Password: {$defaultAdmin['password']}\n";
} catch (Throwable $e) {
    http_response_code(500);
    echo "Seeder failed: " . $e->getMessage() . "\n";
    exit(1);
}

