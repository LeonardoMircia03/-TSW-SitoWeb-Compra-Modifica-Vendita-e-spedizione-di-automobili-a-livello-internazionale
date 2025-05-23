<?php
session_start();
require_once('config.php');

if (!isset($_GET['user_id'])) {
    echo "ID venditore non specificato.";
    exit;
}

$user_id = (int)$_GET['user_id'];

// Recupera informazioni del venditore
$query_venditore = "SELECT username FROM utenti WHERE id = $1";
$result_venditore = pg_query_params($dbconnect, $query_venditore, array($user_id));
if (!$result_venditore || pg_num_rows($result_venditore) === 0) {
    echo "Venditore non trovato.";
    exit;
}
$venditore = pg_fetch_assoc($result_venditore);

// Recupera recensioni ricevute
$query_recensioni = "SELECT r.rating, r.review_text, r.review_date, u.username as reviewer_name FROM user_car_reviews r JOIN utenti u ON r.reviewer_id = u.id WHERE r.seller_id = $1 ORDER BY r.review_date DESC";
$result_recensioni = pg_query_params($dbconnect, $query_recensioni, array($user_id));

?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Recensioni di <?= htmlspecialchars($venditore['username']) ?></title>
    <link rel="stylesheet" href="auto.css">
    <link rel="stylesheet" href="../stilicss/recensioni_venditore.css">
</head>
<body>
<div class="profile-container">
    <a href="auto.php" class="back-button">← Torna alla ricerca</a>
    <a id="header" href="<?php echo isset($_SESSION['user_id']) ? 'areaprivata.php' : '../index.html'; ?>"> AutoMarket - Trova la tua Auto </a>
    <h1 class="section-title">Recensioni per <span style="color:#00bfa5;"><?= htmlspecialchars($venditore['username']) ?></span></h1>
    <?php if ($result_recensioni && pg_num_rows($result_recensioni) > 0): ?>
        <?php while ($rec = pg_fetch_assoc($result_recensioni)): ?>
            <div class="review-card">
                <div class="review-header">
                    <span class="reviewer-name">Da: <?= htmlspecialchars($rec['reviewer_name']) ?></span>
                    <span class="review-rating">★ <?= intval($rec['rating']) ?>/5</span>
                    <span class="review-date">(<?= date('d/m/Y', strtotime($rec['review_date'])) ?>)</span>
                </div>
                <div><?= nl2br(htmlspecialchars($rec['review_text'])) ?></div>
            </div>
        <?php endwhile; ?>
    <?php else: ?>
        <div class="no-cars"><p>Nessuna recensione trovata per questo venditore.</p></div>
    <?php endif; ?>
</div>
</body>
</html>
