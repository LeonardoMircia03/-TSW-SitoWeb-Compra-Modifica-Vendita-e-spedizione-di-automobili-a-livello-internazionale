<?php
session_start();
require_once('php/config.php');

if (!isset($_SESSION['user_id'])) {
    header("Location: php/login.php");
    exit;
}

// Verifica che auto_id sia stato passato come parametro GET
if (!isset($_GET['auto_id']) || !is_numeric($_GET['auto_id'])) {
    die("Errore: ID auto non valido.");
}

$auto_id = intval($_GET['auto_id']);
$utente_id = $_SESSION['user_id'];

// Verifica che l'auto sia nel carrello dell'utente
$query = "SELECT * FROM carrello WHERE utente_id = $1 AND auto_id = $2";
$result = pg_query_params($dbconnect, $query, array($utente_id, $auto_id));

if (!$result || pg_num_rows($result) == 0) {
    die("Auto non trovata nel tuo carrello.");
}

// Salva le modifiche se il form è stato inviato
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $esthetiche = isset($_POST['esthetiche']) ? json_encode($_POST['esthetiche']) : null;
    $tecniche = isset($_POST['tecniche']) ? json_encode($_POST['tecniche']) : null;

    // Aggiorna le modifiche nel database
    $update_query = "
        UPDATE carrello 
        SET modifiche_estetiche = $1, modifiche_tecniche = $2 
        WHERE utente_id = $3 AND auto_id = $4";

    pg_query_params($dbconnect, $update_query, array($esthetiche, $tecniche, $utente_id, $auto_id));

    echo '
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const successMessage = document.getElementById("success-message");
            successMessage.style.display = "block";
        });
    </script>';
}

// Recupera eventuali modifiche già presenti
$existing = pg_fetch_assoc(pg_query_params(
    $dbconnect,
    "SELECT modifiche_estetiche, modifiche_tecniche FROM carrello WHERE utente_id = $1 AND auto_id = $2",
    array($utente_id, $auto_id)
));

// Decodifica le modifiche (gestione sicura con fallback a array vuoto)
$esthetiche_salvate = json_decode($existing['modifiche_estetiche'] ?? '[]', true);
$tecniche_salvate = json_decode($existing['modifiche_tecniche'] ?? '[]', true);

// Assicurati che siano sempre array
$esthetiche_salvate = is_array($esthetiche_salvate) ? $esthetiche_salvate : [];
$tecniche_salvate = is_array($tecniche_salvate) ? $tecniche_salvate : [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $esthetiche = $_POST['esthetiche'] ?? [];
    $tecniche = $_POST['tecniche'] ?? [];

    // Mappa dei prezzi lato server
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

    // Calcola totale
    $totale_modifiche = 0;

    foreach ($esthetiche as $modifica) {
        $totale_modifiche += $prezzi_estetici[$modifica] ?? 0;
    }

    foreach ($tecniche as $modifica) {
        $totale_modifiche += $prezzi_tecnici[$modifica] ?? 0;
    }

    
}
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Seleziona Modifiche</title>
    <link rel="stylesheet" href="stilicss/modifiche.css">
</head>
<body>
    <img id="sfondo" src="stilicss/Immagini/modifiche/sfondo.png" alt="Sfondo">
<div class="container">
    <h1>Scegli le modifiche per questa auto</h1>
    <p>Puoi aggiungere modifiche estetiche e tecniche alla tua auto.</p>

    <form method="POST" action="modifiche.php?auto_id=<?= $auto_id ?>">

<!-- Categoria Estetica -->
<div class="category">
    <h2>🔧 Modifiche Estetiche</h2>
    <ul>
        <li>
            <label class="checkbox-label">
                <input type="checkbox" name="esthetiche[]" value="cerchi" data-prezzo="500">
                <span class="checkmark"></span>
                Cerchi personalizzati (+€500)
            </label>
        </li>
        <li>
            <label class="checkbox-label">
                <input type="checkbox" name="esthetiche[]" value="tappeti" data-prezzo="200">
                <span class="checkmark"></span>
                Tappeti interni premium (+€200)
            </label>
        </li>
        <li>
            <label class="checkbox-label">
                <input type="checkbox" name="esthetiche[]" value="paraurti" data-prezzo="800">
                <span class="checkmark"></span>
                Paraurti sportivo (+€800)
            </label>
        </li>
        <li>
            <label class="checkbox-label">
                <input type="checkbox" name="esthetiche[]" value="luci" data-prezzo="300">
                <span class="checkmark"></span>
                Kit luci LED (+€300)
            </label>
        </li>
        <li>
            <label class="checkbox-label">
                <input type="checkbox" name="esthetiche[]" value="wrap" data-prezzo="1500">
                <span class="checkmark"></span>
                Wrap completo o parziale (+€1500)
            </label>
        </li>
    </ul>
</div>

<!-- Categoria Tecnica -->
<div class="category">
    <h2>⚙️ Modifiche Tecniche</h2>
    <ul>
        <li>
            <label class="checkbox-label">
                <input type="checkbox" name="tecniche[]" value="sospensioni" data-prezzo="1200">
                <span class="checkmark"></span>
                Sospensioni regolabili (+€1200)
            </label>
        </li>
        <li>
            <label class="checkbox-label">
                <input type="checkbox" name="tecniche[]" value="freni" data-prezzo="1000">
                <span class="checkmark"></span>
                Freni ad alte prestazioni (+€1000)
            </label>
        </li>
        <li>
            <label class="checkbox-label">
                <input type="checkbox" name="tecniche[]" value="turbo" data-prezzo="2500">
                <span class="checkmark"></span>
                Sistema Turbo migliorato (+€2500)
            </label>
        </li>
        <li>
            <label class="checkbox-label">
                <input type="checkbox" name="tecniche[]" value="cambio" data-prezzo="900">
                <span class="checkmark"></span>
                Cambio manuale/automatico (+€900)
            </label>
        </li>
        <li>
            <label class="checkbox-label">
                <input type="checkbox" name="tecniche[]" value="scarico" data-prezzo="600">
                <span class="checkmark"></span>
                Scarico sportivo (+€600)
            </label>
        </li>
    </ul>
</div>



<!-- Bottone submit -->
<button type="submit" class="submit-btn">💾 Salva Modifiche</button>
<a href="php/carrello.php" class="back-link">⬅ Torna al Carrello</a>
</form>
<!-- Riepilogo totale -->
<div id="total-container">
    Totale modifiche: <span id="total-price">€0</span>
</div>


<div id="success-message" style="
    margin-top: 20px;
    display: none;
    text-align: center;
    color: green;
    font-weight: bold;
    font-size: 1.1rem;">
    ✅ Modifiche salvate con successo!
</div>


</div>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const checkboxes = document.querySelectorAll('.checkbox-label input[type="checkbox"]');
        const totalPriceEl = document.getElementById('total-price');

        function updateTotal() {
            let total = 0;

            checkboxes.forEach(input => {
                if (input.checked && input.dataset.prezzo) {
                    total += parseInt(input.dataset.prezzo);
                }
            });

            totalPriceEl.textContent = '€' + total.toLocaleString();
        }

        checkboxes.forEach(input => {
            input.addEventListener('change', updateTotal);
        });

        // Calcola il totale iniziale se ce ne sono già selezionati
        updateTotal();
    });
</script>
</body>
</html>