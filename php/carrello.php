<?php
session_start();
require_once('config.php');

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$utente_id = $_SESSION['user_id'];

// Recupera le auto nel carrello dell'utente (con modifiche)
$query = "SELECT a.*, c.modifiche_estetiche, c.modifiche_tecniche FROM auto a JOIN carrello c ON a.id = c.auto_id WHERE c.utente_id = $1";
$result = pg_query_params($dbconnect, $query, array($utente_id));

$rows = [];
$totale_auto = 0;
$totale_modifiche = 0;
if ($result && pg_num_rows($result) > 0) {
    while ($row = pg_fetch_assoc($result)) {
        $rows[] = $row;
        $totale_auto += $row['prezzo'];
        
        // Gestione delle modifiche in formato JSON
        $modifiche_estetiche = json_decode($row['modifiche_estetiche'] ?? '[]', true);
        $modifiche_tecniche = json_decode($row['modifiche_tecniche'] ?? '[]', true);
        
        // Se il JSON non è valido, fallback a stringa vuota
        if (!is_array($modifiche_estetiche)) {
            $modifiche_estetiche = [];
        }
        if (!is_array($modifiche_tecniche)) {
            $modifiche_tecniche = [];
        }
        
        // Prezzi per modifiche
        $prezzi_estetici = [
            'cerchi' => 500,
            'tappeti' => 200,
            'paraurti' => 800,
            'luci' => 300,
            'wrap' => 1500
        ];
        
        $prezzi_tecnici = [
            'sospensioni' => 1200,
            'freni' => 1000,
            'turbo' => 2500,
            'cambio' => 900,
            'scarico' => 600
        ];
        
        // Calcola totale modifiche per questa auto
        foreach ($modifiche_estetiche as $modifica) {
            $totale_modifiche += $prezzi_estetici[$modifica] ?? 0;
        }
        
        foreach ($modifiche_tecniche as $modifica) {
            $totale_modifiche += $prezzi_tecnici[$modifica] ?? 0;
        }
    }
}

if (!$result || pg_num_rows($result) == 0) {
    // Messaggio se il carrello è vuoto
    ?>
    <!DOCTYPE html>
    <html lang="it">
    <head>
        <meta charset="UTF-8">
        <title>Carrello Vuoto</title>
        <link rel="stylesheet" href="../stilicss/index.css">
        <link rel="stylesheet" href="../stilicss/carrello.css">
    </head>
    <body>
        <header>
            <h1>Il tuo carrello</h1>
        </header>
        <div class="empty-cart">
            <img src="https://cdn-icons-png.flaticon.com/512/1163/1163661.png" alt="Carrello vuoto">

            <h2>Il carrello è vuoto 😕</h2>
            <p>Sembra che non hai ancora aggiunto nessuna auto al carrello.</p>
            <a href="auto.php" class="go-back-btn">⬅ Torna alla ricerca</a>
        </div>
    </body>
    </html>
    <?php
    exit;
}



$query = "SELECT a.*, c.modifiche_estetiche, c.modifiche_tecniche FROM auto a JOIN carrello c ON a.id = c.auto_id WHERE c.utente_id = $1";
$result = pg_query_params($dbconnect, $query, array($utente_id));

$rows = [];
$totale_auto = 0;
$totale_modifiche = 0;

if ($result && pg_num_rows($result) > 0) {
    while ($row = pg_fetch_assoc($result)) {
        $rows[] = $row;
        $totale_auto += $row['prezzo'];

        $estetiche = $row['modifiche_estetiche'];
        $tecniche = $row['modifiche_tecniche'];

        if (is_string($estetiche) && strpos($estetiche, '[') === 0) {
            $estetiche = json_decode($estetiche, true) ?? [];
        } else {
            $estetiche = explode(',', $estetiche);
        }
        
        if (is_string($tecniche) && strpos($tecniche, '[') === 0) {
            $tecniche = json_decode($tecniche, true) ?? [];
        } else {
            $tecniche = explode(',', $tecniche);
        }

        // Calcolo totale modifiche estetiche
        foreach ($estetiche as $modifica) {
            $modifica = trim($modifica);
            if (isset($prezzi_estetici[$modifica])) {
                $totale_modifiche += $prezzi_estetici[$modifica];
            }
        }
        
        // Calcolo totale modifiche tecniche
        foreach ($tecniche as $modifica) {
            $modifica = trim($modifica);
            if (isset($prezzi_tecnici[$modifica])) {
                $totale_modifiche += $prezzi_tecnici[$modifica];
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Il tuo carrello | AutoMarket</title>
    <link rel="stylesheet" href="../stilicss/carrello.css">
</head>
<body>
    <div class="video-background">a
        <video autoplay muted loop>
            <source src="../stilicss/Immagini/video.mp4" type="video/mp4">
        </video>
    </div>
    
    <header>
        <h1>IL TUO CARRELLO</h1>
        <p>Gestisci i tuoi veicoli e procedi al pagamento</p>
    </header>
    
    <div class="main-content">

    <div class="car-results">

        <?php foreach ($rows as $row): ?>
            <div class="car-item">
                <strong><?= htmlspecialchars($row['marca']) ?></strong><br>
                Modello: <?= htmlspecialchars($row['modello']) ?><br>
                Anno: <?= $row['anno'] ?><br>
                Prezzo: €<?= number_format($row['prezzo'], 2, ',', '.') ?><br>
                Città: <?= htmlspecialchars($row['citta']) ?>

                <form action="rimuovi_dal_carrello.php" method="POST">
                    <input type="hidden" name="id_auto" value="<?= $row['id'] ?>">
                    <button type="submit" class="remove-from-cart-btn">🗑️ Rimuovi</button>
                </form>
                <a href="modifiche.php?auto_id=<?= $row['id'] ?>" class="modify-btn">🔧 Modifica o rimuovi modifiche</a>
            </div>
        <?php endforeach; ?>

    </div>



<div class="cart-summary">
    <h3>
        Totale Auto: €<?= number_format($totale_auto, 2, ',', '.') ?><br>
        Totale Modifiche: €<?= number_format($totale_modifiche, 2, ',', '.') ?><br>
        <strong>Totale Finale: €<?= number_format($totale_auto + $totale_modifiche, 2, ',', '.') ?></strong>
    </h3>
</div>
<div class="button-group">
    <form action="svuota_carrello.php" method="POST" onsubmit="return confirm('Sei sicuro di voler rimuovere tutte le auto dal carrello?');">
        <button type="submit" class="empty-cart-btn">🗑️ Svuota carrello</button>
    </form>
    <a href="pagamento.php" class="modify-btn">💳 Paga</a>
</div>


    <div class="back-link">
        <a href="auto.php">⬅ Torna alla ricerca</a>
    </div>
       

</body>
</html>
