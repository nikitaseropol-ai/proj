<?php
require_once 'config.php';

// Обработка обычного POST-запроса (fallback, если JS отключён)
$fallbackMessage = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_SERVER['HTTP_X_REQUESTED_WITH'])) {
    // Здесь дублируется логика создания/обновления, но для краткости просто имитируем
    $fallbackMessage = "Форма отправлена. Включите JavaScript для бесшовной работы.";
    // Редирект или показ сообщения – для простоты выведем сообщение на той же странице
}

// Загрузка данных пользователя, если он авторизован
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

// Если передан параметр login (переход по ссылке из профиля) – показываем форму входа
$showLoginForm = isset($_GET['login']) || (isset($_SESSION['need_login']) && $_SESSION['need_login']);
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Аренда милых котиков</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f9eec1; padding: 20px; }
        .container { max-width: 900px; margin: 0 auto; background: white; border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); overflow: hidden; }
        .header { background: #ff9f4a; padding: 20px; text-align: center; color: white; }
        .header h1 { font-size: 2rem; }
        .content { padding: 30px; }
        .tabs { display: flex; gap: 10px; margin-bottom: 20px; border-bottom: 2px solid #ff9f4a; }
        .tab { padding: 10px 20px; cursor: pointer; background: #f0f0f0; border-radius: 20px 20px 0 0; }
        .tab.active { background: #ff9f4a; color: white; }
        .tab-content { display: none; }
        .tab-content.active { display: block; }
        .form-group { margin-bottom: 20px; }
        label { display: block; font-weight: bold; margin-bottom: 5px; color: #333; }
        input, select, textarea { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 10px; }
        .cat-options { display: flex; gap: 20px; flex-wrap: wrap; margin-top: 10px; }
        .cat-options label { display: inline-flex; align-items: center; gap: 5px; font-weight: normal; }
        button { background: #ff9f4a; color: white; border: none; padding: 12px 24px; border-radius: 30px; cursor: pointer; font-size: 1rem; width: 100%; }
        .message { padding: 15px; margin-bottom: 20px; border-radius: 10px; display: none; }
        .success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .credentials { background: #fff3cd; color: #856404; border: 1px solid #ffeeba; }
        footer { text-align: center; padding: 15px; background: #f8f9fa; font-size: 0.8rem; }
        @media (max-width: 600px) { .content { padding: 20px; } }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <h1>🐱 Аренда милых котиков 🐱</h1>
        <p>Возьмите пушистого друга на выходные!</p>
    </div>
    <div class="content">
        <div class="tabs">
            <div class="tab active" data-tab="rent">Арендовать котика</div>
            <div class="tab" data-tab="login">Вход для редактирования</div>
        </div>
        
        <!-- Форма аренды -->
        <div id="rent-tab" class="tab-content active">
            <div id="messageBox" class="message"></div>
            <form id="rentalForm" method="POST" action="<?= htmlspecialchars($_SERVER['PHP_SELF']) ?>">
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
                        <option value="Мурзик (рыжий)" <?= (($userData['cat_name'] ?? '') == 'Мурзик (рыжий)') ? 'selected' : '' ?>>Мурзик (рыжий) – ласковый</option>
                        <option value="Снежок (белый)" <?= (($userData['cat_name'] ?? '') == 'Снежок (белый)') ? 'selected' : '' ?>>Снежок (белый) – пушистый</option>
                        <option value="Басик (чёрный)" <?= (($userData['cat_name'] ?? '') == 'Басик (чёрный)') ? 'selected' : '' ?>>Басик (чёрный) – игривый</option>
                        <option value="Маркиза (полосатая)" <?= (($userData['cat_name'] ?? '') == 'Маркиза (полосатая)') ? 'selected' : '' ?>>Маркиза (полосатая) – грациозная</option>
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
                    <div class="cat-options">
                        <label><input type="checkbox" name="food" value="1" <?= isset($userData['food']) && $userData['food'] ? 'checked' : '' ?>> Корм (300 руб/день)</label>
                        <label><input type="checkbox" name="litter" value="1" <?= isset($userData['litter']) && $userData['litter'] ? 'checked' : '' ?>> Лоток + наполнитель (200 руб/день)</label>
                        <label><input type="checkbox" name="toys" value="1" <?= isset($userData['toys']) && $userData['toys'] ? 'checked' : '' ?>> Игрушки (100 руб/день)</label>
                    </div>
                </div>
                <div class="form-group">
                    <label>Комментарий (пожелания)</label>
                    <textarea name="comment" id="comment" rows="3"><?= htmlspecialchars($userData['comment'] ?? '') ?></textarea>
                </div>
                <button type="submit" id="submitBtn"><?= $userData ? 'Обновить данные' : 'Арендовать котика' ?></button>
            </form>
        </div>
        
        <!-- Форма входа -->
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
            <?php if (isset($_GET['login'])): ?>
                <script>document.getElementById('login').value = decodeURIComponent("<?= htmlspecialchars($_GET['login']) ?>");</script>
            <?php endif; ?>
        </div>
    </div>
    <footer>🐾 После первой аренды вы получите логин/пароль для редактирования заказа. 🐾</footer>
</div>

<script>
    // Переключение вкладок
    document.querySelectorAll('.tab').forEach(tab => {
        tab.addEventListener('click', function() {
            document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
            this.classList.add('active');
            document.querySelectorAll('.tab-content').forEach(tc => tc.classList.remove('active'));
            document.getElementById(this.dataset.tab + '-tab').classList.add('active');
        });
    });
    
    // Авторизация через AJAX
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
    
    // Отправка формы аренды через fetch
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
        // Конвертируем чекбоксы: если не отмечены, их нет в FormData, поэтому явно ставим false
        ['food', 'litter', 'toys'].forEach(opt => {
            if (!jsonData[opt]) jsonData[opt] = false;
        });
        
        submitBtn.disabled = true;
        submitBtn.textContent = 'Отправка...';
        
        try {
            const response = await fetch('api.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                },
                body: JSON.stringify(jsonData)
            });
            const result = await response.json();
            if (response.status === 200 || response.status === 201) {
                if (result.status === 'created') {
                    showMessage('messageBox', `Учётная запись создана!<br>Логин: ${result.login}<br>Пароль: ${result.password}<br><a href="${result.profile_url}" target="_blank">Перейти в профиль</a>`, 'credentials');
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
            submitBtn.textContent = '<?= $userData ? 'Обновить данные' : 'Арендовать котика' ?>';
        }
    });
    
    function showMessage(containerId, text, type) {
        const container = document.getElementById(containerId);
        container.innerHTML = text;
        container.className = `message ${type}`;
        container.style.display = 'block';
        setTimeout(() => {
            container.style.display = 'none';
        }, 8000);
    }
    
    // Для fallback: если JS включён, то убираем стандартную отправку (уже сделано)
    // Но если JS выключен, форма отправится обычным POST – это обработается на сервере (но мы не реализовывали fallback полноценно, т.к. по условию достаточно наличия JS)
</script>
</body>
</html>