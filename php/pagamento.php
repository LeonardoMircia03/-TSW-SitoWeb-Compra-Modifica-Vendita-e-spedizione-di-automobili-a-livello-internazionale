<?php
session_start();
require_once('config.php');

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$utente_id = $_SESSION['user_id'];

// Recupera le auto nel carrello dell'utente
$query = "SELECT a.*, c.modifiche_estetiche, c.modifiche_tecniche FROM auto a JOIN carrello c ON a.id = c.auto_id WHERE c.utente_id = $1";
$result = pg_query_params($dbconnect, $query, array($utente_id));

// Prezzi delle modifiche come da carrello.php
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

$carrello = [];
$totale = 0;
if ($result && pg_num_rows($result) > 0) {
    while ($row = pg_fetch_assoc($result)) {
        $prezzo_modifiche_estetiche = 0;
        $prezzo_modifiche_tecniche = 0;
        $modifiche_estetiche = json_decode($row['modifiche_estetiche'] ?? '[]', true);
        $modifiche_tecniche = json_decode($row['modifiche_tecniche'] ?? '[]', true);
        if (!is_array($modifiche_estetiche)) $modifiche_estetiche = [];
        if (!is_array($modifiche_tecniche)) $modifiche_tecniche = [];
        foreach ($modifiche_estetiche as $modifica) {
            $prezzo_modifiche_estetiche += $prezzi_estetici[$modifica] ?? 0;
        }
        foreach ($modifiche_tecniche as $modifica) {
            $prezzo_modifiche_tecniche += $prezzi_tecnici[$modifica] ?? 0;
        }
        $row['prezzo_modifiche_estetiche'] = $prezzo_modifiche_estetiche;
        $row['prezzo_modifiche_tecniche'] = $prezzo_modifiche_tecniche;
        $row['modifiche_estetiche_arr'] = $modifiche_estetiche;
        $row['modifiche_tecniche_arr'] = $modifiche_tecniche;
        $row['prezzo_totale'] = $row['prezzo'] + $prezzo_modifiche_estetiche + $prezzo_modifiche_tecniche;
        $carrello[] = $row;
        $totale += $row['prezzo'] + $prezzo_modifiche_estetiche + $prezzo_modifiche_tecniche;
    }
}

