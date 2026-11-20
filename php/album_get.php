<?php
require_once __DIR__ . '/album_functions.php';

$userId = getUserId();

$stmt = $connect->prepare("
    SELECT
        id,
        title,
        cover_url,
        template_id,
        pages_count,
        created_at,
        updated_at
    FROM albums
    WHERE user_id = ?
    ORDER BY created_at DESC
");

$stmt->execute([$userId]);

$albums = $stmt->fetchAll(PDO::FETCH_ASSOC);

jsonResponse(true, 'Альбомы получены', [
    'albums' => $albums
]);