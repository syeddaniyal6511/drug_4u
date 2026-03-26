<p><a href="./dashboard.php">Back to dashboard</a></p>
<?php

include "../database/queries.php";

$errors  = [];
$success = null;

// --- Handle POST submission ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Collect and trim inputs
    $name = trim($_POST['name'] ?? '');
    $basic_unit = trim($_POST['basic_unit'] ?? '');
    $collective_unit = trim($_POST['collective_unit'] ?? '');
    $no_of_basic_units_in_collective_unit = trim($_POST['no_of_basic_units_in_collective_unit'] ?? '');
    $age_limit = trim($_POST['age_limit'] ?? '');

    // --- Validation ---
    if ($name === '') {
        $errors[] = "Drug name is required.";
    }

    if ($basic_unit === '' || !ctype_digit($basic_unit)) {
        $errors[] = "Basic unit must be a valid integer.";
    }

    if ($collective_unit === '' || !ctype_digit($collective_unit)) {
        $errors[] = "Collective unit must be a valid integer.";
    }

    if ($no_of_basic_units_in_collective_unit === '' || !is_numeric($no_of_basic_units_in_collective_unit)) {
        $errors[] = "Number of basic units in collective unit must be numeric.";
    }

    if ($age_limit === '' || !ctype_digit($age_limit)) {
        $errors[] = "Age limit must be a valid integer.";
    }

    // --- If no errors, insert into DB ---
    if (empty($errors)) {
         $result = new_drug($name, (int)$basic_unit, (int)$collective_unit, (float)$no_of_basic_units_in_collective_unit, (int)$age_limit);
        if (is_array($result) && ($result['success'] ?? false)) {
                $success = 'New drug added successfully with ID: ' . htmlspecialchars((string)$result['drugID'], ENT_QUOTES, 'UTF-8');
                // reset fields
                $name = $basic_unit = $collective_unit = $no_of_basic_units_in_collective_unit = $age_limit = '';
            } else {
                $errors[] = isset($result['error']) ? $result['error'] : 'An unknown error occurred while saving the customer.';
            }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Add New Drug</title>
    <style>
        body { font-family: Arial; margin: 40px; }
        .error { color: red; margin-bottom: 10px; }
        .success { color: green; margin-bottom: 10px; }
        form { max-width: 400px; }
        input, label { display: block; width: 100%; margin-bottom: 10px; }
        input[type='submit'] { width: auto; }
    </style>
</head>
<body>

<h2>Add New Drug</h2>

<?php if (!empty($errors)): ?>
    <div class="error">
        <strong>Please fix the following:</strong>
        <ul>
            <?php foreach ($errors as $e): ?>
                <li><?= htmlspecialchars($e) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<?php if ($success): ?>
    <div class="success">
        <?= htmlspecialchars($success) ?>
    </div>
<?php endif; ?>

<form method="POST">

    <label>Drug Name</label>
    <input type="text" name="name" value="<?= htmlspecialchars($name ?? '') ?>">

    <label>Basic Unit (INT)</label>
    <input type="number" name="basic_unit" value="<?= htmlspecialchars($basic_unit ?? '') ?>">

    <label>Collective Unit (INT)</label>
    <input type="number" name="collective_unit" value="<?= htmlspecialchars($collective_unit ?? '') ?>">

    <label>No. of Basic Units in Collective Unit (DECIMAL)</label>
    <input type="text" name="no_of_basic_units_in_collective_unit" value="<?= htmlspecialchars($no_of_basic_units_in_collective_unit ?? '') ?>">

    <label>Age Limit (INT)</label>
    <input type="number" name="age_limit" value="<?= htmlspecialchars($age_limit ?? '') ?>">

    <input type="submit" value="Add Drug">

</form>

</body>
</html>
