<?php
#include_once "./connect_db.php";

function new_customer(string $firstname, string $lastname, string $gender, string $dob, int $postcode){
    require __DIR__ . '/connect_db.php';
    try {


            $stmt = $objPdo->prepare("
                INSERT INTO customer (firstname, lastname, gender, dob, postcode)
                VALUES (:firstname, :lastname, :gender, :dob, :postcode)
            ");

            $stmt->execute([
                ':firstname' => $firstname,
                ':lastname'  => $lastname,
                ':gender'    => $gender,
                ':dob'       => $dob,        // YYYY-MM-DD
                ':postcode'  => $postcode,   // stored as digits (string ok for BIGINT)
            ]);

            $success = 'Customer registered successfully with ID: ' . $objPdo->lastInsertId();
            // Reset form fields after success
            $firstname = $lastname = $gender = $dob = $postcode = '';
            // Rotate CSRF token after successful submission
            #$_SESSION['csrf_token'] = bin2hex(random_bytes(32));

        } catch (PDOException $e) {
            // You may want to log $e->getMessage() rather than echo it
            $errors[] = 'Database error: ' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
        }
}

?>
