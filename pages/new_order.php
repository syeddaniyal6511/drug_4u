<?php
// new_order.php — Create a new cashier order
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.html");
    exit();
}

include '../database/queries.php';
include '../database/connect_db.php';

function e($v) { return htmlspecialchars($v ?? '', ENT_QUOTES, 'UTF-8'); }

$errors  = [];
$success = null;

// ── Fetch customers, users, drugs ──────────────────────────────────────────
$customers = [];
$users     = [];
$drugs     = [];
$drugPrices = [];

try {
    $cstmt     = $objPdo->query("SELECT customerID, firstname, lastname FROM customer ORDER BY firstname, lastname");
    $customers = $cstmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $ex) {}

try {
    $dstmt = $objPdo->query(
        "SELECT d.drugID, d.name,
                COALESCE(MIN(s.selling_price_per_pack), 0) AS selling_price,
                COALESCE(SUM(s.quantity), 0) AS stock_qty
         FROM drug d
         LEFT JOIN stock s ON d.drugID = s.drugID
         GROUP BY d.drugID
         ORDER BY d.name"
    );
    $drugs = $dstmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($drugs as $dd) {
        $drugPrices[(int)$dd['drugID']] = (float)$dd['selling_price'];
    }
} catch (Exception $ex) {}

// Build JS-friendly drug options HTML
$drugOptionsHtml = '';
foreach ($drugs as $dd) {
    $id    = e($dd['drugID']);
    $name  = e($dd['name']);
    $price = number_format((float)$dd['selling_price'], 2, '.', '');
    $stock = (int)$dd['stock_qty'];
    $drugOptionsHtml .= '<option value="' . $id . '" data-price="' . $price . '" data-stock="' . $stock . '">'
        . $name . ' — £' . $price . ' (stock: ' . $stock . ')</option>' . "\n";
}

try {
    $ustmt = $objPdo->query("SELECT userID, firstname, lastname FROM user_ ORDER BY firstname, lastname");
    $users = $ustmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $ex) {}

// ── Handle POST ────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $customerID = (int)($_POST['customerID'] ?? 0);
    $userID     = (int)($_POST['userID']     ?? 0);
    $status     = trim($_POST['status']      ?? 'pending');
    $notes      = trim($_POST['notes']       ?? '');

    $prices    = $_POST['price']    ?? [];
    $drugIDs   = $_POST['drugID']   ?? [];
    $quantities = $_POST['quantity'] ?? [];

    if ($customerID <= 0) $errors[] = 'Please select a customer.';
    if ($userID <= 0)     $errors[] = 'Please select a cashier.';

    $allowedStatuses = ['pending','paid','cancelled'];
    if (!in_array($status, $allowedStatuses, true)) $errors[] = 'Invalid status.';

    $items = [];
    $rowCount = max(count($prices), count($drugIDs), count($quantities));
    for ($i = 0; $i < $rowCount; $i++) {
        $drugID   = isset($drugIDs[$i])   ? (int)$drugIDs[$i]   : 0;
        $price    = isset($prices[$i])    ? (float)$prices[$i]  : 0.0;
        $quantity = isset($quantities[$i]) ? (int)$quantities[$i] : 1;
        if ($drugID <= 0 && $price <= 0 && $quantity <= 0) continue;
        if ($drugID   <= 0) { $errors[] = 'Item #' . ($i+1) . ': no drug selected.';          continue; }
        if ($quantity <= 0) { $errors[] = 'Item #' . ($i+1) . ': quantity must be ≥ 1.';      continue; }
        if ($price    <= 0) { $errors[] = 'Item #' . ($i+1) . ': price must be positive.';    continue; }
        $items[] = ['drugID' => $drugID, 'price' => $price, 'quantity' => $quantity];
    }

    if (empty($items)) $errors[] = 'At least one item is required.';

    if (empty($errors)) {
        $result = new_order($customerID, $userID, $items, $status);
        if (is_array($result) && ($result['success'] ?? false)) {
            $success = 'Order saved successfully. Order ID: ' . e((string)$result['orderID']);
            $_POST   = [];
        } else {
            $errors[] = isset($result['error']) ? $result['error'] : 'An unknown error occurred.';
        }
    }
}

