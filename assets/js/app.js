document.addEventListener('DOMContentLoaded', function () {
    const deleteForms = document.querySelectorAll('form[data-confirm]');
    deleteForms.forEach(function (form) {
        form.addEventListener('submit', function (e) {
            const message = form.getAttribute('data-confirm') || 'Confirmer la suppression ?';
            if (!confirm(message)) {
                e.preventDefault();
            }
        });
    });

    const saleForm = document.getElementById('sale-form');
    if (saleForm) {
        const qtyInput = saleForm.querySelector('[name="quantite"]');
        const priceInput = saleForm.querySelector('[name="prix_unitaire"]');
        const totalDisplay = document.getElementById('sale-total');

        function updateTotal() {
            const qty = parseFloat(qtyInput?.value) || 0;
            const price = parseFloat(priceInput?.value) || 0;
            if (totalDisplay) {
                totalDisplay.textContent = (qty * price).toLocaleString('fr-FR') + ' FCFA';
            }
        }

        qtyInput?.addEventListener('input', updateTotal);
        priceInput?.addEventListener('input', updateTotal);
    }
});
