<?php
declare(strict_types=1);

/** Catalogue frais C.S. Les Super Genies — 2026-2027 */
function getFeeCatalog(): array
{
    static $catalog = null;
    if ($catalog !== null) {
        return $catalog;
    }
    $catalog = [
        'connexe' => [
            'label' => 'Frais connexe',
            'montant_du' => 30.00,
            'montant_agent' => 20.00,
            'fee_type_code' => 'FRAIS_CONNEXE_PRIMAIRE',
            'categorie' => 'inscription',
            'monthly' => false,
        ],
        'minerval' => [
            'label_prefix' => 'Minerval',
            'montant_du' => 65.00,
            'fee_type_code' => 'SCOLAIRE_PRIMAIRE',
            'categorie' => 'scolaire',
            'monthly' => true,
        ],
        'bus' => [
            'label' => 'Frais de bus',
            'montant_du' => 20.00,
            'fee_type_code' => 'TRANSPORT_PROCHE',
            'categorie' => 'transport',
            'monthly' => true,
        ],
        'cravate' => [
            'label' => 'Cravate',
            'montant_du' => 10.00,
            'fee_type_code' => 'CRAVATE',
            'categorie' => 'equipement',
            'monthly' => false,
        ],
        'cravate_normal' => [
            'label' => 'Cravate normale',
            'montant_du' => 5.00,
            'fee_type_code' => 'CRAVATE_NORMAL',
            'categorie' => 'equipement',
            'monthly' => false,
        ],
        'combinaison' => [
            'label' => 'Combinaison',
            'montant_du' => 25.00,
            'fee_type_code' => 'COMBINAISON',
            'categorie' => 'equipement',
            'monthly' => false,
        ],
        'pull' => [
            'label' => 'Pull-over',
            'montant_du' => 20.00,
            'fee_type_code' => 'PULLOVER',
            'categorie' => 'equipement',
            'monthly' => false,
        ],
        'pull_normal' => [
            'label' => 'Pull-over normal',
            'montant_du' => 15.00,
            'fee_type_code' => 'PULLOVER_NORMAL',
            'categorie' => 'equipement',
            'monthly' => false,
        ],
        'sac_scolaire' => [
            'label' => 'Sac scolaire',
            'montant_du' => 10.00,
            'fee_type_code' => 'SAC_SCOLAIRE',
            'categorie' => 'equipement',
            'monthly' => false,
        ],
        'journal_classe' => [
            'label' => 'Journal de classe',
            'montant_du' => 12.00,
            'fee_type_code' => 'JOURNAL_CLASSE',
            'categorie' => 'equipement',
            'monthly' => false,
        ],
        'kit_maternelle' => [
            'label' => 'Kit maternelle',
            'montant_du' => 40.00,
            'fee_type_code' => 'KIT_CAGOULE',
            'categorie' => 'equipement',
            'monthly' => false,
        ],
        'tenue_gym' => [
            'label' => 'Tenue de gymnastique',
            'montant_du' => 15.00,
            'fee_type_code' => 'TENUE_GYM',
            'categorie' => 'equipement',
            'monthly' => false,
        ],
        'inscriptions' => [
            'label' => 'Inscription',
            'categorie' => 'inscription',
            'monthly' => false,
        ],
    ];
    return $catalog;
}

function getImportFeeKinds(): array
{
    return [
        'inscriptions' => '1. Inscriptions (liste élèves — obligatoire en premier)',
        'connexe' => '2. Frais connexe (30 USD · 4ème 50 · agent 20)',
        'minerval' => '3. Minerval / frais scolaires (par mois)',
        'bus' => '4. Frais de bus (20 USD · par mois)',
        'cravate' => '5. Cravate (10 USD)',
        'cravate_normal' => '6. Cravate normale (5 USD)',
        'combinaison' => '7. Combinaison (25 USD)',
        'pull' => '8. Pull-over (20 USD)',
        'pull_normal' => '9. Pull-over normal (15 USD)',
        'sac_scolaire' => '10. Sac scolaire (10 USD)',
        'journal_classe' => '11. Journal de classe (12 USD)',
        'kit_maternelle' => '12. Kit maternelle (40 USD)',
        'tenue_gym' => '13. Tenue de gymnastique (15 USD)',
        'paiements' => '14. Paiements mixtes (PDF Super Genies — auto)',
    ];
}

function getImportSections(): array
{
    return array_keys(getImportSectionsWithClasses());
}

