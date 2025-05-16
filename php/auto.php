<?php
session_start();
require_once('config.php');
require_once('review_model.php');

$utente_loggato = null;
$review_error = '';
$review_success = '';

if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];

    // Recupera il username dell'utente loggato
    $query = "SELECT username FROM utenti WHERE id = $1";
    $result = pg_query_params($dbconnect, $query, array($user_id));

    if ($row = pg_fetch_assoc($result)) {
        $utente_loggato = $row['username'];
        $_SESSION['username'] = $utente_loggato; // Salva in sessione per usi futuri
    }
}


$marche_result = pg_query($dbconnect, "SELECT DISTINCT marca FROM auto ORDER BY marca ASC");
$modelli_result = pg_query($dbconnect, "SELECT DISTINCT modello FROM auto ORDER BY modello ASC");
$anni_result = pg_query($dbconnect, "SELECT DISTINCT anno FROM auto ORDER BY anno DESC");
$prezzi_result = pg_query($dbconnect, "SELECT DISTINCT prezzo FROM auto ORDER BY prezzo ASC");

$marca = $_GET['marca'] ?? '';
$modello = $_GET['modello'] ?? '';
$anno = $_GET['anno'] ?? '';
$prezzo = $_GET['prezzo'] ?? '';

$where_clauses = [];
$params = [];
$param_index = 1;

if (!empty($marca)) {
    $where_clauses[] = "marca = $" . $param_index++;
    $params[] = $marca;
}
if (!empty($modello)) {
    $where_clauses[] = "modello = $" . $param_index++;
    $params[] = $modello;
}
if (!empty($anno)) {
    $where_clauses[] = "anno = $" . $param_index++;
    $params[] = $anno;
}
if (!empty($prezzo)) {
    $where_clauses[] = "prezzo <= $" . $param_index++;
    $params[] = $prezzo;
}

$query = "SELECT * FROM auto";
if (!empty($where_clauses)) {
    $query .= " WHERE " . implode(" AND ", $where_clauses);
}

$result = pg_query_params($dbconnect, $query, $params);

// Inizializza il modello recensioni
require_once 'review_model.php';
$reviewModel = new ReviewModel($dbconnect);

// Handle review submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_review'])) {
    if (!isset($_SESSION['user_id'])) {
        $review_error = 'Devi effettuare il login per lasciare una recensione.';
    } else {
        $car_id = filter_input(INPUT_POST, 'car_id', FILTER_VALIDATE_INT);
        $seller_id = filter_input(INPUT_POST, 'seller_id', FILTER_VALIDATE_INT);
        $rating = filter_input(INPUT_POST, 'rating', FILTER_VALIDATE_INT);
        $review_text = filter_input(INPUT_POST, 'review_text', FILTER_SANITIZE_STRING);

        if (!$car_id || !$seller_id || !$rating || !$review_text) {
            $review_error = 'Tutti i campi sono obbligatori.';
        } else {
            try {
                $reviewModel = new ReviewModel($dbconnect);
                $result = $reviewModel->submitReview($seller_id, $_SESSION['user_id'], $car_id, $rating, $review_text);
                
                if ($result) {
                    $review_success = 'Recensione inviata con successo!';
                } else {
                    $review_error = 'Impossibile inviare la recensione. Riprova più tardi.';
                }
            } catch (Exception $e) {
                $review_error = 'Si è verificato un errore: ' . $e->getMessage();
            }
        }
    }
}

$totale_carrello = isset($_SESSION['carrello']) ? count($_SESSION['carrello']) : 0;
?>
<!DOCTYPE html>
<html lang="it">
<head>
    
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AutoMarket - Ricerca Auto</title>
    <link rel="stylesheet" href="auto.css">
</head>
<body>
<header class="main-header">
    <h1 ><a id ="header" href="areaprivata.php"> AutoMarket - Trova la tua Auto </a></h1>
    <div class="user-buttons">
        <?php if (isset($_SESSION['user_id'])): ?>
            <span class="welcome">👤 <?= htmlspecialchars($utente_loggato) ?></span>
            <a href="carrello.php" class="header-btn">🛒 Carrello (<?= $totale_carrello ?>)</a>
            <a href="sell_cars.php" class="header-btn">➕ Vendi</a>
            <a href="logout.php" class="header-btn">Logout</a>
        <?php else: ?>
            <a href="../Login.html" class="header-btn">Accedi</a>
            <a href="../Login.html" class="header-btn">Registrati</a>
        <?php endif; ?>
    </div>
</header>

