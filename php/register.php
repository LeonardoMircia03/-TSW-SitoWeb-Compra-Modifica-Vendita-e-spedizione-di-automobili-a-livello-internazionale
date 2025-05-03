<?php
require_once('config.php'); 

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    
    $username = $_POST['username'] ?? null;
    $email = $_POST['email'] ?? null;
    $password = $_POST['password'] ?? null;

    
    if (!$username || !$email || !$password ) {
        echo "Errore: Tutti i campi sono obbligatori.";
    }

    
    $passwordHash = password_hash($password, PASSWORD_DEFAULT);

    
    $query = "INSERT INTO utenti (email, username, password ) VALUES ($1, $2, $3)";
    $params = array($email, $username, $passwordHash);

    $result = pg_query_params($dbconnect, $query, $params);

    if ($result) {
        echo "Registrazione completata con successo!";
    } else {
        echo "Errore durante la registrazione: " . pg_last_error($dbconnect);
    }
} else {
    echo "Accesso non valido.";
}

?>