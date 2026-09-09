<?php
/**
 * Formulaire public d'inscription au transport scolaire (accessible via QR Code)
 */
$pageTitle = 'Inscription au transport scolaire';
require_once __DIR__ . '/includes/header_public.php';

$classes = getActiveClasses();
$stops = getActiveStops();
$tariffs = getActiveTariffs();
$defaultTariff = getDefaultTariff();
$year = getActiveAcademicYear();

if (!$year) {
    echo '<div class="alert alert-danger">Aucune année scolaire active. Contactez l\'administration.</div>';
    require_once __DIR__ . '/includes/footer_public.php';
    exit;
}

$months = SCHOOL_MONTHS;
$currentMonth = (int) date('n');
// Suggérer le mois scolaire courant
$suggestedMonth = in_array($currentMonth, array_keys($months)) ? $currentMonth : 9;
?>

<div class="form-card-header-logo d-md-none">
    <?= renderSchoolLogo('medium') ?>
</div>

<div class="step-indicator">
    <?php
    $steps = ['Élève', 'Parent', 'Transport', 'Paiement', 'Confirmation'];
    foreach ($steps as $i => $label):
        $num = $i + 1;
    ?>
    <div class="step-item <?= $num === 1 ? 'active' : '' ?>" data-step="<?= $num ?>">
        <div class="step-circle"><?= $num ?></div>
        <div class="step-label"><?= $label ?></div>
    </div>
    <?php endforeach; ?>
</div>

