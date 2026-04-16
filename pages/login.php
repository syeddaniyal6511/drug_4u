<?php
require_once('../database/connect_db.php');

?>
<!DOCTYPE html>
    <html>
        <head>
            <title>Pharmacy System Login</title>
        </head>

        <body>

            <h2>Pharmacy System Login</h2>

            <form action="../controllers/authenticate.php" method="POST">

            <label>Username:</label>
            <input type="text" name="email" required>

            <br><br>

            <label>Password:</label>
            <input type="password" name="password" required>

            <br><br>

            <button type="submit">Login</button>

            </form>

        </body>
    </html>