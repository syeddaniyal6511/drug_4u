<style>
body {
    font-family: Arial, sans-serif;
    margin: 20px;
    background-color: #f4f4f4;
    color: #333;
}

h2, h3 {
    color: #2c3e50;
}

form {
    background-color: #fff;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 0 10px rgba(0,0,0,0.1);
    margin-bottom: 20px;
}

label {
    display: block;
    margin-bottom: 8px;
    font-weight: bold;
}

select, button {
    padding: 10px;
    margin-bottom: 10px;
    border: 1px solid #ddd;
    border-radius: 4px;
}

button {
    background-color: #3498db;
    color: white;
    cursor: pointer;
    border: none;
}

button:hover {
    background-color: #2980b9;
}

hr {
    border: none;
    height: 1px;
    background-color: #ddd;
    margin: 20px 0;
}

.order {
    background-color: #fff;
    padding: 15px;
    border-radius: 8px;
    box-shadow: 0 0 10px rgba(0,0,0,0.1);
    margin-bottom: 20px;
}

.order h3 {
    margin-top: 0;
}

ul {
    list-style-type: none;
    padding: 0;
}

li {
    background-color: #ecf0f1;
    margin: 5px 0;
    padding: 10px;
    border-radius: 4px;
}

a {
    color: #3498db;
    text-decoration: none;
}

a:hover {
    text-decoration: underline;
}
</style>

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