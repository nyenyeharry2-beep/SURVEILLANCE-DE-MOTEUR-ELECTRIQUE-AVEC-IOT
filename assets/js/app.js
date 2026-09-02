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
});
