<?php
/*******************************
 * Customer registration page
 * Shows form + handles POST
 *******************************/
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.html");
    exit();
}

include "../database/queries.php";

$errors  = [];
$success = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $firstname     = trim($_POST['firstname'] ?? '');
    $lastname      = trim($_POST['lastname']  ?? '');
    $gender        = trim($_POST['gender']    ?? '');
    $dob           = trim($_POST['dob']       ?? '');
    $postcode      = trim($_POST['postcode']  ?? '');
    $allergies_raw = trim($_POST['allergies'] ?? '');

    if ($firstname === '') $errors[] = 'First name is required.';
    if ($lastname  === '') $errors[] = 'Last name is required.';

    $allowedGenders = ['man', 'woman'];
    if (!in_array($gender, $allowedGenders, true)) {
        $errors[] = 'Gender must be either "man" or "woman".';
    }

    $dobValid = false;
    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $dob)) {
        $parts    = explode('-', $dob);
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

    if ($postcode === '' || !preg_match('/^\d+$/', $postcode)) {
        $errors[] = 'Postcode must contain digits only.';
    }

    if (!$errors) {
        $allergies = [];
        if ($allergies_raw !== '') {
            $parts = preg_split('/[\r\n,;]+/', $allergies_raw);
            foreach ($parts as $p) {
                $d = trim($p);
                if ($d !== '') $allergies[] = mb_substr($d, 0, 1000);
            }
        }

        $result = new_customer($firstname, $lastname, $gender, $dob, $postcode, $allergies);
        if (is_array($result) && ($result['success'] ?? false)) {
            $success = 'Customer registered successfully. ID: ' . htmlspecialchars((string)$result['customerID'], ENT_QUOTES, 'UTF-8');
            $firstname = $lastname = $gender = $dob = $postcode = $allergies_raw = '';
        } else {
            $errors[] = isset($result['error']) ? $result['error'] : 'An unknown error occurred.';
        }
    }
}

$pageTitle   = 'Register Customer';
$currentPage = 'register_customer';
include './partials/header.php';
?>

<div class="page-header">
  <h1>Register Customer</h1>
  <p>Add a new patient to the pharmacy system</p>
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

  <form method="post" action="">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8') ?>">

    <div class="form-grid">

      <div class="field">
        <label for="firstname">First name</label>
        <input id="firstname" name="firstname" type="text" maxlength="255"
               value="<?= htmlspecialchars($firstname ?? '', ENT_QUOTES, 'UTF-8') ?>"
               placeholder="Jane" required>
      </div>

      <div class="field">
        <label for="lastname">Last name</label>
        <input id="lastname" name="lastname" type="text" maxlength="255"
               value="<?= htmlspecialchars($lastname ?? '', ENT_QUOTES, 'UTF-8') ?>"
               placeholder="Smith" required>
      </div>

      <div class="field">
        <label for="gender">Gender</label>
        <select id="gender" name="gender" required>
          <option value="" disabled <?= empty($gender) ? 'selected' : '' ?>>Choose…</option>
          <option value="man"   <?= (isset($gender) && $gender === 'man')   ? 'selected' : '' ?>>Man</option>
          <option value="woman" <?= (isset($gender) && $gender === 'woman') ? 'selected' : '' ?>>Woman</option>
        </select>
      </div>

      <div class="field">
        <label for="dob">Date of birth</label>
        <input id="dob" name="dob" type="date"
               value="<?= htmlspecialchars($dob ?? '', ENT_QUOTES, 'UTF-8') ?>" required>
      </div>

      <div class="field field-full">
        <label for="postcode">Postcode</label>
        <input id="postcode" name="postcode" type="text" inputmode="numeric" pattern="\d+"
               value="<?= htmlspecialchars($postcode ?? '', ENT_QUOTES, 'UTF-8') ?>"
               placeholder="12345" required>
      </div>

      <div class="field field-full">
        <label for="allergies">Allergies <span class="text-muted" style="text-transform:none;letter-spacing:0">(optional)</span></label>
        <textarea id="allergies" name="allergies"
                  placeholder="List allergies separated by commas, semicolons, or new lines…"><?= htmlspecialchars($allergies_raw ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
      </div>

    </div><!-- /.form-grid -->

    <div class="form-actions">
      <button class="btn" type="submit">Register Customer</button>
      <a class="btn btn-ghost" href="./dashboard.php">Cancel</a>
    </div>
  </form>

</div><!-- /.card -->

<?php include './partials/footer.php'; ?>
