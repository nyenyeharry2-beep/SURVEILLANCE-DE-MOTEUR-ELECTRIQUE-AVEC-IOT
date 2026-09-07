<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/bootstrap.php';

$config = getAppConfig();

jsonResponse([
    'success' => true,
    'title' => 'Trousseau & Équipements',
    'annee_scolaire' => '2026-2027',
    'school' => [
        'name' => $config['school_name'],
        'address' => $config['school_address'],
        'phone' => $config['school_phone'],
        'email' => $config['school_email'],
    ],
    'equipements' => [
        ['label' => 'Pull-over cagoule', 'montant' => 20, 'devise' => 'USD'],
        ['label' => 'Combinaison', 'montant' => 25, 'devise' => 'USD'],
        ['label' => 'Tenue de gymnastique (blouson, science et pétrochimie)', 'montant' => 15, 'devise' => 'USD'],
        ['label' => 'Sac scolaire', 'montant' => 10, 'devise' => 'USD'],
        ['label' => 'Cravate', 'montant' => '6 et 10', 'devise' => 'USD'],
    ],
    'uniformes' => [
        'filles' => 'Jupe plissée bleue et chemise blanche manches longues',
        'garcons' => 'Pantalon bleu et chemise blanche manches longues',
    ],
    'coiffure' => [
        'garcons' => 'Ras',
        'filles' => 'Tresses poupée',
    ],
    'transport' => [
        ['zone' => 'Zones proches', 'montant_mensuel' => 20],
        ['zone' => 'Maternelle', 'montant_mensuel' => 25],
        ['zone' => 'Plateau, Lido, Kasangulu, Juge, Kitungwa, Kabulamenshi, Kisanga', 'montant_mensuel' => 30],
        'note' => 'Les frais de bus sont payés d\'avance. Reprise du bus : 30 USD de réinscription + mensualité.',
    ],
    'politiques' => [
        'L\'anglais et l\'informatique sont enseignés dès la maternelle.',
        'Tous les cahiers et manuels doivent être couverts.',
        'Réduction multi-enfants : plus de 5 enfants inscrits — gratuité pour un enfant (déterminé par l\'école).',
    ],
]);
