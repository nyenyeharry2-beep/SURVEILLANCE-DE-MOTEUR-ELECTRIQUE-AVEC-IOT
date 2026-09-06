<?php

declare(strict_types=1);

/**
 * Utilitaires schéma DB — compatibilité InfinityFree (migration progressive).
 */
function dbColumnExists(PDO $db, string $table, string $column): bool
{
    static $cache = [];
    $key = $table . '.' . $column;
    if (array_key_exists($key, $cache)) {
        return $cache[$key];
    }

    try {
        $stmt = $db->prepare('SHOW COLUMNS FROM `' . str_replace('`', '', $table) . '` LIKE ?');
        $stmt->execute([$column]);
        $cache[$key] = (bool) $stmt->fetch();
    } catch (Throwable $e) {
        $cache[$key] = false;
    }

    return $cache[$key];
}

function sqlVenteLigneDetailExpr(string $vl = 'vl', string $m = 'm'): string
{
    return 'CONCAT(' . $m . '.nom, " x", ' . $vl . '.quantite)';
}

function sqlVenteLigneDetailExprFull(PDO $db, string $vl = 'vl', string $m = 'm'): string
{
    if (!dbColumnExists($db, 'vente_lignes', 'unite_vente')) {
        return sqlVenteLigneDetailExpr($vl, $m);
    }

    return 'CONCAT(' . $m . '.nom, " x", ' . $vl . '.quantite,
        CASE COALESCE(' . $vl . '.unite_vente, "unite")
            WHEN "comprime" THEN " cp"
            WHEN "plaquette" THEN " plt"
            WHEN "flacon" THEN " fl"
            ELSE ""
        END)';
}

function sqlAchatLigneDetailExpr(PDO $db, string $al = 'al', string $m = 'm'): string
{
    $qty = dbColumnExists($db, 'achat_lignes', 'stock_ajoute')
        ? 'COALESCE(' . $al . '.stock_ajoute, ' . $al . '.quantite)'
        : $al . '.quantite';

    if (!dbColumnExists($db, 'achat_lignes', 'unite_entree')) {
        return 'CONCAT(' . $m . '.nom, " +", ' . $qty . ')';
    }

    return 'CONCAT(' . $m . '.nom, " +", ' . $qty . ',
        CASE COALESCE(' . $al . '.unite_entree, "unite")
            WHEN "comprime" THEN " cp"
            WHEN "plaquette" THEN " plt"
            WHEN "flacon" THEN " fl"
            ELSE ""
        END)';
}

function venteDetailsSqlCompat(PDO $db): string
{
    $expr = sqlVenteLigneDetailExprFull($db, 'vl', 'm');

    return '(SELECT GROUP_CONCAT(' . $expr . ' SEPARATOR ", ")
             FROM vente_lignes vl JOIN medicaments m ON m.id = vl.medicament_id
             WHERE vl.vente_id = v.id) AS details';
}
