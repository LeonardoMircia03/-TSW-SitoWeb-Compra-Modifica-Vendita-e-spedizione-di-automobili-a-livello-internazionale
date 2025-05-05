<?php
require_once('config.php');

$marca = $_GET['marca'] ?? '';

if ($marca) {
    $query = "SELECT DISTINCT modello FROM auto WHERE marca = $1 ORDER BY modello";
    $result = pg_query_params($dbconnect, $query, array($marca));

    while ($row = pg_fetch_assoc($result)) {
        echo "<option value=\"{$row['modello']}\">{$row['modello']}</option>";
    }
}
?>