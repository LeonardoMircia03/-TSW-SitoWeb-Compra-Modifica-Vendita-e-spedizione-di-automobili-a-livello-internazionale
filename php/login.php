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
                // Login riuscito
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['email'] = $user['email'];

                header("Location: areaprivata.php");
                exit;
            } else {
                // Password errata
                echo '
                <html>
                    <head>
                        <meta charset="UTF-8">
                        <title>Password Errata</title>
                        <style>
                            body {
                                font-family: Arial, sans-serif;
                                background: linear-gradient(to right,rgb(56, 56, 56),rgb(33, 32, 32));
                                color: white;
                                display: flex;
                                justify-content: center;
                                align-items: center;
                                height: 100vh;
                                margin: 0;
                            }
                            .error-box {
                                background-color: rgba(0, 0, 0, 0.7);
                                padding: 30px;
                                border-radius: 10px;
                                text-align: center;
                                max-width: 400px;
                                width: 90%;
                                box-shadow: 0 8px 20px rgba(0, 0, 0, 0.5);
                            }
                            h2 {
                                margin-bottom: 20px;
                                font-size: 1.5rem;
                            }
                            p {
                                margin: 10px 0;
                                font-size: 1rem;
                            }
                            a {
                                display: inline-block;
                                margin-top: 20px;
                                padding: 10px 20px;
                                background-color: #fff;
                                color: black;
                                text-decoration: none;
                                border-radius: 5px;
                                font-weight: bold;
                                transition: background 0.3s ease;
                            }
                            a:hover {
                                background-color: #ffebee;
                            }
                        </style>
                    </head>
                    <body>
                        <div class="error-box">
                            <h2>❌ Password errata</h2>
                            <p>La password inserita non è corretta.</p>
                            <p>Hai dimenticato la password?</p>
                            <a href="../Recupero.html">Recupera Password</a>
                        </div>
                    </body>
                </html>';
                exit;
            }
        } else {
            // Utente non trovato
            echo '
            <html>
                <head>
                    <meta charset="UTF-8">
                    <title>Utente Non Trovato</title>
                    <style>
                        body {
                            font-family: Arial, sans-serif;
                            background: linear-gradient(to right, #1976d2, #2196f3);
                            color: white;
                            display: flex;
                            justify-content: center;
                            align-items: center;
                            height: 100vh;
                            margin: 0;
                        }
                        .error-box {
                            background-color: rgba(0, 0, 0, 0.7);
                            padding: 30px;
                            border-radius: 10px;
                            text-align: center;
                            max-width: 400px;
                            width: 90%;
                            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.5);
                        }
                        h2 {
                            margin-bottom: 20px;
                            font-size: 1.5rem;
                        }
                        p {
                            margin: 10px 0;
                            font-size: 1rem;
                        }
                        a {
                            display: inline-block;
                            margin-top: 20px;
                            padding: 10px 20px;
                            background-color: #fff;
                            color: #1976d2;
                            text-decoration: none;
                            border-radius: 5px;
                            font-weight: bold;
                            transition: background 0.3s ease;
                        }
                        a:hover {
                            background-color: #bbdefb;
                        }
                    </style>
                </head>
                <body>
                    <div class="error-box">
                        <h2>🚫 Utente non trovato</h2>
                        <p>Sembra che non tu abbia ancora un account.</p>
                        <p>Registrati per accedere alle tue auto preferite!</p>
                        <a href="../Login.html">Registrati Ora</a>
                    </div>
                </body>
            </html>';
            exit;
        }
    } else {
        // Errore generico sul database
        echo '
        <html>
            <head>
                <meta charset="UTF-8">
                <title>Errore Database</title>
                <style>
                    body {
                        font-family: Arial, sans-serif;
                        background: linear-gradient(to right, #4a148c, #8e24aa);
                        color: white;
                        display: flex;
                        justify-content: center;
                        align-items: center;
                        height: 100vh;
                        margin: 0;
                    }
                    .error-box {
                        background-color: rgba(0, 0, 0, 0.7);
                        padding: 30px;
                        border-radius: 10px;
                        text-align: center;
                        max-width: 400px;
                        width: 90%;
                        box-shadow: 0 8px 20px rgba(0, 0, 0, 0.5);
                    }
                    h2 {
                        margin-bottom: 20px;
                        font-size: 1.5rem;
                    }
                    p {
                        margin: 10px 0;
                        font-size: 1rem;
                    }
                    a {
                        display: inline-block;
                        margin-top: 20px;
                        padding: 10px 20px;
                        background-color: #fff;
                        color: #8e24aa;
                        text-decoration: none;
                        border-radius: 5px;
                        font-weight: bold;
                        transition: background 0.3s ease;
                    }
                    a:hover {
                        background-color: #e1bee7;
                    }
                </style>
            </head>
            <body>
                <div class="error-box">
                    <h2>🔧 Problema Tecnico</h2>
                    <p>Si è verificato un errore durante il tentativo di accesso.</p>
                    <p>Potresti riprovare più tardi o contattare l’amministratore.</p>
                    <a href="../login.html">Riprova</a>
                </div>
            </body>
        </html>';
        exit;
    }
}