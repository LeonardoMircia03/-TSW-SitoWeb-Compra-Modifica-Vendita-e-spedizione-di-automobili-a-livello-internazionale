<?php
session_start();
require_once('config.php');

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$utente_id = $_SESSION['user_id'];
$id_auto = intval($_POST['id_auto']);

// Verifica che l'auto non sia già nel carrello
$query = "SELECT * FROM carrello WHERE utente_id = $1 AND auto_id = $2";
$result = pg_query_params($dbconnect, $query, array($utente_id, $id_auto));

if (pg_num_rows($result) == 0) {
    // Inserisci nel carrello
    $insert_query = "INSERT INTO carrello (utente_id, auto_id) VALUES ($1, $2)";
    pg_query_params($dbconnect, $insert_query, array($utente_id, $id_auto));
}

header("Location: carrello.php");
exit;