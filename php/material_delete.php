
<?php
require_once __DIR__ . '/admin_guard.php';
require_once __DIR__ . '/db.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Недопустимый метод запроса.'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);

if (!$id || $id < 1) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Некорректный ID материала.'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $stmt = $pdo->prepare(
        'SELECT image_path FROM materials WHERE id = :id'
    );
    $stmt->execute(['id' => $id]);

    $material = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$material) {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'message' => 'Материал не найден.'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $deleteStmt = $pdo->prepare(
        'DELETE FROM materials WHERE id = :id'
    );
    $deleteStmt->execute(['id' => $id]);

    $imagePath = $material['image_path'] ?? '';

    if (strpos($imagePath, '/uploads/covers/') === 0) {
        $coversDir = realpath(__DIR__ . '/../uploads/covers');
        $filePath = realpath(__DIR__ . '/..' . $imagePath);

        if (
            $coversDir &&
            $filePath &&
            strpos(
                $filePath,
                $coversDir . DIRECTORY_SEPARATOR
            ) === 0 &&
            is_file($filePath)
        ) {
            unlink($filePath);
        }
    }

    echo json_encode([
        'success' => true,
        'message' => 'Материал успешно удалён.'
    ], JSON_UNESCAPED_UNICODE);

} catch (PDOException $error) {
    error_log('Ошибка удаления материала: ' . $error->getMessage());

    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Не удалось удалить материал из базы данных.'
    ], JSON_UNESCAPED_UNICODE);
}
