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
    win.document.write('<html><head><title>QR Code - Transport Scolaire</title></head><body style="text-align:center;font-family:sans-serif;padding:40px;">');
    <?php if (schoolLogoExists()): ?>
    win.document.write('<img src="<?= e(getSchoolLogoUrl()) ?>" alt="Logo" style="height:90px;margin-bottom:12px;">');
    <?php endif; ?>
    win.document.write('<h2><?= e(getSetting('school_name')) ?></h2>');
    win.document.write('<p>Inscription au transport scolaire</p>');
    win.document.write('<img src="<?= e($qrApiUrl) ?>" style="width:300px;">');
    win.document.write('<p><?= e($inscriptionUrl) ?></p>');
    win.document.write('</body></html>');
    win.document.close();
    win.print();
}
</script>

<?php require_once __DIR__ . '/../includes/footer_admin.php'; ?>
