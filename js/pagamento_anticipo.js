// Gestione visualizzazione e calcolo anticipo, sincronizzazione con il form
window.addEventListener('DOMContentLoaded', function() {
    const pagamentoDirect = document.getElementById('payment-method-direct');
    const pagamentoLoan = document.getElementById('payment-method-loan');
    const anticipoContainer = document.getElementById('anticipo-container');
    const anticipoPercentuale = document.getElementById('anticipo_percentuale');
    const totalePagamento = document.getElementById('totale-pagamento');
    const totaleReale = parseFloat(totalePagamento.textContent.replace(/\./g, '').replace(',', '.'));
    const anticipoHidden = document.getElementById('anticipo_hidden');
    const tipoPagamentoHidden = document.getElementById('tipo_pagamento_hidden');
    const paymentForm = document.getElementById('payment-form');
    const applicaAnticipoBtn = document.getElementById('applica-anticipo');

    function mostraAnticipo() {
        anticipoContainer.style.display = pagamentoLoan.checked ? 'block' : 'none';
    }

    function resetTotale() {
        totalePagamento.textContent = totaleReale.toLocaleString('it-IT', {minimumFractionDigits: 2, maximumFractionDigits: 2}) + ' €';
        anticipoHidden.value = totaleReale.toFixed(2);
        tipoPagamentoHidden.value = 'direct';
    }

    function applicaAnticipo() {
        let percent = parseFloat(anticipoPercentuale.value);
        if (isNaN(percent) || percent < 1 || percent > 99) percent = 20;
        const anticipo = (totaleReale * percent / 100);
        totalePagamento.textContent = anticipo.toLocaleString('it-IT', {minimumFractionDigits: 2, maximumFractionDigits: 2}) + ' €';
        anticipoHidden.value = anticipo.toFixed(2);
        tipoPagamentoHidden.value = 'loan';
    }

    pagamentoDirect.addEventListener('change', function() {
        mostraAnticipo();
        resetTotale();
    });
    pagamentoLoan.addEventListener('change', function() {
        mostraAnticipo();
        resetTotale();
    });
    applicaAnticipoBtn.addEventListener('click', function() {
        applicaAnticipo();
    });
    paymentForm.addEventListener('submit', function() {
        if (pagamentoLoan.checked && tipoPagamentoHidden.value !== 'loan') {
            applicaAnticipo();
        }
    });
    mostraAnticipo();
    resetTotale();
});