<!-- HERO SECTION -->
<section class="hero-section" style="background: linear-gradient(120deg,#00bfa5 60%,#23272f 100%); color: #fff; padding: 60px 0 40px 0; text-align: center; box-shadow: 0 8px 32px rgba(0,0,0,0.18);">
    <h2 style="font-size: 2.5rem; margin-bottom: 10px; font-weight: 700; letter-spacing: 1.5px;">Benvenuto su AutoMarket</h2>
    <p style="font-size: 1.25rem; color: #e0f7fa; margin-bottom: 8px;">Il tuo portale di fiducia per comprare, vendere e recensire auto in tutta Italia</p>
    <p style="font-size: 1.1rem; color: #fff; max-width: 620px; margin: 0 auto;">Scopri centinaia di auto, leggi le recensioni dei venditori e trova la tua occasione perfetta in pochi click!</p>
</section>

<!-- FINE HERO SECTION -->
    

    <!-- SEZIONE MOTIVAZIONALE -->
<section class="why-us-section" style="background: #fff; color: #23272f; max-width: 1100px; margin: 40px auto 0 auto; border-radius: 18px; box-shadow: 0 4px 24px rgba(0,0,0,0.10); padding: 32px 28px 18px 28px; text-align: center;">
    <h3 style="font-size: 1.5rem; color: #00bfa5; margin-bottom: 18px;">Perché scegliere AutoMarket?</h3>
    <div style="display: flex; flex-wrap: wrap; justify-content: center; gap: 32px;">
        <div style="flex:1 1 220px; min-width: 180px;">
            <span style="font-size: 2.1rem;">🔒</span>
            <p style="font-weight: 500; margin-bottom: 4px;">Transazioni Sicure</p>
            <span style="font-size: 0.98rem; color: #555;">Pagamenti protetti e utenti verificati</span>
        </div>
        <div style="flex:1 1 220px; min-width: 180px;">
            <span style="font-size: 2.1rem;">⭐</span>
            <p style="font-weight: 500; margin-bottom: 4px;">Recensioni Autentiche</p>
            <span style="font-size: 0.98rem; color: #555;">Solo chi ha acquistato può recensire</span>
        </div>
        <div style="flex:1 1 220px; min-width: 180px;">
            <span style="font-size: 2.1rem;">🚗</span>
            <p style="font-weight: 500; margin-bottom: 4px;">Ampia Scelta</p>
            <span style="font-size: 0.98rem; color: #555;">Auto per ogni esigenza e budget</span>
        </div>
        <div style="flex:1 1 220px; min-width: 180px;">
            <span style="font-size: 2.1rem;">💬</span>
            <p style="font-weight: 500; margin-bottom: 4px;">Supporto Dedicato</p>
            <span style="font-size: 0.98rem; color: #555;">Assistenza rapida e cordiale</span>
        </div>
    </div>
</section>
<!-- FINE SEZIONE MOTIVAZIONALE -->

<div class="search-bar-container">
        <form method="GET" action="auto.php">
            <label for="marca">Marca:</label>
            <select name="marca" id="marca">
                <option value="">-- Tutte --</option>
                <?php while ($row_marca = pg_fetch_assoc($marche_result)) {
                    $selected = ($row_marca['marca'] === $marca) ? 'selected' : '';
                    echo "<option value=\"{$row_marca['marca']}\" $selected>{$row_marca['marca']}</option>";
                } ?>
            </select>

            <label for="modello">Modello:</label>
            <select name="modello" id="modello">
                <option value="">-- Tutti --</option>
                <?php while ($row_modello = pg_fetch_assoc($modelli_result)) {
                    $selected = ($row_modello['modello'] === $modello) ? 'selected' : '';
                    echo "<option value=\"{$row_modello['modello']}\" $selected>{$row_modello['modello']}</option>";
                } ?>
            </select>

            <label for="anno">Anno:</label>
            <select name="anno" id="anno">
                <option value="">-- Qualsiasi --</option>
                <?php while ($row_anno = pg_fetch_assoc($anni_result)) {
                    $selected = ($row_anno['anno'] == $anno) ? 'selected' : '';
                    echo "<option value=\"{$row_anno['anno']}\" $selected>{$row_anno['anno']}</option>";
                } ?>
            </select>

            <label for="prezzo">Prezzo massimo:</label>
            <select name="prezzo" id="prezzo">
                <option value="">-- Qualsiasi --</option>
                <?php while ($row_prezzo = pg_fetch_assoc($prezzi_result)) {
                    $selected = ($row_prezzo['prezzo'] == $prezzo) ? 'selected' : '';
                    echo "<option value=\"{$row_prezzo['prezzo']}\" $selected>€ {$row_prezzo['prezzo']}</option>";
                } ?>
            </select>

            <button type="submit">Cerca</button>
        </form>
    </div>

    <?php if ($result && pg_num_rows($result) > 0): ?>
    <div class="car-results" style="margin-top: 32px;">
<!-- Effetto fade-in sulle card -->
<link rel="stylesheet" href="auto.css">
<?php while ($row = pg_fetch_assoc($result)): ?>
    
