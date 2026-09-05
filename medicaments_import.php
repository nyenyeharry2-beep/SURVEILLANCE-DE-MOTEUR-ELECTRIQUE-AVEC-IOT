<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/medicaments_unites.php';
requireAdmin();

$db = getDB();
ensureMedicamentUnitesSchema($db);

$templateCsv = "code;nom;categorie;type_unite;prix_achat;prix_comprime;prix_plaquette;prix_flacon;comprimes_par_plaquette;quantite_stock;stock_a_ajouter;seuil_alerte;date_expiration;description\n"
    . "PAR500;Paracétamol 500mg;Antalgique;comprime_plaquette;50;100;1000;0;10;500;0;20;2027-12-31;Vente par comprimé ou plaquette\n"
    . "AMOX250;Amoxicilline sirop;Antibiotique;flacon;2800;0;0;3500;0;24;0;5;2026-06-30;Vente par flacon\n";

if (isset($_GET['template'])) {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="modele_medicaments.csv"');
    echo "\xEF\xBB\xBF" . $templateCsv;
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();

    if (empty($_FILES['fichier']['tmp_name'])) {
        flash('danger', 'Choisissez un fichier Excel (.xlsx) ou CSV.');
        redirect('medicaments_import.php');
    }

    $name = $_FILES['fichier']['name'] ?? '';
    $tmp = $_FILES['fichier']['tmp_name'];
    $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
    $mode = ($_POST['import_mode'] ?? 'replace') === 'add_stock' ? 'add_stock' : 'replace';
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
            flash('danger', 'Format non supporté. Utilisez .csv ou .xlsx (Excel).');
            redirect('medicaments_import.php');
        }
    } catch (Throwable $e) {
        flash('danger', 'Erreur lecture fichier : ' . $e->getMessage());
        redirect('medicaments_import.php');
    }

    if ($rows === []) {
        flash('danger', 'Fichier vide ou colonnes non reconnues.');
        redirect('medicaments_import.php');
    }

    $result = importMedicamentsFromRows($db, $rows, ['mode' => $mode]);
    $msg = $result['imported'] . ' ajouté(s), ' . $result['updated'] . ' mis à jour';
    $msg .= $mode === 'add_stock' ? ' (stock cumulé).' : ' (stock remplacé).';
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

$pageTitle = 'Import Excel / CSV';
require_once __DIR__ . '/includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-lg-9">
        <div class="card">
            <div class="card-header">
                <strong><i class="bi bi-file-earmark-spreadsheet me-1"></i> Importer catalogue, prix et stock (Excel / CSV)</strong>
            </div>
            <div class="card-body">
                <p class="text-muted">
                    Importez vos médicaments avec <strong>noms, prix de vente</strong> (comprimé / plaquette / flacon)
                    et <strong>quantités en stock</strong>. Formats : CSV (séparateur <code>;</code>) ou <strong>.xlsx</strong>.
                </p>

                <div class="alert alert-secondary py-2">
                    <strong>Deux modes :</strong>
                    <ul class="mb-0 small">
                        <li><strong>Remplacer le stock</strong> — la colonne <code>quantite_stock</code> devient le stock total</li>
                        <li><strong>Ajouter au stock</strong> — utilise <code>stock_a_ajouter</code> (ou <code>quantite_stock</code>) pour cumuler</li>
                    </ul>
                    Pour des entrées avec historique fournisseur, utilisez aussi
                    <a href="achats_import.php">Import entrées stock</a>.
                </div>

                <h6 class="mt-3">Colonnes du fichier</h6>
                <div class="table-responsive">
                    <table class="table table-sm table-bordered">
                        <thead class="table-light">
                            <tr><th>Colonne</th><th>Obligatoire</th><th>Description</th></tr>
                        </thead>
                        <tbody>
                            <tr><td><code>nom</code></td><td>Oui</td><td>Nom du médicament / produit</td></tr>
                            <tr><td><code>code</code></td><td>Non</td><td>Code (généré si vide)</td></tr>
                            <tr><td><code>categorie</code></td><td>Non</td><td>Catégorie (créée si absente)</td></tr>
                            <tr><td><code>type_unite</code></td><td>Non</td><td><code>comprime_plaquette</code> ou <code>flacon</code></td></tr>
                            <tr><td><code>prix_achat</code></td><td>Non</td><td>Prix d'achat unitaire (FC)</td></tr>
                            <tr><td><code>prix_comprime</code></td><td>Si comprimés</td><td>Prix vente / comprimé (FC)</td></tr>
                            <tr><td><code>prix_plaquette</code></td><td>Non</td><td>Prix vente / plaquette</td></tr>
                            <tr><td><code>prix_flacon</code></td><td>Si flacon</td><td>Prix vente / flacon (FC)</td></tr>
                            <tr><td><code>comprimes_par_plaquette</code></td><td>Non</td><td>Défaut : 10</td></tr>
                            <tr><td><code>quantite_stock</code></td><td>Non</td><td>Stock (comprimés ou flacons)</td></tr>
                            <tr><td><code>stock_a_ajouter</code></td><td>Mode ajout</td><td>Quantité à ajouter au stock existant</td></tr>
                            <tr><td><code>seuil_alerte</code></td><td>Non</td><td>Défaut : 10</td></tr>
                            <tr><td><code>date_expiration</code></td><td>Non</td><td>AAAA-MM-JJ ou date Excel</td></tr>
                        </tbody>
                    </table>
                </div>

                <a href="medicaments_import.php?template=1" class="btn btn-outline-secondary btn-sm mb-4">
                    <i class="bi bi-download me-1"></i> Télécharger le modèle CSV
                </a>

                <form method="post" enctype="multipart/form-data">
                    <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                    <div class="mb-3">
                        <label class="form-label">Mode d'import stock</label>
                        <select name="import_mode" class="form-select">
                            <option value="replace">Remplacer le stock (nouveau catalogue)</option>
                            <option value="add_stock">Ajouter au stock existant</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Fichier Excel / CSV *</label>
                        <input type="file" name="fichier" class="form-control" accept=".csv,.txt,.xlsx" required>
                    </div>
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-upload me-1"></i> Importer prix et stock
                        </button>
                        <a href="medicaments.php" class="btn btn-secondary">Retour</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
