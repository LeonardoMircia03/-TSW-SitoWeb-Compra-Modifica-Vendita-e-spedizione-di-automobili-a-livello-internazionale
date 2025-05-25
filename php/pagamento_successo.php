<?php
session_start();
require_once('config.php');

// Se il pagamento è stato completato con successo
if (isset($_GET['success']) && $_GET['success'] == '1') {
    $utente_id = $_SESSION['user_id'];
    
    // Verifica se la connessione al database è attiva
    if (!$dbconnect) {
        die("Errore: Connessione al database non riuscita!");
    }
    
    // Recupera le auto dal carrello
    $query_carrello = "
        SELECT c.auto_id, a.utente_id, a.prezzo, c.modifiche_estetiche, c.modifiche_tecniche 
        FROM carrello c 
        JOIN auto a ON c.auto_id = a.id 
        WHERE c.utente_id = $1
    ";
    
    error_log("Query carrello: " . $query_carrello);
    
    $result_carrello = pg_query_params($dbconnect, $query_carrello, array($utente_id));
    
    if (!$result_carrello) {
        error_log("Errore nella query del carrello: " . pg_last_error($dbconnect));
        die("Errore durante la query del carrello. Riprova.");
    }
    
    $num_rows = pg_num_rows($result_carrello);
    error_log("Numero di auto trovate: " . $num_rows);
    
    if ($num_rows > 0) {
        // Per ogni auto nel carrello
        while ($row = pg_fetch_assoc($result_carrello)) {
            error_log("Elaborando auto_id: " . $row['auto_id']);
            
            // Inserisce la transazione
            $query_transazione = "
                INSERT INTO transazione (auto_id, venditore_id, acquirente_id, prezzo_totale, modifiche_estetiche, modifiche_tecniche, data_transazione)
                VALUES ($1, $2, $3, $4, $5, $6, CURRENT_TIMESTAMP)
            ";
            
            $params = array(
                $row['auto_id'],
                $row['utente_id'],
                $utente_id,
                $row['prezzo'],
                $row['modifiche_estetiche'],
                $row['modifiche_tecniche']
            );
            
            error_log("Eseguendo query transazione: " . $query_transazione);
            error_log("Parametri: " . json_encode($params));
            
            $result_transazione = pg_query_params($dbconnect, $query_transazione, $params);
            
            if (!$result_transazione) {
                $error = pg_last_error($dbconnect);
                error_log("Errore nella query: " . $error);
                die("Errore durante la registrazione della transazione: " . $error);
            }
            
            error_log("Transazione salvata con successo per auto_id: " . $row['auto_id']);
        }

        // Solo dopo aver salvato tutte le transazioni, svuota il carrello
        $query = "DELETE FROM carrello WHERE utente_id = $1";
        $result = pg_query_params($dbconnect, $query, array($utente_id));
        
        if (!$result) {
            error_log("Errore durante la rimozione dal carrello: " . pg_last_error($dbconnect));
            die("Errore durante la rimozione dal carrello. Riprova.");
        }
        
        // Svuota anche il carrello nella sessione
        if (isset($_SESSION['carrello'])) {
            $_SESSION['carrello'] = [];
        }
    } else {
        error_log("Nessuna auto trovata nel carrello!");
        die("Nessuna auto trovata nel carrello. Riprova.");
    }
}
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Pagamento Completato</title>
    <style>
        body {
            font-family: 'Segoe UI', sans-serif;
            background-color: #f5f5f5;
            text-align: center;
            padding: 60px 20px;
        }
        .success-box {
            background: #e8f5e9;
            padding: 30px;
            border-radius: 10px;
            max-width: 600px;
            margin: auto;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
        }
        .success-box h1 {
            color: #2e7d32;
            font-size: 2rem;
            margin-bottom: 20px;
        }
        .success-box p {
            font-size: 1.1rem;
            color: #444;
        }
        .back-link {
            margin-top: 30px;
            display: inline-block;
            background-color: #4CAF50;
            color: white;
            padding: 12px 24px;
            border-radius: 5px;
            text-decoration: none;
        }
        .back-link:hover {
            background-color: #45a049;
        }
    </style>
</head>
<body>
    <div class="success-box">
        <h1>✅ Pagamento completato con successo!</h1>
        <?php
        if (isset($_GET['success']) && $_GET['success'] == '1') {
            echo '<p style="color: #2e7d32;">Le transazioni sono state salvate correttamente nel database!</p>';
        }
        ?>
        <p>Grazie per il tuo acquisto. Ti abbiamo inviato una conferma via email (simulata).</p>
        <a class="back-link" href="auto.php">Torna alla ricerca</a>
    </div>
</body>
</html>
