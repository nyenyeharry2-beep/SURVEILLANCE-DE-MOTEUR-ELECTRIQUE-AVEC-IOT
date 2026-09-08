<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/bootstrap.php';

$config = getAppConfig();

jsonResponse([
    'success' => true,
    'title' => 'Conditions d\'admission & Inscriptions',
    'annee_scolaire' => '2026-2027',
    'school' => [
        'name' => $config['school_name'],
        'address' => $config['school_address'],
        'phone' => $config['school_phone'],
        'email' => $config['school_email'],
        'motto' => 'Discipline · Compétence · Excellence',
        'slogan' => 'L\'excellence, notre engagement !',
    ],
    'sections' => [
        [
            'id' => 'primaire',
            'title' => 'Section Primaire',
            'inscriptions' => [
                'label' => 'Inscriptions et réinscriptions',
                'frais_connexe' => 30,
                'devise' => 'USD',
            ],
            'frais_scolaires' => [
                'montant_mensuel' => 65,
                'duree_mois' => 8,
                'description' => 'Couvre frais d\'État, bulletin, inspection technique et formation, incluant les frais connexes.',
            ],
            'extras' => [
                ['label' => 'Kit complet (t-shirt, jupe/short, tablier, pull-over)', 'montant' => 35],
                ['label' => 'Kit complet avec cagoule', 'montant' => 40],
                ['label' => 'Sac scolaire', 'montant' => 6],
            ],
            'transport' => [
                ['zone' => 'Zones proches', 'montant' => 20],
                ['zone' => 'Mater Dei', 'montant' => 25],
                ['zone' => 'Plateau, Lido, Juge, Kitungwa, Kabulamenshi, Kisanga', 'montant' => 30],
            ],
            'coiffure' => [
                'garcons' => 'Ras',
                'filles' => 'Tresses poupée',
            ],
            'notes' => [
                'Les frais ne sont pas remboursables sauf cas de force majeure justifié.',
                'Réduction familiale : à partir de 5 enfants inscrits, le 5e bénéficie de la gratuité (1er mois payé).',
                'Les objets classiques doivent être déposés avant la rentrée.',
            ],
        ],
        [
            'id' => 'secondaire',
            'title' => 'Section Secondaire',
            'inscriptions' => [
                'label' => 'Inscriptions et réinscriptions',
                'gratuit' => true,
                'frais_connexe' => [
                    ['niveau' => '7e à 3e', 'montant' => 30],
                    ['niveau' => '4e à 6e', 'montant' => 50],
                ],
            ],
            'frais_scolaires' => [
                ['niveau' => '7e-8e EB', 'montant_mensuel' => 65, 'mois' => 8],
                ['niveau' => '1re Option Générale', 'montant_mensuel' => 70, 'mois' => 8],
                ['niveau' => '2e-3e Options Générales', 'montant_mensuel' => 70, 'mois' => 8],
                ['niveau' => '1re Options Techniques', 'montant_mensuel' => 75, 'mois' => 9],
                ['niveau' => '2e-3e Options Techniques', 'montant_mensuel' => 75, 'mois' => 9],
                ['niveau' => '4e Générale', 'montant_mensuel' => 115, 'mois' => 9],
                ['niveau' => '4e Technique', 'montant_mensuel' => 120, 'mois' => 9],
            ],
        ],
        [
            'id' => 'petrochimie',
            'title' => 'Section Pétrochimie',
            'slogan' => 'Choisis l\'avenir, choisis la Pétrochimie !',
            'description' => 'Deviens acteur du changement, là où la science transforme le monde.',
            'annee' => '2025-2026',
            'contact' => [
                'website' => 'www.cssupergenies.com',
                'phone' => '+243 858 357 777',
            ],
        ],
    ],
]);
