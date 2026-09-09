<?php
$pageTitle = 'QR Code';
require_once __DIR__ . '/../includes/header_admin.php';

$inscriptionUrl = BASE_URL . '/inscription.php';
$qrApiUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=' . urlencode($inscriptionUrl);
?>

<h2 class="mb-4"><i class="bi bi-qr-code"></i> QR Code d'inscription</h2>

<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card text-center">
            <div class="card-body py-4">
                <div class="qr-poster-header mb-3">
                    <?php if (schoolLogoExists()): ?>
                    <div class="mb-2"><?= renderSchoolLogo('large') ?></div>
                    <?php endif; ?>
                    <h5 class="mb-0 fw-bold"><?= e(getSetting('school_foundation', 'FONDATION EBEN EZER – ORA S.A.R.I')) ?></h5>
                    <p class="mb-0 small text-muted"><?= e(getSetting('school_project', 'PROJET EDUCATIF')) ?></p>
                    <h4 class="mb-1 fw-bold"><?= e(getSetting('school_name', 'C.S LES SUPER GENIES')) ?></h4>
                    <p class="mb-0">🚌 Inscription au transport scolaire</p>
                </div>
                <p class="text-muted">Scannez ce QR Code pour accéder au formulaire d'inscription</p>
                <div class="my-4" id="qrContainer">
                    <img src="<?= e($qrApiUrl) ?>" alt="QR Code Inscription" id="qrImage" class="img-fluid border p-2" style="max-width:300px;">
                </div>
                <p class="mb-1"><strong>URL :</strong></p>
                <p><a href="<?= e($inscriptionUrl) ?>" target="_blank"><?= e($inscriptionUrl) ?></a></p>
                <div class="d-flex gap-2 justify-content-center mt-4">
                    <a href="<?= e($qrApiUrl) ?>" download="qr-inscription-transport.png" class="btn btn-primary">
                        <i class="bi bi-download"></i> Télécharger
                    </a>
                    <button onclick="printQR()" class="btn btn-secondary">
                        <i class="bi bi-printer"></i> Imprimer
                    </button>
                    <a href="<?= e($inscriptionUrl) ?>" target="_blank" class="btn btn-outline-primary">
                        <i class="bi bi-box-arrow-up-right"></i> Ouvrir
                    </a>
                </div>
            </div>
        </div>

        <div class="card mt-4">
            <div class="card-header">Instructions</div>
            <div class="card-body">
                <ul class="mb-0">
                    <li>Imprimez ce QR Code et affichez-le à l'école, dans les salles de classe</li>
                    <li>Partagez-le dans les groupes WhatsApp des parents</li>
                    <li>Ajoutez-le sur les affiches et reçus</li>
                    <li>Les parents scannent → remplissent le formulaire → données enregistrées automatiquement</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<script>
function printQR() {
    const win = window.open('', '_blank');
    win.document.write('<html><head><title>QR Code - Transport Scolaire</title><style>body{font-family:sans-serif;text-align:center;padding:40px 20px;margin:0;} .logo{height:90px;margin-bottom:12px;} h1{font-size:1.2rem;margin:0.25rem 0;} h2{font-size:1.5rem;margin:0.5rem 0;} p{margin:0.25rem 0;color:#444;} .qr{margin:24px 0;} .url{font-size:0.85rem;color:#666;word-break:break-all;}</style></head><body>');
    <?php if (schoolLogoExists()): ?>
    win.document.write('<img src="<?= e(getSchoolLogoUrl()) ?>" alt="Logo" class="logo">');
    <?php endif; ?>
    win.document.write('<h1><?= e(getSetting('school_foundation', 'FONDATION EBEN EZER – ORA S.A.R.I')) ?></h1>');
    win.document.write('<p><?= e(getSetting('school_project', 'PROJET EDUCATIF')) ?></p>');
    win.document.write('<h2><?= e(getSetting('school_name', 'C.S LES SUPER GENIES')) ?></h2>');
    win.document.write('<p><strong>🚌 Inscription au transport scolaire</strong></p>');
    win.document.write('<div class="qr"><img src="<?= e($qrApiUrl) ?>" style="width:280px;height:280px;"></div>');
    win.document.write('<p class="url"><?= e($inscriptionUrl) ?></p>');
    win.document.write('</body></html>');
    win.document.close();
    win.print();
}
</script>

<?php require_once __DIR__ . '/../includes/footer_admin.php'; ?>
