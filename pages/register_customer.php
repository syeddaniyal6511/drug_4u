<?php
/*******************************
 * Simple customer registration
 * - One file: shows form + handles POST
 * - Uses PDO + prepared statements
 *******************************/
include "../database/queries.php";

//session userid check (optional, depends on your auth system)
    if (!isset($_SESSION['user_id'])) {
        $errors[] = 'User not logged in.';
    }

$errors  = [];
$success = null;

// --- Handle POST submission ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    

    // Collect and trim inputs
    $firstname = trim($_POST['firstname'] ?? '');
    $lastname  = trim($_POST['lastname'] ?? '');
    $gender    = trim($_POST['gender'] ?? '');
    $dob       = trim($_POST['dob'] ?? '');
    $postcode  = trim($_POST['postcode'] ?? '');
    $allergies_raw = trim($_POST['allergies'] ?? '');

    // --- Validate ---
    if ($firstname === '') $errors[] = 'First name is required.';
    if ($lastname === '')  $errors[] = 'Last name is required.';

    // Gender must be one of the enum values
    $allowedGenders = ['man', 'woman'];
    if (!in_array($gender, $allowedGenders, true)) {
        $errors[] = 'Gender must be either "man" or "woman".';
    }

    // Validate date (YYYY-MM-DD) and that it is a real date
    $dobValid = false;
    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $dob)) {
        $parts = explode('-', $dob);
        $dobValid = checkdate((int)$parts[1], (int)$parts[2], (int)$parts[0]);
    }
    if (!$dobValid) $errors[] = 'Date of birth must be a valid date (YYYY-MM-DD).';

    // Postcode: BIGINT in DB — accept only digits here; store as string to preserve leading zeros
    if ($postcode === '' || !preg_match('/^\d+$/', $postcode)) {
        $errors[] = 'Postcode must contain digits only.';
    }

    // --- If valid, insert into DB ---
    if (!$errors) {
            // Parse allergies: accept comma-separated list and remove empty items
            $allergies = [];
            if ($allergies_raw !== '') {
                // split on commas, semicolons or newlines
                $parts = preg_split('/[\r\n,;]+/', $allergies_raw);
                foreach ($parts as $p) {
                    $d = trim($p);
                    if ($d !== '') {
                        // limit description length to 1000 characters to be safe
                        $allergies[] = mb_substr($d, 0, 1000);
                    }
                }
            }

            $result = new_customer($firstname, $lastname, $gender, $dob, $postcode, $allergies);
            if (is_array($result) && ($result['success'] ?? false)) {
                $success = 'Customer registered successfully with ID: ' . htmlspecialchars((string)$result['customerID'], ENT_QUOTES, 'UTF-8');
                // reset fields
                $firstname = $lastname = $gender = $dob = $postcode = $allergies_raw = '';
                // go back to dashboard
                    header("Location: ../pages/dashboard.php");
                    exit();
            } else {
                $errors[] = isset($result['error']) ? $result['error'] : 'An unknown error occurred while saving the customer.';
            }
    }
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Register a New Customer</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        body { 
            font-family: Arial, sans-serif; 
            margin: 20px; 
            background-color: #f4f4f4; 
            color: #333; 
        }
        h1 {
            color: #2c3e50;
            text-align: center;
            margin-bottom: 20px;
        }
        form { 
            max-width: 640px; 
            padding: 20px; 
            border: none; 
            border-radius: 8px; 
            background-color: #fff;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            margin: 0 auto;
        }
        .row { 
            display: grid; 
            grid-template-columns: 1fr 1fr; 
            gap: 1rem; 
        }
        .row.full { 
            grid-template-columns: 1fr; 
        }
        label { 
            display: block; 
            margin-bottom: .25rem; 
            font-weight: 600; 
            color: #2c3e50;
        }
        input, select, textarea { 
            width: 100%; 
            padding: .5rem; 
            border: 1px solid #ddd; 
            border-radius: 6px; 
            font-size: 16px;
        }
        textarea {
            resize: vertical;
            min-height: 80px;
        }
        .actions { 
            margin-top: 1rem; 
            text-align: center;
        }
        .btn { 
            background: #3498db; 
            color: #fff; 
            border: 0; 
            padding: .6rem 1rem; 
            border-radius: 6px; 
            cursor: pointer; 
            font-size: 16px;
            transition: background-color 0.3s;
        }
        .btn:hover {
            background: #2980b9;
        }
        .btn:disabled { 
            opacity: .6; 
            cursor: not-allowed; 
        }
        .messages { 
            margin-bottom: 1rem; 
            max-width: 640px;
            margin-left: auto;
            margin-right: auto;
        }
        .error { 
            background: #ffe9e9; 
            color: #b00020; 
            padding: .75rem; 
            border-radius: 6px; 
            margin-bottom: .5rem; 
            border-left: 4px solid #e74c3c;
        }
        .success { 
            background: #e6ffed; 
            color: #0a7f47; 
            padding: .75rem; 
            border-radius: 6px; 
            border-left: 4px solid #27ae60;
        }
    </style>
    </style>
</head>
<body>

<h1>Register a New Customer</h1>

<div class="messages">
    <?php if ($success): ?>
        <div class="success"><?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>

    <?php foreach ($errors as $err): ?>
        <div class="error"><?= htmlspecialchars($err, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endforeach; ?>
</div>

<form method="post" action="">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">

    <div class="row">
        <div>
            <label for="firstname">First name</label>
            <input id="firstname" name="firstname" type="text" maxlength="255"
                   value="<?= isset($firstname) ? htmlspecialchars($firstname, ENT_QUOTES, 'UTF-8') : '' ?>" required>
        </div>
        <div>
            <label for="lastname">Last name</label>
            <input id="lastname" name="lastname" type="text" maxlength="255"
                   value="<?= isset($lastname) ? htmlspecialchars($lastname, ENT_QUOTES, 'UTF-8') : '' ?>" required>
        </div>
    </div>

    <div class="row">
        <div>
            <label for="gender">Gender</label>
            <select id="gender" name="gender" required>
                <option value="" disabled <?= empty($gender) ? 'selected' : '' ?>>Choose…</option>
                <option value="man"   <?= (isset($gender) && $gender === 'man') ? 'selected' : '' ?>>man</option>
                <option value="woman" <?= (isset($gender) && $gender === 'woman') ? 'selected' : '' ?>>woman</option>
            </select>
        </div>
        <div>
            <label for="dob">Date of birth</label>
            <input id="dob" name="dob" type="date"
                   value="<?= isset($dob) ? htmlspecialchars($dob, ENT_QUOTES, 'UTF-8') : '' ?>" required>
        </div>
    </div>

    <div class="row full">
        <div>
            <label for="postcode">Postcode</label>
            <input id="postcode" name="postcode" type="text" inputmode="numeric" pattern="\d+"
                   value="<?= isset($postcode) ? htmlspecialchars($postcode, ENT_QUOTES, 'UTF-8') : '' ?>" required>
        </div>
    </div>

    <div class="row full">
        <div>
            <label for="allergies">Allergies (optional)</label>
            <textarea id="allergies" name="allergies" rows="3" placeholder="List allergies (comma, semicolon or newline separated)"><?= isset($allergies_raw) ? htmlspecialchars($allergies_raw, ENT_QUOTES, 'UTF-8') : '' ?></textarea>
        </div>
    </div>

    <div class="actions">
        <button class="btn" type="submit">Register</button>
    </div>
</form>

</body>
</html>