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

    if (!$dobValid) {
        $errors[] = 'Date of birth must be a valid date (YYYY-MM-DD).';
    } else {
        // Check not in future
        $today = new DateTime('today');
        $birthDate = DateTime::createFromFormat('Y-m-d', $dob);
        if ($birthDate > $today) {
            $errors[] = 'Date of birth cannot be in the future.';
        }
    }

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
        body { font-family: system-ui, -apple-system, Segoe UI, Roboto, sans-serif; margin: 2rem; background: linear-gradient(180deg, #7f9bff 0%, #a98dff 45%, #ede7ff 100%); color: #0f172a; }
        form { max-width: 640px; padding: 1.75rem; border: 1px solid rgba(15,23,42,0.08); border-radius: 24px; background: #ffffff; box-shadow: 0 28px 64px rgba(15,23,42,0.10); }
        .row { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }
        .row.full { grid-template-columns: 1fr; }
        label { display: block; margin-bottom: .35rem; font-weight: 700; color: #475569; }
        input, select, textarea { width: 100%; padding: 0.95rem 1rem; border: 1px solid rgba(15,23,42,0.12); border-radius: 16px; background: #f8fafc; color: #0f172a; font-size: 15px; }
        .actions { margin-top: 1.5rem; }
        .btn { background: linear-gradient(135deg, #2563eb 0%, #0ea5e9 100%); color: #ffffff; border: 0; padding: 0.95rem 1.2rem; border-radius: 999px; cursor: pointer; font-weight: 700; box-shadow: 0 18px 35px rgba(37,99,235,0.18); }
        .btn:hover { opacity: 0.98; }
        .btn:disabled { opacity: .65; cursor: not-allowed; }
        .messages { margin-bottom: 1.25rem; }
        .error { background: #fef2f2; color: #991b1b; padding: .95rem; border-radius: 16px; margin-bottom: .85rem; border: 1px solid rgba(239,68,68,0.18); }
        .success { background: #dcfce7; color: #166534; padding: .95rem; border-radius: 16px; border: 1px solid rgba(34,197,94,0.18); }
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
            <input id="dob" name="dob" type="date" required>
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