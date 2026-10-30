<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$isAdmin = isset($_SESSION['admin_id']);

require_once __DIR__ . '/../php/db.php';

function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

$search = trim($_GET['q'] ?? '');
$selectedCategory = trim($_GET['category'] ?? '');

$categoriesStmt = $pdo->query("
    SELECT
        categories.id,
        categories.name,
        COUNT(materials.id) AS material_count
    FROM categories
    LEFT JOIN materials ON materials.category_id = categories.id
    GROUP BY categories.id, categories.name
    ORDER BY categories.id
");

$categories = $categoriesStmt->fetchAll(PDO::FETCH_ASSOC);

$sql = "
    SELECT
        materials.id,
        materials.title,
        materials.description,
        materials.category_id,
        materials.content,
        materials.features,
        materials.difficulty,
        materials.tip,
        materials.file_path,
        materials.image_path,
        categories.name AS category,
        COALESCE(ratings.rating_count, 0) AS rating_count,
        ratings.average_rating
    FROM materials
    JOIN categories ON materials.category_id = categories.id

    LEFT JOIN (
        SELECT
            material_id,
            COUNT(*) AS rating_count,
            AVG(rating) AS average_rating
        FROM material_ratings
        GROUP BY material_id
    ) AS ratings ON ratings.material_id = materials.id

    WHERE 1 = 1
";

$params = [];

if ($search !== '') {
    $sql .= " AND (
        materials.title LIKE :search_title
        OR materials.description LIKE :search_description
    )";

    $params['search_title'] = '%' . $search . '%';
    $params['search_description'] = '%' . $search . '%';
}

if ($selectedCategory !== '') {
    $sql .= " AND categories.name = :category";
    $params['category'] = $selectedCategory;
}

$sql .= " ORDER BY materials.id";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$materials = $stmt->fetchAll(PDO::FETCH_ASSOC);

$images = [
    1 => '/pic/studyhub_assets/2026-10-23_17-46-25.png',
    2 => '/pic/2026-10-23_17-48-01.png',
    3 => '/pic/studyhub_assets/2026-10-23_17-51-21.png',
    4 => '/pic/2026-10-23_17-55-52.png',
    5 => '/pic/studyhub_assets/2026-10-23_17-59-33.png',
    6 => '/pic/studyhub_assets/2026-10-23_18-07-15.png'
];

$categoryClasses = [
    'Программирование' => 'prog',
    'Математика' => 'math',
    'Иностранные языки' => 'lang',
    'Экономика' => 'eco',
    'Другое' => 'other'
];
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Все материалы — StudyHub</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@200..800&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@100..900&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Unbounded:wght@200..900&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="https://unpkg.com/lenis@1.1.18/dist/lenis.css">
    <link rel="stylesheet" href="/style.css">
    <link rel="stylesheet" href="/html/materials.css">

    <style>
        .material-card-rating {
            display: flex;
            align-items: center;
            gap: 8px;
            margin: 12px 0;
            font-size: 14px;
            color: #6b7280;
        }

        .material-card-rating .rating-stars {
            color: #f5b82e;
            font-size: 17px;
            letter-spacing: 1px;
        }

        .material-card-rating .rating-value {
            color: #1a1a2e;
            font-weight: 700;
        }

        .material-card-rating .rating-empty {
            color: #9ca3af;
            font-size: 13px;
        }
    </style>
</head>
<body>

<header>
    <div class="header-inner">
        <a href="/html/index.php" class="header-logo">
            <img src="/pic/Образовательный логотип StudyHub.png" alt="StudyHub">
        </a>

        <nav class="header-nav">
            <ul class="header-list">
                <li><a href="/html/index.php" class="header-link">Главная</a></li>
                <li><a href="/html/materials.php" class="header-link">Материалы</a></li>
                <li><a href="/html/index.php#categories" class="header-link">Категории</a></li>
            </ul>
        </nav>

        <div class="header-right">
            <?php if ($isAdmin): ?>
                <span class="admin-status">Режим администратора</span>
                <form action="/php/logout.php" method="post">
                    <button type="submit" class="second-btn">Выйти</button>
                </form>
            <?php else: ?>
                <button onclick="window.location.href='/html/admin-page.php'" class="second-btn">
                    Вход для администратора
                </button>
            <?php endif; ?>
        </div>
    </div>
</header>

