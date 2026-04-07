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


all_presc_history();



?>