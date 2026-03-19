<?php
#include_once "./connect_db.php";

/**
 * Insert a new customer and optional allergies.
 * Returns an array with success true and customerID on success,
 * or success false and error message on failure.
 */
function new_customer(string $firstname, string $lastname, string $gender, string $dob, $postcode, array $allergies = []){
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

            $customerId = (int)$objPdo->lastInsertId();

            // If allergies were provided, insert them into the allergies table
            if (!empty($allergies)) {
                $insertAllergy = $objPdo->prepare("INSERT INTO allergies (drugID, description, customerID) VALUES (:drugID, :description, :customerID)");
                foreach ($allergies as $desc) {
                    // store as NULL drugID unless provided; description is user-provided text
                    $insertAllergy->execute([
                        ':drugID' => null,
                        ':description' => $desc,
                        ':customerID' => $customerId,
                    ]);
                }
            }

            return ['success' => true, 'customerID' => $customerId];

    } catch (PDOException $e) {
        // Return structured error so caller can display it
        return ['success' => false, 'error' => 'Database error: ' . $e->getMessage()];
    }
}

/**
 * Convenience: insert a single allergy record.
 */
function insert_allergy(int $customerID, string $description, $drugID = null)
{
    require __DIR__ . '/connect_db.php';
    $stmt = $objPdo->prepare("INSERT INTO allergies (drugID, description, customerID) VALUES (:drugID, :description, :customerID)");
    return $stmt->execute([
        ':drugID' => $drugID,
        ':description' => $description,
        ':customerID' => $customerID,
    ]);
}


function new_drug(string $name,int $basic_unit, int $collective_unit, float $no_of_basic_units_in_collective_unit, int $age_limit){
    $query = "INSERT INTO drug 
                    (name, basic_unit, collective_unit, no_of_basic_units_in_collective_unit, age_limit)
                  VALUES (?, ?, ?, ?, ?)";

        $params = [
            $name,
            $basic_unit,
            $collective_unit,
            $no_of_basic_units_in_collective_unit,
            $age_limit
        ];

        if (executeQuery($query, $params)) {
            $success = "Drug added successfully!";
        } else {
            $errors[] = "Database error: failed to insert drug.";
        }
}

?>
