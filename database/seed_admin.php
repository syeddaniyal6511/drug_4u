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

function seed_admin(PDO $pdo): void
{
    $defaultAdmin = [
        'firstname' => 'System',
        'lastname'  => 'Administrator',
        'dob'       => '1990-01-01',
        'email'     => 'admin@gmail.com',
        'password'  => 'password',
        'role'      => 'admin',
    ];

    $stmt = $pdo->prepare('SELECT userID, role FROM user_ WHERE email = :email LIMIT 1');
    $stmt->execute([':email' => $defaultAdmin['email']]);
    $existing = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($existing) {
        if (($existing['role'] ?? null) !== 'admin') {
            $upd = $pdo->prepare('UPDATE user_ SET role = :role WHERE userID = :userID');
            $upd->execute([
                ':role'   => 'admin',
                ':userID' => (int)$existing['userID'],
            ]);
        }
        return;
    }

    $pwdHash = password_hash($defaultAdmin['password'], PASSWORD_DEFAULT);
    if ($pwdHash === false) {
        throw new RuntimeException('Failed to hash password.');
    }

    $ins = $pdo->prepare('
        INSERT INTO user_ (firstname, lastname, dob, email, pwd, role)
        VALUES (:firstname, :lastname, :dob, :email, :pwd, :role)
    ');
    $ins->execute([
        ':firstname' => $defaultAdmin['firstname'],
        ':lastname'  => $defaultAdmin['lastname'],
        ':dob'       => $defaultAdmin['dob'],
        ':email'     => $defaultAdmin['email'],
        ':pwd'       => $pwdHash,
        ':role'      => $defaultAdmin['role'],
    ]);
}

// Run directly when executed as a standalone script
if (basename(__FILE__) === basename($_SERVER['SCRIPT_FILENAME'] ?? '')) {
    require_once __DIR__ . '/connect_db.php';
    global $objPdo;
    try {
        seed_admin($objPdo);
        echo "Admin seeded. Email: admin@gmail.com / Password: password\n";
    } catch (Throwable $e) {
        http_response_code(500);
        echo "Seeder failed: " . $e->getMessage() . "\n";
        exit(1);
    }
}