<form id="inscriptionForm" method="POST" action="<?= BASE_URL ?>/api/submit_inscription.php" novalidate>
    <?= csrfField() ?>
    <input type="hidden" name="academic_year_id" value="<?= (int)$year['id'] ?>">

    <!-- Étape 1: Élève -->
    <div class="form-step active" data-step="1">
        <div class="form-card">
            <h5 class="mb-3"><i class="bi bi-person"></i> Informations de l'élève</h5>
            <div class="mb-3">
                <label class="form-label">Nom complet de l'élève *</label>
                <input type="text" name="nom_complet" class="form-control" required
                       placeholder="Ex: Jean Mukendi" autocomplete="name">
            </div>
            <div class="mb-3">
                <label class="form-label">Classe de l'élève *</label>
                <select name="classe_id" class="form-select" required>
                    <option value="">-- Choisir la classe --</option>
                    <?php foreach (getClassesForSelect() as $c): ?>
                    <option value="<?= $c['id'] ?>"><?= e(formatClassWithSection($c)) ?></option>
                    <?php endforeach; ?>
                </select>
                <div class="form-text">Liste du plus petit au plus grand : Maternelle → Primaire → Secondaire → Options.</div>
            </div>
            <button type="button" class="btn btn-primary btn-step w-100 next-step">Suivant <i class="bi bi-arrow-right"></i></button>
        </div>
    </div>

    <!-- Étape 2: Parent -->
    <div class="form-step" data-step="2">
        <div class="form-card">
            <h5 class="mb-3"><i class="bi bi-people"></i> Informations du parent / tuteur</h5>
            <div class="mb-3">
                <label class="form-label">Nom du parent / tuteur</label>
                <input type="text" name="parent_nom" class="form-control" placeholder="Ex: Pierre Mukendi">
            </div>
            <div class="mb-3">
                <label class="form-label">Téléphone principal *</label>
                <input type="tel" name="telephone_parent" class="form-control" required
                       placeholder="Ex: +243 81 545 4401">
            </div>
            <div class="mb-3">
                <label class="form-label">Téléphone secondaire</label>
                <input type="tel" name="telephone_parent2" class="form-control"
                       placeholder="Optionnel">
            </div>
            <div class="d-flex gap-2">
                <button type="button" class="btn btn-outline-secondary btn-step flex-fill prev-step"><i class="bi bi-arrow-left"></i> Retour</button>
                <button type="button" class="btn btn-primary btn-step flex-fill next-step">Suivant <i class="bi bi-arrow-right"></i></button>
            </div>
        </div>
    </div>

    <!-- Étape 3: Transport -->
    <div class="form-step" data-step="3">
        <div class="form-card">
            <h5 class="mb-3"><i class="bi bi-bus-front"></i> Informations du transport</h5>
            <div class="mb-3">
                <label class="form-label">Adresse / lieu de prise en charge *</label>
                <textarea name="adresse" class="form-control" rows="2" required
                          placeholder="Ex: Golf Maisha, près de la pharmacie..."></textarea>
            </div>
            <div class="mb-3">
                <label class="form-label">Arrêt de bus</label>
                <select name="arret_id" class="form-select">
                    <option value="">-- Sélectionner un arrêt --</option>
                    <?php foreach ($stops as $s): ?>
                    <option value="<?= $s['id'] ?>"><?= e($s['nom']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label">Précision sur l'arrêt</label>
                <input type="text" name="arret_precision" class="form-control"
                       placeholder='Ex: "Devant la pharmacie..."'>
            </div>
            <div class="d-flex gap-2">
                <button type="button" class="btn btn-outline-secondary btn-step flex-fill prev-step"><i class="bi bi-arrow-left"></i> Retour</button>
                <button type="button" class="btn btn-primary btn-step flex-fill next-step">Suivant <i class="bi bi-arrow-right"></i></button>
            </div>
        </div>
    </div>

    <!-- Étape 4: Paiement -->
    <div class="form-step" data-step="4">
        <div class="form-card">
            <h5 class="mb-3"><i class="bi bi-cash-coin"></i> Frais de transport</h5>
            <div class="mb-3">
                <label class="form-label">Mois concerné *</label>
                <select name="mois" class="form-select" required>
                    <?php foreach ($months as $num => $info): ?>
                    <option value="<?= $num ?>" <?= $num === $suggestedMonth ? 'selected' : '' ?>>
                        <?= e($info['label']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label">Tarif / Frais du mois</label>
                <select name="tariff_id" class="form-select" id="tariffSelect">
                    <?php foreach ($tariffs as $t): ?>
                    <option value="<?= $t['id'] ?>" data-montant="<?= $t['montant'] ?>"
                            <?= $t['is_default'] ? 'selected' : '' ?>>
                        <?= e($t['nom']) ?> — <?= number_format($t['montant'], 0) ?> USD
                    </option>
                    <?php endforeach; ?>
                </select>
                <input type="hidden" name="montant_du" id="montantDu" value="<?= $defaultTariff ? $defaultTariff['montant'] : 50 ?>">
            </div>
            <div class="mb-3">
                <label class="form-label">Le frais de transport a-t-il déjà été payé ? *</label>
                <div class="d-flex flex-column gap-2">
                    <label class="btn btn-outline-success text-start payment-choice">
                        <input type="radio" name="statut_paiement" value="paye" class="me-2"> Oui, entièrement payé
                    </label>
                    <label class="btn btn-outline-warning text-start payment-choice">
                        <input type="radio" name="statut_paiement" value="partiel" class="me-2"> Partiellement payé
                    </label>
                    <label class="btn btn-outline-danger text-start payment-choice">
                        <input type="radio" name="statut_paiement" value="impaye" class="me-2" checked> Non, pas encore payé
                    </label>
                </div>
            </div>
            <div class="mb-3" id="montantPayeGroup" style="display:none;">
                <label class="form-label">Montant payé (USD)</label>
                <input type="number" name="montant_paye" class="form-control" min="0" step="0.01" value="0">
            </div>
            <div class="alert alert-info" id="resteInfo" style="display:none;">
                Reste à payer : <strong id="resteAmount">0</strong> USD
            </div>
            <div class="d-flex gap-2">
                <button type="button" class="btn btn-outline-secondary btn-step flex-fill prev-step"><i class="bi bi-arrow-left"></i> Retour</button>
                <button type="submit" class="btn btn-success btn-step flex-fill" id="submitBtn">
                    <i class="bi bi-check-circle"></i> Valider l'inscription
                </button>
            </div>
        </div>
    </div>
</form>

<div id="loadingOverlay" class="d-none position-fixed top-0 start-0 w-100 h-100 bg-dark bg-opacity-50 d-flex align-items-center justify-content-center" style="z-index:9999;">
    <div class="spinner-border text-light" style="width:3rem;height:3rem;"></div>
</div>

<?php require_once __DIR__ . '/includes/footer_public.php'; ?>
