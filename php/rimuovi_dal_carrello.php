<?php
session_start();
require_once('config.php');

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$utente_id = $_SESSION['user_id'];
$id_auto = intval($_POST['id_auto']);

// Rimuovi dal carrello
$query = "DELETE FROM carrello WHERE utente_id = $1 AND auto_id = $2";
pg_query_params($dbconnect, $query, array($utente_id, $id_auto));

header("Location: carrello.php");
exit;