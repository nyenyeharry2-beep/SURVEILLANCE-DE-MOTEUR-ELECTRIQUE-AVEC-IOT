<?php
declare(strict_types=1);

/**
 * Portail parents — suivi paiements (WEB, compatible app Android WebView)
 * http://supergenies2026.site.je/suivi.php
 */
define('SUPERGENIES_NO_HEADERS', true);

session_set_cookie_params([
    'lifetime' => 86400,
    'path' => '/',
    'secure' => false,
    'httponly' => true,
    'samesite' => 'Lax',
]);
session_start();

require_once __DIR__ . '/config/bootstrap.php';
require_once __DIR__ . '/config/fee_catalog.php';
require_once __DIR__ . '/includes/theme.php';
require_once __DIR__ . '/parser/PdfParser.php';

$config = getAppConfig();
$tab = $_GET['tab'] ?? 'accueil';
$searchError = null;
$searchResult = null;
$nameCheckResult = null;
$msgSuccess = null;
$msgError = null;

$motifs = [
    'paiement_non_enregistre' => 'Paiement non enregistré',
    'montant_incorrect' => 'Montant incorrect',
    'double_paiement' => 'Double paiement',
    'probleme_inscription' => 'Problème d\'inscription',
    'autre' => 'Autre',
];

// Recherche par nom (vérifier inscription)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['search_nom'])) {
    $tab = 'verifier';
    $nomQuery = trim($_POST['nom_eleve'] ?? '');
    if ($nomQuery === '') {
        $searchError = 'Entrez le nom de l\'élève.';
    } else {
        try {
            $pdo = getPdo();
            $all = $pdo->query('SELECT matricule, nom, prenom, classe, section FROM students ORDER BY nom, prenom')->fetchAll();
            $matches = [];
            $q = PdfParser::normalizeName($nomQuery);
            foreach ($all as $st) {
                $full = PdfParser::normalizeName($st['nom'] . ' ' . $st['prenom']);
                if (str_contains($full, $q) || str_contains($q, $full) || PdfParser::scoreStudentMatch($nomQuery, $st) >= 20) {
                    $matches[] = $st;
                }
            }
            if ($matches === []) {
                $searchError = 'Aucun élève inscrit sous ce nom. Contactez le secrétariat.';
            } else {
                $nameCheckResult = $matches;
            }
        } catch (Throwable $e) {
            $searchError = 'Service momentanément indisponible.';
        }
    }
}

// Recherche matricule
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['search_matricule'])) {
    $tab = 'accueil';
    $matricule = strtoupper(trim($_POST['matricule'] ?? ''));
    if ($matricule === '') {
        $searchError = 'Veuillez entrer un matricule.';
    } else {
        try {
            $pdo = getPdo();
            $stmt = $pdo->prepare('SELECT * FROM students WHERE matricule = ? LIMIT 1');
            $stmt->execute([$matricule]);
            $student = $stmt->fetch();
            if (!$student) {
                $searchError = 'Aucun élève trouvé pour ce matricule.';
            } else {
                $feesStmt = $pdo->prepare('SELECT label, montant_du, montant_paye, statut, mois, notes FROM student_fees WHERE student_id = ? ORDER BY FIELD(statut, "impaye", "partiel", "paye", "exempt"), mois, label');
                $feesStmt->execute([$student['id']]);
                $fees = $feesStmt->fetchAll();
                $totalDu = 0;
                $totalPaye = 0;
                foreach ($fees as $f) {
                    $totalDu += (float) $f['montant_du'];
                    $totalPaye += (float) $f['montant_paye'];
                }
                $searchResult = [
                    'student' => $student,
                    'fees' => $fees,
                    'total_du' => $totalDu,
                    'total_paye' => $totalPaye,
                    'solde' => $totalDu - $totalPaye,
                ];
                $_SESSION['parent_last_student'] = $student;
            }
        } catch (Throwable $e) {
            $searchError = 'Service momentanément indisponible. Réessayez dans quelques minutes.';
        }
    }
}

