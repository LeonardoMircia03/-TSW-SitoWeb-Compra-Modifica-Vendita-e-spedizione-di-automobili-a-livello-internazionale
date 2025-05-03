<?php
$dbconnect = pg_connect("host=localhost port=5433 dbname=Automarket user=postgres password=1234");

if (!$dbconnect) {
    die("Errore durante la connessione al database.");
}
?>