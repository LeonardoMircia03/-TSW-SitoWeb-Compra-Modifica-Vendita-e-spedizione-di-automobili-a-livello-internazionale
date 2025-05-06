<?php
$dbconnect = pg_connect("host=localhost port=5432 dbname=automarket user=postgres password=1234");

if (!$dbconnect) {
    echo "Errore durante la connessione al database.";
}
?>