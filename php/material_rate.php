<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/db.php';

function redirectToMaterial(int $id): void
{
    header('Location: /html/material.php?id=' . $id . '#rating');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Метод не разрешён.');
}

$csrfToken = $_POST['csrf_token'] ?? '';
$sessionToken = $_SESSION['rating_csrf_token'] ?? '';

if (
    !is_string($csrfToken) ||
    !is_string($sessionToken) ||
    $csrfToken === '' ||
    $sessionToken === '' ||
    !hash_equals($sessionToken, $csrfToken)
) {
    http_response_code(403);
    exit('Проверка безопасности не пройдена. Обнови страницу и попробуй снова.');
}

$id = filter_input(INPUT_POST, 'material_id', FILTER_VALIDATE_INT);

$rating = filter_input(INPUT_POST, 'rating', FILTER_VALIDATE_INT);

if (!$id || $id < 1 || !$rating || $rating < 1 || $rating > 5) {
    http_response_code(400);
    exit('Некорректные данные.');
}

$materialStmt = $pdo->prepare(
    "SELECT id FROM materials WHERE id = :id LIMIT 1"
);

$materialStmt->execute(['id' => $id]);

if (!$materialStmt->fetch()) {
    http_response_code(404);
    exit('Материал не найден.');
}

$sessionRatingKey = 'rated_material_' . $id;

if (isset($_SESSION[$sessionRatingKey])) {
    redirectToMaterial($id);
}

try {
    $stmt = $pdo->prepare("
        INSERT INTO material_ratings (material_id, rating)
        VALUES (:material_id, :rating)
    ");

    $stmt->execute([
        'material_id' => $id,
        'rating' => $rating
    ]);

    $_SESSION[$sessionRatingKey] = $rating;

    redirectToMaterial($id);

} catch (PDOException $e) {
    error_log('Ошибка сохранения рейтинга: ' . $e->getMessage());

    http_response_code(500);
    exit('Не удалось сохранить оценку. Попробуй позже.');
}
