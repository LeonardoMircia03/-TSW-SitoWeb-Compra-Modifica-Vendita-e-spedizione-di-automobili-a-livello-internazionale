<?php
session_start();
require_once('config.php');

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Verifica i dati del pagamento
if (
    empty($_POST['carta']) ||
    empty($_POST['scadenza']) ||
    empty($_POST['cvv'])
) {
    header("Location: carrello.php");
    exit;
}

$utente_id = $_SESSION['user_id'];

// Svuota il carrello dell'utente nel database
$query = "DELETE FROM carrello WHERE utente_id = $1";
$result = pg_query_params($dbconnect, $query, array($utente_id));

if (!$result) {
    // Errore durante la rimozione
    die("Errore durante il pagamento. Riprova.");
}

// Svuota anche il carrello nella sessione per aggiornare il contatore
if (isset($_SESSION['carrello'])) {
    $_SESSION['carrello'] = [];
}

// Reindirizza a una pagina di conferma
header("Location: pagamento_successo.php");
exit;
?>
