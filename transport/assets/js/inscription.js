/**
 * Formulaire d'inscription multi-étapes
 */
(function () {
    'use strict';

    let currentStep = 1;
    const totalSteps = 4;
    const form = document.getElementById('inscriptionForm');
    if (!form) return;

    // Sélecteur de classe par section
    initClassPicker();

    function initClassPicker() {
        const dataEl = document.getElementById('classPickerData');
        const sectionSelect = document.getElementById('sectionSelect');
        const simpleGroup = document.getElementById('simpleClassGroup');
        const simpleSelect = document.getElementById('simpleClassSelect');
        const optionsGroup = document.getElementById('optionsClassGroup');
        const yearSelect = document.getElementById('optionYearSelect');
        const specialtySelect = document.getElementById('optionSpecialtySelect');
        const classeIdInput = document.getElementById('classeIdInput');
        const hint = document.getElementById('selectedClassHint');

        if (!dataEl || !sectionSelect || !classeIdInput) return;

        let pickerData;
        try {
            pickerData = JSON.parse(dataEl.textContent || '{}');
        } catch (e) {
            return;
        }

        function setClasseId(id, label) {
            classeIdInput.value = id || '';
            if (hint) {
                hint.textContent = label ? ('Classe choisie : ' + label) : 'Maternelle → Primaire → Secondaire → Options';
            }
        }

        function fillSimpleSelect(items) {
            simpleSelect.innerHTML = '<option value="">-- Choisir la classe --</option>';
            items.forEach(item => {
                const opt = document.createElement('option');
                opt.value = item.id;
                opt.textContent = item.label;
                simpleSelect.appendChild(opt);
            });
        }

        function fillSpecialtySelect(year) {
            specialtySelect.innerHTML = '<option value="">-- Choisir l\'option --</option>';
            const specialties = pickerData.Options?.specialties || {};
            Object.keys(specialties).sort().forEach(name => {
                const match = specialties[name].find(c => c.year === year);
                if (match) {
                    const opt = document.createElement('option');
                    opt.value = match.id;
                    opt.textContent = name;
                    opt.dataset.label = match.label;
                    specialtySelect.appendChild(opt);
                }
            });
        }

        function resetPicker() {
            simpleGroup.style.display = 'none';
            optionsGroup.style.display = 'none';
            simpleSelect.innerHTML = '<option value="">-- Choisir la classe --</option>';
            yearSelect.value = '';
            specialtySelect.innerHTML = '<option value="">-- Choisir l\'option --</option>';
            setClasseId('', '');
        }

        sectionSelect.addEventListener('change', () => {
            resetPicker();
            const section = sectionSelect.value;

            if (section === 'Maternelle' || section === 'Primaire' || section === 'Secondaire') {
                simpleGroup.style.display = 'block';
                fillSimpleSelect(pickerData[section] || []);
            } else if (section === 'Options') {
                optionsGroup.style.display = 'block';
            }
        });

        simpleSelect.addEventListener('change', () => {
            const opt = simpleSelect.selectedOptions[0];
            setClasseId(simpleSelect.value, opt && opt.value ? opt.textContent : '');
        });

        yearSelect.addEventListener('change', () => {
            specialtySelect.innerHTML = '<option value="">-- Choisir l\'option --</option>';
            setClasseId('', '');
            if (yearSelect.value) {
                fillSpecialtySelect(yearSelect.value);
            }
        });

        specialtySelect.addEventListener('change', () => {
            const opt = specialtySelect.selectedOptions[0];
            const label = opt?.dataset?.label || opt?.textContent || '';
            setClasseId(specialtySelect.value, label);
        });
    }

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

        if (step === 1) {
            const classeIdInput = document.getElementById('classeIdInput');
            const sectionSelect = document.getElementById('sectionSelect');
            if (classeIdInput && sectionSelect && sectionSelect.offsetParent !== null) {
                if (!classeIdInput.value) {
                    classeIdInput.classList.add('is-invalid');
                    sectionSelect.classList.add('is-invalid');
                    valid = false;
                } else {
                    classeIdInput.classList.remove('is-invalid');
                    sectionSelect.classList.remove('is-invalid');
                }
            }
        }

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
            montantDuInput.value = opt.dataset.montant || montantDuInput.value || 15;
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
            const raw = await response.text();
            let data;
            try {
                data = JSON.parse(raw);
            } catch (parseErr) {
                throw new Error('Réponse serveur invalide. Rechargez la page et réessayez.');
            }

            if (data.success) {
                window.location.href = data.redirect;
            } else {
                alert(data.message || 'Erreur lors de l\'inscription.');
            }
        } catch (err) {
            alert(err.message || 'Erreur de connexion. Vérifiez votre réseau et réessayez.');
        } finally {
            overlay.classList.add('d-none');
            overlay.classList.remove('d-flex');
            submitBtn.disabled = false;
        }
    });
})();
