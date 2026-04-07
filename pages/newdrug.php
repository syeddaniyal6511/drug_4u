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
                $errors[] = isset($result['error']) ? $result['error'] : 'An unknown error occurred while saving the drug.';
            }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Add New Drug</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 40px;
            background-color: #f4f4f4;
            color: #333;
        }
        h2 {
            color: #2c3e50;
            border-bottom: 2px solid #3498db;
            padding-bottom: 10px;
        }
        .error {
            color: #e74c3c;
            background-color: #faddd7;
            padding: 10px;
            border: 1px solid #e74c3c;
            border-radius: 5px;
            margin-bottom: 15px;
        }
        .success {
            color: #27ae60;
            background-color: #d5f4e6;
            padding: 10px;
            border: 1px solid #27ae60;
            border-radius: 5px;
            margin-bottom: 15px;
        }
        form {
            max-width: 400px;
            background-color: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            color: #2c3e50;
        }
        input {
            display: block;
            width: 100%;
            padding: 10px;
            margin-bottom: 15px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
            font-size: 14px;
        }
        input[type='submit'] {
            width: auto;
            background-color: #27ae60;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
        }
        input[type='submit']:hover {
            background-color: #229954;
        }
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
