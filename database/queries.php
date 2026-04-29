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


function new_drug(string $name, string $basic_unit, string $collective_unit, float $no_of_basic_units_in_collective_unit, int $age_limit): array {
    require __DIR__ . '/connect_db.php';
    try {
        $stmt = $objPdo->prepare("INSERT INTO drug
                        (name, basic_unit, collective_unit, no_of_basic_units_in_collective_unit, age_limit)
                      VALUES (:name, :basic_unit, :collective_unit, :no_of_basic_units_in_collective_unit, :age_limit)");
        $stmt->execute([
            ':name'                                => $name,
            ':basic_unit'                          => $basic_unit,
            ':collective_unit'                     => $collective_unit,
            ':no_of_basic_units_in_collective_unit' => $no_of_basic_units_in_collective_unit,
            ':age_limit'                           => $age_limit,
        ]);
        return ['success' => true, 'drugID' => (int)$objPdo->lastInsertId()];
    } catch (PDOException $e) {
        return ['success' => false, 'error' => 'Database error: ' . $e->getMessage()];
    }
}

function get_today_orders(): array {
    require __DIR__ . '/connect_db.php';
    try {
        $stmt = $objPdo->query("
            SELECT o.orderID, o.status, o.created_at,
                   c.customerID, c.firstname, c.lastname,
                   d.name AS drug_name,
                   oi.order_itemID, oi.price AS item_price
            FROM order_ o
            JOIN customer c    ON o.customerID  = c.customerID
            JOIN order_item oi ON o.orderID     = oi.orderID
            JOIN drug d        ON oi.drugID     = d.drugID
            WHERE DATE(o.created_at) = CURDATE()
            ORDER BY o.orderID DESC, oi.order_itemID ASC
        ");
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $orders = [];
        foreach ($rows as $row) {
            $oid = $row['orderID'];
            if (!isset($orders[$oid])) {
                $orders[$oid] = [
                    'orderID'    => $oid,
                    'status'     => $row['status'],
                    'created_at' => $row['created_at'],
                    'customer'   => $row['firstname'] . ' ' . $row['lastname'],
                    'customerID' => $row['customerID'],
                    'items'      => [],
                ];
            }
            $orders[$oid]['items'][] = [
                'name'  => $row['drug_name'],
                'price' => (float)$row['item_price'],
            ];
        }
        return array_values($orders);
    } catch (PDOException $e) {
        return [];
    }
}

function update_order_status(int $orderID, string $status): array {
    require __DIR__ . '/connect_db.php';
    if (!in_array($status, ['pending', 'paid', 'cancelled'], true)) {
        return ['success' => false, 'error' => 'Invalid status.'];
    }
    try {
        $stmt = $objPdo->prepare("UPDATE order_ SET status = :status WHERE orderID = :orderID");
        $stmt->execute([':status' => $status, ':orderID' => $orderID]);
        return ['success' => true];
    } catch (PDOException $e) {
        return ['success' => false, 'error' => 'Database error: ' . $e->getMessage()];
    }
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


/**
 * Returns an array of orders (with nested items) for a given customer.
 */
function presc_history(int $customerID): array {
    require __DIR__ . '/connect_db.php';

    try {
        $stmt = $objPdo->prepare("
            SELECT o.orderID, o.status, o.created_at,
                   c.firstname, c.lastname,
                   d.name AS drug_name, oi.price AS item_price
            FROM order_ o
            JOIN customer c  ON o.customerID  = c.customerID
            JOIN order_item oi ON o.orderID   = oi.orderID
            JOIN drug d      ON oi.drugID     = d.drugID
            WHERE c.customerID = :customerID
            ORDER BY o.orderID DESC, oi.order_itemID ASC
        ");
        $stmt->execute([':customerID' => $customerID]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $orders = [];
        foreach ($rows as $row) {
            $oid = $row['orderID'];
            if (!isset($orders[$oid])) {
                $orders[$oid] = [
                    'orderID'    => $oid,
                    'status'     => $row['status'],
                    'created_at' => $row['created_at'],
                    'customer'   => $row['firstname'] . ' ' . $row['lastname'],
                    'items'      => [],
                ];
            }
            $orders[$oid]['items'][] = [
                'name'  => $row['drug_name'],
                'price' => $row['item_price'],
            ];
        }
        return array_values($orders);

    } catch (PDOException $e) {
        return [];
    }
}

function get_all_customers(): array {
    require __DIR__ . '/connect_db.php';
    try {
        $stmt = $objPdo->query("
            SELECT c.customerID, c.firstname, c.lastname, c.gender, c.dob, c.postcode,
                   GROUP_CONCAT(a.description SEPARATOR '; ') AS allergies
            FROM customer c
            LEFT JOIN allergies a ON c.customerID = a.customerID
            GROUP BY c.customerID
            ORDER BY c.customerID ASC
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return [];
    }
}

function get_all_drugs(): array {
    require __DIR__ . '/connect_db.php';
    $stmt = $objPdo->prepare("SELECT drugID, name FROM drug ORDER BY name ASC");
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function get_all_stock(): array {
    require __DIR__ . '/connect_db.php';
    try {
        $stmt = $objPdo->query("
            SELECT s.stockID, d.name AS drug_name, s.name AS stock_name,
                   s.batch_number, s.quantity, s.buying_price_per_pack,
                   s.selling_price_per_pack, s.expiry_date
            FROM stock s
            JOIN drug d ON s.drugID = d.drugID
            ORDER BY s.expiry_date ASC, s.stockID ASC
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return [];
    }
}

function new_stock(int $drugID, string $name, int $quantity, string $batch_number, float $buying_price, float $selling_price, string $expiry_date): array {
    require __DIR__ . '/connect_db.php';
    try {
        $stmt = $objPdo->prepare("INSERT INTO stock
            (drugID, name, quantity, batch_number, buying_price_per_pack, selling_price_per_pack, expiry_date)
            VALUES (:drugID, :name, :quantity, :batch_number, :buying_price, :selling_price, :expiry_date)");
        $stmt->execute([
            ':drugID'        => $drugID,
            ':name'          => $name,
            ':quantity'      => $quantity,
            ':batch_number'  => $batch_number,
            ':buying_price'  => $buying_price,
            ':selling_price' => $selling_price,
            ':expiry_date'   => $expiry_date,
        ]);
        return ['success' => true, 'stockID' => (int)$objPdo->lastInsertId()];
    } catch (PDOException $e) {
        return ['success' => false, 'error' => 'Database error: ' . $e->getMessage()];
    }
}

/**
 * Returns an array of all orders (with nested items) across all customers.
 */
function all_presc_history(): array {
    require __DIR__ . '/connect_db.php';

    try {
        $stmt = $objPdo->prepare("
            SELECT o.orderID, o.status, o.created_at,
                   c.firstname, c.lastname,
                   d.name AS drug_name, oi.price AS item_price
            FROM order_ o
            JOIN customer c  ON o.customerID  = c.customerID
            JOIN order_item oi ON o.orderID   = oi.orderID
            JOIN drug d      ON oi.drugID     = d.drugID
            ORDER BY o.orderID DESC, oi.order_itemID ASC
        ");
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $orders = [];
        foreach ($rows as $row) {
            $oid = $row['orderID'];
            if (!isset($orders[$oid])) {
                $orders[$oid] = [
                    'orderID'    => $oid,
                    'status'     => $row['status'],
                    'created_at' => $row['created_at'],
                    'customer'   => $row['firstname'] . ' ' . $row['lastname'],
                    'items'      => [],
                ];
            }
            $orders[$oid]['items'][] = [
                'name'  => $row['drug_name'],
                'price' => $row['item_price'],
            ];
        }
        return array_values($orders);

    } catch (PDOException $e) {
        return [];
    }
}
?>
