/**
 * Scripts administration
 */
(function () {
    'use strict';

    document.querySelectorAll('.alert-dismissible').forEach(el => {
        setTimeout(() => {
            const btn = el.querySelector('.btn-close');
            if (btn) btn.click();
        }, 5000);
    });

    initReportFilters();
})();

function initReportFilters() {
    const sectionSelect = document.getElementById('filterSection');
    const optionGroup = document.getElementById('filterOptionGroup');
    const optionSelect = document.getElementById('filterOption');
    const classeSelect = document.getElementById('filterClasse');
    const dataEl = document.getElementById('adminFilterClassesData');

    if (!sectionSelect || !classeSelect || !dataEl) return;

    let pickerData;
    try {
        pickerData = JSON.parse(dataEl.textContent || '{}');
    } catch (e) {
        return;
    }

    const coreSections = pickerData.core || [];
    const allClasses = pickerData.classes || [];

    function fillClassSelect(section, option) {
        let list = allClasses;

        if (section && coreSections.includes(section)) {
            list = allClasses.filter(c => c.section === section);
        } else if (section === 'Options') {
            list = allClasses.filter(c => !coreSections.includes(c.section));
            if (option) {
                list = list.filter(c => c.section === option);
            }
        }

        const current = classeSelect.value;
        classeSelect.innerHTML = '<option value="">Toutes les classes</option>';
        list.forEach(c => {
            const opt = document.createElement('option');
            opt.value = c.id;
            opt.textContent = c.label;
            if (String(c.id) === current) opt.selected = true;
            classeSelect.appendChild(opt);
        });

        if (current && !list.some(c => String(c.id) === current)) {
            classeSelect.value = '';
        }
    }

    function updateOptionVisibility() {
        const isOptions = sectionSelect.value === 'Options';
        if (optionGroup) {
            optionGroup.style.display = isOptions ? '' : 'none';
        }
        if (!isOptions && optionSelect) {
            optionSelect.value = '';
        }
    }

    sectionSelect.addEventListener('change', () => {
        updateOptionVisibility();
        fillClassSelect(sectionSelect.value, optionSelect ? optionSelect.value : '');
    });

    if (optionSelect) {
        optionSelect.addEventListener('change', () => {
            fillClassSelect(sectionSelect.value, optionSelect.value);
        });
    }

    updateOptionVisibility();
    fillClassSelect(sectionSelect.value, optionSelect ? optionSelect.value : '');
}
