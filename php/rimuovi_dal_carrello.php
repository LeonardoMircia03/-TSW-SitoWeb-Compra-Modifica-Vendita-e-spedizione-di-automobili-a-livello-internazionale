<?php
session_start();
require_once('config.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_auto = intval($_POST['id_auto']);
    $utente_id = $_SESSION['user_id'];

    // Rimuovi dal carrello
    $query = "DELETE FROM carrello WHERE utente_id = $1 AND auto_id = $2";
    pg_query_params($dbconnect, $query, array($utente_id, $id_auto));

    // Rimuovi anche dalla sessione locale se usi $_SESSION['carrello']
    if (isset($_SESSION['carrello'])) {
        $key = array_search($id_auto, $_SESSION['carrello']);
        if ($key !== false) {
            unset($_SESSION['carrello'][$key]);
            $_SESSION['carrello'] = array_values($_SESSION['carrello']);
        }
    }
}

// Reindirizza alla pagina principale (dove il carrello si aggiorna)
header("Location: auto.php");
exit;