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
$pageTitle = 'Reçu ' . $vente['numero'];
require_once __DIR__ . '/includes/header.php';
?>

<div class="receipt-actions mb-3 d-print-none">
    <a href="ventes.php" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i> Retour aux ventes</a>
    <button type="button" class="btn btn-primary" onclick="window.print()"><i class="bi bi-printer me-1"></i> Imprimer</button>
</div>

<div class="receipt-paper card mx-auto">
    <div class="receipt-header text-center">
        <img src="<?= e(appLogo()) ?>" alt="<?= e(appName()) ?>" class="receipt-logo">
        <h2 class="receipt-title mb-0">PHARMACIE <?= e(strtoupper(appName())) ?></h2>
        <p class="receipt-tagline mb-1"><?= e(appTagline()) ?></p>
        <p class="receipt-url mb-0">mapharmaciepk.xo.je</p>
    </div>

    <hr class="receipt-divider">

    <div class="receipt-meta">
        <div><strong>N° reçu :</strong> <?= e($vente['numero']) ?></div>
        <div><strong>Date :</strong> <?= date('d/m/Y H:i', strtotime($vente['date_vente'])) ?></div>
        <div><strong>Vendeur :</strong> <?= e($vente['vendeur']) ?></div>
        <?php if ($vente['client_nom']): ?>
        <div><strong>Client :</strong> <?= e($vente['client_nom']) ?></div>
        <?php endif; ?>
    </div>

    <table class="table table-sm receipt-table mt-3 mb-3">
        <thead>
            <tr>
                <th>Produit</th>
                <th class="text-center">Qté</th>
                <th class="text-end">P.U.</th>
                <th class="text-end">Total</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($details as $l): ?>
        <tr>
            <td>
                <small class="text-muted"><?= e($l['code']) ?></small><br>
                <?= e($l['nom']) ?>
            </td>
            <td class="text-center"><?= $l['quantite'] ?></td>
            <td class="text-end"><?= formatMoney((float) $l['prix_unitaire'], $devise) ?></td>
            <td class="text-end"><?= formatMoney((float) $l['sous_total'], $devise) ?></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
        <tfoot>
            <tr class="receipt-total">
                <td colspan="3" class="text-end"><strong>TOTAL (<?= e($devise) ?>)</strong></td>
                <td class="text-end"><strong><?= formatMoney((float) $vente['montant_total'], $devise) ?></strong></td>
            </tr>
            <tr>
                <td colspan="4" class="text-end text-muted small">
                    Équivalent : <?= formatDualMoney((float) $vente['montant_total'], $devise) ?>
                </td>
            </tr>
        </tfoot>
    </table>

    <?php if ($vente['notes']): ?>
    <p class="small mb-2"><strong>Notes :</strong> <?= e($vente['notes']) ?></p>
    <?php endif; ?>

    <hr class="receipt-divider">

    <p class="receipt-footer text-center mb-0">
        Merci pour votre confiance !<br>
        <small>Conservez ce reçu.</small>
    </p>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
