<?php
/*******************************
 * Simple customer registration
 * - One file: shows form + handles POST
 * - Uses PDO + prepared statements
 *******************************/
include "../database/queries.php";

// (Optional) basic CSRF token support for the form
//session_start();
//if (empty($_SESSION['csrf_token'])) {
//    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
//}

$errors  = [];
$success = null;

// --- Handle POST submission ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF check (optional but recommended)
    #if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    #    $errors[] = 'Invalid form token. Please refresh and try again.';
    #}

    // Collect and trim inputs
    $firstname = trim($_POST['firstname'] ?? '');
    $lastname  = trim($_POST['lastname'] ?? '');
    $gender    = trim($_POST['gender'] ?? '');
    $dob       = trim($_POST['dob'] ?? '');
    $postcode  = trim($_POST['postcode'] ?? '');

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
            new_customer($firstname, $lastname, $gender, $dob, $postcode);
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
        body { font-family: system-ui, -apple-system, Segoe UI, Roboto, sans-serif; margin: 2rem; }
        form { max-width: 640px; padding: 1.25rem; border: 1px solid #ddd; border-radius: 8px; }
        .row { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }
        .row.full { grid-template-columns: 1fr; }
        label { display: block; margin-bottom: .25rem; font-weight: 600; }
        input, select { width: 100%; padding: .5rem; border: 1px solid #ccc; border-radius: 6px; }
        .actions { margin-top: 1rem; }
        .btn { background: #0a66c2; color: #fff; border: 0; padding: .6rem 1rem; border-radius: 6px; cursor: pointer; }
        .btn:disabled { opacity: .6; cursor: not-allowed; }
        .messages { margin-bottom: 1rem; }
        .error { background: #ffe9e9; color: #b00020; padding: .75rem; border-radius: 6px; margin-bottom: .5rem; }
        .success { background: #e6ffed; color: #0a7f47; padding: .75rem; border-radius: 6px; }
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

    <div class="actions">
        <button class="btn" type="submit">Register</button>
    </div>
</form>

</body>
</html>