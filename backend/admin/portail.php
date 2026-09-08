<?php
declare(strict_types=1);

require_once __DIR__ . '/_init.php';
require_once __DIR__ . '/../config/fee_catalog.php';
require_once __DIR__ . '/../includes/theme.php';
adminRequireLogin();

$config = getAppConfig();
$feeKinds = getImportFeeKinds();
$sectionsWithClasses = getImportSectionsWithClasses();
$sections = array_keys($sectionsWithClasses);
$moisScolaires = getMoisScolaires();
$pdo = getPdo();
ensureParentMessagesTable($pdo);
ensureCommuniquesTable($pdo);
ensureImportLogsTable($pdo);

$tab = $_GET['tab'] ?? 'imports';
$uploadMessage = null;
$uploadError = null;
$actionMessage = null;
$communiqueMessage = null;
$communiqueError = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_FILES['pdf'])) {
        require_once __DIR__ . '/../parser/PdfParser.php';
        if ($_FILES['pdf']['error'] !== UPLOAD_ERR_OK) {
            $uploadError = 'Erreur upload PDF (code ' . $_FILES['pdf']['error'] . ')';
        } else {
            $type = $_POST['type'] ?? 'inscriptions';
            $importContext = [
                'fee_kind' => $type,
                'section' => trim($_POST['section'] ?? ''),
                'classe' => trim($_POST['classe'] ?? ''),
                'mois' => $_POST['mois'] ?? '',
            ];
            if ($type !== 'paiements' && ($importContext['section'] ?? '') === '') {
                $uploadError = 'Choisissez une section.';
            } elseif ($type !== 'paiements' && ($importContext['classe'] ?? '') === '') {
                $uploadError = 'Choisissez la classe (ex: 1ère ANNEE MATERNELLE).';
            } elseif (in_array($type, ['minerval', 'bus'], true) && ($importContext['mois'] ?? '') === '') {
                $uploadError = 'Choisissez le mois scolaire (ex: Octobre) pour minerval ou bus.';
            } else {
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
                    if ($type === 'inscriptions') {
                        $rows = PdfParser::parseInscriptions($text);
                        $result = ImportService::importInscriptions($pdo, $rows, $filename, $importContext);
                    } else {
                        $studentCount = (int) $pdo->query('SELECT COUNT(*) FROM students')->fetchColumn();
                        if ($studentCount === 0) {
                            throw new RuntimeException('Importez d\'abord le PDF Inscriptions (liste élèves + matricules).');
                        }
                        if ($type === 'paiements') {
                            $importContext['fee_kind'] = 'auto';
                        }
                        $rows = PdfParser::parsePaiements($text);
                        $result = ImportService::importPaiements($pdo, $rows, $filename, $importContext);
                    }
                    if ($result['processed'] > 0) {
                        if ($type !== 'inscriptions' && isset($result['inserted'])) {
                            $kindLabel = $feeKinds[$importContext['fee_kind']] ?? $type;
                            $uploadMessage = sprintf(
                                'Import OK : %d ligne(s) — %d nouvelle(s), %d mise(s) à jour, %d non reconnue(s)',
                                $result['processed'],
                                $result['inserted'],
                                $result['updated'],
                                $result['unmatched']
                            );
                            $uploadMessage .= ' · ' . $kindLabel;
                            if ($importContext['section'] !== '' && $importContext['section'] !== 'Toutes') {
                                $uploadMessage .= ' · ' . $importContext['section'];
                            }
                            if ($importContext['classe'] !== '') {
                                $uploadMessage .= ' · ' . $importContext['classe'];
                            }
                        } else {
                            $uploadMessage = sprintf(
                                'Import %s OK : %d élève(s) importé(s), %d erreur(s)',
                                $type,
                                $result['processed'],
                                $result['errors']
                            );
                        }
                    } else {
                        $matriculesDetectes = count(PdfParser::extractMatricules($text));
                        $uploadError = sprintf(
                            'Aucune donnée importée (%d car.). Vérifiez le type choisi, la section et que les inscriptions sont déjà importées.',
                            strlen($text)
                        );
                    }
                    }
                } catch (Throwable $e) {
                    $uploadError = 'Erreur : ' . $e->getMessage();
                }
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

    if (isset($_POST['reply_message'])) {
        $id = (int) ($_POST['msg_id'] ?? 0);
        $reponse = trim($_POST['note_admin'] ?? '');
        if ($id <= 0) {
            $actionMessage = 'Message introuvable.';
        } elseif ($reponse === '' || mb_strlen($reponse) < 5) {
            $actionMessage = 'La réponse doit contenir au moins 5 caractères.';
        } else {
            $stmt = $pdo->prepare('UPDATE parent_messages SET statut = "traite", note_admin = ?, reponse_at = NOW() WHERE id = ?');
            $stmt->execute([$reponse, $id]);
            $actionMessage = 'Réponse envoyée — visible par le parent dans Suivi.';
        }
        $tab = 'messages';
    }

    if (isset($_POST['delete_message'])) {
        $id = (int) ($_POST['msg_id'] ?? 0);
        if ($id > 0) {
            $stmt = $pdo->prepare('DELETE FROM parent_messages WHERE id = ?');
            $stmt->execute([$id]);
            $actionMessage = 'Message supprimé.';
        }
        $tab = 'messages';
    }

    if (isset($_POST['delete_import'])) {
        require_once __DIR__ . '/../parser/PdfParser.php';
        $logId = (int) ($_POST['import_id'] ?? 0);
        if ($logId > 0) {
            try {
                $result = ImportService::revertImport($pdo, $logId);
                $uploadMessage = sprintf(
                    'Import annulé (%s) — %d élève(s) retiré(s), %d frais supprimé(s), %d frais restauré(s).',
                    $result['fichier'],
                    $result['removed_students'],
                    $result['removed_fees'],
                    $result['restored_fees']
                );
            } catch (Throwable $e) {
                $uploadError = 'Annulation impossible : ' . $e->getMessage();
            }
        }
        $tab = 'imports';
    }

    if (isset($_POST['create_communique'])) {
        $titre = trim($_POST['titre'] ?? '');
        $contenu = trim($_POST['contenu'] ?? '');
        if ($titre === '' || $contenu === '') {
            $communiqueError = 'Titre et message obligatoires.';
        } elseif (mb_strlen($contenu) < 5) {
            $communiqueError = 'Le message doit contenir au moins 5 caractères.';
        } else {
            $stmt = $pdo->prepare('INSERT INTO communiques (titre, contenu) VALUES (?, ?)');
            $stmt->execute([$titre, $contenu]);
            $communiqueMessage = 'Communiqué publié — visible sur Suivi paiements.';
        }
        $tab = 'communiques';
    }

    if (isset($_POST['delete_communique'])) {
        $id = (int) ($_POST['communique_id'] ?? 0);
        if ($id > 0) {
            $stmt = $pdo->prepare('DELETE FROM communiques WHERE id = ?');
            $stmt->execute([$id]);
            $communiqueMessage = 'Communiqué supprimé.';
        }
        $tab = 'communiques';
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

$communiques = fetchCommuniques($pdo, 50);

$importLogs = $pdo->query('
    SELECT id, type_import, fichier, classe_detectee, section_detectee,
           lignes_traitees, lignes_erreur, details, imported_at
    FROM import_logs
    ORDER BY imported_at DESC
    LIMIT 40
')->fetchAll();

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
    <?php renderThemeHead('Admin Super Genies'); ?>
</head>
<body>
<?php renderBrandHeader('Administration — imports & messages', 'logout.php'); ?>

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
            <h2>Import PDF — ordre obligatoire</h2>
            <div class="warn">① Inscriptions → ② Connexe → ③ Minerval (mois par mois) → ④ Bus (mois par mois) → ⑤ Équipements (sans mois)</div>
            <?php if ($uploadMessage): ?><div class="ok"><?= htmlspecialchars($uploadMessage) ?></div><?php endif; ?>
            <?php if ($uploadError): ?><div class="err"><?= htmlspecialchars($uploadError) ?></div><?php endif; ?>
            <form method="post" enctype="multipart/form-data" action="portail.php?tab=imports">
                <label>Type d'import *</label>
                <select name="type" id="import-type" required>
                    <?php foreach ($feeKinds as $k => $label): ?>
                        <option value="<?= htmlspecialchars($k) ?>"><?= htmlspecialchars($label) ?></option>
                    <?php endforeach; ?>
                </select>
                <div class="grid2">
                    <div>
                        <label>Section *</label>
                        <select name="section" id="import-section" required>
                            <?php foreach ($sections as $s): ?>
                                <option value="<?= htmlspecialchars($s) ?>"><?= htmlspecialchars($s) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label>Classe *</label>
                        <select name="classe" id="import-classe" required>
                            <option value="">— Choisir section d'abord —</option>
                        </select>
                    </div>
                </div>
                <div id="mois-field">
                    <label>Mois scolaire * (minerval ou bus)</label>
                    <select name="mois" id="import-mois">
                        <option value="">— Choisir le mois —</option>
                        <?php foreach ($moisScolaires as $num => $nom): ?>
                            <option value="<?= $num ?>"><?= htmlspecialchars($nom) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <p class="hint">Par classe : ① Matricules → ② Connexe → ③ Minerval (1 PDF/mois) → ④ Bus (1 PDF/mois) → ⑤ Équipements.</p>
                <p class="hint">Maternelle 1-3 · Primaire 1-6 · EB 7-8 · Options 1ère à 4ème (Pétrochimie, Commercial, Sciences, HP, etc.).</p>
                <p class="hint">Connexe 20 USD = enfant d'agent. Secondaire : 7-8ème 65 · 1-3ème 75 (HP/Sciences 70) · 4ème 120 (HP/Sciences 115).</p>
                <label>Fichier PDF</label>
                <input type="file" name="pdf" accept="application/pdf,application/octet-stream" required>
                <button type="submit" class="btn">Publier et importer</button>
            </form>
        </div>
        <div class="card">
            <h2>Historique des imports</h2>
            <p class="hint">Supprimez un import pour le refaire (ex: mauvais mois ou mauvaise classe).</p>
            <?php if (empty($importLogs)): ?>
                <p style="color:#888;text-align:center;padding:16px;">Aucun import enregistré</p>
            <?php else: ?>
                <?php foreach ($importLogs as $log):
                    $det = json_decode($log['details'] ?? '{}', true) ?: [];
                    $feeKind = $det['fee_kind'] ?? $log['type_import'];
                    $moisNum = $det['mois'] ?? null;
                    $moisLabel = $moisNum ? ($moisScolaires[(int)$moisNum] ?? '') : '';
                    $kindLabel = $feeKinds[$feeKind] ?? $log['type_import'];
                ?>
                    <div class="import-row">
                        <div>
                            <strong><?= htmlspecialchars($kindLabel) ?></strong>
                            <?php if ($log['section_detectee']): ?> · <?= htmlspecialchars($log['section_detectee']) ?><?php endif; ?>
                            <?php if ($log['classe_detectee']): ?> · <?= htmlspecialchars($log['classe_detectee']) ?><?php endif; ?>
                            <?php if ($moisLabel): ?> · <?= htmlspecialchars($moisLabel) ?><?php endif; ?>
                            <br>
                            <small><?= htmlspecialchars(date('d/m/Y H:i', strtotime($log['imported_at']))) ?>
                            — <?= (int)$log['lignes_traitees'] ?> ligne(s)
                            — <?= htmlspecialchars($log['fichier']) ?></small>
                        </div>
                        <form method="post" action="portail.php?tab=imports" onsubmit="return confirm('Annuler cet import ? Les données importées seront retirées.');">
                            <input type="hidden" name="delete_import" value="1">
                            <input type="hidden" name="import_id" value="<?= (int)$log['id'] ?>">
                            <button type="submit" class="btn btn-sm" style="background:#b71c1c;">Supprimer</button>
                        </form>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        <script>
            const sectionMap = <?= getImportSectionsWithClassesJson() ?>;
            const typeSel = document.getElementById('import-type');
            const sectionSel = document.getElementById('import-section');
            const classeSel = document.getElementById('import-classe');
            const moisField = document.getElementById('mois-field');
            const moisSel = document.getElementById('import-mois');

            function fillClasses() {
                const section = sectionSel.value;
                const classes = sectionMap[section] || [];
                classeSel.innerHTML = '';
                classes.forEach(c => {
                    const opt = document.createElement('option');
                    opt.value = c;
                    opt.textContent = c;
                    classeSel.appendChild(opt);
                });
            }

            function toggleMois() {
                const v = typeSel.value;
                const needsMonth = v === 'minerval' || v === 'bus' || v === 'paiements';
                moisField.style.display = needsMonth ? 'block' : 'none';
                moisSel.required = (v === 'minerval' || v === 'bus');
                const needsClass = v !== 'paiements';
                sectionSel.required = needsClass;
                classeSel.required = needsClass;
            }

            sectionSel.addEventListener('change', fillClasses);
            typeSel.addEventListener('change', toggleMois);
            fillClasses();
            toggleMois();
        </script>
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
                        <strong><?= htmlspecialchars($motifLabels[$m['motif']] ?? $m['motif']) ?></strong>
                        <small style="color:#888;"> · <?= htmlspecialchars(date('d/m/Y H:i', strtotime($m['created_at']))) ?></small><br>
                        <?= nl2br(htmlspecialchars($m['message'])) ?>
                        <?php if (!empty($m['note_admin'])): ?>
                            <div class="admin-reply">
                                <strong>Réponse admin :</strong><br>
                                <?= nl2br(htmlspecialchars($m['note_admin'])) ?>
                                <?php if (!empty($m['reponse_at'])): ?>
                                    <small style="display:block;color:#666;margin-top:4px;"><?= htmlspecialchars(date('d/m/Y H:i', strtotime($m['reponse_at']))) ?></small>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
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
                                <form method="post" action="portail.php?tab=messages" class="reply-form">
                                    <input type="hidden" name="reply_message" value="1">
                                    <input type="hidden" name="msg_id" value="<?= (int)$m['id'] ?>">
                                    <textarea name="note_admin" placeholder="Réponse au parent (visible dans Suivi)…" required minlength="5"></textarea>
                                    <button class="btn btn-sm btn-green" type="submit">Répondre et clôturer</button>
                                </form>
                            <?php endif; ?>
                            <form method="post" action="portail.php?tab=messages" style="display:inline" onsubmit="return confirm('Supprimer ce message ?');">
                                <input type="hidden" name="delete_message" value="1">
                                <input type="hidden" name="msg_id" value="<?= (int)$m['id'] ?>">
                                <button class="btn btn-sm" style="background:#b71c1c;" type="submit">Supprimer</button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <div id="tab-communiques" class="<?= $tab === 'communiques' ? '' : 'hidden' ?>">
        <div class="card">
            <h2>Communiqués — Suivi paiements</h2>
            <p class="hint" style="margin-bottom:10px;">Publiez un message affiché sur l'accueil parents (site + APK Suivi). La suppression retire le message immédiatement.</p>
            <?php if ($communiqueMessage): ?><div class="ok"><?= htmlspecialchars($communiqueMessage) ?></div><?php endif; ?>
            <?php if ($communiqueError): ?><div class="err"><?= htmlspecialchars($communiqueError) ?></div><?php endif; ?>
            <form method="post" action="portail.php?tab=communiques">
                <input type="hidden" name="create_communique" value="1">
                <label for="titre">Titre du communiqué *</label>
                <input type="text" id="titre" name="titre" required maxlength="200" placeholder="Ex : Réunion parents">
                <label for="contenu">Message *</label>
                <textarea id="contenu" name="contenu" required placeholder="Texte visible par tous les parents…"></textarea>
                <button type="submit" class="btn">Publier le communiqué</button>
            </form>
        </div>
        <div class="card">
            <h2>Communiqués publiés (<?= count($communiques) ?>)</h2>
            <?php if (empty($communiques)): ?>
                <p style="color:#888;text-align:center;padding:20px;">Aucun communiqué pour le moment</p>
            <?php else: ?>
                <?php foreach ($communiques as $c): ?>
                    <div class="msg nouveau">
                        <strong><?= htmlspecialchars($c['titre']) ?></strong>
                        <small style="display:block;color:#888;margin:4px 0;">
                            <?= htmlspecialchars(date('d/m/Y H:i', strtotime($c['created_at']))) ?>
                        </small>
                        <?= nl2br(htmlspecialchars($c['contenu'])) ?>
                        <div class="msg-actions">
                            <form method="post" action="portail.php?tab=communiques" onsubmit="return confirm('Supprimer ce communiqué ? Il disparaîtra aussi sur l\'APK Suivi.');">
                                <input type="hidden" name="delete_communique" value="1">
                                <input type="hidden" name="communique_id" value="<?= (int) $c['id'] ?>">
                                <button type="submit" class="btn btn-sm" style="background:#b71c1c;margin-top:8px;">Supprimer</button>
                            </form>
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
    <a href="portail.php?tab=communiques" class="<?= $tab === 'communiques' ? 'active' : '' ?>">📢 Communiqués</a>
</div>
</body>
</html>
