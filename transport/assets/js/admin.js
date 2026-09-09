/**
 * Scripts administration
 */
(function () {
    'use strict';
    // Auto-dismiss alerts after 5s
    document.querySelectorAll('.alert-dismissible').forEach(el => {
        setTimeout(() => {
            const btn = el.querySelector('.btn-close');
            if (btn) btn.click();
        }, 5000);
    });
})();
