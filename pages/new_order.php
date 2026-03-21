<?php
// new_order.php
// Page to register cashier orders matching the order_ and order_item tables

// include DB helpers
include '../database/queries.php';
include '../database/connect_db.php';

// simple helpers
function e($v) { return htmlspecialchars($v ?? '', ENT_QUOTES, 'UTF-8'); }
$errors = [];
$success = null;


// fetch customers and users for selects
$customers = [];
$users = [];
try {
    $cstmt = $objPdo->query("SELECT customerID, firstname, lastname FROM customer ORDER BY firstname, lastname");
    $customers = $cstmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $ex) {
    // ignore; leave empty list
}
// fetch drugs with a representative selling price and aggregated stock for item selects
$drugs = [];
$drugPrices = [];
try {
    $dstmt = $objPdo->query("SELECT d.drugID, d.name, COALESCE(MIN(s.selling_price_per_pack), 0) AS selling_price, COALESCE(SUM(s.quantity), 0) AS stock_qty FROM drug d LEFT JOIN stock s ON d.drugID = s.drugID GROUP BY d.drugID ORDER BY d.name");
    $drugs = $dstmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($drugs as $dd) {
        $drugPrices[(int)$dd['drugID']] = (float)$dd['selling_price'];
    }
} catch (Exception $ex) {
    // ignore
}
// build options HTML for JS template including data attributes (price and stock)
$drugOptionsHtml = '';
foreach ($drugs as $dd) {
    $id = htmlspecialchars($dd['drugID'], ENT_QUOTES, 'UTF-8');
    $name = htmlspecialchars($dd['name'], ENT_QUOTES, 'UTF-8');
    $price = number_format((float)$dd['selling_price'], 2, '.', '');
    $stock = (int)$dd['stock_qty'];
    $drugOptionsHtml .= '<option value="' . $id . '" data-price="' . $price . '" data-stock="' . $stock . '">' . $name . ' — $' . $price . ' — stock: ' . $stock . '</option>' . "\n";
}
try {
    $ustmt = $objPdo->query("SELECT userID, firstname, lastname FROM user_ ORDER BY firstname, lastname");
    $users = $ustmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $ex) {
    // ignore; leave empty list
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Collect basic order info (match DB columns)
    $customerID = (int)($_POST['customerID'] ?? 0);
    $userID = (int)($_POST['userID'] ?? 0);
    $status = trim($_POST['status'] ?? 'pending'); // pending/paid/cancelled
    $notes = trim($_POST['notes'] ?? '');

    // Collect line items (drugID + price + quantity)
    $prices = $_POST['price'] ?? [];
    $drugIDs = $_POST['drugID'] ?? [];
    $quantities = $_POST['quantity'] ?? [];

    // Validate
    if ($customerID <= 0) {
        $errors[] = 'Please select a customer.';
    }
    if ($userID <= 0) {
        $errors[] = 'Please select a user (cashier).';
    }
    $allowedStatuses = ['pending','paid','cancelled'];
    if (!in_array($status, $allowedStatuses, true)) {
        $errors[] = 'Invalid status selected.';
    }

    $items = [];
    $total = 0.0;
    $rowCount = max(count($prices), max(count($drugIDs), count($quantities)));
    for ($i = 0; $i < $rowCount; $i++) {
        $drugID = isset($drugIDs[$i]) ? (int)$drugIDs[$i] : 0;
        $price = isset($prices[$i]) ? (float)$prices[$i] : 0.0;
        $quantity = isset($quantities[$i]) ? (int)$quantities[$i] : 1;
        if ($drugID <= 0 && $price <= 0 && $quantity <= 0) continue; // empty row
        if ($drugID <= 0) {
            $errors[] = 'Item #' . ($i+1) . ' is missing a selected drug.';
            continue;
        }
        if ($quantity <= 0) {
            $errors[] = 'Item #' . ($i+1) . ' must have quantity of at least 1.';
            continue;
        }
        if ($price <= 0) {
            $errors[] = 'Item #' . ($i+1) . ' must have a positive price.';
            continue;
        }
        $items[] = ['drugID' => $drugID, 'price' => $price, 'quantity' => $quantity];
        $total += $price * $quantity;
    }

    if (empty($items)) {
        $errors[] = 'At least one item (drug + positive price) is required.';
    }

    if (empty($errors)) {
    // Insert order and items using the queries helper (pass selected status)
    $result = new_order($customerID, $userID, $items, $status);
        if (is_array($result) && ($result['success'] ?? false)) {
            $success = 'Order registered successfully with ID: ' . htmlspecialchars((string)$result['orderID'], ENT_QUOTES, 'UTF-8');
            $_POST = [];
        } else {
            $errors[] = isset($result['error']) ? $result['error'] : 'An unknown error occurred while saving the order.';
        }
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>New Order - Cashier</title>
    <style>
        body{font-family: Arial, sans-serif; margin:20px;}
        .error{color:#a00;}
        .success{color:#090;}
        table{width:100%; border-collapse:collapse; margin-bottom:10px;}
        th,td{padding:6px; border:1px solid #ddd; text-align:left;}
        .right{text-align:right;}
        input[type="text"], input[type="number"], select {width:100%;}
        .small {width:80px;}
        .add-row {margin-bottom:10px;}
    </style>
</head>
<body>
    <h1>Register New Order (Cashier)</h1>

    <?php if (!empty($errors)): ?>
        <div class="error">
            <ul>
            <?php foreach ($errors as $err): ?>
                <li><?php echo e($err); ?></li>
            <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="success"><?php echo e($success); ?></div>
    <?php endif; ?>

    <form method="post" id="orderForm">
        <div>
            <label>Customer</label><br>
            <select name="customerID" required>
                <option value="">-- choose customer --</option>
                <?php foreach ($customers as $c): ?>
                    <option value="<?php echo e($c['customerID']); ?>" <?php if(($_POST['customerID'] ?? '') == $c['customerID']) echo 'selected'; ?>><?php echo e($c['firstname'].' '.$c['lastname']); ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div style="margin-top:8px;">
            <label>User (cashier)</label><br>
            <select name="userID" required>
                <option value="">-- choose user --</option>
                <?php foreach ($users as $u): ?>
                    <option value="<?php echo e($u['userID']); ?>" <?php if(($_POST['userID'] ?? '') == $u['userID']) echo 'selected'; ?>><?php echo e($u['firstname'].' '.$u['lastname']); ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div style="margin-top:8px;">
            <label>Status</label><br>
            <select name="status">
                <option value="pending" <?php if(($_POST['status'] ?? '')==='pending') echo 'selected'; ?>>pending</option>
                <option value="paid" <?php if(($_POST['status'] ?? '')==='paid') echo 'selected'; ?>>paid</option>
                <option value="cancelled" <?php if(($_POST['status'] ?? '')==='cancelled') echo 'selected'; ?>>cancelled</option>
            </select>
        </div>

    <h3>Items (select drug + price)</h3>
        <button type="button" class="add-row" onclick="addRow()">Add item</button>
        <table id="itemsTable">
            <thead>
                <tr>
                                    <th style="width:45%">Drug</th>
                                    <th class="small">Qty</th>
                                    <th class="small">Price</th>
                                    <th class="small">Line total</th>
                                    <th class="small">Remove</th>
                </tr>
            </thead>
            <tbody>
                <?php
                                    // repopulate rows if POST (include quantities)
                                    $rows = max(1, max(count($_POST['price'] ?? []), max(count($_POST['drugID'] ?? []), count($_POST['quantity'] ?? []))));
                                    for ($r = 0; $r < $rows; $r++):
                                        $selDrug = isset($_POST['drugID'][$r]) ? (int)$_POST['drugID'][$r] : '';
                                        // if no price provided but drug selected, use the representative selling price we fetched
                                        $price = $_POST['price'][$r] ?? '';
                                        $qty = $_POST['quantity'][$r] ?? '';
                                        if (($price === '' || $price === null) && $selDrug && isset($drugPrices[$selDrug])) {
                                            $price = number_format($drugPrices[$selDrug], 2, '.', '');
                                        }
                                        if ($qty === '' || $qty === null) {
                                            $qty = 1;
                                        }
                ?>
                <tr>
                    <td>
                        <select name="drugID[]" required>
                            <option value="">-- choose drug --</option>
                            <?php foreach ($drugs as $dd): ?>
                                <?php
                                    $optPrice = number_format((float)$dd['selling_price'], 2, '.', '');
                                    $optStock = (int)$dd['stock_qty'];
                                ?>
                                <option value="<?php echo e($dd['drugID']); ?>" data-price="<?php echo e($optPrice); ?>" data-stock="<?php echo e($optStock); ?>" <?php if($selDrug == $dd['drugID']) echo 'selected'; ?>><?php echo e($dd['name'] . ' — $' . $optPrice . ' — stock: ' . $optStock); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                    <td><input type="number" name="quantity[]" step="1" min="1" value="<?php echo e($qty); ?>" class="qty small"></td>
                    <td><input type="number" name="price[]" step="0.01" min="0" value="<?php echo e($price); ?>" class="price small"></td>
                    <td class="right line-total">0.00</td>
                    <td class="right"><button type="button" onclick="removeRow(this)">✖</button></td>
                </tr>
                <?php endfor; ?>
            </tbody>
        </table>

        <div style="max-width:420px;">
            <div>
                <label>Notes (optional)</label>
                <input type="text" name="notes" value="<?php echo e($_POST['notes'] ?? ''); ?>">
            </div>

            <div style="margin-top:8px;">
                <strong>Total: $<span id="orderTotal">0.00</span></strong>
            </div>

            <div style="margin-top:12px;">
                <button type="submit">Save Order</button>
                <button type="reset" onclick="setTimeout(recalc,0)">Reset</button>
            </div>
        </div>
    </form>

<script>
function addRow() {
    const tbody = document.querySelector('#itemsTable tbody');
    const tr = document.createElement('tr');
    const drugOptions = <?php echo json_encode($drugOptionsHtml); ?>;
    tr.innerHTML = `<td><select name="drugID[]" required><option value="">-- choose drug --</option>${drugOptions}</select></td>
                    <td><input type="number" name="quantity[]" step="1" min="1" value="1" class="qty small"></td>
                    <td><input type="number" name="price[]" step="0.01" min="0" value="" class="price small"></td>
                    <td class="right line-total">0.00</td>
                    <td class="right"><button type="button" onclick="removeRow(this)">✖</button></td>`;
    tbody.appendChild(tr);
    attachListeners(tr);
    recalc();
}

function removeRow(btn){
    const tr = btn.closest('tr');
    if (!tr) return;
    tr.remove();
    recalc();
}

function recalc(){
    let total = 0;
    document.querySelectorAll('#itemsTable tbody tr').forEach(function(tr){
        const qty = parseInt((tr.querySelector('.qty') || {value:0}).value) || 0;
        const price = parseFloat((tr.querySelector('.price') || {value:0}).value) || 0;
        const line = qty * price;
        const lt = tr.querySelector('.line-total');
        if (lt) lt.textContent = line.toFixed(2);
        total += line;
    });
    document.getElementById('orderTotal').textContent = total.toFixed(2);
}

// attach change listeners to inputs to auto recalc
function attachListeners(root){
    // price inputs -> recalc
    (root.querySelectorAll ? root.querySelectorAll('.price') : []).forEach(function(inp){
        inp.addEventListener('input', recalc);
    });
    // qty inputs -> recalc
    (root.querySelectorAll ? root.querySelectorAll('.qty') : []).forEach(function(inp){
        inp.addEventListener('input', recalc);
    });
    // drug selects -> autofill price from selected option data-price
    (root.querySelectorAll ? root.querySelectorAll('select[name="drugID[]"]') : []).forEach(function(sel){
        sel.addEventListener('change', function(ev){
            const opt = sel.options[sel.selectedIndex];
            if (!opt) return;
            const p = opt.dataset.price;
            // find price input in same row
            const row = sel.closest('tr');
            if (!row) return;
            const priceInput = row.querySelector('.price');
            if (priceInput && p !== undefined) {
                // set price if empty or zero
                if (!priceInput.value || parseFloat(priceInput.value) === 0) {
                    priceInput.value = parseFloat(p).toFixed(2);
                    // trigger recalc
                    recalc();
                }
            }
        });
    });
}

// attach to existing rows
document.querySelectorAll('#itemsTable tbody tr').forEach(attachListeners);

// initial recalc
recalc();
</script>
</body>
</html>