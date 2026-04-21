<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.html");
    exit();
}

include "../database/queries.php";

$errors  = [];
$success = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $drugID        = trim($_POST['drugID']        ?? '');
    $name          = trim($_POST['name']          ?? '');
    $quantity      = trim($_POST['quantity']      ?? '');
    $batch_number  = trim($_POST['batch_number']  ?? '');
    $buying_price  = trim($_POST['buying_price']  ?? '');
    $selling_price = trim($_POST['selling_price'] ?? '');
    $expiry_date   = trim($_POST['expiry_date']   ?? '');

    if ($drugID === ''        || !ctype_digit($drugID))          $errors[] = 'Please select a drug.';
    if ($name === '')                                             $errors[] = 'Stock name / description is required.';
    if ($quantity === ''      || !ctype_digit($quantity))        $errors[] = 'Quantity must be a valid whole number.';
    if ($batch_number === ''  || !ctype_digit($batch_number))    $errors[] = 'Batch number must be numeric.';
    if ($buying_price === ''  || !is_numeric($buying_price)  || (float)$buying_price < 0)
        $errors[] = 'Buying price must be a valid non-negative number.';
    if ($selling_price === '' || !is_numeric($selling_price) || (float)$selling_price < 0)
        $errors[] = 'Selling price must be a valid non-negative number.';
    if ($expiry_date === '')                                      $errors[] = 'Expiry date is required.';
    elseif (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $expiry_date)) $errors[] = 'Expiry date format is invalid.';

    if (empty($errors)) {
        $result = new_stock(
            (int)$drugID,
            $name,
            (int)$quantity,
            $batch_number,
            (float)$buying_price,
            (float)$selling_price,
            $expiry_date
        );
        if (is_array($result) && ($result['success'] ?? false)) {
            $success = 'Stock entry added successfully. ID: ' . htmlspecialchars((string)$result['stockID'], ENT_QUOTES, 'UTF-8');
            $drugID = $name = $quantity = $batch_number = $buying_price = $selling_price = $expiry_date = '';
        } else {
            $errors[] = isset($result['error']) ? $result['error'] : 'An unknown error occurred.';
        }
    }
}

$drugs = get_all_drugs();

$pageTitle   = 'Add Stock';
$currentPage = 'add_stock';
include './partials/header.php';
?>

<div class="page-header">
  <h1>Add Stock</h1>
  <p>Record a new stock batch for an existing drug</p>
</div>

<div class="card card-sm">

  <?php if ($success): ?>
    <div class="alert alert-success">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
      <span><?= $success ?></span>
    </div>
  <?php endif; ?>

  <?php if ($errors): ?>
    <div class="alert alert-error">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
      <div>
        <strong>Please fix the following:</strong>
        <ul>
          <?php foreach ($errors as $err): ?>
            <li><?= htmlspecialchars($err, ENT_QUOTES, 'UTF-8') ?></li>
          <?php endforeach; ?>
        </ul>
      </div>
    </div>
  <?php endif; ?>

  <form method="POST" action="">
    <div class="form-grid">

      <div class="field field-full">
        <label for="drugID">Drug</label>
        <select id="drugID" name="drugID" required>
          <option value="">— select a drug —</option>
          <?php foreach ($drugs as $drug): ?>
            <option value="<?= (int)$drug['drugID'] ?>"
              <?= ((string)($drugID ?? '') === (string)$drug['drugID']) ? 'selected' : '' ?>>
              <?= htmlspecialchars($drug['name'], ENT_QUOTES, 'UTF-8') ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="field field-full">
        <label for="name">Stock name / description</label>
        <input id="name" name="name" type="text" maxlength="255"
               value="<?= htmlspecialchars($name ?? '', ENT_QUOTES, 'UTF-8') ?>"
               placeholder="e.g. Amoxicillin 500mg Capsules" required>
      </div>

      <div class="field">
        <label for="batch_number">Batch number</label>
        <input id="batch_number" name="batch_number" type="text" maxlength="20"
               value="<?= htmlspecialchars($batch_number ?? '', ENT_QUOTES, 'UTF-8') ?>"
               placeholder="e.g. 20240901" required>
      </div>

      <div class="field">
        <label for="quantity">Quantity (packs)</label>
        <input id="quantity" name="quantity" type="number" min="0" step="1"
               value="<?= htmlspecialchars($quantity ?? '', ENT_QUOTES, 'UTF-8') ?>"
               placeholder="0" required>
      </div>

      <div class="field">
        <label for="buying_price">Buying price per pack</label>
        <input id="buying_price" name="buying_price" type="number" min="0" step="0.01"
               value="<?= htmlspecialchars($buying_price ?? '', ENT_QUOTES, 'UTF-8') ?>"
               placeholder="0.00" required>
      </div>

      <div class="field">
        <label for="selling_price">Selling price per pack</label>
        <input id="selling_price" name="selling_price" type="number" min="0" step="0.01"
               value="<?= htmlspecialchars($selling_price ?? '', ENT_QUOTES, 'UTF-8') ?>"
               placeholder="0.00" required>
      </div>

      <div class="field">
        <label for="expiry_date">Expiry date</label>
        <input id="expiry_date" name="expiry_date" type="date"
               value="<?= htmlspecialchars($expiry_date ?? '', ENT_QUOTES, 'UTF-8') ?>"
               required>
      </div>

    </div><!-- /.form-grid -->

    <div class="form-actions">
      <button class="btn" type="submit">Add Stock</button>
      <a class="btn btn-ghost" href="./dashboard.php">Cancel</a>
    </div>
  </form>

</div><!-- /.card -->

<?php include './partials/footer.php'; ?>