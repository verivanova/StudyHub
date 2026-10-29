<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

if (!isset($_SESSION['admin_id'])) {
    http_response_code(403);
    header('Content-Type: application/json; charset=utf-8');

    echo json_encode([
        'success' => false,
        'message' => 'Доступ разрешён только администратору.'
    ], JSON_UNESCAPED_UNICODE);

    exit;
}
