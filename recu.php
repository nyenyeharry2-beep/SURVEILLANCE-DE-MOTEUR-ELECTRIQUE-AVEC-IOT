<?php
require_once __DIR__ . '/includes/auth.php';
requireLogin();

$db = getDB();
$id = (int) ($_GET['id'] ?? 0);

if ($id <= 0) {
    flash('danger', 'Reçu introuvable.');
    redirect('ventes.php');
}

$stmt = $db->prepare('
    SELECT v.*, u.nom AS vendeur
    FROM ventes v
    JOIN utilisateurs u ON u.id = v.utilisateur_id
    WHERE v.id = ?
');
$stmt->execute([$id]);
$vente = $stmt->fetch();

if (!$vente) {
    flash('danger', 'Reçu introuvable.');
    redirect('ventes.php');
}

$lignes = $db->prepare('
    SELECT vl.*, m.code, m.nom
    FROM vente_lignes vl
    JOIN medicaments m ON m.id = vl.medicament_id
    WHERE vl.vente_id = ?
');
$lignes->execute([$id]);
$details = $lignes->fetchAll();

$devise = normalizeDevise($vente['devise'] ?? 'CDF');
$montant = (float) $vente['montant_total'];
$client = trim($vente['client_nom'] ?? '') ?: 'Client comptant';
$produits = array_map(static fn ($l) => e($l['nom']) . ' x' . (int) $l['quantite'], $details);
$produitsTexte = $produits ? implode(', ', $produits) : 'médicaments';

$pageTitle = 'Reçu ' . $vente['numero'];
require_once __DIR__ . '/includes/header.php';
?>

<div class="receipt-actions mb-3 d-print-none">
    <a href="ventes.php" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i> Retour aux ventes</a>
    <button type="button" class="btn btn-primary" onclick="window.print()"><i class="bi bi-printer me-1"></i> Imprimer</button>
</div>

<div class="receipt-paper mx-auto">
    <div class="receipt-header text-center">
        <img src="<?= e(appLogo()) ?>" alt="<?= e(appName()) ?>" class="receipt-logo" width="56" height="56">
        <div class="receipt-brand">Pharmacie <?= e(appName()) ?></div>
        <div class="receipt-tagline"><?= e(appTagline()) ?></div>
        <?php if (appAddress()): ?>
        <div class="receipt-contact"><?= e(appAddress()) ?></div>
        <?php endif; ?>
        <?php if (appPhone()): ?>
        <div class="receipt-contact"><?= e(appPhone()) ?></div>
        <?php endif; ?>
        <?php if (appUrl()): ?>
        <div class="receipt-contact"><?= e(preg_replace('#^https?://#', '', appUrl())) ?></div>
        <?php endif; ?>
    </div>

    <div class="receipt-title-block text-center">
        <div class="receipt-doc-title">RECU DE VENTE</div>
        <div class="receipt-doc-subtitle">CAISSE</div>
    </div>

    <div class="receipt-separator">----------------------------------------</div>

    <div class="receipt-meta">
        <div><span class="receipt-label">REFERENCE</span> : <?= e($vente['numero']) ?></div>
        <div><span class="receipt-label">DATE</span> : <?= date('d/m/Y H:i', strtotime($vente['date_vente'])) ?></div>
        <div><span class="receipt-label">VENDEUR</span> : <?= e($vente['vendeur']) ?></div>
        <div><span class="receipt-label">CLIENT</span> : <?= e($client) ?></div>
    </div>

    <div class="receipt-separator">----------------------------------------</div>

    <p class="receipt-body">
        Nous avons reçu la somme de <strong><?= formatMoneyPlain($montant, $devise) ?></strong>
        (<?= e(montantEnLettres($montant, $devise)) ?>), comptant pour l'achat de
        <?= $produitsTexte ?> pour <?= e($client) ?>.
    </p>

    <div class="receipt-items">
        <?php foreach ($details as $l): ?>
        <div class="receipt-item">- <?= e($l['nom']) ?> x<?= (int) $l['quantite'] ?></div>
        <?php endforeach; ?>
    </div>

    <div class="receipt-separator">----------------------------------------</div>

    <div class="receipt-total-line">
        <strong>MONTANT : <?= formatMoneyPlain($montant, $devise) ?></strong>
    </div>
    <div class="receipt-equiv">
        Équivalent : <?= formatDualMoney($montant, $devise) ?>
    </div>

    <?php if ($vente['notes']): ?>
    <div class="receipt-note"><strong>Note :</strong> <?= e($vente['notes']) ?></div>
    <?php endif; ?>

    <div class="receipt-separator">----------------------------------------</div>

    <p class="receipt-footer text-center">
        En cas de réclamation, veuillez présenter ce reçu.<br>
        Merci pour votre confiance !
    </p>
    <p class="receipt-credit text-center">
        Imprimé par <?= e(appName()) ?> — <?= e(appUrl() ?: 'mapharmaciepk.xo.je') ?>
    </p>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