$pageTitle   = 'New Order';
$currentPage = 'new_order';
include './partials/header.php';
?>

<div class="page-header">
  <h1>New Order</h1>
  <p>Register a cashier order for a customer</p>
</div>

<div class="card">

  <?php if ($success): ?>
    <div class="alert alert-success">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
      <span><?= $success ?></span>
    </div>
  <?php endif; ?>

  <?php if (!empty($errors)): ?>
    <div class="alert alert-error">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
      <div>
        <strong>Please fix the following:</strong>
        <ul>
          <?php foreach ($errors as $err): ?>
            <li><?= e($err) ?></li>
          <?php endforeach; ?>
        </ul>
      </div>
    </div>
  <?php endif; ?>

  <form method="post" id="orderForm">

    <!-- Order details row -->
    <div class="form-grid">
      <div class="field">
        <label for="customerID">Customer</label>
        <select name="customerID" id="customerID" required>
          <option value="">— choose customer —</option>
          <?php foreach ($customers as $c): ?>
            <option value="<?= e($c['customerID']) ?>"
              <?= (($_POST['customerID'] ?? '') == $c['customerID']) ? 'selected' : '' ?>>
              <?= e($c['firstname'] . ' ' . $c['lastname']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="field">
        <label for="userID">Cashier</label>
        <select name="userID" id="userID" required>
          <option value="">— choose cashier —</option>
          <?php foreach ($users as $u): ?>
            <option value="<?= e($u['userID']) ?>"
              <?= (($_POST['userID'] ?? '') == $u['userID']) ? 'selected' : '' ?>>
              <?= e($u['firstname'] . ' ' . $u['lastname']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="field">
        <label for="status">Status</label>
        <select name="status" id="status">
          <option value="pending"   <?= (($_POST['status'] ?? 'pending') === 'pending')   ? 'selected' : '' ?>>Pending</option>
          <option value="paid"      <?= (($_POST['status'] ?? '') === 'paid')      ? 'selected' : '' ?>>Paid</option>
          <option value="cancelled" <?= (($_POST['status'] ?? '') === 'cancelled') ? 'selected' : '' ?>>Cancelled</option>
        </select>
      </div>

      <div class="field">
        <label for="notes">Notes <span class="text-muted" style="text-transform:none;letter-spacing:0">(optional)</span></label>
        <input type="text" name="notes" id="notes" value="<?= e($_POST['notes'] ?? '') ?>" placeholder="Any order notes…">
      </div>
    </div><!-- /.form-grid -->

    <hr class="divider">

    <!-- Items table -->
    <div class="flex-between mb-16">
      <h3 style="font-size:15px;font-weight:600">Order Items</h3>
      <button type="button" class="btn btn-ghost btn-sm" onclick="addRow()">
        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
        Add item
      </button>
    </div>

    <div class="items-table-wrap">
      <table id="itemsTable">
        <thead>
          <tr>
            <th style="width:45%">Drug</th>
            <th style="width:10%">Qty</th>
            <th style="width:13%">Unit price</th>
            <th style="width:13%">Line total</th>
            <th style="width:8%"></th>
          </tr>
        </thead>
        <tbody>
          <?php
          $rows = max(1, count($_POST['price'] ?? [1]), count($_POST['drugID'] ?? [1]), count($_POST['quantity'] ?? [1]));
          for ($r = 0; $r < $rows; $r++):
              $selDrug = isset($_POST['drugID'][$r])   ? (int)$_POST['drugID'][$r] : '';
              $price   = $_POST['price'][$r]    ?? '';
              $qty     = $_POST['quantity'][$r] ?? 1;
              if (($price === '' || $price === null) && $selDrug && isset($drugPrices[$selDrug])) {
                  $price = number_format($drugPrices[$selDrug], 2, '.', '');
              }
          ?>
          <tr>
            <td>
              <select name="drugID[]" required>
                <option value="">— choose drug —</option>
                <?php foreach ($drugs as $dd):
                    $optPrice = number_format((float)$dd['selling_price'], 2, '.', '');
                    $optStock = (int)$dd['stock_qty'];
                ?>
                  <option value="<?= e($dd['drugID']) ?>"
                          data-price="<?= e($optPrice) ?>"
                          data-stock="<?= e($optStock) ?>"
                          <?= ($selDrug == $dd['drugID']) ? 'selected' : '' ?>>
                    <?= e($dd['name'] . ' — £' . $optPrice . ' (stock: ' . $optStock . ')') ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </td>
            <td><input type="number" name="quantity[]" step="1" min="1" value="<?= e($qty) ?>" class="qty"></td>
            <td><input type="number" name="price[]" step="0.01" min="0" value="<?= e($price) ?>" class="price"></td>
            <td class="line-total text-right">—</td>
            <td class="text-right">
              <button type="button" class="btn btn-danger btn-sm" onclick="removeRow(this)"
                      title="Remove row" style="padding:4px 8px">
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
              </button>
            </td>
          </tr>
          <?php endfor; ?>
        </tbody>
      </table>
    </div><!-- /.items-table-wrap -->

    <div class="total-row">
      Order total: <span class="total-amount">£<span id="orderTotal">0.00</span></span>
    </div>

    <div class="form-actions">
      <button class="btn" type="submit">Save Order</button>
      <button class="btn btn-ghost" type="reset" onclick="setTimeout(recalc,0)">Reset</button>
      <a class="btn btn-ghost" href="./dashboard.php">Cancel</a>
    </div>

  </form>
</div><!-- /.card -->

<script>
function addRow() {
    const tbody = document.querySelector('#itemsTable tbody');
    const tr    = document.createElement('tr');
    const drugOptions = <?php echo json_encode($drugOptionsHtml); ?>;
    tr.innerHTML =
        '<td><select name="drugID[]" required><option value="">— choose drug —</option>' + drugOptions + '</select></td>' +
        '<td><input type="number" name="quantity[]" step="1" min="1" value="1" class="qty"></td>' +
        '<td><input type="number" name="price[]" step="0.01" min="0" value="" class="price"></td>' +
        '<td class="line-total text-right">—</td>' +
        '<td class="text-right"><button type="button" class="btn btn-danger btn-sm" onclick="removeRow(this)" title="Remove" style="padding:4px 8px">' +
        '<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>' +
        '</button></td>';
    tbody.appendChild(tr);
    attachListeners(tr);
    recalc();
}

function removeRow(btn) {
    const tr = btn.closest('tr');
    if (tr) { tr.remove(); recalc(); }
}

function recalc() {
    let total = 0;
    document.querySelectorAll('#itemsTable tbody tr').forEach(function(tr) {
        const qty   = parseInt((tr.querySelector('.qty')   || {value:0}).value)   || 0;
        const price = parseFloat((tr.querySelector('.price') || {value:0}).value) || 0;
        const line  = qty * price;
        const lt    = tr.querySelector('.line-total');
        if (lt) lt.textContent = line > 0 ? '£' + line.toFixed(2) : '—';
        total += line;
    });
    document.getElementById('orderTotal').textContent = total.toFixed(2);
}

function attachListeners(root) {
    root.querySelectorAll('.price, .qty').forEach(function(inp) {
        inp.addEventListener('input', recalc);
    });
    root.querySelectorAll('select[name="drugID[]"]').forEach(function(sel) {
        sel.addEventListener('change', function() {
            const opt = sel.options[sel.selectedIndex];
            if (!opt) return;
            const p = opt.dataset.price;
            const row = sel.closest('tr');
            if (!row) return;
            const priceInput = row.querySelector('.price');
            if (priceInput && p !== undefined && (!priceInput.value || parseFloat(priceInput.value) === 0)) {
                priceInput.value = parseFloat(p).toFixed(2);
                recalc();
            }
        });
    });
}

document.querySelectorAll('#itemsTable tbody tr').forEach(attachListeners);
recalc();
</script>

<?php include './partials/footer.php'; ?>
