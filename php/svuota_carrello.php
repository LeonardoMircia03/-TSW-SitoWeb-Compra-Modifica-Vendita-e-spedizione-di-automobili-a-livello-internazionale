<?php
session_start();
require_once('config.php');

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$utente_id = $_SESSION['user_id'];

// Elimina tutte le auto dal carrello dell'utente
$query = "DELETE FROM carrello WHERE utente_id = $1";
pg_query_params($dbconnect, $query, array($utente_id));
$_SESSION['carrello'] = [];

header('Location: carrello.php');
exit;
