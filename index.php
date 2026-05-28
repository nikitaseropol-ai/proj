<?php
require_once 'config.php'; // содержит подключение к БД и функции

// Обработка обычного POST (fallback)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_SERVER['HTTP_X_REQUESTED_WITH'])) {
    // упрощённый fallback – выведем сообщение и покажем форму с ошибкой
    $fallbackError = "Включите JavaScript для удобной отправки формы.";
}

// Данные котиков (id, имя, фото (эмодзи или ссылка), описание, характер, цена)
$cats = [
    1 => ['id'=>1, 'name'=>'Мурзик', 'photo'=>'🐱', 'price'=>500, 'character'=>'Ласковый и игривый', 'description'=>'Обожает сидеть на руках и мурлыкать. Любит игрушки-мышки.', 'color'=>'рыжий'],
    2 => ['id'=>2, 'name'=>'Снежок', 'photo'=>'🐈‍⬛', 'price'=>450, 'character'=>'Спокойный и пушистый', 'description'=>'Белый красавец, любит спать на подушках. Очень фотогеничный.', 'color'=>'белый'],
    3 => ['id'=>3, 'name'=>'Басик', 'photo'=>'🐆', 'price'=>550, 'character'=>'Энергичный и умный', 'description'=>'Черный котик, любит охотиться за лазерной указкой.', 'color'=>'чёрный'],
    4 => ['id'=>4, 'name'=>'Маркиза', 'photo'=>'🐯', 'price'=>600, 'character'=>'Грациозная и нежная', 'description'=>'Полосатая кошечка, обожает почёсывания за ушком.', 'color'=>'полосатая'],
    5 => ['id'=>5, 'name'=>'Пухляш', 'photo'=>'🐈', 'price'=>400, 'character'=>'Соня и лакомка', 'description'=>'Рыжий толстячок, любит покушать и поспать на солнышке.', 'color'=>'рыжий'],
    6 => ['id'=>6, 'name'=>'Бусинка', 'photo'=>'🐾', 'price'=>650, 'character'=>'Общительная и активная', 'description'=>'Чёрно-белая кошечка, любит играть с детьми.', 'color'=>'чёрно-белый']
];

