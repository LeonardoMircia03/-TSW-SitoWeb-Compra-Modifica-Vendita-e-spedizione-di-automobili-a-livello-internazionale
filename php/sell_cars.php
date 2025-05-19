<?php
session_start();
require_once('config.php');

// Verifica che l'utente sia loggato
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$success = "";
$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $marca = pg_escape_string($dbconnect, $_POST['marca']);
    $modello = pg_escape_string($dbconnect, $_POST['modello']);
    $anno = intval($_POST['anno']);
    $prezzo = floatval($_POST['prezzo']);
    $citta = pg_escape_string($dbconnect, $_POST['citta']);
    $descrizione = pg_escape_string($dbconnect, $_POST['descrizione'] ?? '');
    $utente_id = $_SESSION['user_id'];

    // Validazione base
    if (empty($marca) || empty($modello) || empty($anno) || empty($prezzo) || empty($citta)) {
        $error = "Tutti i campi sono obbligatori.";
    } else {
        // Inserisci l'auto nel database
        $query = "INSERT INTO auto (marca, modello, anno, prezzo, citta, utente_id, descrizione)
                  VALUES ('$marca', '$modello', $anno, $prezzo, '$citta', $utente_id, '$descrizione')";

        if (pg_query($dbconnect, $query)) {
            $success = "Auto inserita correttamente!";
        } else {
            $error = "Errore durante l'inserimento dell'auto.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Inserisci Annuncio</title>
    <link rel="stylesheet" href="auto.css">
    <style>
        body {
            font-family: 'Segoe UI', sans-serif;
            background-color: #f5f5f5;
            margin: 0;
            padding: 20px;
        }

        .container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #fff;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        h2 {
            text-align: center;
            color: #333;
            margin-bottom: 20px;
        }

        label {
            display: block;
            margin-top: 15px;
            font-weight: bold;
            color: #1a1a1a;  
            font-size: 1rem;  
        }

        input[type="text"],
        input[type="number"],
        input[type="number"],
        textarea {
            width: 100%;
            padding: 10px;
            margin-top: 5px;
            border: 1px solid #ccc;
            border-radius: 5px;
            font-size: 1rem;
        }

        button {
            margin-top: 20px;
            width: 100%;
            padding: 12px;
            background-color: #4CAF50;
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            cursor: pointer;
            transition: background 0.3s ease;
        }

        button:hover {
            background-color: #45a049;
        }

        .message {
            text-align: center;
            margin-top: 20px;
            padding: 10px;
            border-radius: 5px;
        }

        .success {
            background-color: #e8f5e9;
            color: green;
        }

        .error {
            background-color: #ffebee;
            color: red;
        }

        .back-link {
            display: block;
            text-align: center;
            margin-top: 20px;
        }

        .back-link a {
            color: #4CAF50;
            text-decoration: none;
            font-weight: bold;
        }

        .back-link a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>

<div class="container">
    <h2>Inserisci un'Auto in Vendita</h2>

    <?php if ($success): ?>
        <div class="message success"><?= htmlspecialchars($success) ?></div>
    <?php elseif ($error): ?>
        <div class="message error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST" action="sell_cars.php">
        <label for="marca">Marca:</label>
        <input type="text" id="marca" name="marca" required>

        <label for="modello">Modello:</label>
        <input type="text" id="modello" name="modello" required>

        <label for="anno">Anno:</label>
        <input type="number" id="anno" name="anno" min="1900" max="<?= date("Y") ?>" required>

        <label for="prezzo">Prezzo (€):</label>
        <input type="number" id="prezzo" name="prezzo" step="100" min="0" required>

        <label for="citta">Città:</label>
        <input type="text" id="citta" name="citta" required>

        <label for="descrizione">Descrizione dell'auto (opzionale):</label>
        <textarea id="descrizione" name="descrizione" rows="4" placeholder="Inserisci dettagli aggiuntivi sull'auto"></textarea>

        <button type="submit">Inserisci Auto</button>
    </form>

    <div class="back-link">
        <a href="auto.php">⬅ Torna alla ricerca</a>
    </div>
</div>

</body>
</html>