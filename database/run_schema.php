<?php
/**
 * Runs createtables.sql to ensure database tables exist.
 * Call this after establishing a DB connection.
 *
 * @param mysqli $connection Active mysqli connection
 * @return bool True on success, false on failure
 */
function run_create_tables($connection) {
    $sqlFile = __DIR__ . '/createtables.sql';
    if (!is_readable($sqlFile)) {
        return false;
    }
    $sql = file_get_contents($sqlFile);
    if ($sql === false) {
        return false;
    }
    if (!$connection->multi_query($sql)) {
        return false;
    }
    // Drain results so the connection can be used for other queries
    do {
        if ($result = $connection->store_result()) {
            $result->free();
        }
    } while ($connection->more_results() && $connection->next_result());
    return true;
}
