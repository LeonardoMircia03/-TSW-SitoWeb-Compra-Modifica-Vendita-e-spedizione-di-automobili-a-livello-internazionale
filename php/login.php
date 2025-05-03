<?php
session_start();
require_once('config.php'); 

if ($_SERVER["REQUEST_METHOD"] === "POST") {


    $email = $_POST['email'] ?? null;
    $password = $_POST['password'] ?? null;

    if (!$email || !$password) {
        echo "Errore: email e password sono obbligatori.";
        
    }


    $query = "SELECT id, email, username, password FROM utenti WHERE email = $1";
    $result = pg_query_params($dbconnect, $query, array($email));

    if ($result) {
        $user = pg_fetch_assoc($result);

        if ($user) {
            if (password_verify($password, $user['password'])) {

                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['email'] = $user['email'];


                header("Location: login.html"); /* mettere la location giusta */
                exit;
            } else {
                echo "Errore: Password errata.";
            }
        } else {
            echo "Errore: Utente non trovato.";
        }
    } else {
        echo "Errore durante la connessione al database.";
    }
}
?>