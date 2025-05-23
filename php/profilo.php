<?php
session_start();
require_once('config.php');

// Verifica che l'utente sia loggato
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Handle car removal
$remove_success = '';
$remove_error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['remove_car_id'])) {
    $car_id = (int)$_POST['remove_car_id'];
    // Ensure the car belongs to the user and is not sold
    $check_query = "SELECT id FROM auto WHERE id = $1 AND utente_id = $2";
    $check_result = pg_query_params($dbconnect, $check_query, [$car_id, $user_id]);
    if (pg_num_rows($check_result) > 0) {
        // Delete the car
        $delete_query = "DELETE FROM auto WHERE id = $1 AND utente_id = $2";
        $delete_result = pg_query_params($dbconnect, $delete_query, [$car_id, $user_id]);
        if ($delete_result) {
            $remove_success = 'Auto rimossa con successo.';
            header("Location: profilo.php?removed=1");
            exit;
        } else {
            $remove_error = 'Errore durante la rimozione.';
        }
    } else {
        $remove_error = 'Non puoi rimuovere questa auto.';
    }
}
if (isset($_GET['removed'])) {
    $remove_success = 'Auto rimossa con successo.';
}


// Query per auto in vendita dell'utente
$query_in_vendita = "
    SELECT * 
    FROM auto 
    WHERE utente_id = $user_id 
    AND id NOT IN (
        SELECT auto_id 
        FROM transazione 
        WHERE venditore_id = $user_id
    )
";
$result_in_vendita = pg_query($dbconnect, $query_in_vendita);

// Query per auto vendute dall'utente
$query_vendute = "
    SELECT auto.*, utenti.username AS nome_acquirente
    FROM auto 
    JOIN transazione ON auto.id = transazione.auto_id
    JOIN utenti ON transazione.acquirente_id = utenti.id
    WHERE transazione.venditore_id = $user_id
";
$result_vendute = pg_query($dbconnect, $query_vendute);

// Query per auto acquistate dall'utente
$query_acquistate = "
    SELECT auto.*, utenti.username AS nome_venditore
    FROM auto 
    JOIN transazione ON auto.id = transazione.auto_id
    JOIN utenti ON transazione.venditore_id = utenti.id
    WHERE transazione.acquirente_id = $1
";
$result_acquistate = pg_query_params($dbconnect, $query_acquistate, array($user_id));
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Profilo Utente - Le Mie Auto</title>
    <link rel="stylesheet" href="auto.css">
    <link rel="stylesheet" href="../stilicss/profilo.css">
    <style>
        .profile-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f4f4f4;
        }

        .section-title {
            color: #00bfa5;
            margin-bottom: 20px;
            border-bottom: 2px solid #00bfa5;
            padding-bottom: 10px;
        }

        .car-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 20px;
        }

        .no-cars {
            text-align: center;
            color: #888;
            padding: 20px;
            background-color: #fff;
            border-radius: 8px;
        }

        .back-button {
            display: inline-block;
            background-color: #00bfa5;
            color: white;
            padding: 10px 20px;
            text-decoration: none;
            border-radius: 5px;
            margin-bottom: 20px;
            transition: background-color 0.3s ease;
        }

        .back-button:hover {
            background-color: #008f7f;
        }
        .success-message {
            background: #d4edda;
            color: #155724;
            padding: 12px 18px;
            border-radius: 6px;
            margin-bottom: 18px;
            border: 1px solid #c3e6cb;
            text-align: center;
        }
        .error-message {
            background: #f8d7da;
            color: #721c24;
            padding: 12px 18px;
            border-radius: 6px;
            margin-bottom: 18px;
            border: 1px solid #f5c6cb;
            text-align: center;
        }
        .remove-btn {
            background: #e53935;
            color: #fff;
            border: none;
            padding: 7px 16px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1rem;
            transition: background 0.2s;
        }
        .remove-btn:hover {
            background: #b71c1c;
        }
    </style>
</head>
<body>
    <div class="profile-container">
        <a href="areaprivata.php" class="back-button">← Torna all'Area Privata</a>
        <a href="auto.php" class="back-button" style="margin-left:10px;">🔍 Ricerca Auto</a>

        <?php if (
            !empty(
                $remove_success
            )): ?>
            <div class="success-message"><?= htmlspecialchars($remove_success) ?></div>
        <?php endif; ?>
        <?php if (!empty($remove_error)): ?>
            <div class="error-message"><?= htmlspecialchars($remove_error) ?></div>
        <?php endif; ?>
        
        <h1 class="section-title" align="center">🚗 Le Mie Auto</h1>

        <h2 class="section-title" align="center">Auto in Vendita</h2>
        <div class="car-grid">
            <?php if (pg_num_rows($result_in_vendita) > 0): ?>
                <?php while ($row = pg_fetch_assoc($result_in_vendita)): ?>
                    <div class="car-item car-card">
                        <div class="car-details">
                            <div class="car-details-content">
                                <strong class="brand"><?= htmlspecialchars($row['marca']) ?></strong><br>
                                <span class="model-label">Modello:</span> <span class="model"><?= htmlspecialchars($row['modello']) ?></span><br>
                                <span class="info">Anno: <?= $row['anno'] ?> | Città: <?= htmlspecialchars($row['citta']) ?></span><br>
                                <p>Prezzo: € <?= number_format($row['prezzo'], 2, ',', '.') ?></p>
                                <form method="POST" onsubmit="return confirm('Sei sicuro di voler rimuovere questa auto?');" style="margin-top:10px;">
    <input type="hidden" name="remove_car_id" value="<?= $row['id'] ?>">
    <button type="submit" class="remove-btn">🗑️ Rimuovi</button>
