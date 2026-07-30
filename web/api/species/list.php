<?php
/**
 * API: List Species Master Dictionary
 * GET /api/species/list.php
 */
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') jsonResponse(['error' => 'Method not allowed'], 405);

$db = getDB();
$pagination = getPagination();

$where = ["1=1"];
$params = [];

if (!empty($_GET['search'])) {
    $where[] = "(scientific_name LIKE ? OR common_name LIKE ? OR family LIKE ?)";
    $s = '%' . $_GET['search'] . '%';
    $params = [$s, $s, $s];
}

if (!empty($_GET['family'])) {
    $where[] = "family = ?";
    $params[] = $_GET['family'];
}

if (!empty($_GET['native_status'])) {
    $where[] = "native_status = ?";
    $params[] = $_GET['native_status'];
}

$whereClause = implode(' AND ', $where);

$countStmt = $db->prepare("SELECT COUNT(*) FROM species WHERE $whereClause");
$countStmt->execute($params);
$total = (int)$countStmt->fetchColumn();

$sql = "
    SELECT s.*, COUNT(pr.id) AS plant_count
    FROM species s
    LEFT JOIN plant_records pr ON pr.species_id = s.id
    WHERE $whereClause
    GROUP BY s.id
    ORDER BY s.common_name ASC
    LIMIT ? OFFSET ?
";

$stmt = $db->prepare($sql);
$paramIdx = 1;
foreach ($params as $param) {
    $stmt->bindValue($paramIdx++, $param);
}
$stmt->bindValue($paramIdx++, $pagination['limit'], PDO::PARAM_INT);
$stmt->bindValue($paramIdx++, $pagination['offset'], PDO::PARAM_INT);

$stmt->execute();
$species = $stmt->fetchAll();

jsonResponse([
    'success' => true,
    'species' => $species,
    'pagination' => [
        'total' => $total,
        'page' => $pagination['page'],
        'limit' => $pagination['limit']
    ]
]);
