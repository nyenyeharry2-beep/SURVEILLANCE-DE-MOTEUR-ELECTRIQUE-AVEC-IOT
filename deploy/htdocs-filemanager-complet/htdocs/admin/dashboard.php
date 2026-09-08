<?php
declare(strict_types=1);

require_once __DIR__ . '/_init.php';
adminRequireLogin();

$config = getAppConfig();
$pdo = getPdo();

$stats = [
    'students' => (int) $pdo->query('SELECT COUNT(*) FROM students')->fetchColumn(),
    'fees' => (int) $pdo->query('SELECT COUNT(*) FROM student_fees')->fetchColumn(),
    'impayes' => (int) $pdo->query('SELECT COUNT(*) FROM student_fees WHERE statut IN ("impaye", "partiel")')->fetchColumn(),
];

$messagesCount = 0;
try {
    $messagesCount = (int) $pdo->query('SELECT COUNT(*) FROM parent_messages WHERE statut = "nouveau"')->fetchColumn();
} catch (Throwable $e) {
    // table peut manquer si migration non faite
}

$uploadMessage = null;
$uploadError = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['pdf'])) {
    require_once __DIR__ . '/../parser/PdfParser.php';

    if ($_FILES['pdf']['error'] !== UPLOAD_ERR_OK) {
        $uploadError = 'Fichier PDF requis ou erreur upload (code ' . $_FILES['pdf']['error'] . ')';
    } else {
        $type = $_POST['type'] ?? 'auto';
        $uploadDir = __DIR__ . '/../uploads';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        $filename = date('Ymd_His') . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '_', $_FILES['pdf']['name']);
        $destPath = $uploadDir . '/' . $filename;

        if (!move_uploaded_file($_FILES['pdf']['tmp_name'], $destPath)) {
            $uploadError = 'Impossible d\'enregistrer le PDF dans uploads/';
        } else {
            try {
                $text = PdfParser::extractText($destPath);
                if (trim($text) === '') {
                    $uploadError = 'Texte PDF illisible';
                } else {
                    if ($type === 'auto') {
                        $type = PdfParser::detectImportType($text);
                    }
                    if ($type === 'inscriptions') {
                        $rows = PdfParser::parseInscriptions($text);
                        $result = ImportService::importInscriptions($pdo, $rows, $filename);
                    } else {
                        $rows = PdfParser::parsePaiements($text);
                        $result = ImportService::importPaiements($pdo, $rows, $filename);
                    }
                    $uploadMessage = sprintf(
                        'Import %s : %d ligne(s) OK, %d erreur(s)',
                        $type,
                        $result['processed'],
                        $result['errors']
                    );
                    $stats['students'] = (int) $pdo->query('SELECT COUNT(*) FROM students')->fetchColumn();
                }
            } catch (Throwable $e) {
                $uploadError = 'Erreur import : ' . $e->getMessage();
            }
        }
    }
}

adminLayoutStart('Tableau de bord');
?>
<div class="topbar">
    <span>Admin — <?= htmlspecialchars($config['school_name']) ?></span>
    <a href="logout.php">Déconnexion</a>
</div>
<div class="wrap">
    <div class="alert alert-success">
        Connexion web réussie. Session valide jusqu'à <?= htmlspecialchars($_SESSION['admin_expires']) ?>.
        <div class="detail">Token API (pour tests) : <code><?= htmlspecialchars(substr($_SESSION['admin_token'], 0, 16)) ?>…</code></div>
    </div>

    <div class="card">
        <h1>Statistiques</h1>
        <div class="stats">
            <div class="stat"><b><?= $stats['students'] ?></b>Élèves</div>
            <div class="stat"><b><?= $stats['fees'] ?></b>Frais</div>
            <div class="stat"><b><?= $stats['impayes'] ?></b>Impayés</div>
        </div>
        <?php if ($messagesCount > 0): ?>
            <p class="warn" style="margin-top:1rem;"><?= $messagesCount ?> message(s) parent en attente</p>
        <?php endif; ?>
    </div>

    <div class="card">
        <h1>Import PDF</h1>
        <p class="sub">Même fonction que l'application Android — testez l'import ici d'abord.</p>
        <?php if ($uploadMessage): ?>
            <div class="alert alert-success"><?= htmlspecialchars($uploadMessage) ?></div>
        <?php endif; ?>
        <?php if ($uploadError): ?>
            <div class="alert alert-error"><?= htmlspecialchars($uploadError) ?></div>
        <?php endif; ?>
        <form method="post" enctype="multipart/form-data">
            <label for="type">Type d'import</label>
            <select name="type" id="type" style="margin-bottom:1rem;">
                <option value="auto">Auto-détection</option>
                <option value="inscriptions">Inscriptions</option>
                <option value="paiements">Paiements</option>
            </select>
            <label for="pdf">Fichier PDF</label>
            <input type="file" name="pdf" id="pdf" accept="application/pdf" required style="margin-bottom:1rem;">
            <button type="submit" class="btn">Importer le PDF</button>
        </form>
    </div>

    <div class="card">
        <h1 style="font-size:1.1rem;">Prochaine étape</h1>
        <p>Si cette page web fonctionne, le serveur est correct. L'application Android devra utiliser la même URL et le même mot de passe.</p>
        <p><a href="diagnostic.php">Voir le diagnostic</a> · <a href="../api/admin/login.php" target="_blank">Tester login API (JSON)</a></p>
    </div>
</div>
<?php adminLayoutEnd(); ?>
