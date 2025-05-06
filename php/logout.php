<?php
// Avvia la gestione delle sessioni
session_start();

// Cancella tutte le variabili di sessione
$_SESSION = [];

// Se esiste un cookie di sessione, lo elimina
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

// Distruggi la sessione
session_destroy();

// Reindirizza l'utente alla homepage o alla pagina login
header("Location: auto.php"); // Puoi cambiare con index.php o login.php
exit;