/** Sections → classes pour l'import (matricules puis paiements par classe/mois) */
function getImportSectionsWithClasses(): array
{
    $options = [
        'Pétrochimie', 'Commercial', 'Sciences', 'HP', 'Électricité',
        'Électronique', 'Mécanique auto', 'Mécanique générale',
    ];
    $classes = [
        'Maternelle' => [
            '1ère ANNEE MATERNELLE',
            '2ème ANNEE MATERNELLE',
            '3ème ANNEE MATERNELLE',
        ],
        'Primaire' => [
            '1ère ANNEE PRIMAIRE',
            '2ème ANNEE PRIMAIRE',
            '3ème ANNEE PRIMAIRE',
            '4ème ANNEE PRIMAIRE',
            '5ème ANNEE PRIMAIRE',
            '6ème ANNEE PRIMAIRE',
        ],
        'EB (7ème — 8ème)' => [
            '7ème ANNEE EB',
            '8ème ANNEE EB',
        ],
    ];
    foreach ($options as $opt) {
        $key = 'Options — ' . $opt;
        $classes[$key] = [];
        for ($n = 1; $n <= 4; $n++) {
            $ord = match ($n) {
                1 => '1ère',
                2 => '2ème',
                3 => '3ème',
                4 => '4ème',
                default => $n . 'ème',
            };
            $classes[$key][] = $ord . ' ' . $opt;
        }
    }
    return $classes;
}

function getImportSectionsWithClassesJson(): string
{
    return json_encode(getImportSectionsWithClasses(), JSON_UNESCAPED_UNICODE);
}

function getMoisScolaires(): array
{
    return [
        9 => 'Septembre', 10 => 'Octobre', 11 => 'Novembre', 12 => 'Décembre',
        1 => 'Janvier', 2 => 'Février', 3 => 'Mars', 4 => 'Avril',
    ];
}

function normalizeClasse(string $classe): string
{
    $u = mb_strtoupper(trim($classe));
    $u = str_replace(['È', 'É', 'Ê', 'Ë', 'À', 'Â', 'Ô', 'Ù', 'Û', 'Ç'], ['E', 'E', 'E', 'E', 'A', 'A', 'O', 'U', 'U', 'C'], $u);
    return preg_replace('/\s+/u', ' ', $u) ?? $u;
}

function isHpOrScienceClasse(string $classe): bool
{
    $u = normalizeClasse($classe);
    return (bool) preg_match('/\bHP\b|HOTELLERIE|H\.P\b|\bSCIENCE\b|\bSC\.?\b/u', $u);
}

function isQuatriemeClasse(string $classe): bool
{
    $u = normalizeClasse($classe);
    return (bool) preg_match('/\b4\s*(EME|ERE|RE|ANNEE|ANN)\b|\b4E\b|\bQUATRIEME\b/u', $u);
}

function isEbClasse(string $classe): bool
{
    $u = normalizeClasse($classe);
    return (bool) preg_match('/\b[78]\s*(EME|ERE|RE|ANNEE|ANN)\b|\b[78]E\b|\bEB\b/u', $u);
}

function isSecondaireClasse(string $classe): bool
{
    $u = normalizeClasse($classe);
    if (isEbClasse($u) || isQuatriemeClasse($u)) {
        return true;
    }
    return (bool) preg_match(
        '/\b[123]\s*(ER|ERE|RE|EME|ANNEE|ANN)\b|\bSEC\b|SECOND|TECHNIQUE|PETROCHIMIE|COMMERCIAL|ELECTR|MECAN|OPTION/u',
        $u
    );
}

function resolveConnexeExpected(?string $classe): float
{
    if ($classe === null || trim($classe) === '') {
        return 30.0;
    }
    if (isQuatriemeClasse($classe)) {
        return 50.0;
    }
    return 30.0;
}

function resolveMinervalExpected(?string $classe): float
{
    if ($classe === null || trim($classe) === '') {
        return 65.0;
    }
    if (isQuatriemeClasse($classe)) {
        return isHpOrScienceClasse($classe) ? 115.0 : 120.0;
    }
    if (isEbClasse($classe)) {
        return 65.0;
    }
    if (isSecondaireClasse($classe)) {
        return isHpOrScienceClasse($classe) ? 70.0 : 75.0;
    }
    return 65.0;
}

function isAgentConnexePayment(float $paye, float $expectedConnexe): bool
{
    return abs($paye - 20.0) < 1.5 && $expectedConnexe >= 30.0;
}

function buildPartialPaymentNote(string $feeKind, float $expectedDu, float $paye, ?string $classe = null): ?string
{
    if ($paye >= $expectedDu - 0.01) {
        return null;
    }
    $reste = round($expectedDu - $paye, 2);
    if ($feeKind === 'connexe' && isAgentConnexePayment($paye, $expectedDu)) {
        return 'Enfant d\'agent ou pris en charge : 20 USD payés sur ' . number_format($expectedDu, 0) . ' USD. Avance / crédit restant : ' . number_format($reste, 0) . ' USD.';
    }
    if ($paye > 0) {
        return "Paiement partiel ({$paye} / {$expectedDu} USD) — reste {$reste} USD ou prise en charge.";
    }
    return null;
}

