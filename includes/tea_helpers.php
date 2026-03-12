<?php
/**
 * Tea Filter Helper Functions
 * Append these to includes/product_helpers.php
 * ─────────────────────────────────────────────
 */

/**
 * Returns all teas filtered by the given criteria.
 *
 * @param PDO    $pdo
 * @param array  $flavours   e.g. ['Nutty', 'Floral']
 * @param array  $benefits   e.g. ['Antioxidants', 'Sleep Aid']
 * @param array  $caffeine   e.g. ['None', 'Low']
 * @param array  $origins    e.g. ['Japan', 'India']
 * @param string $sort       one of: name_asc, name_desc, price_asc, price_desc
 * @param string $search     free-text search against name + description
 * @return array
 */
function getFilteredTeas(PDO $pdo, array $flavours, array $benefits, array $caffeine, array $origins, string $sort = 'name_asc', string $search = ''): array
{
    $where  = ['1 = 1'];
    $params = [];

    // Flavour filter
    if (!empty($flavours)) {
        $placeholders = implode(',', array_fill(0, count($flavours), '?'));
        $where[]  = "flavour IN ($placeholders)";
        $params   = array_merge($params, $flavours);
    }

    // Caffeine level filter
    if (!empty($caffeine)) {
        $placeholders = implode(',', array_fill(0, count($caffeine), '?'));
        $where[]  = "caffeine_level IN ($placeholders)";
        $params   = array_merge($params, $caffeine);
    }

    // Origin filter
    if (!empty($origins)) {
        $placeholders = implode(',', array_fill(0, count($origins), '?'));
        $where[]  = "origin IN ($placeholders)";
        $params   = array_merge($params, $origins);
    }

    // Health benefits filter — each selected benefit must appear somewhere in the column
    if (!empty($benefits)) {
        $benefitClauses = [];
        foreach ($benefits as $b) {
            $benefitClauses[] = "FIND_IN_SET(?, REPLACE(health_benefits, ', ', ','))";
            $params[] = $b;
        }
        $where[] = '(' . implode(' OR ', $benefitClauses) . ')';
    }

    // Free-text search
    if ($search !== '') {
        $where[]  = '(name LIKE ? OR description LIKE ?)';
        $params[] = "%$search%";
        $params[] = "%$search%";
    }

    // Sort order — whitelist only
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
 * Returns distinct values for a single tea column (for building filter pills).
 *
 * @param PDO    $pdo
 * @param string $column  'flavour' or 'origin'
 * @return array
 */
function getDistinctTeaValues(PDO $pdo, string $column): array
{
    // Whitelist columns to prevent SQL injection
    $allowed = ['flavour', 'origin', 'caffeine_level'];
    if (!in_array($column, $allowed, true)) return [];

    $stmt = $pdo->prepare("SELECT DISTINCT $column FROM products ORDER BY $column ASC");
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_COLUMN);
}

/**
 * Returns a deduplicated, sorted list of all health benefits across all teas.
 * Benefits are stored comma-separated per product, so we split and flatten them.
 *
 * @param PDO $pdo
 * @return array
 */
function getAllTeaBenefits(PDO $pdo): array
{
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
