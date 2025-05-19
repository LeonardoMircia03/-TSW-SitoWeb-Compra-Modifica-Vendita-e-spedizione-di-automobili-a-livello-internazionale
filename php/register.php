<?php
require_once('config.php'); 

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $username = $_POST['username'] ?? null;
    $email = $_POST['email'] ?? null;
    $password = $_POST['password'] ?? null;

    if (!$username || !$email || !$password ) {
        echo "Errore: Tutti i campi sono obbligatori.";
    }

      // Verifica se username o email sono già registrati
    $checkQuery = "SELECT * FROM utenti WHERE email = $1 OR username = $2";
    $checkParams = array($email, $username);
    $checkResult = pg_query_params($dbconnect, $checkQuery, $checkParams);

    if (pg_num_rows($checkResult) > 0) {

        echo '
        <html>
            <head><title>Errore</title></head>
            <body style="background: linear-gradient(to right, #d32f2f, #ff5722); display:flex; justify-content:center; align-items:center; height:100vh; margin:0; font-family: Arial;">
                <div style="background:#fff; color:#c62828; padding:30px; border-radius:10px; text-align:center; max-width:400px; width:90%; box-shadow:0 5px 15px rgba(0,0,0,0.2);">
                    <h2 style="color:#c62828;">❌Hai usato una email o un username gia in uso</h2>
                    <p>'.pg_last_error($dbconnect).'</p>
                    <a href="../login.html" style="display:inline-block; background:#c62828; color:white; padding:10px 20px; text-decoration:none; border-radius:5px;">Torna alla registrazione</a>
                </div>
            </body>
        </html>';

    } else {
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
        $insertQuery = "INSERT INTO utenti (email, username, password) VALUES ($1, $2, $3)";
        $insertParams = array($email, $username, $passwordHash);
         $insertResult = @pg_query_params($dbconnect, $insertQuery, $insertParams);
        echo '
        <html>
            <head>
                <title>Registrazione completata</title>
                <style>
                    body {
                        font-family: "Segoe UI", sans-serif;
                        background: linear-gradient(to right, #4CAF50, #66bb6a);
                        color: white;   
                        display: flex;
                        justify-content: center;
                        align-items: center;
                        height: 100vh;
                        margin: 0;
                    }
                    .success-box {
                        background-color: rgba(0, 0, 0, 0.6);
                        padding: 40px;
                        border-radius: 12px;
                        text-align: center;
                        max-width: 400px;
                        width: 90%;
                        box-shadow: 0 8px 20px rgba(0, 0, 0, 0.3);
                        animation: fadeIn 1s ease-in-out;
                    }
                    h2 {
                        margin-bottom: 20px;
                        font-size: 2rem;
                    }
                    p {
                        font-size: 1.1rem;
                        margin-bottom: 25px;
                    }
                    a {
                        display: inline-block;
                        background-color: #fff;
                        color: #43a047;
                        padding: 12px 24px;
                        text-decoration: none;
                        font-weight: bold;
                        border-radius: 5px;
                        transition: background 0.3s ease;
                    }
                    a:hover {
                        background-color: #f1f8e9;
                    }
                    @keyframes fadeIn {
                        from { opacity: 0; transform: translateY(-20px); }
                        to { opacity: 1; transform: translateY(0); }
                    }
                </style>
            </head>
            <body>
                <div class="success-box">
                    <h2>✅ Registrazione completata!</h2>
                    <p>Sei stato registrato correttamente. Ora puoi effettuare il login.</p>
                    <a href="../login.html">Accedi al tuo account</a>
                </div>
            </body>
        </html>';
    }
    

} 
?>