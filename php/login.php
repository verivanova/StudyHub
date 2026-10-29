
<?php
session_start();
require_once __DIR__ . '/db.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Метод запроса не поддерживается.'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$login = trim($_POST['login'] ?? '');
$password = $_POST['password'] ?? '';

if ($login === '' || $password === '') {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Введи логин и пароль.'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$stmt = $pdo->prepare(
    'SELECT id, login, password_hash FROM admins WHERE login = :login LIMIT 1'
);
$stmt->execute(['login' => $login]);
$admin = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$admin || !password_verify($password, $admin['password_hash'])) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'Неверный логин или пароль.'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

session_regenerate_id(true);

$_SESSION['admin_id'] = (int) $admin['id'];
$_SESSION['admin_login'] = $admin['login'];

echo json_encode([
    'success' => true,
    'redirect' => '/html/index.php'
], JSON_UNESCAPED_UNICODE);