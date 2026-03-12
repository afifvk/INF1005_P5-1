<?php
/**
 * Tea Filter Helper Functions
 * Place in includes/tea_helpers.php
 */

/**
 * Returns all teas filtered by the given criteria.
 */
function getFilteredTeas(array $flavours, array $benefits, array $caffeine, array $origins, string $sort = 'name_asc', string $search = ''): array
{
    $pdo    = getDB();
    $where  = ['1 = 1'];
    $params = [];

    if (!empty($flavours)) {
        $placeholders = implode(',', array_fill(0, count($flavours), '?'));
        $where[]  = "flavour IN ($placeholders)";
        $params   = array_merge($params, $flavours);
    }

    if (!empty($caffeine)) {
        $placeholders = implode(',', array_fill(0, count($caffeine), '?'));
        $where[]  = "caffeine_level IN ($placeholders)";
        $params   = array_merge($params, $caffeine);
    }

    if (!empty($origins)) {
        $placeholders = implode(',', array_fill(0, count($origins), '?'));
        $where[]  = "origin IN ($placeholders)";
        $params   = array_merge($params, $origins);
    }

    if (!empty($benefits)) {
        $benefitClauses = [];
        foreach ($benefits as $b) {
            $benefitClauses[] = "FIND_IN_SET(?, REPLACE(health_benefits, ', ', ','))";
            $params[] = $b;
        }
        $where[] = '(' . implode(' OR ', $benefitClauses) . ')';
    }

    if ($search !== '') {
        $where[]  = '(name LIKE ? OR description LIKE ?)';
        $params[] = "%$search%";
        $params[] = "%$search%";
    }

    $sortMap = [
        'name_asc'   => 'name ASC',
        'name_desc'  => 'name DESC',
        'price_asc'  => 'price ASC',
        'price_desc' => 'price DESC',
    ];
    $orderBy = $sortMap[$sort] ?? 'name ASC';

    $sql  = 'SELECT * FROM products WHERE ' . implode(' AND ', $where) . " ORDER BY $orderBy";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Returns distinct values for a single tea column.
 */
function getDistinctTeaValues(string $column): array
{
    $pdo     = getDB();
    $allowed = ['flavour', 'origin', 'caffeine_level'];
    if (!in_array($column, $allowed, true)) return [];

    $stmt = $pdo->prepare("SELECT DISTINCT $column FROM products ORDER BY $column ASC");
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_COLUMN);
}

/**
 * Returns a deduplicated sorted list of all health benefits.
 */
function getAllTeaBenefits(): array
{
    $pdo  = getDB();
    $stmt = $pdo->query('SELECT health_benefits FROM products');
    $rows = $stmt->fetchAll(PDO::FETCH_COLUMN);

    $all = [];
    foreach ($rows as $row) {
        foreach (explode(',', $row) as $benefit) {
            $b = trim($benefit);
            if ($b !== '') $all[$b] = true;
        }
    }

    $benefits = array_keys($all);
    sort($benefits);
    return $benefits;
}
