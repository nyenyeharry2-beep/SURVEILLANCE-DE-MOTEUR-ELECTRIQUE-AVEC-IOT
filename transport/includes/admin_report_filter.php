<?php
/**
 * Formulaire filtre rapport : Section → Option → Classe
 * Variables attendues : $reportFilters (array), $formId (string, optionnel)
 */
$reportFilters = $reportFilters ?? parseReportFilters($_GET);
$formId = $formId ?? 'reportFilterForm';
$filterSection = $reportFilters['section'] ?? '';
$filterOption = $reportFilters['option'] ?? '';
$filterClasse = (int) ($reportFilters['classe'] ?? 0);
$filterClasses = getClassesForAdminFilter($filterSection ?: null, $filterOption ?: null);
$showOption = ($filterSection === 'Options');
?>
<form method="GET" class="row g-2 align-items-end" id="<?= e($formId) ?>">
    <div class="col-auto">
        <label class="form-label small">Section *</label>
        <select name="section" id="filterSection" class="form-select form-select-sm" required>
            <option value="">-- Choisir la section --</option>
            <?php foreach (getMainSectionsForFilter() as $s): ?>
            <option value="<?= e($s) ?>" <?= $filterSection === $s ? 'selected' : '' ?>><?= e($s) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-auto" id="filterOptionGroup" style="<?= $showOption ? '' : 'display:none;' ?>">
        <label class="form-label small">Option / Filière</label>
        <select name="option" id="filterOption" class="form-select form-select-sm">
            <option value="">Toutes les options</option>
            <?php foreach (getOptionSpecialties() as $opt): ?>
            <option value="<?= e($opt) ?>" <?= $filterOption === $opt ? 'selected' : '' ?>><?= e($opt) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-auto">
        <label class="form-label small">Classe</label>
        <select name="classe" id="filterClasse" class="form-select form-select-sm">
            <option value="">Toutes les classes</option>
            <?php foreach ($filterClasses as $c): ?>
            <option value="<?= $c['id'] ?>" <?= $filterClasse == $c['id'] ? 'selected' : '' ?>><?= e(formatClassWithSection($c)) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-auto">
        <button type="submit" class="btn btn-primary btn-sm">Filtrer</button>
    </div>
</form>
<script type="application/json" id="adminFilterClassesData"><?= json_encode([
    'core' => getCoreSchoolSections(),
    'options' => getOptionSpecialties(),
    'classes' => array_map(static fn($c) => [
        'id' => (int) $c['id'],
        'section' => $c['section'] ?? '',
        'label' => formatClassWithSection($c),
    ], getActiveClasses()),
], JSON_UNESCAPED_UNICODE) ?></script>
