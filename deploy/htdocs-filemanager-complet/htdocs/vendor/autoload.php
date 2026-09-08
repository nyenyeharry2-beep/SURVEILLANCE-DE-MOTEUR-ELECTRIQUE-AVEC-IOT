<?php
declare(strict_types=1);

/** Autoload minimal pour Smalot PdfParser (sans Composer sur InfinityFree). */
spl_autoload_register(static function (string $class): void {
    if (!str_starts_with($class, 'Smalot\\PdfParser\\')) {
        return;
    }
    $relative = str_replace('\\', '/', substr($class, strlen('Smalot\\PdfParser\\')));
    $path = __DIR__ . '/Smalot/PdfParser/' . $relative . '.php';
    if (is_file($path)) {
        require_once $path;
    }
});
