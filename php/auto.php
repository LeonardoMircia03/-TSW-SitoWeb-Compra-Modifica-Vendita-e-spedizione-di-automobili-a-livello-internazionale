<?php
session_start();
require_once('config.php');
require_once('review_model.php');
$utente_loggato = null;
$review_error = '';
$review_success = '';
$user_id = null;
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $query = "SELECT username FROM utenti WHERE id = $1";
    $result = pg_query_params($dbconnect, $query, array($user_id));
    if ($row = pg_fetch_assoc($result)) {
        $utente_loggato = $row['username'];
        $_SESSION['username'] = $utente_loggato; 
    }
}
$marche_result = pg_query($dbconnect, "SELECT DISTINCT marca FROM auto ORDER BY marca ASC");
if (!empty($marca)) {
    $modelli_result = pg_query_params(
        $dbconnect,
        "SELECT DISTINCT modello FROM auto WHERE id NOT IN (SELECT auto_id FROM transazione) AND marca = $1 ORDER BY modello ASC",
        array($marca)
    );
} else {
    $modelli_result = pg_query($dbconnect, "SELECT DISTINCT modello FROM auto WHERE id NOT IN (SELECT auto_id FROM transazione) ORDER BY modello ASC");
}
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

if ($user_id !== null) {
    $query = "SELECT auto.*, utenti.username AS nome_venditore, utenti.id AS utente_id 
        FROM auto 
        JOIN utenti ON auto.utente_id = utenti.id 
        WHERE auto.id NOT IN (
            SELECT auto_id 
            FROM transazione
        )
        AND auto.utente_id != $1";
    $params_with_user = array($user_id);
    $param_index = 2;
    $where_clauses_fixed = [];
    if (!empty($marca)) {
        $where_clauses_fixed[] = "marca = $" . $param_index++;
        $params_with_user[] = $marca;
    }
    if (!empty($modello)) {
        $where_clauses_fixed[] = "modello = $" . $param_index++;
        $params_with_user[] = $modello;
    }
    if (!empty($anno)) {
        $where_clauses_fixed[] = "anno = $" . $param_index++;
        $params_with_user[] = $anno;
    }
    if (!empty($prezzo)) {
        $where_clauses_fixed[] = "prezzo <= $" . $param_index++;
        $params_with_user[] = $prezzo;
    }
    if (!empty($where_clauses_fixed)) {
        $query .= " AND " . implode(" AND ", $where_clauses_fixed);
    }
    $result = pg_query_params($dbconnect, $query, $params_with_user);
} else {
    $query = "SELECT auto.*, utenti.username AS nome_venditore, utenti.id AS utente_id 
        FROM auto 
        JOIN utenti ON auto.utente_id = utenti.id 
        WHERE auto.id NOT IN (
            SELECT auto_id 
            FROM transazione
        )";
    if (!empty($where_clauses)) {
        $query .= " AND " . implode(" AND ", $where_clauses);
    }
    $result = pg_query($dbconnect, $query);
}

// Inizializza il modello recensioni
require_once 'review_model.php';
$reviewModel = new ReviewModel($dbconnect);

// Recensioni con protezione x attacchii
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
                $reviewModel = new ReviewModel($dbconnect);
                $result = $reviewModel->submitReview($seller_id, $_SESSION['user_id'], $car_id, $rating, $review_text);
                
                if ($result) {
                    $review_success = 'Recensione inviata con successo!';
                } else {
                    $review_error = 'Impossibile inviare la recensione. Riprova più tardi.';
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
    <video class="video" id="video" autoplay muted loop><source src="../stilicss/Immagini/video3.mp4" type="video/mp4"></video>
<header class="main-header">
    <h1 >
        <a id="header" href="<?php echo isset($_SESSION['user_id']) ? 'areaprivata.php' : '../index.html'; ?>"> AutoMarket - Trova la tua Auto </a>
    </h1>
    <div class="user-buttons">
        <?php if (isset($_SESSION['user_id'])): ?>
            <a href="profilo.php" class="welcome">👤 <?= htmlspecialchars($utente_loggato) ?></a>
            <a href="carrello.php" class="header-btn">🛒 Carrello (<?= $totale_carrello ?>)</a>
            <a href="sell_cars.php" class="header-btn">➕ Vendi</a>
            <a href="logout.php" class="header-btn">Logout</a>
        <?php else: ?>
            <a href="../Login.html" class="header-btn">Accedi</a>
            <a href="../Login.html" class="header-btn">Registrati</a>
        <?php endif; ?>
    </div>
</header>

<section class="hero-section">
    <h2>Benvenuto su AutoMarket</h2>
    <p>Il tuo portale di fiducia per comprare, vendere e recensire auto in tutta Italia</p>
    <p>Scopri centinaia di auto, leggi le recensioni dei venditori e trova la tua occasione perfetta in pochi click!</p>
</section>

<section class="why-us-section">
    <h3>Perché scegliere AutoMarket?</h3>
    <div>
        <div class="extra-features">
            <span>🔒</span>
            <p>Transazioni Sicure</p>
            <span>Pagamenti protetti e utenti verificati</span>
        </div>
        <div class="extra-features">
            <span>⭐</span>
            <p>Recensioni Autentiche</p>
            <span>Solo chi ha acquistato può recensire</span>
        </div>
        <div class="extra-features">
            <span>🚗</span>
            <p>Ampia Scelta</p>
            <span>Auto per ogni esigenza e budget</span>
        </div>
        <div class="extra-features">
            <span>💬</span>
            <p>Supporto Dedicato</p>
            <span>Assistenza rapida e cordiale</span>
        </div>
    </div>
</section>


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
    <div class="car-results">
<!-- Effetto fade-in sulle card -->
<?php while ($row = pg_fetch_assoc($result)): 
    $nome_venditore = htmlspecialchars($row['nome_venditore']); ?>
    
<div class="car-item car-card">
    <div class="top-badge">TOP</div>
    <div class="car-details">
        
        <div class="car-details-content">
            <strong class="brand"> <?= htmlspecialchars($row['marca']) ?> </strong><br>
            <span class="model-label">Modello:</span> <span class="model"> <?= htmlspecialchars($row['modello']) ?> </span><br>
            <span class="info">Anno: <?= $row['anno'] ?> | Città: <?= htmlspecialchars($row['citta']) ?></span><br>
            <p>Prezzo: € <?= number_format($row['prezzo'], 2, ',', '.') ?></p>
            <p class="seller-name"> Venditore: <a href="recensioni_venditore.php?user_id=<?= $row['utente_id'] ?>"> <?= htmlspecialchars($row['nome_venditore']) ?> </a></p>
            <div class="car-description">
                <strong>Descrizione:</strong>
                <p><?= htmlspecialchars($row['descrizione'] ?? 'Nessuna descrizione disponibile') ?></p>
            </div>
        </div>
    </div>
    <div class="actions">
        <form action="aggiungi_al_carrello.php" method="POST" class="cart-form">
            <input type="hidden" name="id_auto" value="<?= $row['id'] ?>">
            <button type="submit" class="add-to-cart-btn">🛒 Aggiungi al carrello</button>
        </form> 
    </div>
</div>
        <?php endwhile; ?>
    </div>
<?php else: ?>
    <p>Nessuna auto trovata.</p>
    <a id="back_to_home" href="areaprivata.php">Torna alla Home</a>
<?php endif; ?>
</body>