if (!$result || pg_num_rows($result) == 0) {
    header('Location: carrello.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pagamento | AutoMarket</title>
    <link rel="stylesheet" href="../stilicss/pagamento.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="payment-container">
        <h1>Pagamento</h1>
        <div class="payment-summary">
            <h2>Riepilogo del carrello</h2>
            <div class="summary-items">
                <?php foreach ($carrello as $item): ?>
    <div class="summary-item">
        <div class="item-details">
            <h3><?php echo htmlspecialchars($item['modello']); ?></h3>
            <p><?php echo htmlspecialchars($item['descrizione']); ?></p>
            <?php if (!empty($item['modifiche_estetiche_arr'])): ?>
                <p>Modifiche estetiche:
                    <?php foreach ($item['modifiche_estetiche_arr'] as $mod): ?>
                        <span style="margin-right: 8px;">
                            <?php echo htmlspecialchars($mod); ?> (+<?php echo number_format($prezzi_estetici[$mod] ?? 0, 2, ',', '.'); ?> €)
                        </span>
                    <?php endforeach; ?>
                </p>
            <?php endif; ?>
            <?php if (!empty($item['modifiche_tecniche_arr'])): ?>
                <p>Modifiche tecniche:
                    <?php foreach ($item['modifiche_tecniche_arr'] as $mod): ?>
                        <span style="margin-right: 8px;">
                            <?php echo htmlspecialchars($mod); ?> (+<?php echo number_format($prezzi_tecnici[$mod] ?? 0, 2, ',', '.'); ?> €)
                        </span>
                    <?php endforeach; ?>
                </p>
            <?php endif; ?>
        </div>
        <div class="item-price">
            <?php echo number_format($item['prezzo_totale'], 2, ',', '.'); ?> €
        </div>
    </div>
<?php endforeach; ?>
            </div>
            <div class="total">
    <h3>Totale da pagare:</h3>
    <span id="totale-pagamento"><?php echo number_format($totale, 2, ',', '.'); ?> €</span>
</div>
        </div>
        <div class="payment-method-selector">
    <label><input type="radio" name="payment-method" value="direct" id="payment-method-direct" checked> Pagamento diretto</label>
    <label><input type="radio" name="payment-method" value="loan" id="payment-method-loan"> Finanziamento</label>
</div>
<div id="anticipo-container" style="display:none; margin: 15px 0 10px 0;">
    <label for="anticipo_percentuale"><b>Anticipo (%):</b></label>
    <input type="number" id="anticipo_percentuale" min="1" max="99" value="20" style="width: 60px;">%
    <button type="button" id="applica-anticipo" class="pay-button" style="margin-left:10px;">Applica Finanziamento</button>
</div>
        <div id="loan-section" style="display:none; margin-top:30px;">
            <h2>Calcola il tuo finanziamento</h2>
            <div class="loan-form">
                <div class="form-group">
                    <label for="loan-duration">Durata (mesi):</label>
                    <select id="loan-duration">
                        <option value="12">12</option>
                        <option value="24">24</option>
                        <option value="36">36</option>
                        <option value="48">48</option>
                        <option value="60">60</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="loan-downpayment">Anticipo (%):</label>
                    <input type="number" id="loan-downpayment" min="0" max="100" value="20">
                </div>
                <div class="form-group">
                    <label>Tipo di ammortamento:</label>
                    <label><input type="radio" name="loan-model" id="french-model" value="french" checked> Francese</label>
                    <label><input type="radio" name="loan-model" id="italian-model" value="italian"> Italiano</label>
                </div>
                <div class="loan-results">
                    <div><b>Tasso:</b> <span id="loan-interest-rate">3.5%</span></div>
                    <div><b>Rata mensile:</b> <span id="loan-monthly-payment">0,00</span> €</div>
                    <div><b>Totale da pagare:</b> <span id="loan-total-payment">0,00</span> €</div>
                </div>
                <div class="amortization-table">
                    <h3>Piano di ammortamento</h3>
                    <table>
                        <thead>
                            <tr>
                                <th>Mese</th><th>Rata</th><th>Capitale</th><th>Interessi</th><th>Saldo</th>
                            </tr>
                        </thead>
                        <tbody id="amortization-body"></tbody>
                    </table>
                </div>
                <button type="button" id="apply-loan" class="pay-button">Applica Finanziamento</button>
            </div>
        </div>
        <script>
        // --- FINANZIAMENTO LOGICA ---
        const paymentMethodDirect = document.getElementById('payment-method-direct');
        const paymentMethodLoan = document.getElementById('payment-method-loan');
        const loanSection = document.getElementById('loan-section');
        const paymentForm = document.getElementById('payment-form');
        const loanDuration = document.getElementById('loan-duration');
        const loanDownpayment = document.getElementById('loan-downpayment');
        const loanInterestRate = 3.5; // tasso fisso esempio
        const frenchModelRadio = document.getElementById('french-model');
        const italianModelRadio = document.getElementById('italian-model');
        const loanMonthlyPayment = document.getElementById('loan-monthly-payment');
        const loanTotalPayment = document.getElementById('loan-total-payment');
        const amortizationBody = document.getElementById('amortization-body');
        const applyLoanBtn = document.getElementById('apply-loan');
        let totalAmount = 0;
        // Calcola totale dal riepilogo carrello
        (function() {
            const totalSpan = document.querySelector('.total span');
            if (totalSpan) {
                totalAmount = parseFloat(totalSpan.textContent.replace(/\./g, '').replace(',', '.'));
            }
        })();
        function updateLoan() {
            const duration = parseInt(loanDuration.value);
            const downPercent = parseFloat(loanDownpayment.value) || 0;
            const downPayment = totalAmount * downPercent / 100;
            const loaned = totalAmount - downPayment;
            const rate = loanInterestRate / 100 / 12;
            let monthly = 0, total = 0;
            if (frenchModelRadio.checked) {
                // Modello francese (rata costante)
                monthly = loaned * (rate * Math.pow(1 + rate, duration)) / (Math.pow(1 + rate, duration) - 1);
                total = monthly * duration + downPayment;
                generateFrenchTable(loaned, rate, monthly, duration);
            } else {
                // Modello italiano (quota capitale costante)
                const principal = loaned / duration;
                let rem = loaned;
                let totPay = 0;
                amortizationBody.innerHTML = '';
                for (let i = 1; i <= duration; i++) {
                    const interest = rem * rate;
                    const pay = principal + interest;
                    totPay += pay;
                    rem -= principal;
                    const row = `<tr><td>${i}</td><td>${pay.toFixed(2).replace('.', ',')} €</td><td>${principal.toFixed(2).replace('.', ',')} €</td><td>${interest.toFixed(2).replace('.', ',')} €</td><td>${Math.max(0, rem).toFixed(2).replace('.', ',')} €</td></tr>`;
                    amortizationBody.innerHTML += row;
                }
                monthly = (loaned / duration) + (loaned * rate); // prima rata
                total = totPay + downPayment;
            }
            loanMonthlyPayment.textContent = monthly.toFixed(2).replace('.', ',');
            loanTotalPayment.textContent = total.toFixed(2).replace('.', ',');
        }
        function generateFrenchTable(loaned, rate, monthly, duration) {
            let rem = loaned;
            amortizationBody.innerHTML = '';
            for (let i = 1; i <= duration; i++) {
                const interest = rem * rate;
                const principal = monthly - interest;
                rem -= principal;
                const row = `<tr><td>${i}</td><td>${monthly.toFixed(2).replace('.', ',')} €</td><td>${principal.toFixed(2).replace('.', ',')} €</td><td>${interest.toFixed(2).replace('.', ',')} €</td><td>${Math.max(0, rem).toFixed(2).replace('.', ',')} €</td></tr>`;
                amortizationBody.innerHTML += row;
            }
        }
        loanDuration.addEventListener('change', updateLoan);
        loanDownpayment.addEventListener('input', updateLoan);
        frenchModelRadio.addEventListener('change', updateLoan);
        italianModelRadio.addEventListener('change', updateLoan);
        // Cambia visibilità sezioni
        paymentMethodDirect.addEventListener('change', function() {
            if (this.checked) {
                loanSection.style.display = 'none';
                paymentForm.style.display = 'block';
                updateLoan(); // Nascondi box finanziamento e mostra totale carrello
            }
        });
        paymentMethodLoan.addEventListener('change', function() {
            if (this.checked) {
                loanSection.style.display = 'block';
                paymentForm.style.display = 'none';
                updateLoan(); // Mostra box finanziamento in cima
            }
        });
        // Applica finanziamento
        applyLoanBtn.addEventListener('click', function() {
            // Mostra form pagamento e nascondi loan
            loanSection.style.display = 'none';
            paymentForm.style.display = 'block';
            // Mostra un messaggio di conferma sopra il form
            let msg = document.getElementById('loan-applied-msg');
            if (!msg) {
                msg = document.createElement('div');
                msg.id = 'loan-applied-msg';
                msg.style.background = '#e6ffe6';
                msg.style.color = '#217a00';
                msg.style.padding = '10px 20px';
                msg.style.marginBottom = '15px';
                msg.style.borderRadius = '6px';
                msg.style.fontWeight = 'bold';
                msg.innerText = 'Hai scelto il finanziamento. Completa i dati per il pagamento della prima rata.';
                paymentForm.parentNode.insertBefore(msg, paymentForm);
            }
            // Aggiorna il totale e mostra la prima rata
            const totalSpan = document.querySelector('.total span');
            const firstInstallment = frenchModelRadio.checked ?
                (function() {
                    // Modello francese: rata costante
                    const duration = parseInt(loanDuration.value);
                    const downPercent = parseFloat(loanDownpayment.value) || 0;
                    const downPayment = totalAmount * downPercent / 100;
                    const loaned = totalAmount - downPayment;
                    const rate = loanInterestRate / 100 / 12;
                    return loaned * (rate * Math.pow(1 + rate, duration)) / (Math.pow(1 + rate, duration) - 1);
                })() :
                (function() {
                    // Modello italiano: prima rata = quota capitale + interessi iniziali
                    const duration = parseInt(loanDuration.value);
                    const downPercent = parseFloat(loanDownpayment.value) || 0;
                    const downPayment = totalAmount * downPercent / 100;
                    const loaned = totalAmount - downPayment;
                    const rate = loanInterestRate / 100 / 12;
                    return (loaned / duration) + (loaned * rate);
                })();
            const totalLoan = parseFloat(loanTotalPayment.textContent.replace('.', '').replace(',', '.'));
            // Aggiorna il box in cima alla pagina
            let topBox = document.getElementById('finanziamento-top-box');
            if (!topBox) {
                topBox = document.createElement('div');
                topBox.id = 'finanziamento-top-box';
                topBox.style.background = '#f0f8ff';
                topBox.style.color = '#0c2c5a';
                topBox.style.padding = '18px 24px';
                topBox.style.margin = '24px auto 16px auto';
                topBox.style.borderRadius = '8px';
                topBox.style.fontSize = '1.15em';
                topBox.style.fontWeight = 'bold';
                topBox.style.textAlign = 'center';
                // Inserisci subito dopo <body> o all'inizio del container
                const mainContainer = document.querySelector('.container, main, #main, body');
                if (mainContainer) {
                    mainContainer.insertBefore(topBox, mainContainer.firstChild);
                } else {
                    document.body.insertBefore(topBox, document.body.firstChild);
                }
            }
            topBox.innerHTML = `Totale finanziato: <span style='color:#217a00;'>${loanTotalPayment.textContent} €</span> &nbsp; | &nbsp; <span id="first-installment-info" style="color:#217a00;">Prima rata: ${firstInstallment.toFixed(2).replace('.', ',')} €</span>`;
            // (opzionale) nascondi il totale originale nel riepilogo carrello
            if (totalSpan) totalSpan.style.display = 'none';
            // Scrolla fino al form pagamento
            paymentForm.scrollIntoView({ behavior: 'smooth', block: 'start' });
        });
        // Calcola iniziale
        updateLoan();
        </script>
        <form id="payment-form" action="processo_pagamento.php" method="POST" style="margin-top:30px;">
    <input type="hidden" name="anticipo" id="anticipo_hidden" value="">
    <input type="hidden" name="tipo_pagamento" id="tipo_pagamento_hidden" value="direct">
            <div class="form-group">
                <label for="card-number">Numero carta:</label>
                <input type="text" id="card-number" name="carta" placeholder="1234 5678 9012 3456" required>
                <span class="error" id="card-number-error"></span>
            </div>
            <div class="form-group">
                <label for="card-holder">Nome titolare:</label>
                <input type="text" id="card-holder" name="titolare" placeholder="Nome e cognome" required>
                <span class="error" id="card-holder-error"></span>
            </div>
            <div class="form-group">
                <label for="expiry-date">Data di scadenza:</label>
                <input type="text" id="expiry-date" name="scadenza" placeholder="MM/YY" required>
                <span class="error" id="expiry-date-error"></span>
            </div>
            <div class="form-group">
                <label for="cvv">CVV:</label>
                <input type="text" id="cvv" name="cvv" placeholder="123" required>
                <span class="error" id="cvv-error"></span>
            </div>
            <button type="submit" class="pay-button">Conferma Pagamento</button>
        </form>
    </div>
    <script>
    // Formattazione e validazione numero carta
    const cardNumberInput = document.getElementById('card-number');
    const cardNumberError = document.getElementById('card-number-error');
    cardNumberInput.addEventListener('input', function(e) {
        // Rimuove tutto tranne numeri
        let value = this.value.replace(/\D/g, '');
        // Limita a 16 cifre
        value = value.slice(0, 16);
        // Formatta a gruppi di 4
        let formatted = value.replace(/(\d{4})(?=\d)/g, '$1 ');
        this.value = formatted;
        // Validazione
        if (value.length === 16) {
            cardNumberError.textContent = '';
        } else {
            cardNumberError.textContent = 'Il numero della carta deve essere di 16 cifre.';
        }
    });
    cardNumberInput.addEventListener('blur', function() {
        let value = this.value.replace(/\D/g, '');
        if (value.length !== 16) {
            cardNumberError.textContent = 'Il numero della carta deve essere di 16 cifre.';
        } else {
            cardNumberError.textContent = '';
        }
    });

    // Formattazione e validazione scadenza (MM/AA)
    const expiryInput = document.getElementById('expiry-date');
    const expiryError = document.getElementById('expiry-date-error');
    expiryInput.addEventListener('input', function(e) {
        let value = this.value.replace(/[^\d]/g, '');
        if (value.length > 4) value = value.slice(0, 4);
        if (value.length > 2) {
            value = value.slice(0,2) + '/' + value.slice(2);
        }
        this.value = value;
    });
    expiryInput.addEventListener('blur', function() {
        const regex = /^(0[1-9]|1[0-2])\/\d{2}$/;
        if (!regex.test(this.value)) {
            expiryError.textContent = 'Formato MM/AA richiesto.';
        } else {
            // Verifica che la data sia futura
            const [month, year] = this.value.split('/');
            const now = new Date();
            const inputMonth = parseInt(month, 10);
            const inputYear = 2000 + parseInt(year, 10);
            const expiryDate = new Date(inputYear, inputMonth - 1, 1);
            const today = new Date(now.getFullYear(), now.getMonth(), 1);
            if (expiryDate < today) {
                expiryError.textContent = 'La data di scadenza deve essere futura.';
            } else {
                expiryError.textContent = '';
            }
        }
    });

    // Validazione CVV
    const cvvInput = document.getElementById('cvv');
    const cvvError = document.getElementById('cvv-error');
    cvvInput.addEventListener('input', function() {
        let value = this.value.replace(/\D/g, '');
        value = value.slice(0, 3);
        this.value = value;
        if (value.length === 3) {
            cvvError.textContent = '';
        } else {
            cvvError.textContent = 'Il CVV deve essere di 3 cifre.';
        }
    });
    cvvInput.addEventListener('blur', function() {
        if (this.value.length !== 3) {
            cvvError.textContent = 'Il CVV deve essere di 3 cifre.';
        } else {
            cvvError.textContent = '';
        }
    });

    // Validazione campo titolare (almeno due parole)
    const cardHolderInput = document.getElementById('card-holder');
    const cardHolderError = document.getElementById('card-holder-error');
    cardHolderInput.addEventListener('blur', function() {
        const words = this.value.trim().split(/\s+/);
        if (words.length < 2) {
            cardHolderError.textContent = 'Inserisci il nome e il cognome completo.';
        } else {
            cardHolderError.textContent = '';
        }
    });

    // Validazione finale su submit
    document.getElementById('payment-form').addEventListener('submit', function(e) {
        let valid = true;
        // Numero carta
        let cardValue = cardNumberInput.value.replace(/\D/g, '');
        if (cardValue.length !== 16) {
            cardNumberError.textContent = 'Il numero della carta deve essere di 16 cifre.';
            valid = false;
        }
        // Nome titolare
        const words = cardHolderInput.value.trim().split(/\s+/);
        if (words.length < 2) {
            cardHolderError.textContent = 'Inserisci il nome e il cognome completo.';
            valid = false;
        } else {
            cardHolderError.textContent = '';
        }
        // Scadenza
        const regex = /^(0[1-9]|1[0-2])\/\d{2}$/;
        if (!regex.test(expiryInput.value)) {
            expiryError.textContent = 'Formato MM/AA richiesto.';
            valid = false;
        } else {
            const [month, year] = expiryInput.value.split('/');
            const now = new Date();
            const inputMonth = parseInt(month, 10);
            const inputYear = 2000 + parseInt(year, 10);
            const expiryDate = new Date(inputYear, inputMonth - 1, 1);
            const today = new Date(now.getFullYear(), now.getMonth(), 1);
            if (expiryDate < today) {
                expiryError.textContent = 'La carta è scaduta.';
                valid = false;
            } else {
                expiryError.textContent = '';
            }
        }
        // CVV
        if (cvvInput.value.length !== 3) {
            cvvError.textContent = 'Il CVV deve essere di 3 cifre.';
            valid = false;
        }
        if (!valid) e.preventDefault();
    });
    </script>
<script src="../pagamento_anticipo.js"></script>
</body>
</html>
