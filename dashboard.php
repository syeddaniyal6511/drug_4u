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

<a href="logout.php">Logout</a>

</body>
</html>