<?php
define('BASE_URL', 'https://genies.free.je');

function getSetting(string $key, ?string $default = null): ?string
{
    $settings = [
        'school_name' => 'C.S LES SUPER GENIES',
        'school_foundation' => 'FONDATION EBEN EZER – ORA S.A.R.I',
        'school_project' => 'PROJET EDUCATIF',
        'school_phone' => '+243815454401 / +243858357777',
    ];
    return $settings[$key] ?? $default;
}

function schoolLogoExists(): bool
{
    return file_exists(__DIR__ . '/../assets/images/logo.jpg');
}

require_once __DIR__ . '/../includes/guide_parents_image.php';

$out = $argv[1] ?? __DIR__ . '/../assets/guide-inscription-parents.png';
if (!generateGuideParentsPng($out)) {
    fwrite(STDERR, "Echec generation guide\n");
    exit(1);
}
echo "OK: $out\n";
