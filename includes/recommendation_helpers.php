<?php
function createRecommendation($userId, $productId, $productTitle, $answersJson) {
    $pdo = getDB();
    $stmt = $pdo->prepare("INSERT INTO recommendations (user_id, product_id, product_title, answers_json) VALUES (?, ?, ?, ?)");
    // allow null productId
    $pid = $productId ? $productId : null;
    return $stmt->execute([$userId, $pid, $productTitle, $answersJson]);
}

function getRecommendationsByUser($userId) {
    $pdo = getDB();
    $stmt = $pdo->prepare("\n        SELECT\n            r.id,\n            r.product_id,\n            r.product_title,\n            r.answers_json,\n            r.created_at,\n            p.name AS product_name,\n            p.image AS product_image,\n            p.price AS product_price\n        FROM recommendations r\n        LEFT JOIN products p ON p.id = r.product_id\n        WHERE r.user_id = ?\n        ORDER BY r.created_at DESC\n    ");
    $stmt->execute([$userId]);
    return $stmt->fetchAll();
}
