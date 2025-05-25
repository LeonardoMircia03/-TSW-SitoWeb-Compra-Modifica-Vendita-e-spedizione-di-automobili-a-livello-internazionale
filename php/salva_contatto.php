<?php
session_start();
require_once('config.php');
if (!isset($dbconnect) || !$dbconnect) {
    die("Connessione al database fallita.");
}

// Recupera i dati dal form
$nome = trim($_POST['nome'] ?? '');
$email = trim($_POST['email'] ?? '');
$oggetto = trim($_POST['oggetto'] ?? '');
$messaggio = trim($_POST['messaggio'] ?? '');
$utente_id = isset($_SESSION['user_id']) ? intval($_SESSION['user_id']) : null;

// Validazione base
if ($nome && $email && $oggetto && $messaggio) {
    if ($utente_id) {
        $query = "INSERT INTO forum_contatti (utente_id, nome, email, oggetto, messaggio) VALUES ($1, $2, $3, $4, $5)";
        $params = array($utente_id, $nome, $email, $oggetto, $messaggio);
    } else {
        $query = "INSERT INTO forum_contatti (nome, email, oggetto, messaggio) VALUES ($1, $2, $3, $4)";
        $params = array($nome, $email, $oggetto, $messaggio);
    }
    $result = pg_query_params($dbconnect, $query, $params);
    if ($result) {
        header("Location: ../html/about.html?success=1");
        exit();
    } else {
        echo "<p style='color:red;'>Errore nell'invio del messaggio.</p>";
    }
} else {
    echo "<p style='color:red;'>Compila tutti i campi.</p>";
}
?>
