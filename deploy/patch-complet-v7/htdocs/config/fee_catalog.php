<?php
declare(strict_types=1);

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
        ],
        'minerval' => [
            'label_prefix' => 'Minerval',
            'montant_du' => 65.00,
            'montant_du_secondaire' => 70.00,
            'fee_type_code' => 'SCOLAIRE_PRIMAIRE',
            'categorie' => 'scolaire',
        ],
        'bus' => [
            'label' => 'Frais de bus',
            'montant_du' => 20.00,
            'fee_type_code' => 'TRANSPORT_PROCHE',
            'categorie' => 'transport',
        ],
        'ecussons' => [
            'label' => 'Écussons',
            'montant_du' => 15.00,
            'fee_type_code' => 'SAC_SCOLAIRE',
            'categorie' => 'equipement',
        ],
        'pull' => [
            'label' => 'Pull-over',
            'montant_du' => 20.00,
            'fee_type_code' => 'PULLOVER',
            'categorie' => 'equipement',
        ],
        'kit_maternelle' => [
            'label' => 'Kit maternelle',
            'montant_du' => 40.00,
            'fee_type_code' => 'KIT_CAGOULE',
            'categorie' => 'equipement',
        ],
        'inscriptions' => [
            'label' => 'Inscription',
            'categorie' => 'inscription',
        ],
    ];
    return $catalog;
}

function getImportFeeKinds(): array
{
    return [
        'inscriptions' => '1. Inscriptions (liste élèves — obligatoire en premier)',
        'connexe' => '2. Frais connexe (30 USD · agent 20 USD)',
        'minerval' => '3. Minerval / frais scolaires (65 USD + mois)',
        'bus' => '4. Frais de bus (20 USD)',
        'ecussons' => '5. Vente écussons (15 USD)',
        'pull' => '6. Pull-over (20 USD)',
        'kit_maternelle' => '7. Kit maternelle (40 USD)',
    ];
}

function getImportSections(): array
{
    return ['Primaire', 'Maternelle', 'EB', 'Secondaire Général', 'Secondaire Technique', 'Pétrochimie', 'Toutes'];
}

function getMoisScolaires(): array
{
    return [
        9 => 'Septembre', 10 => 'Octobre', 11 => 'Novembre', 12 => 'Décembre',
        1 => 'Janvier', 2 => 'Février', 3 => 'Mars', 4 => 'Avril',
    ];
}

function buildPartialPaymentNote(string $feeKind, float $expectedDu, float $paye): ?string
{
    if ($paye >= $expectedDu) {
        return null;
    }
    $reste = round($expectedDu - $paye, 2);
    if ($feeKind === 'connexe' && abs($paye - 20.0) < 1.0 && abs($expectedDu - 30.0) < 1.0) {
        return 'Enfant d\'agent : 20 USD payés sur 30 USD. Crédit restant : 10 USD.';
    }
    if ($paye > 0) {
        return "Paiement partiel ({$paye} / {$expectedDu} USD) — reste {$reste} USD ou prise en charge.";
    }
    return null;
}