// Envoi message
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_message'])) {
    $tab = 'messagerie';
    ensureParentMessagesTable(getPdo());
    $nomParent = trim($_POST['nom_parent'] ?? '');
    $telephone = trim($_POST['telephone_parent'] ?? '');
    $matricule = strtoupper(trim($_POST['matricule'] ?? ''));
    $motif = $_POST['motif'] ?? 'autre';
    $message = trim($_POST['message'] ?? '');

    if ($nomParent === '' || $telephone === '' || $matricule === '' || $message === '') {
        $msgError = 'Remplissez tous les champs obligatoires.';
    } elseif (strlen($message) < 10) {
        $msgError = 'Le message doit contenir au moins 10 caractères.';
    } elseif (!isset($motifs[$motif])) {
        $msgError = 'Motif invalide.';
    } else {
        try {
            $pdo = getPdo();
            $stmt = $pdo->prepare('SELECT nom, prenom, classe, section FROM students WHERE matricule = ?');
            $stmt->execute([$matricule]);
            $st = $stmt->fetch();
            $ins = $pdo->prepare('INSERT INTO parent_messages (nom_parent, telephone_parent, matricule, nom_eleve, prenom_eleve, classe_eleve, section_eleve, motif, message) VALUES (?,?,?,?,?,?,?,?,?)');
            $ins->execute([
                $nomParent, $telephone, $matricule,
                $st['nom'] ?? null, $st['prenom'] ?? null, $st['classe'] ?? null, $st['section'] ?? null,
                $motif, $message,
            ]);
            $msgSuccess = 'Votre message a été transmis à la facturation. Merci.';
        } catch (Throwable $e) {
            $msgError = 'Impossible d\'envoyer le message pour le moment. Réessayez ou appelez le secrétariat.';
        }
    }
}

$lastStudent = $_SESSION['parent_last_student'] ?? null;
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <?php renderThemeHead('Suivi Paiements — Super Genies'); ?>
</head>
<body>
<?php renderBrandHeader('Suivi des paiements scolaires'); ?>