// Загрузка данных пользователя, если авторизован
$userData = null;
if (isset($_SESSION['user_id'])) {
    $stmt = $mysqli->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $userData = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    if ($userData) {
        $stmt = $mysqli->prepare("SELECT * FROM rentals WHERE user_id = ?");
        $stmt->bind_param("i", $_SESSION['user_id']);
        $stmt->execute();
        $rental = $stmt->get_result()->fetch_assoc();
        $userData = array_merge($userData, $rental ?: []);
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Котики на прокат – аренда пушистых друзей</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', 'Poppins', sans-serif;
            background: #fef9e6;
            color: #3e2a1f;
        }
        /* Header */
        .header {
            background: #ff9f4a;
            padding: 15px 30px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        .logo {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 1.6rem;
            font-weight: bold;
            color: white;
            text-shadow: 2px 2px 0 #b95f1a;
        }
        .logo span { font-size: 2rem; }
        .nav-links {
            display: flex;
            gap: 20px;
        }
        .nav-links a {
            color: white;
            text-decoration: none;
            font-weight: 500;
            background: rgba(0,0,0,0.2);
            padding: 8px 16px;
            border-radius: 40px;
            transition: 0.3s;
        }
        .nav-links a:hover { background: rgba(0,0,0,0.4); }
        /* Hero */
        .hero {
            text-align: center;
            padding: 40px 20px;
            background: linear-gradient(135deg, #ffd89b, #c7e9fb);
            clip-path: polygon(0 0, 100% 0, 100% 85%, 0 100%);
        }
        .hero h1 { font-size: 2.5rem; margin-bottom: 10px; }
        .hero p { font-size: 1.2rem; }
        /* Cats grid */
        .cats-section {
            max-width: 1200px;
            margin: 40px auto;
            padding: 0 20px;
        }
        .cats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 30px;
            margin-top: 20px;
        }
        .cat-card {
            background: white;
            border-radius: 24px;
            overflow: hidden;
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
            transition: transform 0.3s, box-shadow 0.3s;
            cursor: pointer;
        }
        .cat-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 20px 30px rgba(0,0,0,0.15);
        }
        .cat-photo {
            font-size: 100px;
            text-align: center;
            background: #fdebb3;
            padding: 30px;
        }
        .cat-info {
            padding: 20px;
            text-align: center;
        }
        .cat-info h3 { font-size: 1.8rem; margin-bottom: 8px; }
        .cat-price { color: #ff7b2c; font-weight: bold; font-size: 1.2rem; }
        /* Modal */
        .modal {
            display: none;
            position: fixed;
            top: 0; left: 0;
            width: 100%; height: 100%;
            background: rgba(0,0,0,0.7);
            align-items: center;
            justify-content: center;
            z-index: 1000;
        }
        .modal-content {
            background: white;
            max-width: 500px;
            width: 90%;
            border-radius: 32px;
            padding: 25px;
            position: relative;
            animation: fadeIn 0.3s;
        }
        .close {
            position: absolute;
            top: 15px;
            right: 20px;
            font-size: 28px;
            cursor: pointer;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: scale(0.9); }
            to { opacity: 1; transform: scale(1); }
        }
        /* Tabs & Form */
        .tabs-container {
            max-width: 800px;
            margin: 30px auto;
            background: white;
            border-radius: 32px;
            padding: 20px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
        }
        .tabs {
            display: flex;
            gap: 10px;
            border-bottom: 2px solid #ffd89b;
            margin-bottom: 20px;
        }
        .tab {
            padding: 10px 20px;
            cursor: pointer;
            border-radius: 30px 30px 0 0;
            transition: 0.2s;
        }
        .tab.active {
            background: #ff9f4a;
            color: white;
        }
        .tab-content { display: none; }
        .tab-content.active { display: block; }
        .form-group { margin-bottom: 15px; }
        label { display: block; font-weight: bold; margin-bottom: 5px; }
        input, select, textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 20px;
        }
        button {
            background: #ff9f4a;
            border: none;
            padding: 12px 20px;
            border-radius: 40px;
            font-weight: bold;
            cursor: pointer;
            width: 100%;
            color: white;
            font-size: 1rem;
        }
        .message {
            padding: 12px;
            margin: 15px 0;
            border-radius: 16px;
            display: none;
        }
        .success { background: #d4edda; color: #155724; }
        .error { background: #f8d7da; color: #721c24; }
        .credentials { background: #fff3cd; color: #856404; }
        footer {
            text-align: center;
            padding: 20px;
            background: #3e2a1f;
            color: white;
            margin-top: 40px;
        }
        @media (max-width: 700px) {
            .header { flex-direction: column; gap: 10px; }
            .hero h1 { font-size: 1.8rem; }
        }
    </style>
</head>
<body>
<div class="header">
    <div class="logo">
        <span>🐾</span> КОТИК НА ПРОКАТ
    </div>
    <div class="nav-links">
        <a href="#" id="navRentBtn">Арендовать</a>
        <a href="#" id="navLoginBtn">Вход</a>
    </div>
</div>

<div class="hero">
    <h1>🐱 Арендуйте котика на выходные! 🐱</h1>
    <p>Пушистый друг приедет к вам домой с полным набором для счастья</p>
</div>

<section class="cats-section">
    <h2 style="text-align:center">Наши хвостатые звёзды</h2>
    <div class="cats-grid" id="catsGrid">
        <?php foreach ($cats as $cat): ?>
        <div class="cat-card" data-cat-id="<?= $cat['id'] ?>">
            <div class="cat-photo"><?= $cat['photo'] ?></div>
            <div class="cat-info">
                <h3><?= htmlspecialchars($cat['name']) ?></h3>
                <div class="cat-price"><?= $cat['price'] ?> ₽/день</div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</section>

<!-- Модальное окно с деталями котика -->
<div id="catModal" class="modal">
    <div class="modal-content">
        <span class="close">&times;</span>
        <div id="modalBody"></div>
    </div>
</div>

<div class="tabs-container" id="formContainer">
    <div class="tabs">
        <div class="tab active" data-tab="rent">📝 Арендовать котика</div>
        <div class="tab" data-tab="login">🔐 Войти (редактировать заказ)</div>
    </div>
    <div id="rent-tab" class="tab-content active">
        <div id="messageBox" class="message"></div>
        <form id="rentalForm">
            <div class="form-group">
                <label>Ваше ФИО *</label>
                <input type="text" name="fullname" id="fullname" value="<?= htmlspecialchars($userData['fullname'] ?? '') ?>" required>
            </div>
            <div class="form-group">
                <label>Телефон *</label>
                <input type="tel" name="phone" id="phone" value="<?= htmlspecialchars($userData['phone'] ?? '') ?>" required>
            </div>
            <div class="form-group">
                <label>Email *</label>
                <input type="email" name="email" id="email" value="<?= htmlspecialchars($userData['email'] ?? '') ?>" required>
            </div>
            <div class="form-group">
                <label>Адрес доставки котика *</label>
                <input type="text" name="address" id="address" value="<?= htmlspecialchars($userData['address'] ?? '') ?>" required>
            </div>
            <div class="form-group">
                <label>Выберите котика *</label>
                <select name="cat_name" id="cat_name" required>
                    <option value="">-- Выберите --</option>
                    <?php foreach ($cats as $cat): ?>
                    <option value="<?= htmlspecialchars($cat['name']) ?>" <?= (($userData['cat_name'] ?? '') == $cat['name']) ? 'selected' : '' ?>><?= $cat['name'] ?> (<?= $cat['price'] ?>₽/день)</option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Дата начала аренды *</label>
                <input type="date" name="start_date" id="start_date" value="<?= htmlspecialchars($userData['start_date'] ?? '') ?>" required>
            </div>
            <div class="form-group">
                <label>Дата окончания аренды *</label>
                <input type="date" name="end_date" id="end_date" value="<?= htmlspecialchars($userData['end_date'] ?? '') ?>" required>
            </div>
            <div class="form-group">
                <label>Дополнительные опции</label>
                <div style="display:flex; gap:15px; flex-wrap:wrap;">
                    <label><input type="checkbox" name="food" value="1" <?= isset($userData['food']) && $userData['food'] ? 'checked' : '' ?>> Корм (300₽/день)</label>
                    <label><input type="checkbox" name="litter" value="1" <?= isset($userData['litter']) && $userData['litter'] ? 'checked' : '' ?>> Лоток (200₽/день)</label>
                    <label><input type="checkbox" name="toys" value="1" <?= isset($userData['toys']) && $userData['toys'] ? 'checked' : '' ?>> Игрушки (100₽/день)</label>
                </div>
            </div>
            <div class="form-group">
                <label>Комментарий (пожелания)</label>
                <textarea name="comment" id="comment" rows="2"><?= htmlspecialchars($userData['comment'] ?? '') ?></textarea>
            </div>
            <button type="submit" id="submitBtn"><?= $userData ? 'Обновить аренду' : 'Арендовать котика' ?></button>
        </form>
    </div>
    <div id="login-tab" class="tab-content">
        <div id="loginMessage" class="message"></div>
        <form id="loginForm">
            <div class="form-group">
                <label>Логин</label>
                <input type="text" name="login" id="login" required>
            </div>
            <div class="form-group">
                <label>Пароль</label>
                <input type="password" name="password" id="password" required>
            </div>
            <button type="submit">Войти</button>
        </form>
        <div style="margin-top:15px; font-size:0.9rem;">* После первой аренды вы получите логин/пароль</div>
    </div>
</div>

<footer>
    🐾 Лапки в дом — радость в сердце! Все котики привиты и дружелюбны. 🐾
</footer>

<script>
    // Данные котиков из PHP
    const catsData = <?= json_encode($cats); ?>;
    
    // Модальное окно
    const modal = document.getElementById('catModal');
    const modalBody = document.getElementById('modalBody');
    const closeModal = document.querySelector('.close');
    
    // Открыть модалку с информацией о котике
    function openCatModal(catId) {
        const cat = catsData[catId];
        if (!cat) return;
        modalBody.innerHTML = `
            <h2>${cat.name}</h2>
            <div style="font-size:80px; text-align:center">${cat.photo}</div>
            <p><strong>Характер:</strong> ${cat.character}</p>
            <p><strong>Цвет:</strong> ${cat.color}</p>
            <p><strong>Цена аренды:</strong> ${cat.price} ₽/день</p>
            <p><strong>Подробнее:</strong> ${cat.description}</p>
            <button id="rentFromModal" style="margin-top:15px;">Арендовать ${cat.name}</button>
        `;
        modal.style.display = 'flex';
        document.getElementById('rentFromModal')?.addEventListener('click', () => {
            modal.style.display = 'none';
            document.getElementById('cat_name').value = cat.name;
            document.querySelector('[data-tab="rent"]').click();
            window.scrollTo({ top: document.getElementById('formContainer').offsetTop - 20, behavior: 'smooth' });
        });
    }
    
    // Закрыть модалку
    closeModal.onclick = () => modal.style.display = 'none';
    window.onclick = (e) => { if (e.target === modal) modal.style.display = 'none'; };
    
    // Обработчики кликов по карточкам
    document.querySelectorAll('.cat-card').forEach(card => {
        card.addEventListener('click', (e) => {
            const catId = card.dataset.catId;
            openCatModal(catId);
        });
    });
    
    // Переключение вкладок
    document.querySelectorAll('.tab').forEach(tab => {
        tab.addEventListener('click', function() {
            document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
            this.classList.add('active');
            document.querySelectorAll('.tab-content').forEach(tc => tc.classList.remove('active'));
            document.getElementById(this.dataset.tab + '-tab').classList.add('active');
        });
    });
    
    // Навигационные кнопки
    document.getElementById('navRentBtn').addEventListener('click', (e) => {
        e.preventDefault();
        document.querySelector('[data-tab="rent"]').click();
        document.getElementById('formContainer').scrollIntoView({ behavior: 'smooth' });
    });
    document.getElementById('navLoginBtn').addEventListener('click', (e) => {
        e.preventDefault();
        document.querySelector('[data-tab="login"]').click();
        document.getElementById('formContainer').scrollIntoView({ behavior: 'smooth' });
    });
    
    // Логин
    document.getElementById('loginForm').addEventListener('submit', async (e) => {
        e.preventDefault();
        const login = document.getElementById('login').value;
        const password = document.getElementById('password').value;
        const resp = await fetch('api.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            body: JSON.stringify({ action: 'login', login, password })
        });
        const data = await resp.json();
        if (data.status === 'ok') {
            window.location.reload();
        } else {
            showMessage('loginMessage', 'Неверный логин или пароль', 'error');
        }
    });
    
    // Отправка формы аренды
    const form = document.getElementById('rentalForm');
    const submitBtn = document.getElementById('submitBtn');
    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        const formData = new FormData(form);
        const jsonData = {};
        for (let [key, val] of formData.entries()) {
            if (key === 'food' || key === 'litter' || key === 'toys') {
                jsonData[key] = true;
            } else {
                jsonData[key] = val;
            }
        }
        ['food', 'litter', 'toys'].forEach(opt => { if (!jsonData[opt]) jsonData[opt] = false; });
        
        submitBtn.disabled = true;
        submitBtn.textContent = 'Отправка...';
        try {
            const response = await fetch('api.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                body: JSON.stringify(jsonData)
            });
            const result = await response.json();
            if (response.status === 200 || response.status === 201) {
                if (result.status === 'created') {
                    showMessage('messageBox', `✅ Учётная запись создана!<br>🔑 Логин: ${result.login}<br>🔒 Пароль: ${result.password}<br><a href="${result.profile_url}" target="_blank">📋 Ваш профиль</a>`, 'credentials');
                    form.reset();
                } else if (result.status === 'updated') {
                    showMessage('messageBox', 'Данные успешно обновлены!', 'success');
                }
            } else if (response.status === 422 && result.errors) {
                let errMsg = Object.values(result.errors).join('<br>');
                showMessage('messageBox', errMsg, 'error');
            } else {
                showMessage('messageBox', 'Ошибка сервера. Попробуйте позже.', 'error');
            }
        } catch (err) {
            showMessage('messageBox', 'Ошибка соединения. Проверьте интернет.', 'error');
        } finally {
            submitBtn.disabled = false;
            submitBtn.textContent = '<?= $userData ? 'Обновить аренду' : 'Арендовать котика' ?>';
        }
    });
    
    function showMessage(containerId, text, type) {
        const container = document.getElementById(containerId);
        container.innerHTML = text;
        container.className = `message ${type}`;
        container.style.display = 'block';
        setTimeout(() => { container.style.display = 'none'; }, 8000);
    }
</script>
</body>
</html>
