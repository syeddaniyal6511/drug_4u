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
    require __DIR__ . '/connect_db.php';
    $stmt = $objPdo->prepare("INSERT INTO drug 
                    (name, basic_unit, collective_unit, no_of_basic_units_in_collective_unit, age_limit)
                  VALUES (:name, :basic_unit, :collective_unit, :no_of_basic_units_in_collective_unit, :age_limit)");

    return $stmt->execute([
            ':name'=>$name,
            ':basic_unit'=>$basic_unit,
            ':collective_unit'=>$collective_unit,
            ':no_of_basic_units_in_collective_unit'=>$no_of_basic_units_in_collective_unit,
            ':age_limit'=>$age_limit
    ]);
        

        
}

function new_order(int $customerID, int $userID, array $items, string $status = 'pending'){
    // items: array of ['drugID' => int, 'price' => float]
    require __DIR__ . '/connect_db.php';
    try {
        // Start transaction
        $objPdo->beginTransaction();

        // validate status server-side
        $allowedStatuses = ['pending','paid','cancelled'];
        if (!in_array($status, $allowedStatuses, true)) {
            $status = 'pending';
        }

        // Insert order (use provided status)
        $stmt = $objPdo->prepare("INSERT INTO order_ (status, created_at, customerID, userID) VALUES (:status, NOW(), :customerID, :userID)");
        $stmt->execute([
            ':status' => $status,
            ':customerID' => $customerID,
            ':userID' => $userID,
        ]);
        $orderId = (int)$objPdo->lastInsertId();

        // Insert order items (store price) and decrement stock quantities for each drug
        $itemStmt = $objPdo->prepare("INSERT INTO order_item (orderID, drugID, price) VALUES (:orderID, :drugID, :price)");
        $selectStockStmt = $objPdo->prepare("SELECT stockID, quantity FROM stock WHERE drugID = :drugID AND quantity > 0 ORDER BY expiry_date ASC, stockID ASC FOR UPDATE");
        $updateStockStmt = $objPdo->prepare("UPDATE stock SET quantity = :quantity WHERE stockID = :stockID");

        foreach ($items as $it) {
            $drugID = isset($it['drugID']) ? (int)$it['drugID'] : 0;
            $quantityNeeded = isset($it['quantity']) ? (int)$it['quantity'] : 0;
            $price = isset($it['price']) && is_numeric($it['price']) ? (float)$it['price'] : 0.0;

            // insert order_item row (keeps existing schema: price stored)
            $itemStmt->execute([
                ':orderID' => $orderId,
                ':drugID' => $drugID,
                ':price' => $price,
            ]);

            // If no drugID or no quantity requested, skip stock update
            if ($drugID <= 0 || $quantityNeeded <= 0) {
                continue;
            }

            // Lock stock rows for this drug to safely decrement quantities
            $selectStockStmt->execute([':drugID' => $drugID]);
            $stockRows = $selectStockStmt->fetchAll(PDO::FETCH_ASSOC);

            $available = 0;
            foreach ($stockRows as $sr) {
                $available += (int)$sr['quantity'];
            }

            if ($available < $quantityNeeded) {
                // not enough stock -> rollback and return error
                throw new PDOException(sprintf('Insufficient stock for drugID %d: requested %d, available %d', $drugID, $quantityNeeded, $available));
            }

            // consume from stock rows (FEFO: earliest expiry first)
            $toConsume = $quantityNeeded;
            foreach ($stockRows as $sr) {
                if ($toConsume <= 0) break;
                $take = min((int)$sr['quantity'], $toConsume);
                $newQty = (int)$sr['quantity'] - $take;
                $updateStockStmt->execute([':quantity' => $newQty, ':stockID' => $sr['stockID']]);
                $toConsume -= $take;
            }
        }

        // Commit transaction
        $objPdo->commit();
        return ['success' => true, 'orderID' => $orderId];
    } catch (PDOException $e) {
        // Rollback on error
        if ($objPdo->inTransaction()) {
            $objPdo->rollBack();
        }
        return ['success' => false, 'error' => 'Database error: ' . $e->getMessage()];
    }
}


