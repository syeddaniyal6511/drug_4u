<p><a href="./dashboard.php">Back to dashboard</a></p>
<?php

require_once '../database/queries.php';

$customers = get_all_customers();

// If a customer is selected:
$selectedCustomerID = isset($_GET['customerID']) ? (int)$_GET['customerID'] : null;

?>

<h2>Select Customer</h2>

<form method="GET" action="">
    <label for="customerID">Customer:</label>
    <select name="customerID" id="customerID" required>
        <option value="">-- choose --</option>

        <?php foreach ($customers as $c): ?>
            <option value="<?= $c['customerID'] ?>"
                <?= ($selectedCustomerID === (int)$c['customerID']) ? 'selected' : '' ?>>
                <?= htmlspecialchars($c['firstname'] . ' ' . $c['lastname']) ?>
            </option>
        <?php endforeach; ?>

    </select>

    <button type="submit">View History</button>
</form>

<hr>

<?php
if ($selectedCustomerID) {
    echo "<h3>Prescription History</h3>";
    presc_history($selectedCustomerID);
}




?>