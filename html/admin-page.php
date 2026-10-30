<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>StudyHub</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Unbounded:wght@200..900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/mat.css">
</head>
<body>
    <main>
        <div class="logo-center">
            <a href="/html/index.php" class="logo">
                <img src="/pictures/Obrazovatelny_logotip_StudyHub.png"></img>
            </a>
        </div>
        <div class="aut-center">
            <div class="aut-block">
                <p class="section-title">Авторизация</p>
                <form class="user-form" id="userForm">
                    <label>
                        <p>Логин</p>
                        <input type="text" id="login" required>
                    </label>
                    <label>
                        <p>Пароль</p>  
                        <input type="password" id="pass" required>
                    </label>
                    <button type="submit" class="btn-vhod">Войти</button>
                </form>
            </div>
        </div>
    </main>

    <script src="/js/auth.js"></script>
</body>
</html>
