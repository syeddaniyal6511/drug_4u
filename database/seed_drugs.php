<?php
declare(strict_types=1);

/**
 * Seed sample drugs and stock batches.
 * Safe to re-run: skips drugs that already exist by name.
 *
 * Usage (browser): http://localhost/drug_4u/database/seed_drugs.php
 * Usage (CLI):     php database/seed_drugs.php
 */

function seed_drugs(PDO $pdo): void
{
    $drugs = [
        [
            'name'           => 'Amoxicillin 500mg',
            'basic_unit'     => 1,
            'collective_unit'=> 21,
            'units_per_pack' => 21,
            'age_limit'      => 0,
            'stock' => [
                ['desc' => 'Amoxicillin 500mg Capsules', 'batch' => '20240101', 'qty' => 120, 'buy' => 3.50, 'sell' => 5.99, 'expiry' => '2026-12-31'],
            ],
        ],
        [
            'name'           => 'Paracetamol 500mg',
            'basic_unit'     => 1,
            'collective_unit'=> 32,
            'units_per_pack' => 32,
            'age_limit'      => 0,
            'stock' => [
                ['desc' => 'Paracetamol 500mg Tablets', 'batch' => '20240202', 'qty' => 200, 'buy' => 1.20, 'sell' => 2.49, 'expiry' => '2027-06-30'],
            ],
        ],
        [
            'name'           => 'Ibuprofen 400mg',
            'basic_unit'     => 1,
            'collective_unit'=> 24,
            'units_per_pack' => 24,
            'age_limit'      => 12,
            'stock' => [
                ['desc' => 'Ibuprofen 400mg Tablets', 'batch' => '20240303', 'qty' => 150, 'buy' => 1.80, 'sell' => 3.29, 'expiry' => '2027-03-31'],
            ],
        ],
        [
            'name'           => 'Omeprazole 20mg',
            'basic_unit'     => 1,
            'collective_unit'=> 28,
            'units_per_pack' => 28,
            'age_limit'      => 18,
            'stock' => [
                ['desc' => 'Omeprazole 20mg Gastro-resistant Capsules', 'batch' => '20240404', 'qty' => 80, 'buy' => 4.00, 'sell' => 7.49, 'expiry' => '2026-09-30'],
            ],
        ],
        [
            'name'           => 'Metformin 500mg',
            'basic_unit'     => 1,
            'collective_unit'=> 56,
            'units_per_pack' => 56,
            'age_limit'      => 18,
            'stock' => [
                ['desc' => 'Metformin 500mg Tablets', 'batch' => '20240505', 'qty' => 100, 'buy' => 2.60, 'sell' => 4.99, 'expiry' => '2027-01-31'],
            ],
        ],
        [
            'name'           => 'Cetirizine 10mg',
            'basic_unit'     => 1,
            'collective_unit'=> 30,
            'units_per_pack' => 30,
            'age_limit'      => 6,
            'stock' => [
                ['desc' => 'Cetirizine 10mg Tablets', 'batch' => '20240606', 'qty' => 90, 'buy' => 2.00, 'sell' => 3.79, 'expiry' => '2027-08-31'],
            ],
        ],
        [
            'name'           => 'Atorvastatin 20mg',
            'basic_unit'     => 1,
            'collective_unit'=> 28,
            'units_per_pack' => 28,
            'age_limit'      => 18,
            'stock' => [
                ['desc' => 'Atorvastatin 20mg Tablets', 'batch' => '20240707', 'qty' => 60, 'buy' => 5.50, 'sell' => 9.99, 'expiry' => '2026-11-30'],
            ],
        ],
    ];

    $checkDrug = $pdo->prepare('SELECT drugID FROM drug WHERE name = :name LIMIT 1');
    $insDrug   = $pdo->prepare('
        INSERT INTO drug (name, basic_unit, collective_unit, no_of_basic_units_in_collective_unit, age_limit)
        VALUES (:name, :basic_unit, :collective_unit, :units_per_pack, :age_limit)
    ');
    $insStock  = $pdo->prepare('
        INSERT INTO stock (drugID, name, quantity, batch_number, buying_price_per_pack, selling_price_per_pack, expiry_date)
        VALUES (:drugID, :name, :qty, :batch, :buy, :sell, :expiry)
    ');

    foreach ($drugs as $d) {
        $checkDrug->execute([':name' => $d['name']]);
        if ($checkDrug->fetch(PDO::FETCH_ASSOC)) continue;

        $insDrug->execute([
            ':name'           => $d['name'],
            ':basic_unit'     => $d['basic_unit'],
            ':collective_unit'=> $d['collective_unit'],
            ':units_per_pack' => $d['units_per_pack'],
            ':age_limit'      => $d['age_limit'],
        ]);
        $drugID = (int)$pdo->lastInsertId();

        foreach ($d['stock'] as $s) {
            $insStock->execute([
                ':drugID' => $drugID,
                ':name'   => $s['desc'],
                ':qty'    => $s['qty'],
                ':batch'  => $s['batch'],
                ':buy'    => $s['buy'],
                ':sell'   => $s['sell'],
                ':expiry' => $s['expiry'],
            ]);
        }
    }
}

// Run directly when executed as a standalone script
if (basename(__FILE__) === basename($_SERVER['SCRIPT_FILENAME'] ?? '')) {
    require_once __DIR__ . '/connect_db.php';
    try {
        seed_drugs($objPdo);
        echo "Drugs seeded.\n";
    } catch (Throwable $e) {
        http_response_code(500);
        echo "Seeder failed: " . $e->getMessage() . "\n";
        exit(1);
    }
}
