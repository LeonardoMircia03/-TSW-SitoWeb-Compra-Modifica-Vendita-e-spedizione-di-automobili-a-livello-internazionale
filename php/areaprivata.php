<?php
   session_start();

   if (!isset($_SESSION['username'])) {
       header("Location: index.html");
       exit;
   } 
   require_once('../php/config.php');
require_once('review_model.php');

// Get seller's rating
$reviewModel = new ReviewModel($dbconnect);
$sellerRating = $reviewModel->getSellerAverageRating($_SESSION['user_id']);
$sellerReviews = $reviewModel->getSellerReviews($_SESSION['user_id']);
?>

<!DOCTYPE html>
<html lang="ita">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AutoMarket - Buy and Sell Auto</title>
    <link rel="stylesheet" href="../stilicss/index.css">
</head>
<body>
    <video class="video" id="video" autoplay muted loop><source src="../stilicss/Immagini/video.mp4" type="video/mp4"></video>
    <header>
        <div class="container">
            <button class="button" data-text="Awesome">
                <span class="actual-text">&nbsp;Automarket&nbsp;</span>
                <span aria-hidden="true" class="hover-text">&nbsp;Automarket&nbsp;</span>
            </button>
            <h1>Nest Generation Luxury Auto Swapper</h1>
            <button class="sidebar-toggle" onclick="toggleSidebar()">☰ Info</button>
            <a id="login">| Benvenuto <?php echo htmlspecialchars($_SESSION['username']); ?> </a>
        </div>
    </header>

    <div class="search-bar-container">
        <section class="search-section">
            <div class="hero-banner">
                <h1>Alla ricerca di un auto ?</h1>
                <p>Esplora migliaia di veicoli nuovi e usati a portata di clic.</p>
            </div>

            <div class="search-container">
                <form class="search-form" action="auto.php" method="GET">
                    <div class="search-group">
                        <label for="marca">Marca</label>
                        <select id="marca" name="marca">
                            <option value="">Seleziona...</option>
                            <?php
                            $query = "SELECT DISTINCT marca FROM auto ORDER BY marca";
                            $result = pg_query($dbconnect, $query);
                            while ($row = pg_fetch_assoc($result)) {
                                $marca = htmlspecialchars($row['marca']);
                                echo "<option value=\"$marca\">$marca</option>";
                            }
                            ?>
                        </select>
                    </div>

                    <div class="search-group">
                        <label for="modello">Modello</label>
                        <select id="modello" name="modello">
                            <option value="">Seleziona...</option>
                        </select>
                    </div>

                    <div class="search-group">
                        <label for="anno">Anno da</label>
                        <select id="anno" name="anno">
                            <option value="">Seleziona...</option>
                            <?php
                            $query = "SELECT DISTINCT anno FROM auto ORDER BY anno DESC";
                            $result = pg_query($dbconnect, $query);
                            while ($row = pg_fetch_assoc($result)) {
                                $anno = htmlspecialchars($row['anno']);
                                echo "<option value=\"$anno\">$anno</option>";
                            }
                            ?>
                        </select>
                    </div>

                    <div class="search-group">
                        <label for="prezzo">Prezzo fino a</label>
                        <select id="prezzo" name="prezzo">
                            <option value="">Seleziona ...</option>
                            <?php
                            $query = "SELECT DISTINCT prezzo FROM auto ORDER BY prezzo ASC";
                            $result = pg_query($dbconnect, $query);
                            while ($row = pg_fetch_assoc($result)) {
                                $prezzo = htmlspecialchars($row['prezzo']);
                                echo "<option value=\"$prezzo\">€$prezzo</option>";
                            }
                            ?>
                        </select>
                    </div>

                    <button type="submit" class="search-button">Cerca</button>
                </form>
            </div>
        </section>

        <div class="sidebar" id="sidebar">
            <div class="sidebar-content">
                <h2>Informazioni</h2>
                <div class="navbar">
                    <a href="../about.html">About Us</a> </br>
                    <a href="sell_cars.php">Sell Cars</a></br>
                    <a href="auto.php">Cerca Auto </a></br>
                    <a href="profilo.php">Le Mie Auto</a></br>
                    <a href="../FAQ.html">FAQ</a></br>
                    <a href="../Contattaci.html">Contact</a>
                </div>
            </div>
            <a href="#" id="btnbarra" class="btn" onclick="toggleSidebar()">Chiudi</a>
        </div>

        <div class="cliente">
            <h1>Trova la tua prossima auto</h1>
            <br>
        </div>

        <div class="venditore">
            <h1>Vendi la tua Auto </h1>
            <br>
        </div>

        <div class="vantaggi">
            <h1> I nostri servizi : </h1>
            <ul>
                <li><h3>Richiedi una <a href="modifiche.html">modifica</a> ci pensiamo noi !</h3></li>
                <li><h3>La vendita è sempre assicurata</h3></li>
                <li><h3>Consegna <a href="index.html">tracciata</a> del veicolo direttametne a casa tua</h3></li>
                <li><h3>Modalità di pagamento cash o <a href="index.html">prestito</a></h3></li>
                <li><h3>Sistema di <a href="index.html">rating</a> dei venditori</h3></li>
            </ul>
        </div>

        <div class="seller-reviews" style="background-color: #f9f9f9; border-radius: 10px; padding: 20px; margin-top: 20px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
            <h2 style="color: #333; border-bottom: 2px solid #007bff; padding-bottom: 10px; margin-bottom: 15px;">Le tue recensioni</h2>
            <?php if (!empty($sellerReviews)): ?>
                <div class="rating-summary" style="display: flex; align-items: center; margin-bottom: 20px;">
                    <div style="flex: 1;">
                        <h3 style="margin: 0; color: #555;">Valutazione Media</h3>
                        <div class="stars" style="font-size: 24px; color: #ffc107;">
                            <?php 
                            $avgRating = $sellerRating['avg_rating'] ?? 0;
                            for ($i = 1; $i <= 5; $i++) {
                                echo $i <= $avgRating ? '★' : '☆';
                            }
                            ?>
                        </div>
                        <p style="margin: 5px 0 0; color: #777;"><?php echo number_format($avgRating, 1); ?> / 5 (<?php echo $sellerRating['total_reviews'] ?? 0; ?> recensioni)</p>
                    </div>
                </div>

                <div class="reviews-list">
                    <?php foreach ($sellerReviews as $review): ?>
                    <div class="review" style="background-color: white; border-radius: 8px; padding: 15px; margin-bottom: 15px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                        <div class="review-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                            <span class="reviewer-name" style="font-weight: bold; color: #333;"><?php echo htmlspecialchars($review['reviewer_name']); ?></span>
                            <div class="review-rating" style="color: #ffc107; font-size: 18px;">
                                <?php 
                                for ($i = 1; $i <= 5; $i++) {
                                    echo $i <= $review['rating'] ? '★' : '☆';
                                }
                                ?>
                            </div>
                        </div>
                        <p class="review-text" style="color: #555; margin-bottom: 10px;"><?php echo htmlspecialchars($review['review_text']); ?></p>
                        <span class="review-date" style="color: #888; font-size: 0.8em;"><?php echo date('d/m/Y', strtotime($review['review_date'])); ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p style="color: #777; text-align: center; padding: 20px;">Non hai ancora ricevuto recensioni.</p>
            <?php endif; ?>
        </div>

        <div class="seller-rating" style="background-color: #f9f9f9; border-radius: 10px; padding: 20px; margin-top: 20px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
            <h2 style="color: #333; border-bottom: 2px solid #007bff; padding-bottom: 10px; margin-bottom: 15px;">Il tuo profilo venditore</h2>
            <div class="rating-summary" style="display: flex; align-items: center; margin-bottom: 20px;">
                <div style="flex: 1;">
                    <h3 style="margin: 0; color: #555;">Valutazione Complessiva</h3>
                    <div class="stars" style="font-size: 24px; color: #ffc107;">
                        <?php 
                        $avgRating = $sellerRating['avg_rating'] ?? 0;
                        for ($i = 1; $i <= 5; $i++) {
                            echo $i <= $avgRating ? '★' : '☆';
                        }
                        ?>
                    </div>
                    <div style="display: flex; justify-content: space-between; margin-top: 10px;">
                        <p style="margin: 0; color: #777;">Media: <?php echo number_format($avgRating, 1); ?> / 5</p>
                        <p style="margin: 0; color: #777;">Totale recensioni: <?php echo $sellerRating['total_reviews'] ?? 0; ?></p>
                    </div>
                </div>
            </div>
        </div>      <footer class="footer">
            <div>
                <p>&copy;2025 AutoMarket. Tutti i diritti sono riservati</p>
            </div>
        </footer>

        <script>
            function toggleSidebar() {
                const sidebar = document.getElementById('sidebar');
                sidebar.style.width = sidebar.style.width === '250px' ? '0' : '250px';
            }
        </script>

        <script>
        document.addEventListener('DOMContentLoaded', function () {
            const marcaSelect = document.getElementById('marca');
            const modelloSelect = document.getElementById('modello');

            function aggiornaModelli() {
                const marca = marcaSelect.value;
                modelloSelect.innerHTML = '<option value="">Seleziona...</option>';

                if (marca !== '') {
                    fetch(`../php/get_modelli.php?marca=${encodeURIComponent(marca)}`)
                        .then(response => response.text())
                        .then(data => {
                            modelloSelect.innerHTML += data;
                        });
                }
            }

            marcaSelect.addEventListener('change', aggiornaModelli);
        });
        </script>
    </body>
</html>

