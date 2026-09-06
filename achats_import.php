<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/achats.php';
requireLogin();

$db = getDB();
ensureAchatSchema($db);

$templateCsv = "code;nom;quantite;unite_entree;prix_achat;date_fabrication;date_expiration\n"
    . "PAR500;Paracétamol 500mg;100;comprime;50;2025-01-15;2027-12-31\n"
    . "PAR500;Paracétamol 500mg;10;plaquette;450;2025-01-15;2027-12-31\n"
    . "AMOX250;Amoxicilline sirop;12;flacon;2800;2025-03-01;2026-06-30\n";

if (isset($_GET['template'])) {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="modele_entrees_stock.csv"');
    echo "\xEF\xBB\xBF" . $templateCsv;
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();

    if (empty($_FILES['fichier']['tmp_name'])) {
        flash('danger', 'Choisissez un fichier Excel ou CSV.');
        redirect('achats_import.php');
    }

    $name = $_FILES['fichier']['name'] ?? '';
    $tmp = $_FILES['fichier']['tmp_name'];
    $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
    $rows = [];

    try {
        if (in_array($ext, ['csv', 'txt'], true)) {
            $content = file_get_contents($tmp);
            if ($content === false) {
                throw new RuntimeException('Impossible de lire le fichier.');
            }
            $rows = parseImportSpreadsheet($content);
        } elseif ($ext === 'xlsx') {
            $rows = parseSimpleXlsx($tmp);
        } else {
            flash('danger', 'Format non supporté. Utilisez .csv ou .xlsx.');
            redirect('achats_import.php');
        }
    } catch (Throwable $e) {
        flash('danger', 'Erreur lecture fichier : ' . $e->getMessage());
        redirect('achats_import.php');
    }

    if ($rows === []) {
        flash('danger', 'Fichier vide ou colonnes non reconnues.');
        redirect('achats_import.php');
    }

    $result = importStockEntriesFromRows(
        $db,
        currentUser(),
        $rows,
        !empty($_POST['fournisseur_id']) ? (int) $_POST['fournisseur_id'] : null,
        trim($_POST['date_achat'] ?? '') ?: getBusinessDate()
    );

    $msg = $result['imported'] . ' ligne(s) importée(s).';
    if ($result['achat_id']) {
        $msg .= ' Bon d\'entrée n°' . $result['achat_id'] . '.';
    }
    if ($result['errors'] !== []) {
        $msg .= ' Avertissements : ' . implode(' | ', array_slice($result['errors'], 0, 5));
        flash('warning', $msg);
    } else {
        flash('success', $msg);
    }
    redirect('achats.php');
}

$fournisseurs = $db->query('SELECT id, nom FROM fournisseurs ORDER BY nom')->fetchAll();
$dateMetier = getBusinessDate();

$pageTitle = 'Import entrées stock';
require_once __DIR__ . '/includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <strong><i class="bi bi-box-arrow-in-down me-1"></i> Importer des entrées de stock (Excel / CSV)</strong>
            </div>
            <div class="card-body">
                <p class="text-muted">
                    Ajoutez du stock aux produits <strong>déjà présents</strong> dans le catalogue.
                    Pour créer de nouveaux produits avec prix de vente, utilisez
                    <a href="medicaments_import.php">Import catalogue Excel</a>.
                </p>

                <h6>Colonnes du fichier</h6>
                <table class="table table-sm table-bordered">
                    <thead class="table-light"><tr><th>Colonne</th><th>Obligatoire</th><th>Description</th></tr></thead>
                    <tbody>
                        <tr><td><code>code</code> ou <code>nom</code></td><td>Oui</td><td>Produit existant</td></tr>
                        <tr><td><code>quantite</code></td><td>Oui</td><td>Quantité entrée</td></tr>
                        <tr><td><code>unite_entree</code></td><td>Non</td><td><code>comprime</code>, <code>plaquette</code> ou <code>flacon</code></td></tr>
                        <tr><td><code>prix_achat</code></td><td>Non</td><td>Prix d'achat unitaire (FC)</td></tr>
                        <tr><td><code>date_fabrication</code></td><td>Non</td><td>Lot — AAAA-MM-JJ</td></tr>
                        <tr><td><code>date_expiration</code></td><td>Non</td><td>Lot — AAAA-MM-JJ</td></tr>
                    </tbody>
                </table>

                <a href="achats_import.php?template=1" class="btn btn-outline-secondary btn-sm mb-4">
                    <i class="bi bi-download me-1"></i> Télécharger le modèle CSV
                </a>

                <form method="post" enctype="multipart/form-data">
                    <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                    <div class="mb-3">
                        <label class="form-label">Fichier Excel / CSV *</label>
                        <input type="file" name="fichier" class="form-control" accept=".csv,.txt,.xlsx" required>
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Fournisseur</label>
                            <select name="fournisseur_id" class="form-select">
                                <option value="">— Aucun —</option>
                                <?php foreach ($fournisseurs as $f): ?>
                                <option value="<?= $f['id'] ?>"><?= e($f['nom']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Date entrée</label>
                            <input type="date" name="date_achat" class="form-control" value="<?= e($dateMetier) ?>">
                        </div>
                    </div>
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary"><i class="bi bi-upload me-1"></i> Importer et ajouter au stock</button>
                        <a href="achats.php" class="btn btn-secondary">Retour aux achats</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
