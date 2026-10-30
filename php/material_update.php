
<?php
require_once __DIR__ . '/admin_guard.php';
require_once __DIR__ . '/db.php';

header('Content-Type: application/json; charset=utf-8');

function respond(int $status, array $data): void
{
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function removeUploadedFile(?string $path): void
{
    if (!$path || !str_starts_with($path, '/uploads/')) {
        return;
    }

    $file = dirname(__DIR__) . $path;

    if (is_file($file)) {
        unlink($file);
    }
}

function saveUpload(
    array $file,
    string $directory,
    array $allowedTypes,
    int $maxSize
): string {
    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Ошибка загрузки файла.');
    }

    if ($file['size'] > $maxSize) {
        throw new RuntimeException('Файл превышает допустимый размер.');
    }

    $extension = strtolower(
        pathinfo($file['name'], PATHINFO_EXTENSION)
    );

    if (!isset($allowedTypes[$extension])) {
        throw new RuntimeException('Недопустимый формат файла.');
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($file['tmp_name']);

    if (!in_array($mime, $allowedTypes[$extension], true)) {
        throw new RuntimeException('Содержимое файла не соответствует формату.');
    }

    if (!is_dir($directory) &&
        !mkdir($directory, 0755, true) &&
        !is_dir($directory)) {
        throw new RuntimeException('Не удалось подготовить папку загрузок.');
    }

    $filename = bin2hex(random_bytes(16)) . '.' . $extension;

    if (!move_uploaded_file(
        $file['tmp_name'],
        $directory . $filename
    )) {
        throw new RuntimeException('Не удалось сохранить файл.');
    }

    return $filename;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(405, [
        'success' => false,
        'message' => 'Разрешён только POST-запрос.'
    ]);
}

$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
$title = trim($_POST['title'] ?? '');
$description = trim($_POST['description'] ?? '');
$content = trim($_POST['content'] ?? '');
$featuresText = trim($_POST['features'] ?? '');
$difficulty = trim($_POST['difficulty'] ?? '');
$tip = trim($_POST['tip'] ?? '');
$materialUrl = trim($_POST['material_url'] ?? '');

$categoryId = filter_input(
    INPUT_POST,
    'category_id',
    FILTER_VALIDATE_INT
);

if (!$id || $title === '' || $description === '' || !$categoryId) {
    respond(400, [
        'success' => false,
        'message' => 'Заполни название, описание и категорию.'
    ]);
}

if (!in_array($difficulty, [
    '',
    'Начальный',
    'Средний',
    'Продвинутый'
], true)) {
    respond(400, [
        'success' => false,
        'message' => 'Выбрана неизвестная сложность.'
    ]);
}

$featuresArray = array_values(array_filter(
    array_map('trim', preg_split('/\r\n|\r|\n/', $featuresText)),
    static fn($item) => $item !== ''
));

$featuresJson = json_encode(
    $featuresArray,
    JSON_UNESCAPED_UNICODE
);

if ($featuresJson === false) {
    respond(400, [
        'success' => false,
        'message' => 'Не удалось обработать список тем.'
    ]);
}

$check = $pdo->prepare(
    'SELECT id, image_path, file_path FROM materials WHERE id = ?'
);
$check->execute([$id]);
$material = $check->fetch(PDO::FETCH_ASSOC);

if (!$material) {
    respond(404, [
        'success' => false,
        'message' => 'Материал не найден.'
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

$materialFile = $_FILES['material_file'] ?? null;
$hasMaterialFile = $materialFile &&
    $materialFile['error'] !== UPLOAD_ERR_NO_FILE;

$hasMaterialUrl = $materialUrl !== '';

if ($hasMaterialFile && $hasMaterialUrl) {
    respond(400, [
        'success' => false,
        'message' => 'Выбери файл или ссылку, но не оба варианта.'
    ]);
}

if ($hasMaterialUrl) {
    $urlParts = parse_url($materialUrl);

    if (
        filter_var($materialUrl, FILTER_VALIDATE_URL) === false ||
        !isset($urlParts['scheme']) ||
        strtolower($urlParts['scheme']) !== 'https' ||
        empty($urlParts['host'])
    ) {
        respond(400, [
            'success' => false,
            'message' => 'Укажи корректную ссылку, начинающуюся с https://.'
        ]);
    }
}

$imagePath = $material['image_path'];
$filePath = $material['file_path'];
$newImageUploaded = false;
$newMaterialUploaded = false;

try {
    if (
        isset($_FILES['cover']) &&
        $_FILES['cover']['error'] !== UPLOAD_ERR_NO_FILE
    ) {
        $imageFilename = saveUpload(
            $_FILES['cover'],
            dirname(__DIR__) . '/uploads/covers/',
            [
                'jpg' => ['image/jpeg'],
                'jpeg' => ['image/jpeg'],
                'png' => ['image/png'],
                'webp' => ['image/webp']
            ],
            4 * 1024 * 1024
        );

        $imagePath = '/uploads/covers/' . $imageFilename;
        $newImageUploaded = true;
    }

    if ($hasMaterialFile) {
        $materialFilename = saveUpload(
            $materialFile,
            dirname(__DIR__) . '/uploads/materials/',
            [
                'pdf' => ['application/pdf'],
                'ppt' => ['application/vnd.ms-powerpoint'],
                'pptx' => [
                    'application/vnd.openxmlformats-officedocument.presentationml.presentation'
                ]
            ],
            15 * 1024 * 1024
        );

        $filePath = '/uploads/materials/' . $materialFilename;
        $newMaterialUploaded = true;
    } elseif ($hasMaterialUrl) {
        $filePath = $materialUrl;
    }

    $stmt = $pdo->prepare(
        'UPDATE materials
         SET title = :title,
             description = :description,
             content = :content,
             features = :features,
             category_id = :category_id,
             image_path = :image_path,
             file_path = :file_path,
             difficulty = :difficulty,
             tip = :tip
         WHERE id = :id'
    );

    $stmt->execute([
        'title' => $title,
        'description' => $description,
        'content' => $content !== '' ? $content : null,
        'features' => $featuresJson,
        'category_id' => $categoryId,
        'image_path' => $imagePath,
        'file_path' => $filePath,
        'difficulty' => $difficulty !== '' ? $difficulty : null,
        'tip' => $tip !== '' ? $tip : null,
        'id' => $id
    ]);

    if ($newImageUploaded) {
        removeUploadedFile($material['image_path']);
    }

    if ($newMaterialUploaded ||
        ($hasMaterialUrl && $filePath !== $material['file_path'])) {
        removeUploadedFile($material['file_path']);
    }

    respond(200, [
        'success' => true,
        'id' => $id,
        'image_path' => $imagePath,
        'file_path' => $filePath,
        'message' => 'Материал обновлён.'
    ]);

} catch (Throwable $e) {
    if ($newImageUploaded) {
        removeUploadedFile($imagePath);
    }

    if ($newMaterialUploaded) {
        removeUploadedFile($filePath);
    }

    error_log($e->getMessage());

    respond(500, [
        'success' => false,
        'message' => $e instanceof RuntimeException
            ? $e->getMessage()
            : 'Не удалось обновить материал.'
    ]);
}
