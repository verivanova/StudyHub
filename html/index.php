<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$isAdmin = isset($_SESSION['admin_id']);

require_once __DIR__ . '/../php/db.php';

$categoriesStmt = $pdo->query("
    SELECT id, name
    FROM categories
    ORDER BY id
");
$categories = $categoriesStmt->fetchAll(PDO::FETCH_ASSOC);

if (isset($_GET['ajax']) && $_GET['ajax'] === 'search') {
    header('Content-Type: application/json; charset=utf-8');

    $q = trim($_GET['q'] ?? '');

    if (mb_strlen($q) < 2) {
        echo json_encode([], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $searchStmt = $pdo->prepare("
        SELECT id, title
        FROM materials
        WHERE title LIKE :q
          OR description LIKE :q
        ORDER BY title
        LIMIT 6
    ");

    $searchStmt->execute([
        'q' => '%' . $q . '%'
    ]);

    echo json_encode(
        $searchStmt->fetchAll(PDO::FETCH_ASSOC),
        JSON_UNESCAPED_UNICODE
    );
    exit;
}

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
        COUNT(material_ratings.id) AS rating_count,
        COALESCE(AVG(material_ratings.rating), 0) AS average_rating,

        (
            (
                COUNT(material_ratings.id) /
                (COUNT(material_ratings.id) + 3)
            ) * COALESCE(AVG(material_ratings.rating), 0)
            +
            (
                3 / (COUNT(material_ratings.id) + 3)
            ) * COALESCE(rating_stats.global_average, 0)
        ) AS popularity_score

    FROM materials

    JOIN categories
        ON materials.category_id = categories.id

    LEFT JOIN material_ratings
        ON material_ratings.material_id = materials.id

    CROSS JOIN (
        SELECT AVG(rating) AS global_average
        FROM material_ratings
    ) AS rating_stats

    GROUP BY
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
        categories.name,
        rating_stats.global_average

		ORDER BY
    		CASE
        WHEN COUNT(material_ratings.id) > 0 THEN 1
        ELSE 0
    		END DESC,
    		popularity_score DESC,
    		rating_count DESC,
    		materials.id DESC

    LIMIT 6
";

$stmt = $pdo->query($sql);
$materials = $stmt->fetchAll(PDO::FETCH_ASSOC);

function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@200..800&display=swap" rel="stylesheet">
  <link
    href="https://fonts.googleapis.com/css2?family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&family=Manrope:wght@200..800&display=swap"
    rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Unbounded:wght@200..900&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://unpkg.com/lenis@1.1.18/dist/lenis.css">
  <link rel="stylesheet" href="/style.css">
  <link rel="stylesheet" href="/html/materials.css">
  <title>StudyHub</title>
</head>

<body>
  <header>
    <div class="header-inner">

      <div class="logo-wrapper">
    <span class="logo-orbit orbit-one"></span>
    <span class="logo-orbit orbit-two"></span>

    <a href="/html/index.php" class="header-logo">
        <p>Study<span>Hub</span></p>
    </a>
</div>

      <nav class="header-nav">
        <ul class="header-list">
          <li><a href="#" class="header-link">Главная</a></li>
          <li><a href="/html/materials.php" class="header-link">Материалы</a></li>
          <li><a href="#categories" class="header-link">Категории</a></li>
        </ul>
      </nav>

      <div class="header-right">

        <?php if ($isAdmin): ?>
            <span class="admin-status">Режим администратора</span>
            <form action="/php/logout.php" method="post">
                <button type="submit" class="second-btn">Выйти</button>
            </form>
        <?php else: ?>
            <button class="second-btn"
                    onclick="window.location.href='/html/admin-page.php'">
                Вход для администратора
            </button>
        <?php endif; ?>

      </div>

    </div>
  </header>

  <main>
    <div class="hero-section">
      <h1 class="section-title">Учебные материалы для твоего успеха</h1>
      <p class="second-text">Конспекты, учебники, видеоуроки и многое другое — всё, что нужно для учёбы, в одном месте
      </p>
      
      <div class="search-block" id="search-box">
    <svg xmlns="http://www.w3.org/2000/svg"
      width="20"
      height="20"
      fill="currentColor"
      viewBox="0 0 16 16"
      aria-hidden="true">
        <path d="M11.742 10.344a6.5 6.5 0 1 0-1.397 1.398h-.001q.044.06.098.115l3.85 3.85a1 1 0 0 0 1.415-1.414l-3.85-3.85a1 1 0 0 0-.115-.1zM12 6.5a5.5 5.5 0 1 1-11 0 5.5 5.5 0 0 1 11 0"/>
    </svg>

    <input
        type="search"
        class="search-input"
        id="material-search"
        placeholder="Поиск материалов..."
        autocomplete="off"
        aria-label="Поиск учебных материалов"
        aria-controls="search-suggestions"
        aria-expanded="false"
    >

    <button class="main-btn" id="search-button" type="button">
        Найти
    </button>

    <div class="search-suggestions"
      id="search-suggestions"
      role="listbox"
      hidden>
    </div>
      </div>
    </div>


<div class="categories-section section" id="categories">
    <p class="section-title title">Категории</p>
    
    <div class="categories-list">

        <a href="/html/materials.php?category=Программирование" class="categories-card">
            <img src="/pictures/programming.jpg" alt="Иконка программирования.Изображение с Pinterest, автор неизвестен, учебное использование">
            <p>Программирование</p>
            <p class="second-text">Материалы по программированию</p>
        </a>

        <a href="/html/materials.php?category=Математика" class="categories-card">
            <img src="/pictures/math.jpg" alt="Иконка математики. Изображение с Pinterest, автор неизвестен, учебное использование">
            <p>Математика</p>
            <p class="second-text">Материалы по математике</p>
        </a>

        <a href="/html/materials.php?category=Иностранные%20языки" class="categories-card">
            <img src="/pictures/languages.jpg" alt="Иконка иностранных языков. Изображение с Pinterest, автор неизвестен, учебное использование">
            <p>Иностранные языки</p>
            <p class="second-text">Материалы по языкам</p>
        </a>

        <a href="/html/materials.php?category=Экономика" class="categories-card">
            <img src="/pictures/economics.jpg" alt="Иконка экономики. Изображение с Pinterest, автор неизвестен, учебное использование">
            <p>Экономика</p>
            <p class="second-text">Материалы по экономике</p>
        </a>

        <a href="/html/materials.php?category=Другое" class="categories-card">
            <img src="/pictures/others.jpg" alt="Иконка других материалов. Изображение с Pinterest, автор неизвестен, учебное использование">
            <p>Другое</p>
            <p class="second-text">Другие учебные материалы</p>
        </a>

    </div>
</div>


    <div class="popular-section section">
      <div class="popular-title">
        <p class="section-title title">Самые популярные материалы</p>
        <a href="/html/materials.php">Все материалы <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-arrow-right arrow-right" viewBox="0 0 16 16">
  <path fill-rule="evenodd" d="M1 8a.5.5 0 0 1 .5-.5h11.793l-3.147-3.146a.5.5 0 0 1 .708-.708l4 4a.5.5 0 0 1 0 .708l-4 4a.5.5 0 0 1-.708-.708L13.293 8.5H1.5A.5.5 0 0 1 1 8"/>
</svg></a>
      </div>

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
                <input
                    id="material-title"
                    name="title"
                    type="text"
                    maxlength="200"
                    required
                >

                <label for="material-description">Краткое описание</label>
                <textarea
                    id="material-description"
                    name="description"
                    rows="3"
                    required
                ></textarea>

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
                <textarea
                    id="material-content"
                    name="content"
                    rows="4"
                ></textarea>

                <label for="material-features">
                    Что изучит пользователь (необязательно)
                </label>
                <textarea
                    id="material-features"
                    name="features"
                    rows="3"
                ></textarea>

                <label for="material-tip">
                    Полезный совет (необязательно)
                </label>
                <textarea
                    id="material-tip"
                    name="tip"
                    rows="3"
                ></textarea>

                <label for="material-file">
                    Учебный файл (PDF, PPT, PPTX)
                </label>
                <input
                    id="material-file"
                    name="material_file"
                    type="file"
                    accept=".pdf,.ppt,.pptx,application/pdf,application/vnd.ms-powerpoint,application/vnd.openxmlformats-officedocument.presentationml.presentation"
                >

                <label for="material-url">
                    Или ссылка на материал
                </label>
                <input
                    id="material-url"
                    name="material_url"
                    type="url"
                    placeholder="https://example.com/material"
                >

                <p class="second-text">
                    Можно добавить файл или ссылку. Одновременно оба варианта не используются.
                </p>

                <label for="material-cover">
                    Обложка (JPG, PNG или WebP, до 4 МБ)
                </label>
                <input
                    id="material-cover"
                    name="cover"
                    type="file"
                    accept="image/jpeg,image/png,image/webp"
                >

                <p id="material-form-message" role="status"></p>

                <button
                    type="submit"
                    class="main-btn"
                    id="save-material-button"
                >
                    Сохранить материал
                </button>

            </form>

        </div>

    </div>

<?php endif; ?>


    <div class="popular-list">
    <?php
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

<?php foreach ($materials as $index => $material): ?>
    <div class="popular-card">
        <img
            src="<?= e(!empty($material['image_path'])
                ? $material['image_path']
                : ($images[(int)$material['id']] ?? '/pic/studyhub_assets/2026-10-23_17-46-25.png')) ?>"
            alt="<?= e($material['title']) ?>"
        >

        <p class="category-icon <?= e($categoryClasses[$material['category']] ?? 'other') ?>">
            <?= e($material['category']) ?>
        </p>

        <p><?= e($material['title']) ?></p>
        <div class="popular-rating">
    <?php if ((int)$material['rating_count'] > 0): ?>
        <span class="popular-rating-stars">★</span>

        <strong>
            <?= number_format(
                (float)$material['average_rating'],
                1,
                ',',
                ''
            ) ?>/5
        </strong>

        <span class="second-text">
            (<?= (int)$material['rating_count'] ?>
            <?= ((int)$material['rating_count'] % 10 === 1 &&
                (int)$material['rating_count'] % 100 !== 11)
                ? 'оценка'
                : (((int)$material['rating_count'] % 10 >= 2 &&
                    (int)$material['rating_count'] % 10 <= 4 &&
                    !((int)$material['rating_count'] % 100 >= 12 &&
                    (int)$material['rating_count'] % 100 <= 14))
                    ? 'оценки'
                    : 'оценок') ?>)
        </span>

    <?php else: ?>
        <span class="second-text">Пока нет оценок</span>
    <?php endif; ?>
</div>

        <p class="second-text">
            <?= e($material['description']) ?>
        </p>

        <button
            onclick="window.location.href='/html/material.php?id=<?= (int)$material['id'] ?>'"
            class="main-btn"
            aria-label="Открыть материал: <?= e($material['title']) ?>"
        ><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-arrow-right arrow-right" viewBox="0 0 16 16">
  <path fill-rule="evenodd" d="M1 8a.5.5 0 0 1 .5-.5h11.793l-3.147-3.146a.5.5 0 0 1 .708-.708l4 4a.5.5 0 0 1 0 .708l-4 4a.5.5 0 0 1-.708-.708L13.293 8.5H1.5A.5.5 0 0 1 1 8"/>
</svg></button>
        <?php if ($isAdmin): ?>
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
            >Редактировать</button>

            <button
                type="button"
                class="admin-card-button delete-material-button"
                data-id="<?= (int)$material['id'] ?>"
                data-title="<?= e($material['title']) ?>"
            >Удалить</button>
        <?php endif; ?>
    </div>
<?php endforeach; ?>
    </div>
</div>

    <div class="about-section">
      <p class="section-title">Начни учиться уже сегодня!</p>
      <p>Сотни полезных материалов ждут тебя. <br> Выбери категорию и найти то, что нужно именно тебе</p>
      <button class="about-btn" onclick="toMaterials()">Перейти к материалам <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-arrow-right arrow-right" viewBox="0 0 16 16">
  <path fill-rule="evenodd" d="M1 8a.5.5 0 0 1 .5-.5h11.793l-3.147-3.146a.5.5 0 0 1 .708-.708l4 4a.5.5 0 0 1 0 .708l-4 4a.5.5 0 0 1-.708-.708L13.293 8.5H1.5A.5.5 0 0 1 1 8"/>
</svg></button>
    </div>
  </main>

  <footer>
    <hr>
    <div class="footer-inner">

      <div class="footer-left">
        <img src="/pic/studyhub_assets/icon-graduation.png" alt="логотип">
        <p>Study<span class="footer-name">Hub</span> <br> <span class="second-text">Твой помощник в учёбе</span></p>
      </div>
      <div class="footer-right">
        <div class="feedback">
          <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-telegram"
            viewBox="0 0 16 16">
            <path
              d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0M8.287 5.906q-1.168.486-4.666 2.01-.567.225-.595.442c-.03.243.275.339.69.47l.175.055c.408.133.958.288 1.243.294q.39.01.868-.32 3.269-2.206 3.374-2.23c.05-.012.12-.026.166.016s.042.12.037.141c-.03.129-1.227 1.241-1.846 1.817-.193.18-.33.307-.358.336a8 8 0 0 1-.188.186c-.38.366-.664.64.015 1.088.327.216.589.393.85.571.284.194.568.387.936.629q.14.092.27.187c.331.236.63.448.997.414.214-.02.435-.22.547-.82.265-1.417.786-4.486.906-5.751a1.4 1.4 0 0 0-.013-.315.34.34 0 0 0-.114-.217.53.53 0 0 0-.31-.093c-.3.005-.763.166-2.984 1.09" />
          </svg>
          <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-whatsapp"
            viewBox="0 0 16 16">
            <path
              d="M13.601 2.326A7.85 7.85 0 0 0 7.994 0C3.627 0 .068 3.558.064 7.926c0 1.399.366 2.76 1.057 3.965L0 16l4.204-1.102a7.9 7.9 0 0 0 3.79.965h.004c4.368 0 7.926-3.558 7.93-7.93A7.9 7.9 0 0 0 13.6 2.326zM7.994 14.521a6.6 6.6 0 0 1-3.356-.92l-.24-.144-2.494.654.666-2.433-.156-.251a6.56 6.56 0 0 1-1.007-3.505c0-3.626 2.957-6.584 6.591-6.584a6.56 6.56 0 0 1 4.66 1.931 6.56 6.56 0 0 1 1.928 4.66c-.004 3.639-2.961 6.592-6.592 6.592m3.615-4.934c-.197-.099-1.17-.578-1.353-.646-.182-.065-.315-.099-.445.099-.133.197-.513.646-.627.775-.114.133-.232.148-.43.05-.197-.1-.836-.308-1.592-.985-.59-.525-.985-1.175-1.103-1.372-.114-.198-.011-.304.088-.403.087-.088.197-.232.296-.346.1-.114.133-.198.198-.33.065-.134.034-.248-.015-.347-.05-.099-.445-1.076-.612-1.47-.16-.389-.323-.335-.445-.34-.114-.007-.247-.007-.38-.007a.73.73 0 0 0-.529.247c-.182.198-.691.677-.691 1.654s.71 1.916.81 2.049c.098.133 1.394 2.132 3.383 2.992.47.205.84.326 1.129.418.475.152.904.129 1.246.08.38-.058 1.171-.48 1.338-.943.164-.464.164-.86.114-.943-.049-.084-.182-.133-.38-.232" />
          </svg>
          <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-envelope"
            viewBox="0 0 16 16">
            <path
              d="M0 4a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2zm2-1a1 1 0 0 0-1 1v.217l7 4.2 7-4.2V4a1 1 0 0 0-1-1zm13 2.383-4.708 2.825L15 11.105zm-.034 6.876-5.64-3.471L8 9.583l-1.326-.795-5.64 3.47A1 1 0 0 0 2 13h12a1 1 0 0 0 .966-.741M1 11.105l4.708-2.897L1 5.383z" />
          </svg>
        </div>
        <p>@2026 StudyHub. <br> Сайт создан исключительно в учебных, некоммерческих целях в рамках производственной практики</p>
      </div>
    </div>
  </footer>

  <script src="https://unpkg.com/lenis@1.1.18/dist/lenis.min.js"></script>
  <script src="/js/main.js"></script>
  <script src="/js/material-create.js"></script>
  <script src="/js/material-delete.js"></script>
  
<script>
(() => {
    const input = document.getElementById('material-search');
    const button = document.getElementById('search-button');
    const list = document.getElementById('search-suggestions');
    const box = document.getElementById('search-box');

    let timer;
    let requestId = 0;

    function hideSuggestions() {
        list.hidden = true;
        input.setAttribute('aria-expanded', 'false');
    }

    function searchAll() {
        const query = input.value.trim();

        window.location.href = '/html/materials.php' +
            (query ? '?q=' + encodeURIComponent(query) : '');
    }

    button.addEventListener('click', searchAll);

    input.addEventListener('keydown', event => {
        if (event.key === 'Enter') {
            event.preventDefault();
            searchAll();
        }

        if (event.key === 'Escape') {
            hideSuggestions();
        }
    });

    input.addEventListener('input', () => {
        clearTimeout(timer);

        const query = input.value.trim();
        const currentId = ++requestId;

        list.replaceChildren();

        if (query.length < 2) {
            hideSuggestions();
            return;
        }

        timer = setTimeout(async () => {
            try {
                const response = await fetch(
                    '/html/index.php?ajax=search&q=' +
                    encodeURIComponent(query)
                );

                if (!response.ok) {
                    throw new Error('Ошибка HTTP: ' + response.status);
                }

                const results = await response.json();

                if (currentId !== requestId) return;

                list.replaceChildren();

                if (results.length === 0) {
                    const empty = document.createElement('div');
                    empty.className = 'suggestion-empty';
                    empty.textContent = 'Ничего не найдено';
                    list.appendChild(empty);
                } else {
                    results.forEach(material => {
                        const link = document.createElement('a');

                        link.className = 'suggestion-item';
                        link.href = '/html/material.php?id=' +
                            encodeURIComponent(material.id);
                        link.textContent = material.title;
                        link.setAttribute('role', 'option');

                        list.appendChild(link);
                    });
                }

                list.hidden = false;
                input.setAttribute('aria-expanded', 'true');
            } catch (error) {
                hideSuggestions();
                console.error('Ошибка поиска:', error);
            }
        }, 200);
    });

    document.addEventListener('click', event => {
        if (!box.contains(event.target)) {
            hideSuggestions();
        }
    });
})();
</script>
</body>
</html>