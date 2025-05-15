<?php
session_start();
require_once('config.php');

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$utente_id = $_SESSION['user_id'];

// Recupera le auto nel carrello dell'utente
$query = "
    SELECT a.* 
    FROM auto a
    JOIN carrello c ON a.id = c.auto_id
    WHERE c.utente_id = $1
";

$result = pg_query_params($dbconnect, $query, array($utente_id));

if (!$result || pg_num_rows($result) == 0) {
    // Messaggio se il carrello è vuoto
    ?>
    <!DOCTYPE html>
    <html lang="it">
    <head>
        <meta charset="UTF-8">
        <title>Carrello Vuoto</title>
        <style>
            body {
                font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
                background-color: #f5f5f5;
                margin: 0;
                padding: 0;
            }

            header {
                background: linear-gradient(135deg, #2c3e50, #3498db);
                color: white;
                padding: 20px;
                text-align: center;
                box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
            }

            .empty-cart {
                text-align: center;
                padding: 60px 20px;
                background-color: #fff;
                border-radius: 10px;
                max-width: 600px;
                margin: 50px auto;
                box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
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
            <!-- Icona -->
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
    exit;
}

$rows = [];
$totale = 0;

while ($row = pg_fetch_assoc($result)) {
    $rows[] = $row;
    $totale += $row['prezzo'];
}
$query = "
    SELECT a.*, c.modifiche_estetiche, c.modifiche_tecniche 
    FROM auto a
    JOIN carrello c ON a.id = c.auto_id
    WHERE c.utente_id = $1
";

$result = pg_query_params($dbconnect, $query, array($utente_id));

if (!$result || pg_num_rows($result) == 0) {
    // Messaggio se il carrello è vuoto
    ?>
    <!-- Pagina carrello vuoto -->
    <?php
    exit;
}

$rows = [];
$totale_auto = 0;
$totale_modifiche = 0;

// Prezzi fissi per tipo di modifica
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

while ($row = pg_fetch_assoc($result)) {
    $rows[] = $row;
    $totale_auto += $row['prezzo'];

    // Decodifica le modifiche
    $estetiche = json_decode($row['modifiche_estetiche'] ?? '[]', true);
    $tecniche = json_decode($row['modifiche_tecniche'] ?? '[]', true);

    if (is_array($estetiche)) {
        foreach ($estetiche as $modifica) {
            $totale_modifiche += $prezzi_estetici[$modifica] ?? 0;
        }
    }

    if (is_array($tecniche)) {
        foreach ($tecniche as $modifica) {
            $totale_modifiche += $prezzi_tecnici[$modifica] ?? 0;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Il tuo carrello</title>
    <style>
        
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f5f5f5;
            color: #333;
            line-height: 1.6;
            padding: 20px;
        }

        header {
            background: linear-gradient(135deg, #2c3e50, #3498db);
            color: white;
            padding: 20px;
            text-align: center;
            font-size: 2rem;
            letter-spacing: 1px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
            margin-bottom: 30px;
        }

        .car-results {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 20px;
            max-width: 1200px;
            margin: 0 auto;
        }

        .car-item {
            background-color: #fff;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            border-left: 5px solid #00bfa5;
        }

        .car-item:hover {
            transform: translateY(-5px);
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.15);
        }

        .car-item strong {
            color: #2c3e50;
            font-weight: 600;
        }

        .car-item p {
            margin: 6px 0;
            font-size: 0.95rem;
        }

        .remove-from-cart-btn {
            background-color: #f44336;
            color: white;
            border: none;
            padding: 8px 12px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            transition: background 0.3s ease;
            margin-top: 10px;
        }

        .remove-from-cart-btn:hover {
            background-color: #d32f2f;
        }

        .cart-summary {
            max-width: 1200px;
            margin: 30px auto;
            text-align: right;
            font-size: 1.2rem;
            padding: 15px;
            background-color: #e8f5e9;
            border-radius: 8px;
        }

        .cart-summary h3 {
            margin: 0;
        }

        footer {
            background: #333;
            color: #fff;
            text-align: center;
            padding: 15px;
            margin-top: 40px;
        }

        a {
            color: #4CAF50;
            text-decoration: none;
        }

        a:hover {
            text-decoration: underline;
        }

        .back-link {
            display: block;
            text-align: center;
            margin: 20px;
            font-size: 1rem;
        }

        .back-link a {
            padding: 10px 20px;
            background-color: #4CAF50;
            color: white;
            border-radius: 5px;
            transition: background 0.3s ease;
        }

        .back-link a:hover {
            background-color: #45a049;
        }
        .modify-btn {
            display: inline-block;
            margin-top: 10px;
            padding: 8px 12px;
            background-color: #00bcd4;
            color: white;
            border-radius: 5px;
            text-decoration: none;
            transition: background 0.3s ease;
}

        .modify-btn:hover {
             background-color: #0097a7;
}
    </style>
</head>
<body>
    <header>
        <h1>Il tuo carrello</h1>
    </header>

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
                <a href="../modifiche.php?auto_id=<?= $row['id'] ?>" class="modify-btn">🔧 Richiedi modifiche</a>
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
<div style="text-align: center; margin-top: 20px;">
    <button id="show-payment-btn" class="modify-btn">💳 Paga</button>
</div>


    <div class="back-link">
        <a href="auto.php">⬅ Torna alla ricerca</a>
    </div>
<div id="payment-section" style="display: none; max-width: 600px; margin: 30px auto; background-color: #fff; padding: 20px; border-radius: 10px; box-shadow: 0 4px 15px rgba(0,0,0,0.1);">
    <h2 style="text-align:center; margin-bottom: 20px;">💳 Pagamento</h2>
    <form action="processo_pagamento.php" method="POST">
        <label for="carta">Numero Carta:</label><br>
        <input type="number" id="carta" name="carta" required maxlength="16" style="width: 100%; padding: 8px; margin-bottom: 15px;"><br>

        <label for="scadenza">Data Scadenza:</label><br>
        <input type="date" id="scadenza" name="scadenza" required style="width: 100%; padding: 8px; margin-bottom: 15px;"><br>

        <label for="cvv">CVV:</label><br>
        <input type="number" id="cvv" name="cvv" required maxlength="3" style="width: 100%; padding: 8px; margin-bottom: 15px;"><br>

        <input type="hidden" name="importo" value="<?= $totale_auto + $totale_modifiche ?>">

        <button type="submit" class="modify-btn" style="width: 100%; font-size: 1.1rem;">✅ Conferma Pagamento</button>
    </form>
</div>
    <footer>
        <p>&copy; 2025 AutoMarket - Tutti i diritti riservati</p>
    </footer>
    <script>
    document.getElementById('show-payment-btn').addEventListener('click', function () {
        const section = document.getElementById('payment-section');
        section.style.display = section.style.display === 'none' ? 'block' : 'none';
        this.textContent = section.style.display === 'block' ? '❌ Chiudi Pagamento' : '💳 Paga';
    });
</script>

</body>
</html>