<div class="car-item car-card">
    <div class="top-badge">TOP</div>
    <div class="car-details">
        
        <div class="car-details-content">
            <strong class="brand"> <?= htmlspecialchars($row['marca']) ?> </strong><br>
            <span class="model-label">Modello:</span> <span class="model"> <?= htmlspecialchars($row['modello']) ?> </span><br>
            <span class="info">Anno: <?= $row['anno'] ?> | Città: <?= htmlspecialchars($row['citta']) ?></span><br>
            <span class="price">Prezzo: €<?= number_format($row['prezzo'], 2, ',', '.') ?></span>
        </div>
    </div>
    <div class="actions">
        <form action="aggiungi_al_carrello.php" method="POST" class="cart-form">
            <input type="hidden" name="id_auto" value="<?= $row['id'] ?>">
            <button type="submit" class="add-to-cart-btn">🛒 Aggiungi al carrello</button>
        </form>
        
    </div>
</div>
<br>
    <?php 
                // Recensioni relative al venditore
                $seller_id = $row['utente_id'];
                $sellerReviews = $reviewModel->getSellerReviews($seller_id);
                $avgSellerRating = $reviewModel->getSellerAverageRating($seller_id);
                ?>
                <div class="seller-reviews">
                    <h3>Recensioni Venditore</h3>
                    <?php if ($avgSellerRating !== null): ?>
                        <div class="avg-rating">Valutazione media venditore: <?= round($avgSellerRating, 1) ?> / 5</div>
                    <?php else: ?>
                        <div class="avg-rating">Questo venditore non ha ancora recensioni.</div>
                    <?php endif; ?>
                    <?php if (!empty($sellerReviews)): ?>
                        <?php foreach ($sellerReviews as $review): ?>
                            <div class="review">
                                <div class="review-header">
                                    <span class="reviewer-name"><?= htmlspecialchars($review['reviewer_name']) ?></span>
                                    <div class="review-rating">
                                        <?php 
                                        for ($i = 1; $i <= 5; $i++) {
                                            echo $i <= $review['rating'] ? '★' : '☆';
                                        }
                                        ?>
                                    </div>
                                </div>
                                <p class="review-text"><?= htmlspecialchars($review['review_text']) ?></p>
                                <span class="review-date"><?= date('d/m/Y', strtotime($review['review_date'])) ?></span>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p>Nessuna recensione per questo venditore.</p>
                    <?php endif; ?>

                    <!-- Form Recensione Venditore -->
                    <?php 
require_once 'utils_acquisti.php';
$puo_recensire = false;
if (isset($_SESSION['user_id'])) {
    $puo_recensire = utenteHaAcquistatoDaVenditore($dbconnect, $_SESSION['user_id'], $seller_id);
}
?>
<?php if (isset($_SESSION['user_id']) && $puo_recensire): ?>
                        <div class="review-form">
                            <?php if (!empty($review_error)): ?>
                                <div class="error-message"><?= $review_error ?></div>
                            <?php endif; ?>
                            <?php if (!empty($review_success)): ?>
                                <div class="success-message"><?= $review_success ?></div>
                            <?php endif; ?>
                            <form method="POST" action="">
                                <input type="hidden" name="car_id" value="<?= $row['id'] ?>">
                                <input type="hidden" name="seller_id" value="<?= $seller_id ?>">
                                <label for="rating">Valutazione venditore:</label>
                                <select name="rating" id="rating" required>
                                    <option value="">-- Seleziona --</option>
                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                        <option value="<?= $i ?>"><?= $i ?></option>
                                    <?php endfor; ?>
                                </select>
                                <label for="review_text">Recensione:</label>
                                <textarea name="review_text" id="review_text" required placeholder="Scrivi la tua recensione sul venditore..."></textarea>
                                <button type="submit" name="submit_review">Invia Recensione</button>
                            </form>
                        </div>
                    <?php else: ?>
                        <?php if (!isset($_SESSION['user_id'])): ?>
                            <p>Effettua il <a href="../Login.html">login</a> per lasciare una recensione.</p>
                        <?php else: ?>
                            <p>Puoi recensire questo venditore solo dopo aver acquistato da lui.</p>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
                </div>
            </div>
        <?php endwhile; ?>
    </div>
<?php else: ?>
    <p>Nessuna auto trovata.</p>
    <a id="back_to_home" href="areaprivata.php">Torna alla Home</a>
<?php endif; ?>

    <script>
    document.addEventListener('DOMContentLoaded', function () {
        const marcaSelect = document.getElementById('marca');
        const modelloSelect = document.getElementById('modello');

        function aggiornaModelli() {
            const marca = marcaSelect.value;

            modelloSelect.innerHTML = '<option value="">-- Tutti --</option>';

            if (marca !== '') {
                fetch(`get_modelli.php?marca=${encodeURIComponent(marca)}`)
                    .then(response => response.text())
                    .then(data => {
                        modelloSelect.innerHTML += data;
                    });
            }
        }

        if (marcaSelect.value !== '') {
            aggiornaModelli();
        }

        marcaSelect.addEventListener('change', aggiornaModelli);
    });
    </script>
</body>

