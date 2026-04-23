<?php
include 'database/connect_db.php';
$result = run_create_tables($dbc);
if ($result) {
    echo "Tables created successfully.\n";
} else {
    echo "Failed to create tables.\n";
}
?>