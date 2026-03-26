<?php

session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

?>

<!DOCTYPE html>
<html>
<head>
<title>Pharmacy Dashboard</title>
</head>

<body>

<h1>Welcome to the Pharmacy System</h1>

<p>You are logged in.</p>

<p><a href="../controllers/register_customer.php">Register a New Customer</a></p>
<p><a href="./newdrug.php">Add a New Drug</a></p>
<p><a href="./new_order.php">Create a New Order</a></p>
<p><a href="./prescription_history.php">Review prescription history of a client</a></p>
<p><a href="./all_orders.php">Review all prescriptions</a></p>

<a href="logout.php">Logout</a>

</body>
</html>