function feeKindUsesMonth(string $feeKind): bool
{
    $catalog = getFeeCatalog();
    return !empty($catalog[$feeKind]['monthly']);
}

function guessFeeKindFromAmount(float $amount, string $importKind = 'auto'): string
{
    if ($importKind !== 'auto' && $importKind !== 'paiements') {
        return $importKind;
    }

    $checks = [
        [120.0, 'minerval'], [115.0, 'minerval'], [75.0, 'minerval'], [70.0, 'minerval'],
        [65.0, 'minerval'], [50.0, 'connexe'], [40.0, 'kit_maternelle'], [30.0, 'connexe'],
        [25.0, 'combinaison'], [20.0, 'bus'], [15.0, 'pull_normal'], [12.0, 'journal_classe'],
        [10.0, 'cravate'], [5.0, 'cravate_normal'],
    ];
    foreach ($checks as [$amt, $kind]) {
        if (abs($amount - $amt) < 1.5) {
            if ($kind === 'bus' && abs($amount - 20.0) < 1.5) {
                return 'bus';
            }
            return $kind;
        }
    }
    return 'minerval';
}

/** @return array<string, mixed> */
function resolveImportFeeRow(array $row, string $feeKind, array $context, ?string $classe = null): array
{
    $catalog = getFeeCatalog();
    if (!isset($catalog[$feeKind])) {
        return $row;
    }

    $cfg = $catalog[$feeKind];
    $paye = (float) ($row['montant_paye'] ?? 0);
    $moisForced = isset($context['mois']) && $context['mois'] !== '' ? (int) $context['mois'] : null;
    $moisNoms = getMoisScolaires();

    if ($feeKind === 'minerval') {
        $expected = resolveMinervalExpected($classe);
        $mois = $moisForced ?? ($row['mois'] ?? null);
        if ($mois === null && !empty($row['date_paiement']) && preg_match('/(\d{2})\/(\d{2})\/(\d{4})/', $row['date_paiement'], $dm)) {
            $mois = (int) $dm[2];
        }
        $moisLabel = $mois !== null ? ($moisNoms[$mois] ?? '') : '';
        $row['label'] = $moisLabel !== '' ? 'Minerval — ' . $moisLabel : 'Minerval (frais scolaires)';
        $row['fee_category'] = 'minerval';
        $row['fee_type_code'] = $cfg['fee_type_code'];
        $row['mois'] = $mois;
    } elseif ($feeKind === 'connexe') {
        $expected = resolveConnexeExpected($classe);
        $row['label'] = $cfg['label'];
        $row['fee_category'] = 'connexe';
        $row['fee_type_code'] = isQuatriemeClasse($classe ?? '') ? 'FRAIS_CONNEXE_SEC_4_6' : 'FRAIS_CONNEXE_PRIMAIRE';
        $row['mois'] = null;
    } elseif ($feeKind === 'bus') {
        $expected = (float) $cfg['montant_du'];
        $mois = $moisForced ?? ($row['mois'] ?? null);
        if ($mois === null && !empty($row['date_paiement']) && preg_match('/(\d{2})\/(\d{2})\/(\d{4})/', $row['date_paiement'], $dm)) {
            $mois = (int) $dm[2];
        }
        $moisLabel = $mois !== null ? ($moisNoms[$mois] ?? '') : '';
        $row['label'] = $moisLabel !== '' ? 'Frais de bus — ' . $moisLabel : $cfg['label'];
        $row['fee_category'] = 'transport';
        $row['fee_type_code'] = $cfg['fee_type_code'];
        $row['mois'] = $mois;
    } else {
        $expected = (float) $cfg['montant_du'];
        $row['label'] = $cfg['label'];
        $row['fee_category'] = 'equipement';
        $row['fee_type_code'] = $cfg['fee_type_code'];
        $row['mois'] = null;
    }

    $row['montant_du'] = $expected;
    $row['statut'] = $paye >= $expected - 0.01 ? 'paye' : ($paye > 0 ? 'partiel' : 'impaye');

    $noteParts = [];
    $partial = buildPartialPaymentNote($feeKind, $expected, $paye, $classe);
    if ($partial !== null) {
        $noteParts[] = $partial;
    }
    if (!empty($context['section']) && $context['section'] !== 'Toutes') {
        $tag = $context['section'];
        if (!empty($context['classe'])) {
            $tag .= ' · ' . $context['classe'];
        }
        $noteParts[] = '[' . $tag . ']';
    }
    if (!empty($row['numero_recu'])) {
        $noteParts[] = 'Reçu ' . $row['numero_recu'];
    }
    $row['notes_extra'] = $noteParts !== [] ? implode(' ', $noteParts) : null;
    $row['_fee_kind'] = $feeKind;

    return $row;
}
