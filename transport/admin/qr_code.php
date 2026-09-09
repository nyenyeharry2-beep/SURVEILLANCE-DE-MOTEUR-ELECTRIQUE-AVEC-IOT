<?php
$pageTitle = 'QR Code';
require_once __DIR__ . '/../includes/header_admin.php';

$inscriptionUrl = BASE_URL . '/inscription.php';
$qrApiUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=' . urlencode($inscriptionUrl);
$posterPreviewUrl = BASE_URL . '/admin/download_qr.php?preview=1';
$posterDownloadUrl = BASE_URL . '/admin/download_qr.php';
?>

<h2 class="mb-4"><i class="bi bi-qr-code"></i> QR Code d'inscription</h2>

<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card text-center">
            <div class="card-body py-4">
                <p class="text-muted mb-3">Logo + QR Code sur la même affiche (comme sur l'image téléchargée)</p>

                <div class="qr-poster mx-auto mb-3" id="qrPoster">
                    <img src="<?= e($posterPreviewUrl) ?>" alt="Affiche QR Code avec logo" class="img-fluid qr-poster-image" id="qrPosterImage">
                </div>

                <p class="text-muted small">Scannez le QR Code pour accéder au formulaire d'inscription</p>
                <p class="mb-1"><strong>URL :</strong></p>
                <p><a href="<?= e($inscriptionUrl) ?>" target="_blank"><?= e($inscriptionUrl) ?></a></p>

                <div class="d-flex gap-2 justify-content-center mt-4 flex-wrap">
                    <a href="<?= e($posterDownloadUrl) ?>" class="btn btn-primary" id="btnDownloadQr">
                        <i class="bi bi-download"></i> Télécharger l'affiche (PNG)
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
                    <li>Téléchargez l'affiche : le <strong>logo est en haut</strong>, le <strong>QR Code juste en dessous</strong> sur la même image</li>
                    <li>Imprimez ou partagez l'image dans les groupes WhatsApp des parents</li>
                    <li>Affichez-la à l'école et dans les salles de classe</li>
                    <li>Les parents scannent → remplissent le formulaire → données enregistrées automatiquement</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<script>
function printQR() {
    const win = window.open('', '_blank');
    const posterUrl = <?= json_encode($posterPreviewUrl) ?>;
    win.document.write('<html><head><title>QR Code - Transport Scolaire</title><style>body{font-family:sans-serif;text-align:center;padding:20px;margin:0;} img{max-width:100%;height:auto;}</style></head><body>');
    win.document.write('<img src="' + posterUrl + '" alt="QR Code avec logo">');
    win.document.write('</body></html>');
    win.document.close();
    win.onload = function() { win.print(); };
}
</script>

<?php require_once __DIR__ . '/../includes/footer_admin.php'; ?>
