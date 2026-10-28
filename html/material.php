<?php
require_once __DIR__ . '/../php/db.php';

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id) {
    http_response_code(400);
    exit('Не указан корректный ID материала.');
}

$sql = "
    SELECT
        materials.id,
        materials.title,
        materials.description,
        materials.content,
        materials.features,
        materials.file_path,
        materials.created_at,
        categories.name AS category
    FROM materials
    JOIN categories
        ON materials.category_id = categories.id
    WHERE materials.id = :id
";

$stmt = $pdo->prepare($sql);
$stmt->execute(['id' => $id]);
$material = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$material) {
    http_response_code(404);
    exit('Материал не найден.');
}

function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

$fileSize = null;

if (!empty($material['file_path'])) {
    $fileOnDisk = rtrim($_SERVER['DOCUMENT_ROOT'], '/\\')
        . str_replace('/', DIRECTORY_SEPARATOR, $material['file_path']);

    if (is_file($fileOnDisk)) {
        $fileSize = filesize($fileOnDisk);
    }
}

$fileSizeText = $fileSize !== null
    ? number_format($fileSize / 1024 / 1024, 2) . ' MB'
    : 'Размер неизвестен';

$images = [
    1 => '/pic/studyhub_assets/2026-10-23_17-46-25.png',
    2 => '/pic/2026-10-23_17-48-01.png',
    3 => '/pic/studyhub_assets/2026-10-23_17-51-21.png',
    4 => '/pic/2026-10-23_17-55-52.png',
    5 => '/pic/studyhub_assets/2026-10-23_17-59-33.png',
    6 => '/pic/studyhub_assets/2026-10-23_18-07-15.png'
];

$image = $images[(int)$material['id']] ?? '/pictures/html_css.png';
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($material['title']) ?> — StudyHub</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Unbounded:wght@200..900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://unpkg.com/lenis@1.1.18/dist/lenis.css">
    <link rel="stylesheet" href="/mat.css">
</head>
<body>

    
    <header>
        <div class="header-left">
            <a href="#" class="logo">
                <img src="/pictures/Obrazovatelny_logotip_StudyHub.png"></img>
            </a>
        </div>
        <div class="header-right">
            <button class="btn-login">Войти</button>
        </div>
    </header>

    
    <main class="main">

        
        <div class="breadcrumb">
            <a href="/html/index.html">Главная</a>
            <span class="sep">›</span>
            <a href="/html/index.html"><?= e($material['category']) ?></a>
            <span class="sep">›</span>
            <span class="current"><?= e($material['title']) ?></span>
        </div>

        
        <div class="hero-card">
            <div class="hero-image">
              <img src="<?= e($image) ?>" alt="<?= e($material['title']) ?>">
            </div>
            <div class="hero-content">
                <span class="tag"><?= e($material['category']) ?></span>
                <h1 class="hero-title"><?= e($material['title']) ?></h1>                
                <p class="hero-desc"><?= e($material['description']) ?></p>
                <div class="hero-meta">
                    <div class="meta-item">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                            <polyline points="14 2 14 8 20 8"></polyline>
                        </svg>
                        <p>PDF · <?= e($fileSizeText) ?></p>
                    </div>
                    <div class="meta-item">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                            <line x1="16" y1="2" x2="16" y2="6"></line>
                            <line x1="8" y1="2" x2="8" y2="6"></line>
                            <line x1="3" y1="10" x2="21" y2="10"></line>
                        </svg>
                        <p>Добавлен: <?= date('d.m.Y', strtotime($material['created_at'])) ?></p>
                    </div>
                    <div class="meta-item">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"></path>
                            <polyline points="15 3 21 3 21 9"></polyline>
                            <line x1="10" y1="14" x2="21" y2="3"></line>
                        </svg>
                        <p><?php if (!empty($material['file_path'])): ?>
                            PDF-документ
                        <?php else: ?>
                            Файл не добавлен
                        <?php endif; ?></p>
                    </div>
                </div>
                <?php if (!empty($material['file_path'])): ?>
                    <a class="ssylka"
                      href="<?= e($material['file_path']) ?>"
                      target="_blank"
                      rel="noopener noreferrer">
                        <button class="btn-primary">
                            Перейти к материалу
                            <svg viewBox="0 0 24 24" fill="none"
                                stroke="currentColor" stroke-width="2"
                                stroke-linecap="round" stroke-linejoin="round">
                                <path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"></path>
                                <polyline points="15 3 21 3 21 9"></polyline>
                                <line x1="10" y1="14" x2="21" y2="3"></line>
                            </svg>
                        </button>
                    </a>
                <?php else: ?>
                    <p>Файл для этого материала пока не добавлен.</p>
                <?php endif; ?>
            </div>
        </div>

        
        <div class="stats-bar">
            <div class="stat-item">
                <div class="stat-icon category">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"></path>
                    </svg>
                </div>
                <div>
                    <div class="stat-label">Категория</div>
                    <div class="stat-value"><?= e($material['category']) ?></div>
                </div>
            </div>
            <div class="stat-item">
                <div class="stat-icon level">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="22 12 18 12 15 21 9 3 6 12 2 12"></polyline>
                    </svg>
                </div>
                <div>
                    <div class="stat-label">Уровень сложности</div>
                    <div class="stat-value">Начальный</div>
                </div>
            </div>
            <div class="stat-item">
                <div class="stat-icon format">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                        <polyline points="14 2 14 8 20 8"></polyline>
                    </svg>
                </div>
                <div>
                    <div class="stat-label">Формат</div>
                    <div class="stat-value">PDF</div>
                </div>
            </div>
            <div class="stat-item">
                <div class="stat-icon rating">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="currentColor" stroke="currentColor" stroke-width="1">
                        <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon>
                    </svg>
                </div>
                <div>
                    <div class="stat-label">Рейтинг</div>
                    <div class="stat-value">4.8 (12 оценок)</div>
                </div>
            </div>
        </div>

        
        <div class="section">
            <h2 class="section-title">Описание</h2>
            <p class="section-text">
              <?= nl2br(e($material['description'])) ?>
            </p>
        </div>

        
        <div class="section">
            <h2 class="section-title">Что входит в материал</h2>
            <ul class="feature-list">
                <?php
                $features = json_decode($material['features'] ?? '[]', true);

                if (is_array($features)):
                    foreach ($features as $feature):
                ?>
                    <li><?= e($feature) ?></li>
                <?php
                    endforeach;
                endif;
                ?>
            </ul>
        </div>
        
        <div class="tip-box">
            <div class="tip-icon"></div>
            <div class="tip-content">
                <h3>Полезный совет</h3>
                <p>Рекомендуем сначала изучить основы HTML, а затем переходить к CSS. Это поможет лучше понять структуру и логику веб-страниц.</p>
            </div>
        </div>

        
        <a href="/html/index.html" class="back-link">
            ← Назад к списку материалов
        </a>

    </main>
    <script src="https://unpkg.com/lenis@1.1.18/dist/lenis.min.js"></script>
    <script src="/js/main.js"></script>
</body>
</html>