<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/medicaments_unites.php';
requireAdmin();

$db = getDB();
ensureMedicamentUnitesSchema($db);

$templateCsv = "code;nom;categorie;type_unite;prix_comprime;prix_plaquette;prix_flacon;comprimes_par_plaquette;quantite_stock;seuil_alerte;date_expiration;description\n"
    . "PAR500;Paracétamol 500mg;Antalgique;comprime_plaquette;100;1000;0;10;500;20;2027-12-31;Vente par comprimé ou plaquette\n"
    . "AMOX250;Amoxicilline sirop;Antibiotique;flacon;0;0;3500;0;24;5;2026-06-30;Vente par flacon\n";

if (isset($_GET['template'])) {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="modele_medicaments.csv"');
    echo "\xEF\xBB\xBF" . $templateCsv;
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $action = $_POST['action'] ?? 'import';

    if ($action === 'import') {
        if (empty($_FILES['fichier']['tmp_name'])) {
            flash('danger', 'Choisissez un fichier Excel (.xlsx) ou CSV.');
            redirect('medicaments_import.php');
        }

        $name = $_FILES['fichier']['name'] ?? '';
        $tmp = $_FILES['fichier']['tmp_name'];
        $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
        $rows = [];

        if (in_array($ext, ['csv', 'txt'], true)) {
            $content = file_get_contents($tmp);
            if ($content === false) {
                flash('danger', 'Impossible de lire le fichier.');
                redirect('medicaments_import.php');
            }
            $rows = parseImportSpreadsheet($content);
        } elseif ($ext === 'xlsx') {
            $rows = parseSimpleXlsx($tmp);
        } else {
            flash('danger', 'Format non supporté. Utilisez .csv ou .xlsx (Excel).');
            redirect('medicaments_import.php');
        }

        if ($rows === []) {
            flash('danger', 'Fichier vide ou colonnes non reconnues.');
            redirect('medicaments_import.php');
        }

        $result = importMedicamentsFromRows($db, $rows);
        $msg = $result['imported'] . ' ajouté(s), ' . $result['updated'] . ' mis à jour.';
        if ($result['errors'] !== []) {
            $msg .= ' Erreurs : ' . implode(' | ', array_slice($result['errors'], 0, 5));
            if (count($result['errors']) > 5) {
                $msg .= ' …';
            }
            flash('warning', $msg);
        } else {
            flash('success', $msg);
        }
        redirect('medicaments.php');
    }
}

$pageTitle = 'Import Excel / CSV';
require_once __DIR__ . '/includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <strong><i class="bi bi-file-earmark-spreadsheet me-1"></i> Importer des médicaments</strong>
            </div>
            <div class="card-body">
                <p class="text-muted">
                    Importez vos produits depuis Excel. Enregistrez le fichier au format <strong>CSV</strong>
                    (séparateur <code>;</code>) ou uploadez directement un fichier <strong>.xlsx</strong>.
                </p>

                <h6 class="mt-3">Colonnes attendues</h6>
                <div class="table-responsive">
                    <table class="table table-sm table-bordered">
                        <thead class="table-light">
                            <tr>
                                <th>Colonne</th><th>Obligatoire</th><th>Description</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr><td><code>code</code></td><td>Non</td><td>Code produit (généré si vide)</td></tr>
                            <tr><td><code>nom</code></td><td>Oui</td><td>Nom du médicament</td></tr>
                            <tr><td><code>categorie</code></td><td>Non</td><td>Catégorie (créée si absente)</td></tr>
                            <tr><td><code>type_unite</code></td><td>Non</td><td><code>comprime_plaquette</code> ou <code>flacon</code></td></tr>
                            <tr><td><code>prix_comprime</code></td><td>Si comprimés</td><td>Prix par comprimé (FC)</td></tr>
                            <tr><td><code>prix_plaquette</code></td><td>Non</td><td>Prix par plaquette (sinon cp × nb)</td></tr>
                            <tr><td><code>prix_flacon</code></td><td>Si flacon</td><td>Prix par flacon (FC)</td></tr>
                            <tr><td><code>comprimes_par_plaquette</code></td><td>Non</td><td>Défaut : 10</td></tr>
                            <tr><td><code>quantite_stock</code></td><td>Non</td><td>Stock en comprimés ou flacons</td></tr>
                            <tr><td><code>seuil_alerte</code></td><td>Non</td><td>Défaut : 10</td></tr>
                            <tr><td><code>date_expiration</code></td><td>Non</td><td>AAAA-MM-JJ</td></tr>
                        </tbody>
                    </table>
                </div>

                <a href="medicaments_import.php?template=1" class="btn btn-outline-secondary btn-sm mb-4">
                    <i class="bi bi-download me-1"></i> Télécharger le modèle CSV
                </a>

                <form method="post" enctype="multipart/form-data">
                    <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                    <input type="hidden" name="action" value="import">
                    <div class="mb-3">
                        <label class="form-label">Fichier Excel / CSV *</label>
                        <input type="file" name="fichier" class="form-control" accept=".csv,.txt,.xlsx" required>
                    </div>
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-upload me-1"></i> Importer
                        </button>
                        <a href="medicaments.php" class="btn btn-secondary">Retour</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
