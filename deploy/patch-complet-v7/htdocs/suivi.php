<?php
declare(strict_types=1);

/**
 * Portail parents — design original APK (WebView)
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

if (isset($_GET['app']) && (string) $_GET['app'] === '1') {
    $_SESSION['parent_app'] = true;
}

require_once __DIR__ . '/config/bootstrap.php';
require_once __DIR__ . '/includes/parent_ui.php';

$config = getAppConfig();
$tab = $_GET['tab'] ?? 'accueil';
$searchError = null;
$searchResult = null;
$msgSuccess = null;
$msgError = null;

$motifs = [
    'paiement_non_enregistre' => 'Paiement non enregistré',
    'montant_incorrect' => 'Montant incorrect',
    'double_paiement' => 'Double paiement',
    'probleme_inscription' => 'Problème d\'inscription',
    'autre' => 'Autre',
];

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
                $searchError = 'Matricule non trouvé.';
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
            $msgError = 'Impossible d\'envoyer le message. Réessayez ou appelez le secrétariat.';
        }
    }
}

$lastStudent = $_SESSION['parent_last_student'] ?? null;
$moisNoms = [1=>'Janvier',2=>'Février',3=>'Mars',4=>'Avril',5=>'Mai',6=>'Juin',7=>'Juillet',8=>'Août',9=>'Septembre',10=>'Octobre',11=>'Novembre',12=>'Décembre'];

try {
    $communiques = fetchCommuniques(getPdo());
} catch (Throwable $e) {
    $communiques = [];
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <?php renderParentHead(); ?>
</head>
<body<?= parentIsApp() ? ' class="app-mode"' : '' ?>>
<?php renderParentAppBar(); ?>

<div class="content">
    <!-- ACCUEIL -->
    <div id="panel-accueil" class="tab-panel <?= $tab === 'accueil' ? '' : 'hidden' ?>">
        <div class="welcome">
            <h1>Bienvenue chers parents</h1>
            <p>Entrez le matricule de votre enfant pour consulter ses frais et paiements.</p>
        </div>

        <?php renderParentCommuniques($communiques); ?>

        <?php renderParentCarousel(); ?>

        <form method="post" action="<?= htmlspecialchars(parentPageUrl('accueil')) ?>">
            <input type="hidden" name="search_matricule" value="1">
            <div class="field-outlined">
                <label for="matricule">Matricule élève</label>
                <svg class="ico-search" viewBox="0 0 24 24" fill="#666"><path d="M15.5 14h-.79l-.28-.27A6.471 6.471 0 0016 9.5 6.5 6.5 0 109.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14z"/></svg>
                <input type="text" id="matricule" name="matricule" placeholder="CSLSG-2026-2027-00455" required
                       value="<?= htmlspecialchars($_POST['matricule'] ?? '') ?>" autocomplete="off">
            </div>
            <button type="submit" class="btn-search">Rechercher</button>
        </form>

        <?php if ($searchError && $tab === 'accueil'): ?>
            <div class="alert-err"><?= htmlspecialchars($searchError) ?></div>
        <?php endif; ?>

        <?php if ($searchResult): ?>
            <?php $s = $searchResult['student']; ?>
            <div class="student-card">
                <h3><?= htmlspecialchars($s['nom'] . ' ' . $s['prenom']) ?></h3>
                <p class="hint">Classe : <?= htmlspecialchars($s['classe']) ?></p>
                <p class="hint">Matricule : <?= htmlspecialchars($s['matricule']) ?></p>
                <p style="margin-top:8px;">
                    <strong>Total dû : <?= number_format($searchResult['total_du'], 2) ?> USD</strong><br>
                    Payé : <?= number_format($searchResult['total_paye'], 2) ?> USD ·
                    Solde : <span class="<?= $searchResult['solde'] > 0 ? 'impaye' : 'paye' ?>"><?= number_format($searchResult['solde'], 2) ?> USD</span>
                </p>
            </div>

            <?php if (empty($searchResult['fees'])): ?>
                <p class="hint" style="margin-top:12px;">Aucun frais enregistré pour cet élève.</p>
            <?php else: ?>
                <p class="section-title">Frais attribués</p>
                <?php foreach ($searchResult['fees'] as $f):
                    $moisTxt = $f['mois'] ? ($moisNoms[(int)$f['mois']] ?? '') : '';
                    $displayLabel = $f['label'];
                    if ($moisTxt && !str_contains($displayLabel, $moisTxt)) {
                        $displayLabel .= ' (' . $moisTxt . ')';
                    }
                    $statClass = $f['statut'] === 'partiel' ? 'partiel' : ($f['statut'] === 'paye' ? 'paye' : 'impaye');
                    $statLabel = $f['statut'] === 'paye' ? 'Payé' : ($f['statut'] === 'partiel' ? 'Partiel' : 'Impayé');
                ?>
                    <div class="fee-card">
                        <div>
                            <div><?= htmlspecialchars($displayLabel) ?></div>
                            <?php if (!empty($f['notes'])): ?>
                                <div class="fee-note"><?= htmlspecialchars($f['notes']) ?></div>
                            <?php endif; ?>
                        </div>
                        <div class="<?= $statClass ?>">
                            <?= number_format((float)$f['montant_paye'], 2) ?> / <?= number_format((float)$f['montant_du'], 2) ?> USD<br>
                            <?= $statLabel ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>

            <a class="btn-outline" href="#" data-goto-tab="messagerie">✉ Signaler un problème de paiement</a>
        <?php endif; ?>
    </div>

    <!-- MESSAGERIE -->
    <div id="panel-messagerie" class="tab-panel <?= $tab === 'messagerie' ? '' : 'hidden' ?>">
        <div class="card-page">
            <h2>Messagerie — Facturation</h2>
            <p class="hint">Signalez un problème de paiement ou d'inscription.</p>
            <?php if ($msgSuccess): ?><div class="alert-ok"><?= htmlspecialchars($msgSuccess) ?></div><?php endif; ?>
            <?php if ($msgError): ?><div class="alert-err"><?= htmlspecialchars($msgError) ?></div><?php endif; ?>
            <?php if ($lastStudent): ?>
                <div class="student-card" style="margin-top:12px;">
                    Dernier élève : <strong><?= htmlspecialchars($lastStudent['nom'] . ' ' . $lastStudent['prenom']) ?></strong>
                    (<?= htmlspecialchars($lastStudent['matricule']) ?>)
                </div>
            <?php endif; ?>
            <form method="post" action="<?= htmlspecialchars(parentPageUrl('messagerie')) ?>">
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
                <button type="submit" class="btn-search">Envoyer à la facturation</button>
            </form>
            <p class="hint" style="margin-top:12px;">📞 <?= htmlspecialchars($config['school_phone']) ?></p>
        </div>
    </div>

    <!-- INSCRIPTIONS -->
    <div id="panel-inscriptions" class="tab-panel <?= $tab === 'inscriptions' ? '' : 'hidden' ?>">
        <div class="card-page">
            <h2>Inscriptions 2026-2027</h2>
            <p><strong><?= htmlspecialchars($config['school_name']) ?></strong><br>
            <?= htmlspecialchars($config['school_address']) ?><br>
            📞 <?= htmlspecialchars($config['school_phone']) ?></p>
            <h2 style="margin-top:14px;">Primaire & Maternelle</h2>
            <ul>
                <li>Frais connexes : 30 USD (enfant d'agent : 20 USD)</li>
                <li>Minerval : 65 USD × 8 mois</li>
                <li>Frais de bus : 20 USD</li>
                <li>Kit maternelle : 40 USD</li>
            </ul>
            <h2>Secondaire</h2>
            <ul>
                <li>Frais connexes : 30 à 50 USD</li>
                <li>Mensualités : 65 à 120 USD selon classe</li>
            </ul>
            <h2>Pétrochimie</h2>
            <p>Section spécialisée — +243 858 357 777</p>
        </div>
    </div>

    <!-- TROUSSEAU -->
    <div id="panel-trousseau" class="tab-panel <?= $tab === 'trousseau' ? '' : 'hidden' ?>">
        <div class="card-page">
            <h2>Trousseau & équipements</h2>
            <ul>
                <li>Pull-over : 20 USD</li>
                <li>Écussons : 15 USD</li>
                <li>Combinaison : 25 USD</li>
                <li>Tenue gym : 15 USD</li>
                <li>Sac scolaire : 10 USD</li>
            </ul>
            <p style="margin-top:12px;"><strong>Uniformes :</strong> jupe/pantalon bleu + chemise blanche.</p>
            <p><strong>Coiffure :</strong> garçons ras · filles tresses poupée.</p>
        </div>
    </div>
</div>

<?php renderParentBottomNav($tab); ?>
<?php renderParentCommuniqueNotifier($communiques); ?>
</body>
</html>
