<?php
require_once('config.php');
session_start();

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
    <header>
        <h1>AutoMarket - Trova la tua Auto</h1>
    </header>
    <?php
$totale_carrello = isset($_SESSION['carrello']) ? count($_SESSION['carrello']) : 0;
?>
<a href="carrello.php" class="cart-link">🛒 Carrello (<?= $totale_carrello ?>)</a>
    <div class="search-bar-container">
        <form method="GET" action="auto.php">
            <label for="marca">Marca:</label>
            <select name="marca" id="marca">
                <option value="">-- Tutte --</option>
                <?php while ($row = pg_fetch_assoc($marche_result)) {
                    $selected = ($row['marca'] === $marca) ? 'selected' : '';
                    echo "<option value=\"{$row['marca']}\" $selected>{$row['marca']}</option>";
                } ?>
            </select>

            <label for="modello">Modello:</label>
            <select name="modello" id="modello">
                <option value="">-- Tutti --</option>
                <?php while ($row = pg_fetch_assoc($modelli_result)) {
                    $selected = ($row['modello'] === $modello) ? 'selected' : '';
                    echo "<option value=\"{$row['modello']}\" $selected>{$row['modello']}</option>";
                } ?>
            </select>

            <label for="anno">Anno:</label>
            <select name="anno" id="anno">
                <option value="">-- Qualsiasi --</option>
                <?php while ($row = pg_fetch_assoc($anni_result)) {
                    $selected = ($row['anno'] == $anno) ? 'selected' : '';
                    echo "<option value=\"{$row['anno']}\" $selected>{$row['anno']}</option>";
                } ?>
            </select>

            <label for="prezzo">Prezzo massimo:</label>
            <select name="prezzo" id="prezzo">
                <option value="">-- Qualsiasi --</option>
                <?php while ($row = pg_fetch_assoc($prezzi_result)) {
                    $selected = ($row['prezzo'] == $prezzo) ? 'selected' : '';
                    echo "<option value=\"{$row['prezzo']}\" $selected>€ {$row['prezzo']}</option>";
                } ?>
            </select>

            <button type="submit">Cerca</button>
        </form>
    </div>

    <?php if ($result && pg_num_rows($result) > 0): ?>
    <div class="car-results">
        <?php while ($row = pg_fetch_assoc($result)): ?>
            <div class="car-item">
                <strong>Marca:</strong> <?= htmlspecialchars($row['marca']) ?><br>
                <strong>Modello:</strong> <?= htmlspecialchars($row['modello']) ?><br>
                <strong>Anno:</strong> <?= htmlspecialchars($row['anno']) ?><br>
                <strong>Prezzo:</strong> €<?= htmlspecialchars($row['prezzo']) ?><br>
                <strong>Città:</strong> <?= htmlspecialchars($row['citta']) ?><br>

                <!-- Bottone Aggiungi al carrello -->
                <form action="aggiungi_al_carrello.php" method="POST">
                    <input type="hidden" name="id_auto" value="<?= $row['id'] ?>">
                    <button type="submit" class="add-to-cart-btn">🛒 Aggiungi al carrello</button>
                </form>
            </div>
        <?php endwhile; ?>
    </div>
<?php else: ?>
    <p>Nessuna auto trovata.</p>
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
</html>
