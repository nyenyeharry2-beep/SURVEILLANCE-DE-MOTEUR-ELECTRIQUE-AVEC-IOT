<?php
declare(strict_types=1);

require_once __DIR__ . '/_init.php';
adminRequireLogin();

$config = getAppConfig();
$pdo = getPdo();
ensureParentMessagesTable($pdo);

$tab = $_GET['tab'] ?? 'imports';
$uploadMessage = null;
$uploadError = null;
$actionMessage = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_FILES['pdf'])) {
        require_once __DIR__ . '/../parser/PdfParser.php';
        if ($_FILES['pdf']['error'] !== UPLOAD_ERR_OK) {
            $uploadError = 'Erreur upload PDF (code ' . $_FILES['pdf']['error'] . ')';
        } else {
            $type = $_POST['type'] ?? 'auto';
            $uploadDir = __DIR__ . '/../uploads';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            $filename = date('Ymd_His') . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '_', $_FILES['pdf']['name']);
            $destPath = $uploadDir . '/' . $filename;
            if (!move_uploaded_file($_FILES['pdf']['tmp_name'], $destPath)) {
                $uploadError = 'Impossible d\'enregistrer le PDF';
            } else {
                try {
                    $text = PdfParser::extractText($destPath);
                    if (trim($text) === '') {
                        $uploadError = 'PDF illisible';
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
                            'Import OK : %d ligne(s), %d erreur(s)',
                            $result['processed'],
                            $result['errors']
                        );
                    }
                } catch (Throwable $e) {
                    $uploadError = 'Erreur : ' . $e->getMessage();
                }
            }
        }
        $tab = 'imports';
    }

    if (isset($_POST['msg_action'])) {
        $id = (int) ($_POST['msg_id'] ?? 0);
        $statut = $_POST['statut'] ?? '';
        if ($id > 0 && in_array($statut, ['nouveau', 'en_cours', 'traite'], true)) {
            $stmt = $pdo->prepare('UPDATE parent_messages SET statut = ? WHERE id = ?');
            $stmt->execute([$statut, $id]);
            $actionMessage = 'Message mis à jour';
        }
        $tab = 'messages';
    }
}

$stats = [
    'students' => (int) $pdo->query('SELECT COUNT(*) FROM students')->fetchColumn(),
    'fees' => (int) $pdo->query('SELECT COUNT(*) FROM student_fees')->fetchColumn(),
    'impayes' => (int) $pdo->query('SELECT COUNT(*) FROM student_fees WHERE statut IN ("impaye", "partiel")')->fetchColumn(),
];

$filter = $_GET['filter'] ?? 'all';
$sql = 'SELECT * FROM parent_messages';
$params = [];
if ($filter !== 'all') {
    $sql .= ' WHERE statut = ?';
    $params[] = $filter;
}
$sql .= ' ORDER BY FIELD(statut, "nouveau", "en_cours", "traite"), created_at DESC LIMIT 50';
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$messages = $stmt->fetchAll();

$counts = $pdo->query('SELECT statut, COUNT(*) as n FROM parent_messages GROUP BY statut')->fetchAll(PDO::FETCH_KEY_PAIR);

