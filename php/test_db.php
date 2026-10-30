<?php

require_once __DIR__ . '/db.php';

try {
    $tables = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);

    echo 'Подключение успешно!<br>';
    echo 'Таблицы: ' . htmlspecialchars(
        implode(', ', $tables),
        ENT_QUOTES,
        'UTF-8'
    );
} catch (PDOException $e) {
    error_log($e->getMessage());
    http_response_code(500);
    exit('Не удалось проверить таблицы.');
}