function presc_history(int $customerID){
   
    require __DIR__ . '/connect_db.php';

    try {
        // Query all orders with customer & items
        $stmt = $objPdo->prepare("
            SELECT o.orderID, o.status, o.created_at, c.firstname, c.lastname, d.name AS drug_name, oi.price AS item_price 
            FROM order_ o 
            JOIN customer c ON o.customerID = c.customerID 
            JOIN order_item oi ON o.orderID = oi.orderID 
            JOIN drug d ON oi.drugID = d.drugID 
            WHERE c.customerID = :customerID 
            ORDER BY o.orderID DESC, oi.order_itemID ASC;
        ");
        $stmt->execute([
            ':customerID' => $customerID,
        ]);

        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo "<h2>Order History</h2>";

        if (!$rows) {
            echo "No orders found.";
            exit;
        }

        $currentOrder = null;

        foreach ($rows as $row) {

            // When encountering a new order ID → print header
            if ($currentOrder !== $row['orderID']) {
                if ($currentOrder !== null) {
                    echo "</ul></div>"; // Close previous order's items and div
                }

                $currentOrder = $row['orderID'];

                echo "<div class='order'>";
                echo "<h3>Order #{$row['orderID']}</h3>";
                echo "Customer: {$row['firstname']} {$row['lastname']}<br>";
                echo "Status: {$row['status']}<br>";
                echo "Created: {$row['created_at']}<br>";
                echo "<strong>Items:</strong>";
                echo "<ul>";
            }

            // Print each item
            echo "<li>{$row['drug_name']} — £" . number_format($row['item_price'], 2) . "</li>";
        }

        echo "</ul></div>"; // Close last order item list and div

    } catch (PDOException $e) {
        echo "<p>Error fetching order history: " . htmlspecialchars($e->getMessage()) . "</p>";
    }

}

function get_all_customers() {
    require __DIR__ . '/connect_db.php';

    $stmt = $objPdo->prepare("SELECT customerID, firstname, lastname FROM customer ORDER BY firstname ASC");
    $stmt->execute();

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function all_presc_history(){
   
    require __DIR__ . '/connect_db.php';

    try {
        // Query all orders with customer & items
        $stmt = $objPdo->prepare("
            SELECT o.orderID, o.status, o.created_at, c.firstname, c.lastname, d.name AS drug_name, oi.price AS item_price 
            FROM order_ o 
            JOIN customer c ON o.customerID = c.customerID 
            JOIN order_item oi ON o.orderID = oi.orderID 
            JOIN drug d ON oi.drugID = d.drugID 
            ORDER BY o.orderID DESC, oi.order_itemID ASC;
        ");
        $stmt->execute();

        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo "<h2>Order History</h2>";

        if (!$rows) {
            echo "No orders found.";
            exit;
        }

        $currentOrder = null;

        foreach ($rows as $row) {

            // When encountering a new order ID → print header
            if ($currentOrder !== $row['orderID']) {
                if ($currentOrder !== null) {
                    echo "</ul></div>"; // Close previous order's items and div
                }

                $currentOrder = $row['orderID'];

                echo "<div class='order'>";
                echo "<h3>Order #{$row['orderID']}</h3>";
                echo "Customer: {$row['firstname']} {$row['lastname']}<br>";
                echo "Status: {$row['status']}<br>";
                echo "Created: {$row['created_at']}<br>";
                echo "<strong>Items:</strong>";
                echo "<ul>";
            }

            // Print each item
            echo "<li>{$row['drug_name']} — £" . number_format($row['item_price'], 2) . "</li>";
        }

        echo "</ul></div>"; // Close last order item list and div

    } catch (PDOException $e) {
        echo "<p>Error fetching order history: " . htmlspecialchars($e->getMessage()) . "</p>";
    }

}
?>