<div class="wrap">
    <!-- ACCUEIL -->
    <div class="<?= $tab === 'accueil' ? '' : 'hidden' ?>">
        <div class="card">
            <h2>Rechercher un élève</h2>
            <p class="hint">Entrez le matricule de votre enfant (ex: CSLSG-2026-2027-00167)</p>
            <?php if ($searchError): ?><div class="err"><?= htmlspecialchars($searchError) ?></div><?php endif; ?>
            <form method="post" action="suivi.php?tab=accueil">
                <input type="hidden" name="search_matricule" value="1">
                <label for="matricule">Matricule</label>
                <input type="text" id="matricule" name="matricule" placeholder="CSLSG-2026-2027-00167" required
                       value="<?= htmlspecialchars($_POST['matricule'] ?? '') ?>">
                <button type="submit" class="btn btn-blue">Rechercher</button>
            </form>

            <?php if ($searchResult): ?>
                <?php $s = $searchResult['student']; ?>
                <div class="student">
                    <strong><?= htmlspecialchars($s['nom'] . ' ' . $s['prenom']) ?></strong><br>
                    Classe : <?= htmlspecialchars($s['classe']) ?><br>
                    Matricule : <?= htmlspecialchars($s['matricule']) ?><br>
                    <strong>Total dû : <?= number_format($searchResult['total_du'], 2) ?> USD</strong> ·
                    Payé : <?= number_format($searchResult['total_paye'], 2) ?> USD ·
                    Solde : <span class="<?= $searchResult['solde'] > 0 ? 'impaye' : 'paye' ?>"><?= number_format($searchResult['solde'], 2) ?> USD</span>
                </div>
                <?php if (empty($searchResult['fees'])): ?>
                    <p class="hint" style="margin-top:10px;">Aucun frais enregistré pour cet élève.</p>
                <?php else:
                    $moisNoms = [1=>'Janvier',2=>'Février',3=>'Mars',4=>'Avril',5=>'Mai',6=>'Juin',7=>'Juillet',8=>'Août',9=>'Septembre',10=>'Octobre',11=>'Novembre',12=>'Décembre'];
                    $groupes = ['Frais connexe' => [], 'Minerval' => [], 'Transport' => [], 'Équipement' => [], 'Autres' => []];
                    foreach ($searchResult['fees'] as $f) {
                        $lbl = $f['label'];
                        if (str_contains($lbl, 'connexe')) {
                            $groupes['Frais connexe'][] = $f;
                        } elseif (str_contains($lbl, 'Minerval') || str_contains($lbl, 'scolaire')) {
                            $groupes['Minerval'][] = $f;
                        } elseif (str_contains($lbl, 'bus') || str_contains($lbl, 'Transport')) {
                            $groupes['Transport'][] = $f;
                        } elseif (preg_match('/écusson|ecusson|pull|cravate|kit|combinaison|tenue|sac|équipement/i', $lbl)) {
                            $groupes['Équipement'][] = $f;
                        } else {
                            $groupes['Autres'][] = $f;
                        }
                    }
                    foreach ($groupes as $gLabel => $items):
                        if (empty($items)) continue;
                ?>
                    <h2 style="margin-top:14px;"><?= htmlspecialchars($gLabel) ?></h2>
                    <?php foreach ($items as $f):
                        $moisTxt = $f['mois'] ? ($moisNoms[(int)$f['mois']] ?? 'mois '.$f['mois']) : '';
                        $displayLabel = $f['label'];
                        if ($moisTxt && !str_contains($displayLabel, $moisTxt)) {
                            $displayLabel .= ' (' . $moisTxt . ')';
                        }
                    ?>
                        <div class="fee">
                            <div>
                                <span><?= htmlspecialchars($displayLabel) ?></span>
                                <?php if (!empty($f['notes'])): ?>
                                    <div class="fee-note"><?= htmlspecialchars($f['notes']) ?></div>
                                <?php endif; ?>
                            </div>
                            <span class="<?= $f['statut'] === 'partiel' ? 'partiel' : (in_array($f['statut'], ['impaye'], true) ? 'impaye' : 'paye') ?>">
                                <?= number_format((float)$f['montant_paye'], 2) ?> / <?= number_format((float)$f['montant_du'], 2) ?> USD
                                · <?= $f['statut'] === 'paye' ? 'Payé' : ($f['statut'] === 'partiel' ? 'Partiel / crédit' : 'Impayé') ?>
                            </span>
                        </div>
                    <?php endforeach; ?>
                <?php endforeach; endif; ?>
                <p style="margin-top:12px;"><a href="suivi.php?tab=messagerie">Signaler un problème →</a></p>
            <?php endif; ?>
        </div>
    </div>

    <!-- VÉRIFIER INSCRIPTION PAR NOM -->
    <div class="<?= $tab === 'verifier' ? '' : 'hidden' ?>">
        <div class="card">
            <h2>Vérifier une inscription</h2>
            <p class="hint">Entrez le nom de l'élève pour savoir s'il est enregistré (avant de payer).</p>
            <?php if ($searchError && $tab === 'verifier'): ?><div class="err"><?= htmlspecialchars($searchError) ?></div><?php endif; ?>
            <form method="post" action="suivi.php?tab=verifier">
                <input type="hidden" name="search_nom" value="1">
                <label for="nom_eleve">Nom et prénom de l'élève</label>
                <input type="text" id="nom_eleve" name="nom_eleve" placeholder="Ex: KABIKA AURELIA" required
                       value="<?= htmlspecialchars($_POST['nom_eleve'] ?? '') ?>">
                <button type="submit" class="btn btn-blue">Vérifier</button>
            </form>
            <?php if ($nameCheckResult): ?>
                <div class="ok" style="margin-top:12px;">✅ Élève(s) inscrit(s) trouvé(s) :</div>
                <?php foreach ($nameCheckResult as $m): ?>
                    <div class="student">
                        <strong><?= htmlspecialchars($m['nom'] . ' ' . $m['prenom']) ?></strong><br>
                        Matricule : <span class="badge"><?= htmlspecialchars($m['matricule']) ?></span><br>
                        Classe : <?= htmlspecialchars($m['classe']) ?> · <?= htmlspecialchars($m['section'] ?? '') ?>
                    </div>
                <?php endforeach; ?>
                <p class="hint">Utilisez le matricule dans l'onglet Accueil pour voir les paiements.</p>
            <?php endif; ?>
        </div>
    </div>

    <!-- MESSAGERIE -->
    <div class="<?= $tab === 'messagerie' ? '' : 'hidden' ?>">
        <div class="card">
            <h2>Messagerie — Facturation</h2>
            <p class="hint">Signalez un problème de paiement ou d'inscription.</p>
            <?php if ($msgSuccess): ?><div class="ok"><?= htmlspecialchars($msgSuccess) ?></div><?php endif; ?>
            <?php if ($msgError): ?><div class="err"><?= htmlspecialchars($msgError) ?></div><?php endif; ?>
            <?php if ($lastStudent): ?>
                <div class="student">
                    Dernier élève recherché : <strong><?= htmlspecialchars($lastStudent['nom'] . ' ' . $lastStudent['prenom']) ?></strong>
                    (<?= htmlspecialchars($lastStudent['matricule']) ?>)
                </div>
            <?php endif; ?>
            <form method="post" action="suivi.php?tab=messagerie">
                <input type="hidden" name="send_message" value="1">
                <label for="nom_parent">Nom complet du parent *</label>
                <input type="text" id="nom_parent" name="nom_parent" required>
                <label for="telephone_parent">Téléphone *</label>
                <input type="tel" id="telephone_parent" name="telephone_parent" required placeholder="0999999999">
                <label for="msg_matricule">Matricule élève *</label>
                <input type="text" id="msg_matricule" name="matricule" required
                       value="<?= htmlspecialchars($lastStudent['matricule'] ?? '') ?>"
                       placeholder="CSLSG-2026-2027-00167">
                <label for="motif">Motif *</label>
                <select id="motif" name="motif" required>
                    <?php foreach ($motifs as $k => $v): ?>
                        <option value="<?= htmlspecialchars($k) ?>"><?= htmlspecialchars($v) ?></option>
                    <?php endforeach; ?>
                </select>
                <label for="message">Message * (min. 10 caractères)</label>
                <textarea id="message" name="message" required placeholder="Décrivez le problème..."></textarea>
                <button type="submit" class="btn">Envoyer à la facturation</button>
            </form>
            <p class="hint" style="margin-top:10px;">Secrétariat : <?= htmlspecialchars($config['school_phone']) ?></p>
        </div>
    </div>

    <!-- INSCRIPTIONS -->
    <div class="<?= $tab === 'inscriptions' ? '' : 'hidden' ?>">
        <div class="card">
            <h2>Inscriptions 2026-2027</h2>
            <p><strong><?= htmlspecialchars($config['school_name']) ?></strong><br>
            <?= htmlspecialchars($config['school_address']) ?><br>
            📞 <?= htmlspecialchars($config['school_phone']) ?></p>
            <h2>Primaire</h2>
            <ul>
                <li>Frais connexes : 30 USD (enfant d'agent : 20 USD)</li>
                <li>Frais mensuels (minerval) : 65 USD × 8 mois — Sept., Oct., Nov.…</li>
                <li>Frais de bus : 20 USD</li>
                <li>Kit maternelle : 40 USD · Pull : 20 USD · Écussons : 15 USD</li>
            </ul>
            <h2>Secondaire</h2>
            <ul>
                <li>Inscriptions gratuites</li>
                <li>Frais connexes : 30 à 50 USD selon niveau</li>
                <li>Mensualités : 65 à 120 USD selon classe</li>
            </ul>
            <h2>Pétrochimie</h2>
            <p>Section spécialisée — contact : +243 858 357 777</p>
        </div>
    </div>

    <!-- TROUSSEAU -->
    <div class="<?= $tab === 'trousseau' ? '' : 'hidden' ?>">
        <div class="card">
            <h2>Trousseau & équipements</h2>
            <ul>
                <li>Pull-over : 20 USD</li>
                <li>Kit maternelle : 40 USD</li>
                <li>Écussons : 15 USD</li>
                <li>Combinaison : 25 USD · Tenue gym : 15 USD · Sac : 10 USD</li>
            </ul>
            <p><strong>Uniformes :</strong> jupe/pantalon bleu + chemise blanche.</p>
            <p><strong>Coiffure :</strong> garçons ras · filles tresses poupée.</p>
        </div>
    </div>
</div>

<div class="tabs">
    <a href="suivi.php?tab=accueil" class="<?= $tab === 'accueil' ? 'active' : '' ?>">🔍 Matricule</a>
    <a href="suivi.php?tab=verifier" class="<?= $tab === 'verifier' ? 'active' : '' ?>">👤 Par nom</a>
    <a href="suivi.php?tab=messagerie" class="<?= $tab === 'messagerie' ? 'active' : '' ?>">✉️ Message</a>
    <a href="suivi.php?tab=inscriptions" class="<?= $tab === 'inscriptions' ? 'active' : '' ?>">📋 Frais</a>
</div>
</body>
</html>
