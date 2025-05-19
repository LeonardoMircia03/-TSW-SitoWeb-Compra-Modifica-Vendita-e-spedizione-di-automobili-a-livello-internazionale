<?php
session_start();
require_once('config.php');

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$utente_id = $_SESSION['user_id'];

// Recupera le auto nel carrello dell'utente (con modifiche)
$query = "SELECT a.*, c.modifiche_estetiche, c.modifiche_tecniche FROM auto a JOIN carrello c ON a.id = c.auto_id WHERE c.utente_id = $1";
$result = pg_query_params($dbconnect, $query, array($utente_id));

$rows = [];
$totale_auto = 0;
$totale_modifiche = 0;
if ($result && pg_num_rows($result) > 0) {
    while ($row = pg_fetch_assoc($result)) {
        $rows[] = $row;
        $totale_auto += $row['prezzo'];
        
        // Gestione delle modifiche in formato JSON
        $modifiche_estetiche = json_decode($row['modifiche_estetiche'] ?? '[]', true);
        $modifiche_tecniche = json_decode($row['modifiche_tecniche'] ?? '[]', true);
        
        // Se il JSON non è valido, fallback a stringa vuota
        if (!is_array($modifiche_estetiche)) {
            $modifiche_estetiche = [];
        }
        if (!is_array($modifiche_tecniche)) {
            $modifiche_tecniche = [];
        }
        
        // Prezzi per modifiche
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
        
        // Calcola totale modifiche per questa auto
        foreach ($modifiche_estetiche as $modifica) {
            $totale_modifiche += $prezzi_estetici[$modifica] ?? 0;
        }
        
        foreach ($modifiche_tecniche as $modifica) {
            $totale_modifiche += $prezzi_tecnici[$modifica] ?? 0;
        }
    }
}

if (!$result || pg_num_rows($result) == 0) {
    // Messaggio se il carrello è vuoto
    ?>
    <!DOCTYPE html>
    <html lang="it">
    <head>
        <meta charset="UTF-8">
        <title>Carrello Vuoto</title>
        <link rel="stylesheet" href="../stilicss/index.css">
        <link rel="stylesheet" href="../stilicss/carrello.css">
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

$acquisto_success = '';
if (
    $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['conferma_pagamento']) &&
    !empty($_POST['carta']) &&
    !empty($_POST['scadenza']) &&
    !empty($_POST['cvv'])
) {
    if (!empty($rows)) {
        foreach ($rows as $auto) {
            $venditore_id = $auto['utente_id'];
            $acquirente_id = $utente_id;
            $auto_id = $auto['id'];
            $data = date('Y-m-d H:i:s');
            $prezzo = $auto['prezzo'];
            $modifiche_estetiche = $auto['modifiche_estetiche'];
            $modifiche_tecniche = $auto['modifiche_tecniche'];
            $query_trans = "INSERT INTO transazione (auto_id, venditore_id, acquirente_id, data_transazione, prezzo_totale, modifiche_estetiche, modifiche_tecniche) VALUES ($1, $2, $3, $4, $5, $6, $7)";
            $result_trans = pg_query_params($dbconnect, $query_trans, [
                $auto_id,
                $venditore_id,
                $acquirente_id,
                $data,
                $prezzo,
                $modifiche_estetiche,
                $modifiche_tecniche
            ]);
            if (!$result_trans) {
                error_log('Errore inserimento transazione: ' . pg_last_error($dbconnect));
            }
            // Rimuovi dal carrello sessione
            if (isset($_SESSION['carrello'])) {
                if (($key = array_search($auto_id, $_SESSION['carrello'])) !== false) {
                    unset($_SESSION['carrello'][$key]);
                }
            }
        }
        $acquisto_success = 'Acquisto completato con successo!';
        header('Location: carrello.php?acquisto=1');
        exit;
    }
}
if (isset($_GET['acquisto'])) {
    $acquisto_success = 'Acquisto completato con successo!';
}

$query = "SELECT a.*, c.modifiche_estetiche, c.modifiche_tecniche FROM auto a JOIN carrello c ON a.id = c.auto_id WHERE c.utente_id = $1";
$result = pg_query_params($dbconnect, $query, array($utente_id));

