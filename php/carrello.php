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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Il tuo carrello | AutoMarket</title>
    <link rel="stylesheet" href="../stilicss/index.css">
    <link rel="stylesheet" href="../stilicss/carrello.css">
</head>
<body>
    <div class="video-background">
        <video autoplay muted loop>
            <source src="../stilicss/Immagini/video.mp4" type="video/mp4">
        </video>
    </div>
    
    <header>
        <h1>IL TUO CARRELLO</h1>
        <p style="font-size: 1.2rem; margin-top: 10px;">Gestisci i tuoi veicoli e procedi al pagamento</p>
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
    <form action="svuota_carrello.php" method="POST" onsubmit="return confirm('Sei sicuro di voler rimuovere tutte le auto dal carrello?');" style="margin-bottom: 0;">
        <button type="submit" class="empty-cart-btn">🗑️ Svuota carrello</button>
    </form>
    <button id="show-payment-btn" class="modify-btn">💳 Paga</button>
</div>


    <div class="back-link">
        <a href="auto.php">⬅ Torna alla ricerca</a>
    </div>
    </div> <!-- Chiusura del main-content -->
    
<div id="payment-section" style="display: none; max-width: 600px; margin: 30px auto; background-color: rgba(255, 255, 255, 0.95); padding: 25px; border-radius: 15px; box-shadow: 0 15px 30px rgba(0, 0, 0, 0.2); color: #333; opacity: 0; transform: translateY(20px); transition: opacity 0.5s ease, transform 0.5s ease;">
    <div style="margin-bottom: 25px; text-align: center;">
        <button id="payment-option-direct" class="modify-btn" style="margin-right: 15px;">💵 Pagamento Diretto</button>
        <button id="payment-option-loan" class="modify-btn">🏦 Finanziamento</button>
    </div>
    <div id="loan-section" style="display: none; margin-top: 20px; padding: 20px; background-color: #f8f9fa; border-radius: 12px; box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1); opacity: 0; transform: translateY(10px); transition: opacity 0.4s ease, transform 0.4s ease;">
        <div style="margin-bottom: 15px; padding: 10px; background-color: rgba(255,255,255,0.6); border-radius: 8px;">
            <strong style="color: #2c3e50; font-size: 1.1rem;">Rata mensile:</strong> <span id="monthly-payment" style="font-weight: bold; color: #e74c3c; font-size: 1.1rem;"></span>
        </div>
        <div style="margin-bottom: 15px; padding: 10px; background-color: rgba(255,255,255,0.6); border-radius: 8px;">
            <strong style="color: #2c3e50; font-size: 1.1rem;">Totale da pagare:</strong> <span id="total-payment" style="font-weight: bold; color: #2c3e50; font-size: 1.1rem;"></span>
        </div>
        <div style="margin-bottom: 15px; padding: 10px; background-color: rgba(255,255,255,0.6); border-radius: 8px;">
            <strong style="color: #2c3e50; font-size: 1.1rem;">Totale interessi:</strong> <span id="total-interest" style="font-weight: bold; color: #3498db; font-size: 1.1rem;"></span>
        </div>
        <div style="margin-bottom: 15px;">
            <label for="loan-duration">Durata del finanziamento (mesi):</label>
            <select id="loan-duration" style="width: 100%; padding: 8px; margin-top: 5px; border: 1px solid #ddd; border-radius: 6px; box-shadow: inset 0 1px 3px rgba(0,0,0,0.1); transition: all 0.3s ease;" onfocus="this.style.borderColor='#4CAF50'; this.style.boxShadow='0 0 0 3px rgba(76, 175, 80, 0.1)'" onblur="this.style.borderColor='#ddd'; this.style.boxShadow='inset 0 1px 3px rgba(0,0,0,0.1)'">
                <option value="12">12 mesi</option>
                <option value="24">24 mesi</option>
                <option value="36">36 mesi</option>
                <option value="48">48 mesi</option>
                <option value="60">60 mesi</option>
            </select>
        </div>
        <div style="margin-bottom: 15px;">
            <label for="loan-downpayment">Anticipo (€):</label>
            <input type="number" id="loan-downpayment" min="0" value="0" style="width: 100%; padding: 8px; margin-top: 5px; border: 1px solid #ddd; border-radius: 6px; box-shadow: inset 0 1px 3px rgba(0,0,0,0.1); transition: all 0.3s ease;" onfocus="this.style.borderColor='#4CAF50'; this.style.boxShadow='0 0 0 3px rgba(76, 175, 80, 0.1)'" onblur="this.style.borderColor='#ddd'; this.style.boxShadow='inset 0 1px 3px rgba(0,0,0,0.1)'">
        </div>
        
        <div style="margin-bottom: 20px; padding: 15px; background-color: rgba(255,255,255,0.8); border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.05);">
            <p style="margin-bottom: 10px; font-weight: bold; color: #2c3e50;">Tipo di ammortamento:</p>
            
            <div style="display: flex; gap: 15px;">
                <label style="display: flex; align-items: center; cursor: pointer; padding: 10px; border-radius: 6px; transition: all 0.3s ease; flex: 1; background-color: #e8f5e9; box-shadow: 0 2px 5px rgba(0,0,0,0.05); border: 1px solid #c8e6c9;" class="loan-model-option" onmouseover="this.style.boxShadow='0 4px 8px rgba(0,0,0,0.1)';this.style.transform='translateY(-2px)'" onmouseout="this.style.boxShadow='0 2px 5px rgba(0,0,0,0.05)';this.style.transform='translateY(0)'">
                    <input type="radio" name="loan-model" id="french-model" checked style="margin-right: 10px;">
                    <div>
                        <strong>Francese</strong>
                        <p style="margin: 5px 0 0; font-size: 0.9rem; color: #555;">Rata costante</p>
                    </div>
                </label>
                
                <label style="display: flex; align-items: center; cursor: pointer; padding: 10px; border-radius: 6px; transition: all 0.3s ease; flex: 1; background-color: #e3f2fd; box-shadow: 0 2px 5px rgba(0,0,0,0.05); border: 1px solid #bbdefb;" class="loan-model-option" onmouseover="this.style.boxShadow='0 4px 8px rgba(0,0,0,0.1)';this.style.transform='translateY(-2px)'" onmouseout="this.style.boxShadow='0 2px 5px rgba(0,0,0,0.05)';this.style.transform='translateY(0)'">
                    <input type="radio" name="loan-model" id="italian-model" style="margin-right: 10px;">
                    <div>
                        <strong>Italiano</strong>
                        <p style="margin: 5px 0 0; font-size: 0.9rem; color: #555;">Quota capitale costante</p>
                    </div>
                </label>
            </div>
            
            <p id="model-description" style="margin-top: 10px; font-size: 0.9rem; color: #666; font-style: italic;">Calcolo con ammortamento alla francese: rata costante per tutta la durata del prestito.</p>
        </div>
        <div style="background-color: #e8f5e9; padding: 10px; border-radius: 5px; margin-top: 15px;">
            <p><strong>Importo totale:</strong> €<span id="loan-total-amount"><?= number_format($totale_auto + $totale_modifiche, 2, ',', '.') ?></span></p>
            <p><strong>Tasso di interesse:</strong> <span id="loan-interest-rate">5.9</span>%</p>
            <p><strong id="monthly-payment-label">Rata mensile costante:</strong> €<span id="loan-monthly-payment">0,00</span></p>
            <p><strong>Importo totale da pagare:</strong> €<span id="loan-total-payment">0,00</span></p>
        </div>
        <div id="amortization-container" style="margin-top: 15px; max-height: 300px; overflow-y: auto; font-size: 0.9rem;">
            <h4 style="margin-top: 25px; margin-bottom: 15px; color: #2c3e50; text-align: center; font-size: 1.3rem;">Piano di ammortamento</h4>
            <div style="max-height: 300px; overflow-y: auto; border: 1px solid #ddd; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
                <table id="amortization-table" style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr style="background: linear-gradient(to right, #3498db, #2980b9); color: white;">
                            <th style="padding: 12px; text-align: left; border-bottom: 1px solid #ddd;">Mese</th>
                            <th style="padding: 12px; text-align: left; border-bottom: 1px solid #ddd;">Rata</th>
                            <th style="padding: 12px; text-align: left; border-bottom: 1px solid #ddd;">Quota Capitale</th>
                            <th style="padding: 12px; text-align: left; border-bottom: 1px solid #ddd;">Quota Interessi</th>
                            <th style="padding: 12px; text-align: left; border-bottom: 1px solid #ddd;">Debito Residuo</th>
                        </tr>
                    </thead>
                    <tbody id="amortization-body">
                        <!-- Qui verranno inserite le righe della tabella dinamicamente -->
                    </tbody>
                </table>
            </div>
        </div>
        <button id="apply-loan" class="modify-btn" style="width: 100%; margin-top: 20px; background: linear-gradient(to right, #4CAF50, #2E7D32); color: white; padding: 12px; border: none; border-radius: 8px; font-weight: bold; font-size: 1rem; box-shadow: 0 4px 6px rgba(0,0,0,0.1); transition: all 0.3s ease;" onmouseover="this.style.transform='translateY(-2px)';this.style.boxShadow='0 6px 12px rgba(0,0,0,0.15)';" onmouseout="this.style.transform='translateY(0)';this.style.boxShadow='0 4px 6px rgba(0,0,0,0.1)';">🏦 Applica Finanziamento</button>
    </div>
    <h2 style="text-align:center; margin-bottom: 20px;" id="payment-title">💳 Pagamento Diretto</h2>
    <form action="processo_pagamento.php" method="POST" id="payment-form" style="opacity: 0; transform: translateY(10px); transition: opacity 0.4s ease, transform 0.4s ease;">
        <div style="margin-bottom: 20px;">
            <label for="carta" style="display: block; margin-bottom: 8px; font-weight: bold; color: #34495e;">Numero Carta:</label>
            <input type="text" id="carta" name="carta" maxlength="16" required pattern="[0-9]{16}" title="Il numero della carta deve essere di 16 cifre" style="width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 6px; box-shadow: inset 0 1px 3px rgba(0,0,0,0.1); transition: all 0.3s ease;" onfocus="this.style.borderColor='#4CAF50'; this.style.boxShadow='0 0 0 3px rgba(76, 175, 80, 0.1)'" onblur="this.style.borderColor='#ddd'; this.style.boxShadow='inset 0 1px 3px rgba(0,0,0,0.1)'">
            <div id="carta-error" style="color: #e74c3c; font-size: 0.9rem; margin-top: 5px; display: none;">Il numero della carta deve essere esattamente di 16 cifre.</div>
        </div>
        
        <div style="margin-bottom: 20px;">
            <label for="titolare" style="display: block; margin-bottom: 8px; font-weight: bold; color: #34495e;">Intestatario Carta:</label>
            <input type="text" id="titolare" name="titolare" required style="width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 6px; box-shadow: inset 0 1px 3px rgba(0,0,0,0.1); transition: all 0.3s ease;" onfocus="this.style.borderColor='#4CAF50'; this.style.boxShadow='0 0 0 3px rgba(76, 175, 80, 0.1)'" onblur="this.style.borderColor='#ddd'; this.style.boxShadow='inset 0 1px 3px rgba(0,0,0,0.1)'">
        </div>
        
        <div style="display: flex; gap: 15px; margin-bottom: 20px;">
            <div style="flex: 1;">
                <label for="scadenza" style="display: block; margin-bottom: 8px; font-weight: bold; color: #34495e;">Data Scadenza:</label>
                <input type="date" id="scadenza" name="scadenza" required style="width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 6px; box-shadow: inset 0 1px 3px rgba(0,0,0,0.1); transition: all 0.3s ease;" onfocus="this.style.borderColor='#4CAF50'; this.style.boxShadow='0 0 0 3px rgba(76, 175, 80, 0.1)'" onblur="this.style.borderColor='#ddd'; this.style.boxShadow='inset 0 1px 3px rgba(0,0,0,0.1)'">
                <div id="scadenza-error" style="color: #e74c3c; font-size: 0.9rem; margin-top: 5px; display: none;">La data di scadenza deve essere futura alla data di oggi.</div>
            </div>
            <div style="flex: 1;">
                <label for="cvv" style="display: block; margin-bottom: 8px; font-weight: bold; color: #34495e;">CVV:</label>
                <input type="text" id="cvv" name="cvv" maxlength="3" required pattern="[0-9]{3}" title="Il CVV deve essere di 3 cifre" style="width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 6px; box-shadow: inset 0 1px 3px rgba(0,0,0,0.1); transition: all 0.3s ease;" onfocus="this.style.borderColor='#4CAF50'; this.style.boxShadow='0 0 0 3px rgba(76, 175, 80, 0.1)'" onblur="this.style.borderColor='#ddd'; this.style.boxShadow='inset 0 1px 3px rgba(0,0,0,0.1)'">
                <div id="cvv-error" style="color: #e74c3c; font-size: 0.9rem; margin-top: 5px; display: none;">Il CVV deve essere esattamente di 3 cifre.</div>
            </div>
        </div>
        
        <input type="hidden" name="importo" value="<?= $totale_auto + $totale_modifiche ?>">
        
        <button type="submit" style="background: linear-gradient(to right, #4CAF50, #2E7D32); color: white; padding: 14px 20px; border: none; border-radius: 8px; cursor: pointer; font-size: 16px; width: 100%; font-weight: bold; box-shadow: 0 4px 6px rgba(0,0,0,0.1); transition: all 0.3s ease;" onmouseover="this.style.transform='translateY(-2px)';this.style.boxShadow='0 6px 12px rgba(0,0,0,0.15)';" onmouseout="this.style.transform='translateY(0)';this.style.boxShadow='0 4px 6px rgba(0,0,0,0.1)';">💳 Conferma Pagamento</button>
    </form>
</div>
    <footer>
        <p>&copy; 2025 AutoMarket - Tutti i diritti riservati</p>
    </footer>
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
        // Nascondi la sezione prestito con transizione
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
        
        // Rimuovi il messaggio di conferma del finanziamento se presente
        const confirmationMessages = paymentForm.querySelectorAll('div[style*="background-color: #e8f5e9"]');
        confirmationMessages.forEach(msg => msg.remove());
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
        calculateLoan();
    });
    
    // Aggiungi classe active-option al pulsante di pagamento diretto all'inizio
    document.getElementById('payment-option-direct').classList.add('active-option');
    
    // Mostra automaticamente il form di pagamento all'inizio
    setTimeout(() => {
        paymentForm.style.opacity = '1';
        paymentForm.style.transform = 'translateY(0)';
    }, 500);
    
    // Funzione per calcolare il finanziamento in base al modello selezionato
    function calculateLoan() {
        const totalAmount = <?= $totale_auto + $totale_modifiche ?>;
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
            
            // Genera la tabella di ammortamento francese
            generateFrenchAmortizationTable(loanAmount, monthlyRate, monthlyPayment, duration);
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
