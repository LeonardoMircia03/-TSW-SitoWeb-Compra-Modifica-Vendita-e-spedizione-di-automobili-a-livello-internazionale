<!DOCTYPE html>
<html lang="it">
<head>
    <link rel="stylesheet" href="auto.css">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AutoMarket - Ricerca Auto</title>
    <link rel="stylesheet" href="stilicss/styles.css">
</head>
<body>
    <header>
        <h1>AutoMarket - Trova la tua Auto</h1>
    </header>

    <!-- Barra di ricerca -->
    <div class="search-bar-container">
        <form action="auto.php" method="GET">
            <label for="marca">Marca:</label>
            <input type="text" name="marca" id="marca" value="<?php echo isset($_GET['marca']) ? $_GET['marca'] : ''; ?>">

            <label for="modello">Modello:</label>
            <input type="text" name="modello" id="modello" value="<?php echo isset($_GET['modello']) ? $_GET['modello'] : ''; ?>">

            <label for="anno">Anno:</label>
            <input type="number" name="anno" id="anno" value="<?php echo isset($_GET['anno']) ? $_GET['anno'] : ''; ?>">

            <label for="prezzo">Prezzo fino a:</label>
            <input type="number" name="prezzo" id="prezzo" value="<?php echo isset($_GET['prezzo']) ? $_GET['prezzo'] : ''; ?>">

            <button type="submit">Cerca</button>
        </form>
    </div>

    <!-- Risultati della ricerca -->
    <div class="car-results">
        <?php
        // Connessione al database
        require_once('config.php');

        // Recupera i parametri di ricerca
        $marca = isset($_GET['marca']) ? $_GET['marca'] : '';
        $modello = isset($_GET['modello']) ? $_GET['modello'] : '';
        $anno = isset($_GET['anno']) ? $_GET['anno'] : '';
        $prezzo = isset($_GET['prezzo']) ? $_GET['prezzo'] : '';

        // Prepara i parametri per la query
        $where_clauses = [];
        $params = [];

        if (!empty($marca)) {
            $where_clauses[] = "marca = $1";
            $params[] = $marca;
        }

        if (!empty($modello)) {
            $where_clauses[] = "modello = $" . (count($params) + 1);
            $params[] = $modello;
        }

        if (!empty($anno)) {
            $where_clauses[] = "anno = $" . (count($params) + 1);
            $params[] = $anno;
        }

        if (!empty($prezzo)) {
            $where_clauses[] = "prezzo <= $" . (count($params) + 1);
            $params[] = $prezzo;
        }

        if (!empty($where_clauses)) {
            $sql = "SELECT * FROM auto WHERE " . implode(" AND ", $where_clauses);
        } else {
            $sql = "SELECT * FROM auto"; // Nessun filtro, prendi tutte le auto
        }

        // Esegui la query con i parametri preparati
        $result = pg_query_params($dbconnect, $sql, $params);

        if ($result) {
            while ($row = pg_fetch_assoc($result)) {
                echo "<div class='car-item'>";
                echo "<strong>Marca:</strong> " . htmlspecialchars($row['marca']) . "<br>";
                echo "<strong>Modello:</strong> " . htmlspecialchars($row['modello']) . "<br>";
                echo "<strong>Anno:</strong> " . htmlspecialchars($row['anno']) . "<br>";
                echo "<strong>Prezzo:</strong> €" . htmlspecialchars($row['prezzo']) . "<br>";
                echo "<strong>Città:</strong> " . htmlspecialchars($row['citta']) . "<br>";
                echo "</div><hr>";
            }
        } else {
            echo "Errore nella query.";
        }
        ?>
    </div>
</body>
</html>
