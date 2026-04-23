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

    $name                              = trim($_POST['name']                              ?? '');
    $basic_unit                        = trim($_POST['basic_unit']                        ?? '');
    $collective_unit                   = trim($_POST['collective_unit']                   ?? '');
    $no_of_basic_units_in_collective_unit = trim($_POST['no_of_basic_units_in_collective_unit'] ?? '');
    $age_limit                         = trim($_POST['age_limit']                         ?? '');

    if ($name === '')                                             $errors[] = 'Drug name is required.';
    if ($basic_unit === ''      || !ctype_digit($basic_unit))    $errors[] = 'Basic unit must be a valid integer.';
    if ($collective_unit === '' || !ctype_digit($collective_unit)) $errors[] = 'Collective unit must be a valid integer.';
    if ($no_of_basic_units_in_collective_unit === '' || !is_numeric($no_of_basic_units_in_collective_unit))
        $errors[] = 'Number of basic units must be a numeric value.';
    if ($age_limit === ''       || !ctype_digit($age_limit))     $errors[] = 'Age limit must be a valid integer.';

    if (empty($errors)) {
        $result = new_drug(
            $name,
            (int)$basic_unit,
            (int)$collective_unit,
            (float)$no_of_basic_units_in_collective_unit,
            (int)$age_limit
        );
        if (is_array($result) && ($result['success'] ?? false)) {
            $success = 'Drug added successfully. ID: ' . htmlspecialchars((string)$result['drugID'], ENT_QUOTES, 'UTF-8');
            $name = $basic_unit = $collective_unit = $no_of_basic_units_in_collective_unit = $age_limit = '';
        } else {
            $errors[] = isset($result['error']) ? $result['error'] : 'An unknown error occurred.';
        }
    }
}

$pageTitle   = 'Add Drug';
$currentPage = 'newdrug';
include './partials/header.php';
?>

<div class="page-header">
  <h1>Add Drug</h1>
  <p>Register a new drug in the pharmacy inventory</p>
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
        <label for="name">Drug name</label>
        <input id="name" name="name" type="text" maxlength="255"
               value="<?= htmlspecialchars($name ?? '', ENT_QUOTES, 'UTF-8') ?>"
               placeholder="e.g. Amoxicillin" required>
      </div>

      <div class="field">
        <label for="basic_unit">Basic unit</label>
        <input id="basic_unit" name="basic_unit" type="number" min="0" step="1"
               value="<?= htmlspecialchars($basic_unit ?? '', ENT_QUOTES, 'UTF-8') ?>"
               placeholder="0" required>
      </div>

      <div class="field">
        <label for="collective_unit">Collective unit</label>
        <select id="collective_unit" name="collective_unit" required>
          <option value="" disabled <?= empty($collective_unit) ? 'selected' : '' ?>>Choose…</option>
          <option value="1" <?= (isset($collective_unit) && $collective_unit === '1') ? 'selected' : '' ?>>Box</option>
          <option value="2" <?= (isset($collective_unit) && $collective_unit === '2') ? 'selected' : '' ?>>Bottle</option>
          <option value="3" <?= (isset($collective_unit) && $collective_unit === '3') ? 'selected' : '' ?>>Carton</option>
          <option value="4" <?= (isset($collective_unit) && $collective_unit === '4') ? 'selected' : '' ?>>Pack</option>
          <option value="5" <?= (isset($collective_unit) && $collective_unit === '5') ? 'selected' : '' ?>>Strip</option>
          <option value="6" <?= (isset($collective_unit) && $collective_unit === '6') ? 'selected' : '' ?>>Blister</option>
          <option value="7" <?= (isset($collective_unit) && $collective_unit === '7') ? 'selected' : '' ?>>Vial</option>
          <option value="8" <?= (isset($collective_unit) && $collective_unit === '8') ? 'selected' : '' ?>>Tube</option>
        </select>
      </div>
      </div>

      <div class="field">
        <label for="no_of_basic_units_in_collective_unit">Basic units per collective unit</label>
        <input id="no_of_basic_units_in_collective_unit"
               name="no_of_basic_units_in_collective_unit"
               type="number" min="0" step="any"
               value="<?= htmlspecialchars($no_of_basic_units_in_collective_unit ?? '', ENT_QUOTES, 'UTF-8') ?>"
               placeholder="0.00" required>
      </div>

      <div class="field">
        <label for="age_limit">Age limit</label>
        <input id="age_limit" name="age_limit" type="number" min="0" step="1"
               value="<?= htmlspecialchars($age_limit ?? '', ENT_QUOTES, 'UTF-8') ?>"
               placeholder="0" required>
      </div>

    </div><!-- /.form-grid -->

    <div class="form-actions">
      <button class="btn" type="submit">Add Drug</button>
      <a class="btn btn-ghost" href="./dashboard.php">Cancel</a>
    </div>
  </form>

</div><!-- /.card -->

<?php include './partials/footer.php'; ?>
