<?php
session_start();
require_once('config.php');

if (!isset($_SESSION['user_id'])) {
    header("Location: ../html/Login.html");
    exit;
}

$id_auto = intval($_POST['id_auto']);
$utente_id = $_SESSION['user_id'];

// Verifica che l'auto non sia già nel carrello dell'utente
$query_check = "SELECT * FROM carrello WHERE utente_id = $1 AND auto_id = $2";
$result_check = pg_query_params($dbconnect, $query_check, array($utente_id, $id_auto));

if ($result_check && pg_num_rows($result_check) == 0) {
    // Inserisci nel carrello del database
    $query_insert = "INSERT INTO carrello (utente_id, auto_id) VALUES ($1, $2)";
    pg_query_params($dbconnect, $query_insert, array($utente_id, $id_auto));
}

// Aggiorna la sessione locale del carrello 
if (!isset($_SESSION['carrello'])) {
    $_SESSION['carrello'] = [];
}

if (!in_array($id_auto, $_SESSION['carrello'])) {
    $_SESSION['carrello'][] = $id_auto;
}

header("Location: auto.php");
exit;