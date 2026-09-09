/**
 * Formulaire d'inscription multi-étapes
 */
(function () {
    'use strict';

    let currentStep = 1;
    const totalSteps = 4;
    const form = document.getElementById('inscriptionForm');
    if (!form) return;

    // Navigation entre étapes
    function showStep(step) {
        document.querySelectorAll('.form-step').forEach(el => {
            el.classList.toggle('active', parseInt(el.dataset.step) === step);
        });
        document.querySelectorAll('.step-item').forEach(el => {
            const s = parseInt(el.dataset.step);
            el.classList.remove('active', 'completed');
            if (s === step) el.classList.add('active');
            else if (s < step) el.classList.add('completed');
        });
        currentStep = step;
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    function validateStep(step) {
        const stepEl = document.querySelector(`.form-step[data-step="${step}"]`);
        const inputs = stepEl.querySelectorAll('[required]');
        let valid = true;
        inputs.forEach(input => {
            if (!input.checkValidity()) {
                input.classList.add('is-invalid');
                valid = false;
            } else {
                input.classList.remove('is-invalid');
            }
        });
        return valid;
    }

    document.querySelectorAll('.next-step').forEach(btn => {
        btn.addEventListener('click', () => {
            if (validateStep(currentStep) && currentStep < totalSteps) {
                showStep(currentStep + 1);
            }
        });
    });

    document.querySelectorAll('.prev-step').forEach(btn => {
        btn.addEventListener('click', () => {
            if (currentStep > 1) showStep(currentStep - 1);
        });
    });

    // Tarif
    const tariffSelect = document.getElementById('tariffSelect');
    const montantDuInput = document.getElementById('montantDu');
    if (tariffSelect) {
        tariffSelect.addEventListener('change', () => {
            const opt = tariffSelect.selectedOptions[0];
            montantDuInput.value = opt.dataset.montant || 50;
            updateReste();
        });
    }

    // Paiement
    const montantPayeGroup = document.getElementById('montantPayeGroup');
    const resteInfo = document.getElementById('resteInfo');
    const montantPayeInput = form.querySelector('[name="montant_paye"]');

    function updateReste() {
        const du = parseFloat(montantDuInput.value) || 0;
        const paye = parseFloat(montantPayeInput.value) || 0;
        const reste = Math.max(0, du - paye);
        document.getElementById('resteAmount').textContent = reste.toFixed(0);
        resteInfo.style.display = reste > 0 ? 'block' : 'none';
    }

    form.querySelectorAll('[name="statut_paiement"]').forEach(radio => {
        radio.addEventListener('change', () => {
            const val = radio.value;
            if (val === 'paye') {
                montantPayeGroup.style.display = 'none';
                montantPayeInput.value = montantDuInput.value;
            } else if (val === 'partiel') {
                montantPayeGroup.style.display = 'block';
                montantPayeInput.value = '';
            } else {
                montantPayeGroup.style.display = 'none';
                montantPayeInput.value = 0;
            }
            updateReste();
        });
    });

    if (montantPayeInput) {
        montantPayeInput.addEventListener('input', updateReste);
    }

    // Soumission AJAX
    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        if (!validateStep(currentStep)) return;

        const overlay = document.getElementById('loadingOverlay');
        const submitBtn = document.getElementById('submitBtn');
        overlay.classList.remove('d-none');
        overlay.classList.add('d-flex');
        submitBtn.disabled = true;

        try {
            const formData = new FormData(form);
            const response = await fetch(form.action, {
                method: 'POST',
                body: formData,
            });
            const data = await response.json();

            if (data.success) {
                window.location.href = data.redirect;
            } else {
                alert(data.message || 'Erreur lors de l\'inscription.');
            }
        } catch (err) {
            alert('Erreur de connexion. Vérifiez votre réseau et réessayez.');
        } finally {
            overlay.classList.add('d-none');
            overlay.classList.remove('d-flex');
            submitBtn.disabled = false;
        }
    });
})();
