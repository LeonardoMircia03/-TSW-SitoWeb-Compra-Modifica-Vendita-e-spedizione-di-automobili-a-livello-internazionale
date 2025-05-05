<?php
require_once('config.php');

// Recupera e valida la marca
$marca = $_GET['marca'] ?? '';

if (!empty($marca)) {
    $query = "SELECT DISTINCT modello FROM auto WHERE marca = $1 ORDER BY modello";
    $result = pg_query_params($dbconnect, $query, array($marca));

    if ($result) {
        while ($row = pg_fetch_assoc($result)) {
            // Escaping HTML per sicurezza
            $modello = htmlspecialchars($row['modello']);
            echo "<option value=\"$modello\">$modello</option>";
        }
    } else {
        http_response_code(500);
        echo "<option value=\"\">Errore nella query</option>";
    }
} else {
    echo "<option value=\"\">Seleziona una marca</option>";
}
?>