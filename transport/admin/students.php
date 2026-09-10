<?php
$pageTitle = 'Gestion des élèves';
require_once __DIR__ . '/../includes/header_admin.php';

$yearId = getSelectedYearId();
$db = getDB();

// Actions POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCsrfToken($_POST['csrf_token'] ?? '')) {
    $action = $_POST['action'] ?? '';
    if ($action === 'verify' && !empty($_POST['student_id'])) {
        $sid = (int) $_POST['student_id'];
        $db->prepare('UPDATE students SET verified = 1 WHERE id = ?')->execute([$sid]);
        logActivity('verify_student', 'student', $sid, 'Élève vérifié OK');
        flashMessage('success', 'Élève marqué comme vérifié.');
    }
    if ($action === 'deactivate' && !empty($_POST['student_id'])) {
        $sid = (int) $_POST['student_id'];
        $db->prepare('UPDATE students SET statut = "inactif" WHERE id = ?')->execute([$sid]);
        logActivity('deactivate_student', 'student', $sid, 'Élève désactivé');
        flashMessage('success', 'Élève désactivé.');
    }
    redirect(BASE_URL . '/admin/students.php?' . http_build_query($_GET));
}

// Filtres
$search = trim($_GET['q'] ?? '');
$filterClasse = (int) ($_GET['classe'] ?? 0);
$filterSection = trim($_GET['section'] ?? '');
$filterArret = (int) ($_GET['arret'] ?? 0);
$filterMois = (int) ($_GET['mois'] ?? 0);
$filterStatut = $_GET['statut'] ?? '';
$filterPending = ($_GET['filter'] ?? '') === 'pending';

$sql = 'SELECT s.*, c.nom AS classe_nom, c.section AS classe_section, bs.nom AS arret_nom
        FROM students s
        LEFT JOIN classes c ON s.classe_id = c.id
        LEFT JOIN bus_stops bs ON s.arret_id = bs.id
        WHERE s.academic_year_id = ? AND s.statut = "actif"';
$params = [$yearId];

if ($search) {
    $sql .= ' AND (s.nom_complet LIKE ? OR s.telephone_parent LIKE ? OR s.numero_dossier LIKE ? OR s.parent_nom LIKE ?)';
    $like = "%$search%";
    $params = array_merge($params, [$like, $like, $like, $like]);
}
if ($filterClasse) {
    $sql .= ' AND s.classe_id = ?';
    $params[] = $filterClasse;
}
if ($filterSection) {
    $sql .= ' AND c.section = ?';
    $params[] = $filterSection;
}
if ($filterArret) {
    $sql .= ' AND s.arret_id = ?';
    $params[] = $filterArret;
}
if ($filterPending) {
    $sql .= ' AND s.verified = 0';
}
if ($filterMois && $filterStatut) {
    $sql .= ' AND s.id IN (SELECT student_id FROM payments WHERE academic_year_id = ? AND mois = ? AND statut = ?)';
    $params[] = $yearId;
    $params[] = $filterMois;
    $params[] = $filterStatut;
}

$sql .= ' ORDER BY c.section ASC, c.ordre ASC, s.nom_complet ASC';
$stmt = $db->prepare($sql);
$stmt->execute($params);
$students = $stmt->fetchAll();

$classes = getActiveClasses();
$stops = getActiveStops();
?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <h2><i class="bi bi-people"></i> Élèves (<?= count($students) ?>)</h2>
    <a href="<?= BASE_URL ?>/admin/student.php?action=add" class="btn btn-primary"><i class="bi bi-plus"></i> Ajouter</a>
</div>

<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label small">Recherche</label>
                <input type="text" name="q" class="form-control" placeholder="Nom, téléphone, dossier..." value="<?= e($search) ?>">
            </div>
            <div class="col-md-2">
                <label class="form-label small">Section</label>
                <select name="section" class="form-select">
                    <option value="">Toutes</option>
                    <?php foreach (getSchoolSections() as $s): ?>
                    <option value="<?= e($s) ?>" <?= $filterSection === $s ? 'selected' : '' ?>><?= e($s) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small">Classe</label>
                <select name="classe" class="form-select">
                    <option value="">Toutes</option>
                    <?php foreach (($filterSection ? getClassesBySection($filterSection) : $classes) as $c): ?>
                    <option value="<?= $c['id'] ?>" <?= $filterClasse == $c['id'] ? 'selected' : '' ?>><?= e(formatClassName($c)) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small">Arrêt</label>
                <select name="arret" class="form-select">
                    <option value="">Tous</option>
                    <?php foreach ($stops as $s): ?>
                    <option value="<?= $s['id'] ?>" <?= $filterArret == $s['id'] ? 'selected' : '' ?>><?= e($s['nom']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small">Mois</label>
                <select name="mois" class="form-select">
                    <option value="">—</option>
                    <?php foreach (SCHOOL_MONTHS as $num => $info): ?>
                    <option value="<?= $num ?>" <?= $filterMois == $num ? 'selected' : '' ?>><?= e($info['label']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small">Statut paiement</label>
                <select name="statut" class="form-select">
                    <option value="">—</option>
                    <option value="paye" <?= $filterStatut === 'paye' ? 'selected' : '' ?>>Payé</option>
                    <option value="partiel" <?= $filterStatut === 'partiel' ? 'selected' : '' ?>>Partiel</option>
                    <option value="impaye" <?= $filterStatut === 'impaye' ? 'selected' : '' ?>>Non payé</option>
                </select>
            </div>
            <div class="col-md-1">
                <button type="submit" class="btn btn-primary w-100"><i class="bi bi-search"></i></button>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead class="table-dark">
                <tr>
                    <th>N° Dossier</th>
                    <th>Nom & Post-nom</th>
                    <th>Section</th>
                    <th>Classe</th>
                    <th>Parent</th>
                    <th>Téléphone</th>
                    <th>Arrêt</th>
                    <th>Vérifié</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($students as $s): ?>
            <tr>
                <td><code><?= e($s['numero_dossier']) ?></code></td>
                <td><a href="<?= BASE_URL ?>/admin/student.php?id=<?= $s['id'] ?>"><?= e($s['nom_complet']) ?></a></td>
                <td><?= e($s['classe_section'] ?: $s['section'] ?: '—') ?></td>
                <td><?= e(formatClassName($s)) ?></td>
                <td><?= e($s['parent_nom'] ?: '—') ?></td>
                <td><?= e($s['telephone_parent']) ?></td>
                <td><?= e($s['arret_nom'] ?: '—') ?></td>
                <td>
                    <?php if ($s['verified']): ?>
                    <span class="badge bg-success">OK</span>
                    <?php else: ?>
                    <form method="POST" class="d-inline">
                        <?= csrfField() ?>
                        <input type="hidden" name="action" value="verify">
                        <input type="hidden" name="student_id" value="<?= $s['id'] ?>">
                        <button type="submit" class="btn btn-sm btn-outline-success" title="Marquer OK">✓ OK</button>
                    </form>
                    <?php endif; ?>
                </td>
                <td>
                    <a href="<?= BASE_URL ?>/admin/student.php?id=<?= $s['id'] ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></a>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($students)): ?>
            <tr><td colspan="9" class="text-center text-muted py-4">Aucun élève trouvé.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer_admin.php'; ?>