</form>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="no-cars">
                    <p>Non hai auto attualmente in vendita.</p>
                </div>
            <?php endif; ?>
        </div>

        <h2 class="section-title">Auto Vendute</h2>
        <div class="car-grid">
            <?php if (pg_num_rows($result_vendute) > 0): ?>
                <?php while ($row = pg_fetch_assoc($result_vendute)): ?>
                    <div class="car-item car-card">
                        <div class="car-details">
                            <div class="car-details-content">
                                <strong class="brand"><?= htmlspecialchars($row['marca']) ?></strong><br>
                                <span class="model-label">Modello:</span> <span class="model"><?= htmlspecialchars($row['modello']) ?></span><br>
                                <span class="info">Anno: <?= $row['anno'] ?> | Città: <?= htmlspecialchars($row['citta']) ?></span><br>
                                <p>Prezzo: € <?= number_format($row['prezzo'], 2, ',', '.') ?></p>
                                <?php if ($row['nome_acquirente']): ?>
                                    <p class="seller-name">Acquistata da: <?= htmlspecialchars($row['nome_acquirente']) ?></p>
                                <?php else: ?>
                                    <p class="seller-name">Non ancora venduta</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="no-cars">
                    <p>Non hai auto vendute.</p>
                </div>
            <?php endif; ?>
        </div>

        <h2 class="section-title">Auto Acquistate</h2>
        <?php if (isset($_GET['review']) && $_GET['review'] === 'success'): ?>
            <div style="background: #d4edda; color: #155724; border: 1px solid #c3e6cb; border-radius: 6px; padding: 14px 18px; margin-bottom: 18px; display: flex; align-items: center; gap: 10px; font-size: 1.08rem;">
                <span style="font-size: 1.5em;">&#10003;</span>
                <span><strong>Recensione inviata!</strong> Grazie per aver condiviso la tua esperienza.</span>
            </div>
        <?php endif; ?>
        <div class="car-grid">
            <?php if (pg_num_rows($result_acquistate) > 0): ?>
                <?php while ($row = pg_fetch_assoc($result_acquistate)): ?>
                    <div class="car-item car-card">
                        <div class="car-details">
                            <div class="car-details-content">
                                <strong class="brand"><?= htmlspecialchars($row['marca']) ?></strong><br>
                                <span class="model-label">Modello:</span> <span class="model"><?= htmlspecialchars($row['modello']) ?></span><br>
                                <span class="info">Anno: <?= $row['anno'] ?> | Città: <?= htmlspecialchars($row['citta']) ?></span><br>
                                <p>Prezzo: € <?= number_format($row['prezzo'], 2, ',', '.') ?></p>
                                <p>Venditore: <?= htmlspecialchars($row['nome_venditore']) ?></p>
                                <?php
                                    // Mostra il form recensione solo se non già recensito
                                    $giàRecensito = false;
                                    // Serve id venditore e id auto
                                    $venditore_id = $row['venditore_id'] ?? $row['utente_id'] ?? null;
                                    $car_id = $row['id'];
                                    if ($venditore_id) {
                                        $check_review = pg_query_params(
                                            $dbconnect,
                                            "SELECT 1 FROM user_car_reviews WHERE seller_id = $1 AND reviewer_id = $2 AND car_id = $3 LIMIT 1",
                                            array($venditore_id, $user_id, $car_id)
                                        );
                                        if ($check_review && pg_num_rows($check_review) > 0) {
                                            $giàRecensito = true;
                                        }
                                    }
                                ?>
                                <div class="review-section">
                                    <?php if ($giàRecensito): ?>
                                        <div style="background: #23272f; color: #e0e0e0; border: 1px solid #444; border-radius: 6px; padding: 13px 18px; margin-bottom: 12px; font-size: 1.05rem;">
    <span style="font-size:1.2em;">&#9888;&#65039;</span> Hai già lasciato una recensione per questa auto e venditore.
</div>
                                    <?php else: ?>
                                        <form class="review-form" method="POST" action="submit_review.php" onsubmit="return confirm('Confermi l\'invio della recensione?');">
                                            <input type="hidden" name="seller_id" value="<?= htmlspecialchars($venditore_id) ?>">
                                            <input type="hidden" name="car_id" value="<?= htmlspecialchars($car_id) ?>">
                                            <label for="rating_<?= $car_id ?>">Valutazione:</label>
                                            <select name="rating" id="rating_<?= $car_id ?>" required>
                                                <option value="">Scegli...</option>
                                                <?php for ($i = 5; $i >= 1; $i--): ?>
                                                    <option value="<?= $i ?>"><?= $i ?> stelle</option>
                                                <?php endfor; ?>
                                            </select>
                                            <label for="review_text_<?= $car_id ?>">Recensione:</label>
                                            <textarea name="review_text" id="review_text_<?= $car_id ?>" maxlength="500" required placeholder="Scrivi la tua esperienza..."></textarea>
                                            <button type="submit">Invia Recensione</button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="no-cars">
                    <p>Non hai auto acquistate.</p>
                </div>
            <?php endif; ?>
        </div>

        <?php pg_free_result($result_in_vendita); ?>
        <?php pg_free_result($result_vendute); ?>
        <?php pg_free_result($result_acquistate); ?>
    </div>
</body>
</html>