<main class="materials-page">

    <section class="materials-heading">
        <h1 class="section-title">
            <?= $selectedCategory !== '' ? 'Категория: ' . e($selectedCategory) : 'Все учебные материалы' ?>
        </h1>

        <p class="second-text">
            Находи конспекты и учебные материалы для своей учёбы
        </p>
    </section>

    <section class="materials-filters">

        <form action="/html/materials.php" method="get" class="materials-search">
            <input
                type="search"
                name="q"
                value="<?= e($search) ?>"
                placeholder="Поиск по названию и описанию..."
                aria-label="Поиск материалов"
            >

            <?php if ($selectedCategory !== ''): ?>
                <input type="hidden" name="category" value="<?= e($selectedCategory) ?>">
            <?php endif; ?>

            <button type="submit" class="main-btn">Найти</button>
        </form>

        <div class="filter-categories">

            <a
                class="filter-chip <?= $selectedCategory === '' ? 'active' : '' ?>"
                href="/html/materials.php<?= $search !== '' ? '?q=' . urlencode($search) : '' ?>"
            >Все материалы</a>

            <?php foreach ($categories as $category): ?>
                <a
                    class="filter-chip <?= $selectedCategory === $category['name'] ? 'active' : '' ?>"
                    href="/html/materials.php?category=<?= urlencode($category['name']) ?><?= $search !== '' ? '&q=' . urlencode($search) : '' ?>"
                >
                    <?= e($category['name']) ?>
                    <span><?= (int)$category['material_count'] ?></span>
                </a>
            <?php endforeach; ?>

        </div>
    </section>

    <section class="materials-results">

        <div class="materials-results-heading">
            <h2 class="section-title">Материалы</h2>
            <p class="second-text">Найдено: <?= count($materials) ?></p>
        </div>

        <?php if ($isAdmin): ?>
            <div class="admin-add-section">
                <button type="button" class="main-btn" id="open-material-form">
                    + Добавить материал
                </button>
            </div>
        <?php endif; ?>

        <?php if ($materials): ?>

            <div class="popular-list materials-list">

                <?php foreach ($materials as $material): ?>

                    <?php
                    $ratingCount = (int)$material['rating_count'];

                    $averageRating = $ratingCount > 0
                        ? round((float)$material['average_rating'], 1)
                        : 0;
                    ?>

                    <article class="popular-card">

                        <img
                            src="<?= e(!empty($material['image_path'])
                                ? $material['image_path']
                                : ($images[(int)$material['id']] ?? '/pic/studyhub_assets/2026-10-23_17-46-25.png')) ?>"
                            alt="<?= e($material['title']) ?>"
                        >

                        <p class="category-icon <?= e($categoryClasses[$material['category']] ?? 'other') ?>">
                            <?= e($material['category']) ?>
                        </p>

                        <h3><?= e($material['title']) ?></h3>

                        <p class="second-text">
                            <?= e($material['description']) ?>
                        </p>

                        <div class="material-card-rating">

                            <?php if ($ratingCount > 0): ?>

                                <span class="rating-stars" aria-label="Средняя оценка">
                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                        <?= $i <= round($averageRating) ? '★' : '☆' ?>
                                    <?php endfor; ?>
                                </span>

                                <span class="rating-value">
                                    <?= e($averageRating) ?>/5
                                </span>

                                <span>
                                    (<?= $ratingCount ?>)
                                </span>

                            <?php else: ?>

                                <span class="rating-empty">
                                    ☆☆☆☆☆ Пока нет оценок
                                </span>

                            <?php endif; ?>

                        </div>

                        <a
                            class="main-btn material-open"
                            href="/html/material.php?id=<?= (int)$material['id'] ?>"
                        >
                            <?= !empty($material['file_path']) ? 'Открыть материал →' : 'Подробнее →' ?>
                        </a>

                        <?php if ($isAdmin): ?>

                            <div class="admin-card-actions">

                                <button
                                    type="button"
                                    class="admin-card-button edit-material-button"
                                    data-id="<?= (int)$material['id'] ?>"
                                    data-title="<?= e($material['title']) ?>"
                                    data-description="<?= e($material['description']) ?>"
                                    data-category-id="<?= (int)$material['category_id'] ?>"
                                    data-content="<?= e($material['content'] ?? '') ?>"
                                    data-features="<?= e($material['features'] ?? '') ?>"
                                    data-difficulty="<?= e($material['difficulty'] ?? '') ?>"
                                    data-tip="<?= e($material['tip'] ?? '') ?>"
                                    data-file-path="<?= e($material['file_path'] ?? '') ?>"
                                >
                                    Редактировать
                                </button>

                                <button
                                    type="button"
                                    class="admin-card-button delete-material-button"
                                    data-id="<?= (int)$material['id'] ?>"
                                    data-title="<?= e($material['title']) ?>"
                                >
                                    Удалить
                                </button>

                            </div>

                        <?php endif; ?>

                    </article>

                <?php endforeach; ?>

            </div>

        <?php else: ?>

            <div class="materials-empty">
                <h3>Материалы не найдены</h3>

                <p class="second-text">
                    Попробуй изменить поисковый запрос или выбрать другую категорию.
                </p>

                <a class="main-btn" href="/html/materials.php">
                    Сбросить фильтры
                </a>
            </div>

        <?php endif; ?>

        <?php if ($isAdmin): ?>

            <div id="material-modal" class="material-modal" hidden>

                <div class="material-modal-content">

                    <button type="button" id="close-material-form" class="modal-close">
                        ✕
                    </button>

                    <h2 id="material-form-title">Добавить материал</h2>

                    <form id="material-create-form" enctype="multipart/form-data">

                        <input type="hidden" id="material-id" name="id" value="">

                        <label for="material-title">Название</label>
                        <input id="material-title" name="title" type="text"
                               maxlength="200" required>

                        <label for="material-description">Краткое описание</label>
                        <textarea id="material-description" name="description"
                                  rows="3" required></textarea>

                        <label for="material-category">Категория</label>
                        <select id="material-category" name="category_id" required>
                            <option value="">Выбери категорию</option>

                            <?php foreach ($categories as $category): ?>
                                <option value="<?= (int)$category['id'] ?>">
                                    <?= e($category['name']) ?>
                                </option>
                            <?php endforeach; ?>

                        </select>

                        <label for="material-difficulty">Уровень сложности</label>
                        <select id="material-difficulty" name="difficulty">
                            <option value="">Не указан</option>
                            <option value="Начальный">Начальный</option>
                            <option value="Средний">Средний</option>
                            <option value="Продвинутый">Продвинутый</option>
                        </select>

                        <label for="material-content">Содержание (необязательно)</label>
                        <textarea id="material-content" name="content" rows="4"></textarea>

                        <label for="material-features">Что изучит пользователь (необязательно)</label>
                        <textarea id="material-features" name="features" rows="3"></textarea>

                        <label for="material-tip">Полезный совет (необязательно)</label>
                        <textarea id="material-tip" name="tip" rows="3"></textarea>

                        <label for="material-file">Учебный файл (PDF, PPT, PPTX)</label>
                        <input
                            id="material-file"
                            name="material_file"
                            type="file"
                            accept=".pdf,.ppt,.pptx,application/pdf,application/vnd.ms-powerpoint,application/vnd.openxmlformats-officedocument.presentationml.presentation"
                        >

                        <label for="material-url">Или ссылка на материал</label>
                        <input
                            id="material-url"
                            name="material_url"
                            type="url"
                            placeholder="https://example.com/material"
                        >

                        <p class="second-text">
                            Можно добавить файл или ссылку. Одновременно оба варианта не используются.
                        </p>

                        <label for="material-cover">Обложка (JPG, PNG или WebP, до 4 МБ)</label>
                        <input
                            id="material-cover"
                            name="cover"
                            type="file"
                            accept="image/jpeg,image/png,image/webp"
                        >

                        <p id="material-form-message" role="status"></p>

                        <button type="submit" class="main-btn" id="save-material-button">
                            Сохранить материал
                        </button>

                    </form>

                </div>

            </div>

        <?php endif; ?>

    </section>

</main>

<footer>
    <hr>

    <div class="footer-inner">

        <div class="footer-left">
            <img src="/pic/studyhub_assets/icon-graduation.png" alt="Логотип">

            <p>
                Study<span class="footer-name">Hub</span><br>
                <span class="second-text">Твой помощник в учёбе</span>
            </p>
        </div>

        <div class="footer-right">
            <p>@2026 StudyHub. Все права защищены</p>
        </div>

    </div>
</footer>

<script src="https://unpkg.com/lenis@1.1.18/dist/lenis.min.js"></script>
<script src="/js/main.js"></script>
<script src="/js/material-create.js"></script>
<script src="/js/material-delete.js"></script>

</body>
</html>
