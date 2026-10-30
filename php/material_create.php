
<?php
require_once __DIR__ . '/admin_guard.php';
require_once __DIR__ . '/db.php';

header('Content-Type: application/json; charset=utf-8');

function respond(int $status, array $data): void {
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(405, [
        'success' => false,
        'message' => 'Разрешён только POST-запрос.'
    ]);
}

$title = trim($_POST['title'] ?? '');
$description = trim($_POST['description'] ?? '');
$content = trim($_POST['content'] ?? '');
$features = trim($_POST['features'] ?? '');
$categoryId = filter_input(INPUT_POST, 'category_id', FILTER_VALIDATE_INT);

if ($title === '' || $description === '' || !$categoryId) {
    respond(400, [
        'success' => false,
        'message' => 'Заполни название, описание и категорию.'
    ]);
}

$checkCategory = $pdo->prepare(
    'SELECT id FROM categories WHERE id = ?'
);
$checkCategory->execute([$categoryId]);

if (!$checkCategory->fetch()) {
    respond(400, [
        'success' => false,
        'message' => 'Выбрана неизвестная категория.'
    ]);
}

$imagePath = null;

if (isset($_FILES['cover']) && $_FILES['cover']['error'] !== UPLOAD_ERR_NO_FILE) {
    $file = $_FILES['cover'];

    if ($file['error'] !== UPLOAD_ERR_OK) {
        respond(400, [
            'success' => false,
            'message' => 'Не удалось загрузить изображение.'
        ]);
    }

    if ($file['size'] > 4 * 1024 * 1024) {
        respond(400, [
            'success' => false,
            'message' => 'Размер изображения не должен превышать 4 МБ.'
        ]);
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($file['tmp_name']);

    $allowedTypes = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp'
    ];

    if (!isset($allowedTypes[$mime]) ||
        getimagesize($file['tmp_name']) === false) {
        respond(400, [
            'success' => false,
            'message' => 'Выбери изображение JPG, PNG или WebP.'
        ]);
    }

    $uploadDir = dirname(__DIR__) . '/uploads/covers/';

    if (!is_dir($uploadDir) &&
        !mkdir($uploadDir, 0755, true) &&
        !is_dir($uploadDir)) {
        respond(500, [
            'success' => false,
            'message' => 'Не удалось подготовить папку загрузок.'
        ]);
    }

    $filename = bin2hex(random_bytes(16))
        . '.' . $allowedTypes[$mime];

    if (!move_uploaded_file($file['tmp_name'], $uploadDir . $filename)) {
        respond(500, [
            'success' => false,
            'message' => 'Не удалось сохранить изображение.'
        ]);
    }

    $imagePath = '/uploads/covers/' . $filename;
}

try {
    $stmt = $pdo->prepare(
        'INSERT INTO materials
        (title, description, content, features, category_id, image_path)
        VALUES
        (:title, :description, :content, :features, :category_id, :image_path)'
    );

    $stmt->execute([
        'title' => $title,
        'description' => $description,
        'content' => $content !== '' ? $content : null,
        'features' => $features !== '' ? $features : null,
        'category_id' => $categoryId,
        'image_path' => $imagePath
    ]);

    respond(201, [
        'success' => true,
        'id' => (int)$pdo->lastInsertId(),
        'image_path' => $imagePath,
        'message' => 'Материал добавлен.'
    ]);
} catch (Throwable $e) {
    if ($imagePath !== null) {
        $savedFile = dirname(__DIR__) . $imagePath;
        if (is_file($savedFile)) {
            unlink($savedFile);
        }
    }

    error_log($e->getMessage());

    respond(500, [
        'success' => false,
        'message' => 'Не удалось сохранить материал в базе данных.'
    ]);
}
