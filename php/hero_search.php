<?php

header('Content-Type: application/json');

try {
    require_once __DIR__ . '/connect.php';
    global $connect; 

    $query = trim($_GET['q'] ?? '');

    if (mb_strlen($query) < 2) {
        echo json_encode(['results' => [], 'has_more' => false]);
        exit;
    }

    $limit = 6;
    $searchTerm = '%' . $query . '%';

    $sql = "
        (SELECT 
            'category' AS type, 
            c.id, 
            c.name AS title, 
            c.image_url AS cover_url, 
            NULL AS cooking_time, 
            NULL AS calories,
            c.slug AS link_slug
         FROM categories c
         WHERE c.name LIKE ?)
        UNION ALL
        (SELECT 
            'recipe' AS type, 
            r.id, 
            r.title, 
            r.cover_url, 
            r.cooking_time, 
            r.calories,
            NULL AS link_slug
         FROM recipes r
         WHERE r.status = 'published' AND r.deleted_at IS NULL
         AND (r.title LIKE ? OR r.description LIKE ?))
        ORDER BY type ASC 
        LIMIT ?
    ";

    $stmt = $connect->prepare($sql);
    $stmt->bindValue(1, $searchTerm, PDO::PARAM_STR);
    $stmt->bindValue(2, $searchTerm, PDO::PARAM_STR);
    $stmt->bindValue(3, $searchTerm, PDO::PARAM_STR);
    $stmt->bindValue(4, $limit, PDO::PARAM_INT);
    $stmt->execute();

    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $has_more = count($rows) > 5;
    $results = array_slice($rows, 0, 5);

    echo json_encode([
        'results' => $results,
        'has_more' => $has_more
    ]);

} catch (Exception $e) {
    echo json_encode([
        'error' => true,
        'message' => $e->getMessage()
    ]);
}
?>