$rows = [];
$totale_auto = 0;
$totale_modifiche = 0;

if ($result && pg_num_rows($result) > 0) {
    while ($row = pg_fetch_assoc($result)) {
        $rows[] = $row;
        $totale_auto += $row['prezzo'];
        
        // Gestione sia del formato CSV che JSON
        $estetiche = $row['modifiche_estetiche'];
        $tecniche = $row['modifiche_tecniche'];
        
        // Se è JSON, decodifica
        if (is_string($estetiche) && strpos($estetiche, '[') === 0) {
            $estetiche = json_decode($estetiche, true) ?? [];
        } else {
            $estetiche = explode(',', $estetiche);
        }
        
        if (is_string($tecniche) && strpos($tecniche, '[') === 0) {
            $tecniche = json_decode($tecniche, true) ?? [];
        } else {
            $tecniche = explode(',', $tecniche);
        }
        
        // Calcolo totale modifiche estetiche
        foreach ($estetiche as $modifica) {
            $modifica = trim($modifica);
            if (isset($prezzi_estetici[$modifica])) {
                $totale_modifiche += $prezzi_estetici[$modifica];
            }
        }
        
        // Calcolo totale modifiche tecniche
        foreach ($tecniche as $modifica) {
            $modifica = trim($modifica);
            if (isset($prezzi_tecnici[$modifica])) {
                $totale_modifiche += $prezzi_tecnici[$modifica];
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Il tuo carrello | AutoMarket</title>
    <link rel="stylesheet" href="../stilicss/carrello.css">
</head>
<body>
    <div class="video-background">a
        <video autoplay muted loop>
            <source src="../stilicss/Immagini/video.mp4" type="video/mp4">
        </video>
    </div>
    
    <header>
        <h1>IL TUO CARRELLO</h1>
        <p>Gestisci i tuoi veicoli e procedi al pagamento</p>
    </header>
    
    <div class="main-content">

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
<div class="button-group">
    <form action="svuota_carrello.php" method="POST" onsubmit="return confirm('Sei sicuro di voler rimuovere tutte le auto dal carrello?');">
        <button type="submit" class="empty-cart-btn">🗑️ Svuota carrello</button>
    </form>
    <button id="show-payment-btn" class="modify-btn">💳 Paga</button>
</div>


    <div class="back-link">
        <a href="auto.php">⬅ Torna alla ricerca</a>
    </div>
        <div id="payment-section">
    <div class="payment-options">
        <button id="payment-option-direct" class="modify-btn">💵 Pagamento Diretto</button>
        <button id="payment-option-loan" class="modify-btn">🏦 Finanziamento</button>
    </div>
    <div id="loan-section">

        <div class="loan-duration-container">
            <label for="loan-duration">Durata del finanziamento (mesi):</label>
            <select id="loan-duration" class="loan-duration-select">
                <option value="12">12 mesi</option>
                <option value="24">24 mesi</option>
                <option value="36">36 mesi</option>
                <option value="48">48 mesi</option>
                <option value="60">60 mesi</option>
            </select>
        </div>
        <div class="loan-downpayment-container">
            <label for="loan-downpayment">Anticipo (€):</label>
            <input type="number" id="loan-downpayment" min="0" value="0" class="loan-downpayment-input">
        </div>
        
        <div class="loan-model-container">
            <p>Tipo di ammortamento:</p>
            
            <div class="loan-model-options">
                <label class="loan-model-option loan-model-option-french">
                    <input type="radio" name="loan-model" id="french-model" checked>
                    <div>
                        <strong>Francese</strong>
                        <p class="loan-model-option-description">Rata costante</p>
                    </div>
                </label>
                
                <label class="loan-model-option loan-model-option-italian">
                    <input type="radio" name="loan-model" id="italian-model">
                    <div>
                        <strong>Italiano</strong>
                        <p class="loan-model-option-description">Quota capitale costante</p>
                    </div>
                </label>
            </div>
        </div>    
        <p id="model-description" class="loan-model-description">Calcolo con ammortamento alla francese: rata costante per tutta la durata del prestito.</p>

        <h2 id="payment-title">💳 Pagamento Diretto</h2>
        <form action="processo_pagamento.php" method="POST" id="payment-form">
            <div style="margin-bottom: 20px;">
                <label for="carta">Numero Carta:</label>
                <input type="text" id="carta" name="carta" maxlength="16" required pattern="[0-9]{16}" title="Il numero della carta deve essere di 16 cifre" class="payment-form-input">
                <div id="carta-error" class="payment-form-error">Il numero della carta deve essere esattamente di 16 cifre.</div>
            </div>
            
            <div class="payment-form-section">
                <label for="titolare">Intestatario Carta:</label>
                <input type="text" id="titolare" name="titolare" required class="payment-form-input">
            </div>
            
            <div class="payment-form-split">
                <div>
                    <label for="scadenza">Data Scadenza:</label>
                    <input type="date" id="scadenza" name="scadenza" required class="payment-form-input">
                    <div id="scadenza-error" class="scadenza-error">La data di scadenza deve essere futura alla data di oggi.</div>
                </div>
                <div>
                    <label for="cvv">CVV:</label>
                    <input type="text" id="cvv" name="cvv" maxlength="3" required pattern="[0-9]{3}" title="Il CVV deve essere di 3 cifre" class="payment-form-input">
                    <div id="cvv-error" class="payment-form-error">Il CVV deve essere esattamente di 3 cifre.</div>
                </div>
            </div>
            
            <input type="hidden" name="importo" value="<?= $totale_auto + $totale_modifiche ?>">
            
            <button type="submit" class="submit-payment-btn">💳 Conferma Pagamento</button>
        </form>
        <div class="loan-details">
            <p><strong>Importo totale:</strong> €<span id="loan-total-amount"><?= number_format($totale_auto + $totale_modifiche, 2, ',', '.') ?></span></p>
            <p><strong>Tasso di interesse:</strong> <span id="loan-interest-rate">5.9</span>%</p>
            <p><strong id="monthly-payment-label">Rata mensile costante:</strong> €<span id="loan-monthly-payment">0,00</span></p>
            <p><strong>Importo totale da pagare:</strong> €<span id="loan-total-payment">0,00</span></p>
        </div>
        <div id="amortization-container">
            <h4 class="amortization-container h4">Piano di ammortamento</h4>
            <div class="amortization-table-wrapper">
                <table id="amortization-table">
                    <thead>
                        <tr>
                            <th>Mese</th>
                            <th>Rata</th>
                            <th>Quota Capitale</th>
                            <th>Quota Interessi</th>
                            <th>Debito Residuo</th>
                        </tr>
                    </thead>
                    <tbody id="amortization-body">
                        <!-- Qui verranno inserite le righe della tabella dinamicamente -->
                    </tbody>
                </table>
            </div>
        </div>
        <button id="apply-loan" class="modify-btn">🏦 Applica Finanziamento</button>
    </div>
    <h2 id="payment-title">💳 Pagamento Diretto</h2>
    <form action="processo_pagamento.php" method="POST" id="payment-form">
        <div style="margin-bottom: 20px;">
            <label for="carta">Numero Carta:</label>
            <input type="text" id="carta" name="carta" maxlength="16" required pattern="[0-9]{16}" title="Il numero della carta deve essere di 16 cifre" class="payment-form-input">
            <div id="carta-error" class="payment-form-error">Il numero della carta deve essere esattamente di 16 cifre.</div>
        </div>
        
        <div class="payment-form-section">
            <label for="titolare">Intestatario Carta:</label>
            <input type="text" id="titolare" name="titolare" required class="payment-form-input">
        </div>
        
        <div class="payment-form-split">
            <div>
                <label for="scadenza">Data Scadenza:</label>
                <input type="date" id="scadenza" name="scadenza" required class="payment-form-input">
                <div id="scadenza-error" class="scadenza-error">La data di scadenza deve essere futura alla data di oggi.</div>
            </div>
            <div>
                <label for="cvv">CVV:</label>
                <input type="text" id="cvv" name="cvv" maxlength="3" required pattern="[0-9]{3}" title="Il CVV deve essere di 3 cifre" class="payment-form-input">
                <div id="cvv-error" class="payment-form-error">Il CVV deve essere esattamente di 3 cifre.</div>
            </div>
        </div>
        
        <input type="hidden" name="importo" value="<?= $totale_auto + $totale_modifiche ?>">
        
        <button type="submit" class="submit-payment-btn">💳 Conferma Pagamento</button>
    </form>
</div>
    <footer>
        <p>&copy; 2025 AutoMarket - Tutti i diritti riservati</p>
    </footer>
    </div> <!-- Chiusura del main-content -->
    <script>
    document.getElementById('show-payment-btn').addEventListener('click', function () {
        const section = document.getElementById('payment-section');
        
        if (section.style.display === 'none') {
            // Mostra la sezione
            section.style.display = 'block';
            // Usa setTimeout per permettere al browser di renderizzare il display:block prima di applicare l'opacità
            setTimeout(() => {
                section.style.opacity = '1';
                section.style.transform = 'translateY(0)';
            }, 10);
            this.textContent = '❌ Chiudi Pagamento';
            
            // Scorri fino alla sezione di pagamento
            section.scrollIntoView({ behavior: 'smooth', block: 'start' });
        } else {
            // Nascondi la sezione con transizione
            section.style.opacity = '0';
            section.style.transform = 'translateY(20px)';
            
            // Aspetta che la transizione finisca prima di impostare display:none
            setTimeout(() => {
                section.style.display = 'none';
            }, 500); // Questo valore deve corrispondere alla durata della transizione CSS
            
            this.textContent = '💳 Paga';
        }
    });

    // Gestione delle opzioni di pagamento
    const paymentForm = document.getElementById('payment-form');
    const loanSection = document.getElementById('loan-section');
    const paymentTitle = document.getElementById('payment-title');
    
    document.getElementById('payment-option-direct').addEventListener('click', function() {
        // Nascondi la sezione prestito
        loanSection.style.opacity = '0';
        loanSection.style.transform = 'translateY(10px)';
        
        setTimeout(() => {
            loanSection.style.display = 'none';
            // Mostra il form di pagamento
            paymentForm.style.display = 'block';
            // Usa setTimeout per permettere al browser di renderizzare il display:block
            setTimeout(() => {
                paymentForm.style.opacity = '1';
                paymentForm.style.transform = 'translateY(0)';
            }, 10);
        }, 400);
        
        paymentTitle.innerHTML = '💳 Pagamento Diretto';
        document.getElementById('payment-option-direct').classList.add('active-option');
        document.getElementById('payment-option-loan').classList.remove('active-option');
        
        // Rimuovi il campo nascosto per il finanziamento se presente
        const existingLoanField = document.getElementById('loan-info');
        if (existingLoanField) {
            existingLoanField.remove();
        }
        
        // Rimuovi eventuali messaggi di conferma precedenti
        const existingConfirmations = loanSection.querySelectorAll('div[data-confirmation="true"]');
        existingConfirmations.forEach(msg => msg.remove());
    });
    
    document.getElementById('payment-option-loan').addEventListener('click', function() {
        // Nascondi il form di pagamento
        paymentForm.style.opacity = '0';
        paymentForm.style.transform = 'translateY(10px)';
        
        setTimeout(() => {
            paymentForm.style.display = 'none';
            // Mostra la sezione prestito
            loanSection.style.display = 'block';
            // Usa setTimeout per permettere al browser di renderizzare il display:block
            setTimeout(() => {
                loanSection.style.opacity = '1';
                loanSection.style.transform = 'translateY(0)';
            }, 10);
        }, 400);
        
        paymentTitle.innerHTML = '🏦 Finanziamento';
        document.getElementById('payment-option-loan').classList.add('active-option');
        document.getElementById('payment-option-direct').classList.remove('active-option');
        
        // Rimuovi eventuali messaggi di conferma precedenti
        const existingConfirmations = loanSection.querySelectorAll('div[data-confirmation="true"]');
        existingConfirmations.forEach(msg => msg.remove());
    });
    
    // Aggiungi classe active-option al pulsante di pagamento diretto all'inizio
    document.getElementById('payment-option-direct').classList.add('active-option');
    
    // Mostra automaticamente il form di pagamento all'inizio
    paymentForm.style.display = 'block';
    paymentForm.style.opacity = '1';
    paymentForm.style.transform = 'translateY(0)';
    
    // Funzione per calcolare il finanziamento in base al modello selezionato
    function calculateLoan() {
        const totalAmount = <?= $totale_auto + $totale_modifiche ?>;;
        const duration = parseInt(document.getElementById('loan-duration').value);
        const downPayment = parseFloat(document.getElementById('loan-downpayment').value) || 0;
        const interestRate = 5.9; // Tasso di interesse fisso del 5.9%
        const isFrenchModel = document.getElementById('french-model').checked;
        
        // Calcolo dell'importo finanziato
        const loanAmount = totalAmount - downPayment;
        const monthlyRate = interestRate / 100 / 12;
        
        let monthlyPayment, totalPayment, totalInterest;
        
        if (isFrenchModel) {
            // Modello Francese (rata costante)
            // Formula: R = P * (i * (1 + i)^n) / ((1 + i)^n - 1)
            // dove R = rata mensile, P = capitale prestato, i = tasso mensile, n = numero di rate
            monthlyPayment = loanAmount * (monthlyRate * Math.pow(1 + monthlyRate, duration)) / (Math.pow(1 + monthlyRate, duration) - 1);
            totalPayment = (monthlyPayment * duration) + downPayment;
            totalInterest = (monthlyPayment * duration) - loanAmount;
            
            // Aggiorna la descrizione del modello
            document.getElementById('model-description').textContent = "Calcolo con ammortamento alla francese: rata costante per tutta la durata del prestito.";
            document.getElementById('monthly-payment-label').textContent = "Rata mensile costante:";
            
            // Aggiorna i dettagli del prestito
            document.getElementById('loan-monthly-payment').textContent = monthlyPayment.toFixed(2).replace('.', ',');
            document.getElementById('loan-total-payment').textContent = totalPayment.toFixed(2).replace('.', ',');
            document.getElementById('loan-interest-rate').textContent = interestRate.toFixed(1);
            
            // Genera la tabella di ammortamento francese
            generateFrenchAmortizationTable(loanAmount, monthlyRate, monthlyPayment, duration);
            
            // Rimuovi eventuali messaggi di conferma precedenti
            const existingConfirmations = loanSection.querySelectorAll('div[data-confirmation="true"]');
            existingConfirmations.forEach(msg => msg.remove());
        } else {
            // Modello Italiano (quota capitale costante)
            // La quota capitale è costante = loanAmount / duration
            const constantPrincipal = loanAmount / duration;
            
            // Calcola la prima rata (la più alta)
            const firstMonthlyPayment = constantPrincipal + (loanAmount * monthlyRate);
            
            // Calcola l'ultima rata (la più bassa)
            const lastMonthlyPayment = constantPrincipal + (constantPrincipal * monthlyRate);
            
            // Calcola il totale degli interessi
            totalInterest = (loanAmount * monthlyRate * (duration + 1)) / 2;
            
            // Calcola il totale da pagare
            totalPayment = loanAmount + totalInterest + downPayment;
            
            // Per il display, mostriamo la rata media
            monthlyPayment = (firstMonthlyPayment + lastMonthlyPayment) / 2;
            
            // Aggiorna la descrizione del modello
            document.getElementById('model-description').textContent = "Calcolo con ammortamento all'italiana: quota capitale costante e rata decrescente.";
            document.getElementById('monthly-payment-label').textContent = "Rata mensile media:";
            
            // Genera la tabella di ammortamento italiana
            generateItalianAmortizationTable(loanAmount, monthlyRate, constantPrincipal, duration);
        }
        
        // Aggiornamento dei valori visualizzati
        document.getElementById('loan-total-amount').textContent = totalAmount.toFixed(2).replace('.', ',');
        document.getElementById('loan-interest-rate').textContent = interestRate.toFixed(1);
        document.getElementById('loan-monthly-payment').textContent = monthlyPayment.toFixed(2).replace('.', ',');
        document.getElementById('loan-total-payment').textContent = totalPayment.toFixed(2).replace('.', ',');
    }
    
    // Aggiorna il calcolo quando cambiano i valori
    document.getElementById('loan-duration').addEventListener('change', calculateLoan);
    document.getElementById('loan-downpayment').addEventListener('input', calculateLoan);
    document.getElementById('french-model').addEventListener('change', calculateLoan);
    document.getElementById('italian-model').addEventListener('change', calculateLoan);
    
    // Esegui il calcolo all'avvio della pagina
    calculateLoan();

    // Mostra il form di pagamento diretto di default
    document.getElementById('loan-section').style.display = 'none';
    document.getElementById('payment-form').style.display = 'block';
    
    // Funzione per generare la tabella di ammortamento francese (rata costante)
    function generateFrenchAmortizationTable(loanAmount, monthlyRate, monthlyPayment, duration) {
        const tableBody = document.getElementById('amortization-body');
        tableBody.innerHTML = ''; // Pulisci la tabella esistente
        
        let remainingDebt = loanAmount;
        let totalInterest = 0;
        let totalPrincipal = 0;
        
        // Mostra tutti i mesi del piano di ammortamento
        for (let month = 1; month <= duration; month++) {
            // Calcolo degli interessi per questo mese
            const interestPayment = remainingDebt * monthlyRate;
            totalInterest += interestPayment;
            
            // Calcolo della quota capitale per questo mese
            const principalPayment = monthlyPayment - interestPayment;
            totalPrincipal += principalPayment;
            
            // Aggiornamento del debito residuo
            remainingDebt -= principalPayment;
            
            // Creazione della riga della tabella
            const row = document.createElement('tr');
            
            // Aggiungi una classe per evidenziare le righe alternate
            if (month % 2 === 0) {
                row.style.backgroundColor = '#f9f9f9';
            }
            
            row.innerHTML = `
                <td style="padding: 6px; text-align: left; border: 1px solid #ddd;">${month}</td>
                <td style="padding: 6px; text-align: right; border: 1px solid #ddd;">${monthlyPayment.toFixed(2).replace('.', ',')} €</td>
                <td style="padding: 6px; text-align: right; border: 1px solid #ddd;">${principalPayment.toFixed(2).replace('.', ',')} €</td>
                <td style="padding: 6px; text-align: right; border: 1px solid #ddd;">${interestPayment.toFixed(2).replace('.', ',')} €</td>
                <td style="padding: 6px; text-align: right; border: 1px solid #ddd;">${Math.max(0, remainingDebt).toFixed(2).replace('.', ',')} €</td>
            `;
            
            tableBody.appendChild(row);
        }
        
        // Aggiungi una riga di riepilogo
        const summaryRow = document.createElement('tr');
        summaryRow.style.backgroundColor = '#e8f5e9';
        summaryRow.style.fontWeight = 'bold';
        summaryRow.innerHTML = `
            <td style="padding: 8px; text-align: left; border: 1px solid #ddd;">Totale</td>
            <td style="padding: 8px; text-align: right; border: 1px solid #ddd;">${(monthlyPayment * duration).toFixed(2).replace('.', ',')} €</td>
            <td style="padding: 8px; text-align: right; border: 1px solid #ddd;">${totalPrincipal.toFixed(2).replace('.', ',')} €</td>
            <td style="padding: 8px; text-align: right; border: 1px solid #ddd;">${totalInterest.toFixed(2).replace('.', ',')} €</td>
            <td style="padding: 8px; text-align: right; border: 1px solid #ddd;">0,00 €</td>
        `;
        
        tableBody.appendChild(summaryRow);
    }
    
    // Funzione per generare la tabella di ammortamento italiana (quota capitale costante)
    function generateItalianAmortizationTable(loanAmount, monthlyRate, constantPrincipal, duration) {
        const tableBody = document.getElementById('amortization-body');
        tableBody.innerHTML = ''; // Pulisci la tabella esistente
        
        let remainingDebt = loanAmount;
        let totalInterest = 0;
        let totalPayment = 0;
        
        // Mostra tutti i mesi del piano di ammortamento
        for (let month = 1; month <= duration; month++) {
            // Calcolo degli interessi per questo mese
            const interestPayment = remainingDebt * monthlyRate;
            totalInterest += interestPayment;
            
            // Quota capitale è costante
            const principalPayment = constantPrincipal;
            
            // Calcolo della rata per questo mese (capitale + interessi)
            const monthlyPayment = principalPayment + interestPayment;
            totalPayment += monthlyPayment;
            
            // Aggiornamento del debito residuo
            remainingDebt -= principalPayment;
            
            // Creazione della riga della tabella
            const row = document.createElement('tr');
            
            // Aggiungi una classe per evidenziare le righe alternate
            if (month % 2 === 0) {
                row.style.backgroundColor = '#f9f9f9';
            }
            
            row.innerHTML = `
                <td style="padding: 6px; text-align: left; border: 1px solid #ddd;">${month}</td>
                <td style="padding: 6px; text-align: right; border: 1px solid #ddd;">${monthlyPayment.toFixed(2).replace('.', ',')} €</td>
                <td style="padding: 6px; text-align: right; border: 1px solid #ddd;">${principalPayment.toFixed(2).replace('.', ',')} €</td>
                <td style="padding: 6px; text-align: right; border: 1px solid #ddd;">${interestPayment.toFixed(2).replace('.', ',')} €</td>
                <td style="padding: 6px; text-align: right; border: 1px solid #ddd;">${Math.max(0, remainingDebt).toFixed(2).replace('.', ',')} €</td>
            `;
            
            tableBody.appendChild(row);
        }
        
        // Aggiungi una riga di riepilogo
        const summaryRow = document.createElement('tr');
        summaryRow.style.backgroundColor = '#e8f5e9';
        summaryRow.style.fontWeight = 'bold';
        summaryRow.innerHTML = `
            <td style="padding: 8px; text-align: left; border: 1px solid #ddd;">Totale</td>
            <td style="padding: 8px; text-align: right; border: 1px solid #ddd;">${totalPayment.toFixed(2).replace('.', ',')} €</td>
            <td style="padding: 8px; text-align: right; border: 1px solid #ddd;">${(constantPrincipal * duration).toFixed(2).replace('.', ',')} €</td>
            <td style="padding: 8px; text-align: right; border: 1px solid #ddd;">${totalInterest.toFixed(2).replace('.', ',')} €</td>
            <td style="padding: 8px; text-align: right; border: 1px solid #ddd;">0,00 €</td>
        `;
        
        tableBody.appendChild(summaryRow);
    }
    
    // Applica il finanziamento
    document.getElementById('apply-loan').addEventListener('click', function() {
        const duration = document.getElementById('loan-duration').value;
        const downPayment = document.getElementById('loan-downpayment').value;
        const interestRate = document.getElementById('loan-interest-rate').textContent;
        const monthlyPayment = document.getElementById('loan-monthly-payment').textContent;
        const totalPayment = document.getElementById('loan-total-payment').textContent;
        
        // Crea un campo nascosto con le informazioni del finanziamento
        const loanInfo = {
            duration: duration,
            downPayment: downPayment,
            interestRate: interestRate,
            monthlyPayment: monthlyPayment,
            totalPayment: totalPayment
        };
        
        // Rimuovi il campo nascosto esistente se presente
        const existingLoanField = document.getElementById('loan-info');
        if (existingLoanField) {
            existingLoanField.remove();
        }
        
        // Aggiungi un campo nascosto con le informazioni del finanziamento
        const loanField = document.createElement('input');
        loanField.type = 'hidden';
        loanField.id = 'loan-info';
        loanField.name = 'loan_info';
        loanField.value = JSON.stringify(loanInfo);
        paymentForm.appendChild(loanField);
        
        // Mostra il form di pagamento con transizione
        loanSection.style.opacity = '0';
        loanSection.style.transform = 'translateY(10px)';
        
        setTimeout(() => {
            loanSection.style.display = 'none';
            paymentForm.style.display = 'block';
            
            // Usa setTimeout per permettere al browser di renderizzare il display:block
            setTimeout(() => {
                paymentForm.style.opacity = '1';
                paymentForm.style.transform = 'translateY(0)';
            }, 10);
            
            paymentTitle.innerHTML = '💳 Pagamento con Finanziamento';
        }, 400);
        
        // Aggiungi un messaggio di conferma con animazione
        const confirmationMessage = document.createElement('div');
        confirmationMessage.style.backgroundColor = '#e8f5e9';
        confirmationMessage.style.padding = '15px';
        confirmationMessage.style.borderRadius = '8px';
        confirmationMessage.style.marginBottom = '20px';
        confirmationMessage.style.boxShadow = '0 4px 8px rgba(0,0,0,0.1)';
        confirmationMessage.style.border = '1px solid #c8e6c9';
        confirmationMessage.style.opacity = '0';
        confirmationMessage.style.transform = 'translateY(-10px)';
        confirmationMessage.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
        
        const isFrenchModel = document.getElementById('french-model').checked;
        const modelName = isFrenchModel ? 'Francese' : 'Italiano';
        const paymentLabel = isFrenchModel ? 'Rata mensile costante' : 'Rata mensile media';
        
        confirmationMessage.innerHTML = `
            <div style="text-align: center; margin-bottom: 10px;">
                <span style="font-size: 2rem; color: #4CAF50;">✓</span>
                <h3 style="margin: 5px 0; color: #2E7D32;">Finanziamento Approvato!</h3>
                <p style="color: #388E3C; font-style: italic;">Modello ${modelName}</p>
            </div>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-top: 15px;">
                <div style="background-color: rgba(255,255,255,0.7); padding: 8px; border-radius: 5px;">
                    <strong style="color: #333;">Durata:</strong>
                    <p style="margin: 5px 0; font-size: 1.1rem;">${duration} mesi</p>
                </div>
                <div style="background-color: rgba(255,255,255,0.7); padding: 8px; border-radius: 5px;">
                    <strong style="color: #333;">Anticipo:</strong>
                    <p style="margin: 5px 0; font-size: 1.1rem;">€${parseFloat(downPayment).toFixed(2).replace('.', ',')}</p>
                </div>
                <div style="background-color: rgba(255,255,255,0.7); padding: 8px; border-radius: 5px;">
                    <strong style="color: #333;">${paymentLabel}:</strong>
                    <p style="margin: 5px 0; font-size: 1.1rem;">€${monthlyPayment}</p>
                </div>
                <div style="background-color: rgba(255,255,255,0.7); padding: 8px; border-radius: 5px;">
                    <strong style="color: #333;">Totale da pagare:</strong>
                    <p style="margin: 5px 0; font-size: 1.1rem;">€${totalPayment}</p>
                </div>
            </div>
        `;
        
        // Inserisci il messaggio prima del form
        paymentForm.insertBefore(confirmationMessage, paymentForm.firstChild);
        
        // Anima il messaggio di conferma dopo un breve ritardo
        setTimeout(() => {
            confirmationMessage.style.opacity = '1';
            confirmationMessage.style.transform = 'translateY(0)';
        }, 600);
    });

    // Validazione del form di pagamento
    document.querySelector('#payment-section form').addEventListener('submit', function(e) {
        let isValid = true;
        
        // Validazione numero carta (esattamente 16 cifre)
        const cartaInput = document.getElementById('carta');
        const cartaValue = cartaInput.value.replace(/\s+/g, ''); // Rimuove eventuali spazi
        const cartaError = document.getElementById('carta-error');
        
        if (!/^\d{16}$/.test(cartaValue)) {
            cartaError.style.display = 'block';
            isValid = false;
        } else {
            cartaError.style.display = 'none';
        }
        
        // Validazione data di scadenza (deve essere futura)
        const scadenzaInput = document.getElementById('scadenza');
        const scadenzaDate = new Date(scadenzaInput.value);
        const today = new Date();
        const scadenzaError = document.getElementById('scadenza-error');
        
        // Imposta le ore a 0 per confrontare solo le date
        today.setHours(0, 0, 0, 0);
        scadenzaDate.setHours(0, 0, 0, 0);
        
        if (scadenzaDate <= today) {
            scadenzaError.style.display = 'block';
            isValid = false;
        } else {
            scadenzaError.style.display = 'none';
        }
        
        // Validazione CVV (esattamente 3 cifre)
        const cvvInput = document.getElementById('cvv');
        const cvvValue = cvvInput.value.trim();
        const cvvError = document.getElementById('cvv-error');
        
        if (!/^\d{3}$/.test(cvvValue)) {
            cvvError.style.display = 'block';
            isValid = false;
        } else {
            cvvError.style.display = 'none';
        }
        
        if (!isValid) {
            e.preventDefault(); // Impedisce l'invio del form se non valido
        }
    });
</script>

</body>
</html>
