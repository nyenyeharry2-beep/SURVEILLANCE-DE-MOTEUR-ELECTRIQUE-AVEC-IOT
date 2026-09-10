<?php
/**
 * Page de confirmation après inscription
 */
$pageTitle = 'Inscription confirmée';
require_once __DIR__ . '/includes/header_public.php';

$dossier = $_GET['dossier'] ?? '';
$nom = $_GET['nom'] ?? '';
$mois = $_GET['mois'] ?? '';
$recu = $_GET['recu'] ?? '';

if (!$dossier) {
    redirect(BASE_URL . '/inscription.php');
}

$moisLabel = SCHOOL_MONTHS[(int)$mois]['label'] ?? $mois;
?>

<div class="form-card text-center py-4">
    <div class="confirmation-icon mb-3">✅</div>
    <h4 class="text-success mb-3">Inscription enregistrée avec succès</h4>

    <?php if ($nom): ?>
    <p class="mb-1">Élève : <strong><?= e($nom) ?></strong></p>
    <?php endif; ?>

    <p class="mb-1">Numéro de dossier :</p>
    <p class="dossier-number mb-3"><?= e($dossier) ?></p>

    <?php if ($moisLabel): ?>
    <p class="mb-1">Mois : <strong><?= e($moisLabel) ?></strong></p>
    <?php endif; ?>

    <?php if ($recu): ?>
    <p class="mb-3">N° Reçu : <strong><?= e($recu) ?></strong></p>
    <?php endif; ?>

    <div class="alert alert-warning mx-auto" style="max-width:400px;">
        <i class="bi bi-info-circle"></i> Merci de conserver ce numéro de dossier.
    </div>

    <p class="text-muted small">
        Vos informations seront vérifiées par l'administration.<br>
        Le bus passera à l'adresse indiquée après validation.
    </p>

    <a href="<?= BASE_URL ?>/inscription.php" class="btn btn-primary btn-step mt-3">
        <i class="bi bi-plus-circle"></i> Nouvelle inscription
    </a>
</div>

<?php require_once __DIR__ . '/includes/footer_public.php'; ?>
