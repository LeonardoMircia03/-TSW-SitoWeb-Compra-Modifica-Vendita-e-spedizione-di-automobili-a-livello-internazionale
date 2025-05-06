<?php
session_start();
require_once('config.php');

if (empty($_SESSION['carrello'])) {
    // Carrello vuoto: mostra messaggio personalizzato
    ?>
    <!DOCTYPE html>
    <html lang="it">
    <head>
        <meta charset="UTF-8">
        <title>Carrello Vuoto</title>
        <link rel="stylesheet" href="auto.css">
        <style>
            .empty-cart {
                text-align: center;
                padding: 60px 20px;
                background-color: #f9f9f9;
                border-radius: 10px;
                max-width: 600px;
                margin: 50px auto;
                box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            }

            .empty-cart h2 {
                color: #333;
                font-size: 2rem;
                margin-bottom: 20px;
            }

            .empty-cart p {
                font-size: 1.1rem;
                color: #666;
                margin-bottom: 30px;
            }

            .empty-cart img {
                width: 100px;
                height: auto;
                margin-bottom: 20px;
            }

            .go-back-btn {
                padding: 12px 24px;
                background-color: #4CAF50;
                color: white;
                border: none;
                border-radius: 5px;
                font-size: 16px;
                cursor: pointer;
                transition: background-color 0.3s ease;
                text-decoration: none;
                display: inline-block;
            }

            .go-back-btn:hover {
                background-color: #45a049;
            }
        </style>
    </head>
    <body>

        <header>
            <h1>Il tuo carrello</h1>
        </header>

        <div class="empty-cart">
            <!-- Icona / Immagine -->
            <img src="https://cdn-icons-png.flaticon.com/512/1163/1163661.png" alt="Carrello vuoto">

            <!-- Messaggio -->
            <h2>Il carrello è vuoto 😕</h2>
            <p>Sembra che non hai ancora aggiunto nessuna auto al carrello.</p>

            <!-- Bottone per tornare alle auto -->
            <a href="auto.php" class="go-back-btn">⬅ Torna alla ricerca</a>
        </div>

    </body>
    </html>
    <?php
    exit; // Ferma l'esecuzione se il carrello è vuoto
}

// Altrimenti, continua a mostrare le auto nel carrello...
$ids = implode(',', $_SESSION['carrello']);
$query = "SELECT * FROM auto WHERE id IN ($ids)";
$result = pg_query($dbconnect, $query);
?>

<!-- Continua con il layout normale del carrello -->
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Il tuo carrello</title>
    <link rel="stylesheet" href="auto.css">
</head>
<body>
    <header>
        <h1>Carrello</h1>
        <a href="auto.php">⬅ Torna alla ricerca</a>
    </header>

    <div class="car-results">
        <?php while ($row = pg_fetch_assoc($result)): ?>
            <div class="car-item">
                <strong><?= htmlspecialchars($row['marca']) ?></strong><br>
                Modello: <?= htmlspecialchars($row['modello']) ?><br>
                Anno: <?= $row['anno'] ?><br>
                Prezzo: €<?= number_format($row['prezzo'], 2, ',', '.') ?><br>
                Città: <?= htmlspecialchars($row['citta']) ?>

                <form action="rimuovi_dal_carrello.php" method="POST" style="margin-top: 10px;">
                    <input type="hidden" name="id_auto" value="<?= $row['id'] ?>">
                    <button type="submit" class="remove-from-cart-btn">🗑️ Rimuovi</button>
                </form>
            </div>
        <?php endwhile; ?>
    </div>
</body>
</html>