$motifLabels = [
    'paiement_non_enregistre' => 'Paiement non enregistré',
    'montant_incorrect' => 'Montant incorrect',
    'double_paiement' => 'Double paiement',
    'probleme_inscription' => 'Problème inscription',
    'autre' => 'Autre',
];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
    <title>Admin Super Genies</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: system-ui, sans-serif; margin: 0; padding-bottom: 70px; background: #f4f6f9; }
        .top { background: #1B3A6B; color: #fff; padding: 14px 16px; display: flex; justify-content: space-between; align-items: center; }
        .top a { color: #fff; text-decoration: none; font-size: 14px; }
        .wrap { padding: 12px; max-width: 720px; margin: 0 auto; }
        .card { background: #fff; border-radius: 12px; padding: 14px; margin-bottom: 12px; box-shadow: 0 1px 6px rgba(0,0,0,.08); }
        h2 { margin: 0 0 8px; color: #1B3A6B; font-size: 1.1rem; }
        .stats { display: grid; grid-template-columns: repeat(3,1fr); gap: 8px; }
        .stat { background: #eef2f8; border-radius: 8px; padding: 10px; text-align: center; }
        .stat b { display: block; font-size: 1.3rem; color: #1B3A6B; }
        .btn { background: #C62828; color: #fff; border: 0; border-radius: 8px; padding: 12px 16px; font-weight: 700; width: 100%; font-size: 1rem; }
        .btn-sm { width: auto; padding: 8px 12px; font-size: 0.85rem; }
        .btn-green { background: #2e7d32; }
        .btn-outline { background: #fff; color: #1B3A6B; border: 1px solid #1B3A6B; }
        .ok { background: #e8f5e9; color: #1b5e20; padding: 10px; border-radius: 8px; margin-bottom: 10px; }
        .err { background: #ffebee; color: #b71c1c; padding: 10px; border-radius: 8px; margin-bottom: 10px; }
        input[type=file], select { width: 100%; margin: 8px 0; padding: 10px; }
        .tabs { position: fixed; bottom: 0; left: 0; right: 0; background: #fff; border-top: 1px solid #ddd; display: flex; }
        .tabs a { flex: 1; text-align: center; padding: 12px 8px; text-decoration: none; color: #666; font-size: 13px; }
        .tabs a.active { color: #1B3A6B; font-weight: 700; background: #eef2f8; }
        .filters { display: flex; gap: 6px; flex-wrap: wrap; margin-bottom: 10px; }
        .filters a { padding: 6px 10px; border-radius: 20px; background: #eee; text-decoration: none; color: #333; font-size: 12px; }
        .filters a.active { background: #1B3A6B; color: #fff; }
        .msg { border-left: 4px solid #C62828; padding: 10px; margin-bottom: 10px; background: #fafafa; border-radius: 0 8px 8px 0; }
        .msg.en_cours { border-color: #F57C00; }
        .msg.traite { border-color: #2E7D32; }
        .msg-actions { display: flex; gap: 6px; margin-top: 8px; flex-wrap: wrap; }
        .hidden { display: none; }
    </style>
</head>
<body>
<div class="top">
    <strong>Super Genies Admin</strong>
    <a href="logout.php">Déconnexion</a>
</div>

<div class="wrap">
    <div id="tab-imports" class="<?= $tab === 'imports' ? '' : 'hidden' ?>">
        <div class="card">
            <h2>Statistiques</h2>
            <div class="stats">
                <div class="stat"><b><?= $stats['students'] ?></b>Élèves</div>
                <div class="stat"><b><?= $stats['fees'] ?></b>Frais</div>
                <div class="stat"><b><?= $stats['impayes'] ?></b>Impayés</div>
            </div>
        </div>
        <div class="card">
            <h2>Import PDF</h2>
            <?php if ($uploadMessage): ?><div class="ok"><?= htmlspecialchars($uploadMessage) ?></div><?php endif; ?>
            <?php if ($uploadError): ?><div class="err"><?= htmlspecialchars($uploadError) ?></div><?php endif; ?>
            <form method="post" enctype="multipart/form-data" action="portail.php?tab=imports">
                <label>Type</label>
                <select name="type">
                    <option value="auto">Auto</option>
                    <option value="inscriptions">Inscriptions</option>
                    <option value="paiements">Paiements</option>
                </select>
                <label>Fichier PDF</label>
                <input type="file" name="pdf" accept="application/pdf,application/octet-stream" required>
                <button type="submit" class="btn">Publier et importer</button>
            </form>
        </div>
    </div>

    <div id="tab-messages" class="<?= $tab === 'messages' ? '' : 'hidden' ?>">
        <div class="card">
            <h2>Messagerie parents</h2>
            <?php if ($actionMessage): ?><div class="ok"><?= htmlspecialchars($actionMessage) ?></div><?php endif; ?>
            <div class="filters">
                <a href="?tab=messages&filter=all" class="<?= $filter === 'all' ? 'active' : '' ?>">Tous (<?= array_sum($counts) ?>)</a>
                <a href="?tab=messages&filter=nouveau" class="<?= $filter === 'nouveau' ? 'active' : '' ?>">Nouveaux (<?= (int)($counts['nouveau'] ?? 0) ?>)</a>
                <a href="?tab=messages&filter=en_cours" class="<?= $filter === 'en_cours' ? 'active' : '' ?>">En cours (<?= (int)($counts['en_cours'] ?? 0) ?>)</a>
                <a href="?tab=messages&filter=traite" class="<?= $filter === 'traite' ? 'active' : '' ?>">Traités (<?= (int)($counts['traite'] ?? 0) ?>)</a>
            </div>
            <?php if (empty($messages)): ?>
                <p style="color:#888;text-align:center;padding:20px;">Aucun message pour le moment</p>
            <?php else: ?>
                <?php foreach ($messages as $m): ?>
                    <div class="msg <?= htmlspecialchars($m['statut']) ?>">
                        <strong><?= htmlspecialchars($m['nom_parent']) ?></strong> · <?= htmlspecialchars($m['telephone_parent']) ?><br>
                        <small>Élève : <?= htmlspecialchars(trim(($m['nom_eleve'] ?? '') . ' ' . ($m['prenom_eleve'] ?? ''))) ?> — <?= htmlspecialchars($m['matricule']) ?></small><br>
                        <strong><?= htmlspecialchars($motifLabels[$m['motif']] ?? $m['motif']) ?></strong><br>
                        <?= nl2br(htmlspecialchars($m['message'])) ?>
                        <div class="msg-actions">
                            <?php if ($m['statut'] === 'nouveau'): ?>
                                <form method="post" action="portail.php?tab=messages" style="display:inline">
                                    <input type="hidden" name="msg_action" value="1">
                                    <input type="hidden" name="msg_id" value="<?= (int)$m['id'] ?>">
                                    <input type="hidden" name="statut" value="en_cours">
                                    <button class="btn btn-sm btn-outline" type="submit">Prendre en charge</button>
                                </form>
                            <?php endif; ?>
                            <?php if ($m['statut'] !== 'traite'): ?>
                                <form method="post" action="portail.php?tab=messages" style="display:inline">
                                    <input type="hidden" name="msg_action" value="1">
                                    <input type="hidden" name="msg_id" value="<?= (int)$m['id'] ?>">
                                    <input type="hidden" name="statut" value="traite">
                                    <button class="btn btn-sm btn-green" type="submit">Marquer traité</button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="tabs">
    <a href="portail.php?tab=imports" class="<?= $tab === 'imports' ? 'active' : '' ?>">📤 Imports</a>
    <a href="portail.php?tab=messages" class="<?= $tab === 'messages' ? 'active' : '' ?>">✉️ Messages</a>
</div>
</body>
</html>
