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
    $stmt = $pdo->prepare("SELECT id, product_id, answers_json, created_at FROM recommendations WHERE user_id = ? ORDER BY created_at DESC");
    $stmt->execute([$userId]);
    return $stmt->fetchAll();
}
