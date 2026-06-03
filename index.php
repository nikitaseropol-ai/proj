<?php
require_once 'config.php';

$metal_prices = [
    'gold' => 6000,
    'silver' => 80,
    'platinum' => 3000,
    'palladium' => 4000
];

// Загрузка данных авторизованного пользователя
$userData = null;
$orderData = null;
if (isset($_SESSION['user_id'])) {
    $stmt = $mysqli->prepare("SELECT * FROM `$table_users` WHERE id = ?");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $userData = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if ($userData) {
        $stmt = $mysqli->prepare("SELECT * FROM `$table_orders` WHERE user_id = ?");
        $stmt->bind_param("i", $_SESSION['user_id']);
        $stmt->execute();
        $orderData = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        $userData = array_merge($userData, $orderData ?: []);
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BullionBank | Заказ драгоценных металлов</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Inter', 'Segoe UI', sans-serif;
            background: linear-gradient(135deg, #0a0f1e 0%, #0c1222 100%);
            color: #e0e0e0;
            min-height: 100vh;
        }
        /* Анимации */
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }
        @keyframes glow {
            0% { text-shadow: 0 0 5px rgba(255,215,0,0.3); }
            100% { text-shadow: 0 0 20px rgba(255,215,0,0.8); }
        }
        .container {
            max-width: 1300px;
            margin: 0 auto;
            padding: 20px;
        }
        /* Header */
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px 0;
            border-bottom: 1px solid rgba(255,215,0,0.3);
            flex-wrap: wrap;
            gap: 15px;
        }
        .logo {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 1.8rem;
            font-weight: 700;
            background: linear-gradient(135deg, #FFD700, #B8860B);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            animation: glow 2s ease-in-out infinite alternate;
        }
        .logo span { font-size: 2rem; }
        .nav a {
            color: #ddd;
            text-decoration: none;
            margin-left: 25px;
            font-weight: 500;
            transition: 0.3s;
            padding: 8px 16px;
            border-radius: 40px;
        }
        .nav a:hover { background: rgba(255,215,0,0.2); color: #FFD700; }
        .hero {
            text-align: center;
            padding: 60px 20px 40px;
            animation: fadeInUp 0.8s ease-out;
        }
        .hero h1 {
            font-size: 3rem;
            background: linear-gradient(135deg, #fff, #FFD700);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
        }
        .hero p { font-size: 1.2rem; margin-top: 10px; opacity: 0.8; }
        /* Калькулятор */
        .calculator {
            background: rgba(20, 30, 45, 0.7);
            backdrop-filter: blur(10px);
            border-radius: 32px;
            padding: 20px 30px;
            margin: 30px 0;
            border: 1px solid rgba(255,215,0,0.3);
            display: flex;
            flex-wrap: wrap;
            align-items: flex-end;
            gap: 20px;
            justify-content: center;
        }
        .calc-group {
            flex: 1;
            min-width: 180px;
        }
        .calc-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #FFD700;
        }
        .calc-group input, .calc-group select {
            width: 100%;
            padding: 12px 16px;
            background: #1e2a3a;
            border: 1px solid #2c3e50;
            border-radius: 28px;
            color: white;
            font-size: 1rem;
            transition: 0.2s;
        }
        .calc-group input:focus, .calc-group select:focus {
            outline: none;
            border-color: #FFD700;
            box-shadow: 0 0 10px rgba(255,215,0,0.3);
        }
        .calc-result {
            background: #0f1a24;
            padding: 12px 24px;
            border-radius: 40px;
            text-align: center;
            font-weight: bold;
            font-size: 1.2rem;
        }
        .calc-result span { color: #FFD700; font-size: 1.5rem; }
        /* Форма */
        .form-container {
            background: rgba(15, 25, 35, 0.8);
            backdrop-filter: blur(10px);
            border-radius: 40px;
            padding: 30px;
            margin-top: 20px;
            border: 1px solid rgba(255,215,0,0.2);
            animation: fadeInUp 0.8s ease-out 0.2s backwards;
        }
        .tabs {
            display: flex;
            gap: 15px;
            margin-bottom: 30px;
            border-bottom: 1px solid #2c3e50;
        }
        .tab {
            padding: 12px 28px;
            cursor: pointer;
            border-radius: 40px 40px 0 0;
            transition: 0.3s;
            font-weight: 600;
        }
        .tab.active {
            background: #FFD700;
            color: #0a0f1e;
        }
        .tab-content { display: none; }
        .tab-content.active { display: block; animation: fadeInUp 0.4s; }
        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
        }
        .form-group { margin-bottom: 5px; }
        .form-group label { display: block; margin-bottom: 8px; font-weight: 500; }
        .form-group input, .form-group select, .form-group textarea {
            width: 100%;
            padding: 12px 16px;
            background: #0f1a24;
            border: 1px solid #2c3e50;
            border-radius: 28px;
            color: white;
        }
        .full-width { grid-column: span 2; }
        button {
            background: linear-gradient(135deg, #FFD700, #B8860B);
            border: none;
            padding: 14px 28px;
            border-radius: 40px;
            font-weight: bold;
            font-size: 1rem;
            cursor: pointer;
            transition: transform 0.2s, box-shadow 0.2s;
            width: 100%;
            color: #0a0f1e;
        }
        button:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(255,215,0,0.3);
        }
        .message {
            padding: 15px;
            border-radius: 28px;
            margin-bottom: 20px;
            display: none;
        }
        .success { background: #1e3a2f; color: #8bc34a; border-left: 5px solid #8bc34a; }
        .error { background: #3a1e1e; color: #ff8a8a; border-left: 5px solid #ff4444; }
        .credentials { background: #2a2a1a; color: #ffd966; border-left: 5px solid #ffd700; }
        footer {
            text-align: center;
            padding: 30px;
            margin-top: 40px;
            border-top: 1px solid rgba(255,215,0,0.2);
            font-size: 0.8rem;
        }
        @media (max-width: 700px) {
            .form-grid { grid-template-columns: 1fr; }
            .full-width { grid-column: span 1; }
            .header { flex-direction: column; text-align: center; }
            .nav a { margin: 0 10px; }
        }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <div class="logo"><span>🥇</span> BULLION BANK</div>
        <div class="nav">
            <?php if (isset($_SESSION['user_id'])): ?>
                <span style="margin-right:15px;">👤 <?= htmlspecialchars($_SESSION['user_login']) ?></span>
                <a href="logout.php">🚪 Выйти</a>
            <?php else: ?>
                <a href="#" id="navRentBtn">Заказать</a>
                <a href="#" id="navLoginBtn">Вход</a>
            <?php endif; ?>
        </div>
    </div>

    <div class="hero">
        <h1>Драгоценные металлы из банка</h1>
        <p>Получите на руки золото, серебро, платину или палладий — полная легальность, проба 999.9</p>
    </div>

    <!-- Калькулятор рублей -> граммы -->
    <div class="calculator">
        <div class="calc-group">
            <label>💰 Сумма в рублях</label>
            <input type="number" id="rubAmount" placeholder="Введите сумму" value="100000">
        </div>
        <div class="calc-group">
            <label>🥇 Металл</label>
            <select id="calcMetal">
                <option value="gold">Золото (6000 ₽/г)</option>
                <option value="silver">Серебро (80 ₽/г)</option>
                <option value="platinum">Платина (3000 ₽/г)</option>
                <option value="palladium">Палладий (4000 ₽/г)</option>
            </select>
        </div>
        <div class="calc-result">
            ⚖️ Вы получите <span id="gramsResult">0.00</span> граммов
        </div>
    </div>

    <div class="form-container">
        <div class="tabs">
            <div class="tab active" data-tab="order">📄 Оформить заказ</div>
            <div class="tab" data-tab="login">🔐 Вход для клиентов</div>
        </div>

        <!-- Форма заказа -->
        <div id="order-tab" class="tab-content active">
            <div id="messageBox" class="message"></div>
            <form id="orderForm">
                <div class="form-grid">
                    <div class="form-group"><label>ФИО *</label><input type="text" name="fullname" id="fullname" value="<?= htmlspecialchars($userData['fullname'] ?? '') ?>" required></div>
                    <div class="form-group"><label>Телефон *</label><input type="tel" name="phone" id="phone" value="<?= htmlspecialchars($userData['phone'] ?? '') ?>" required></div>
                    <div class="form-group"><label>Email *</label><input type="email" name="email" id="email" value="<?= htmlspecialchars($userData['email'] ?? '') ?>" required></div>
                    <div class="form-group"><label>Адрес доставки *</label><input type="text" name="address" id="address" value="<?= htmlspecialchars($userData['address'] ?? '') ?>" required></div>
                    <div class="form-group"><label>Металл *</label>
                        <select name="metal_type" id="metal_type">
                            <option value="gold" <?= (($userData['metal_type'] ?? '') == 'gold') ? 'selected' : '' ?>>Золото (6000 ₽/г)</option>
                            <option value="silver" <?= (($userData['metal_type'] ?? '') == 'silver') ? 'selected' : '' ?>>Серебро (80 ₽/г)</option>
                            <option value="platinum" <?= (($userData['metal_type'] ?? '') == 'platinum') ? 'selected' : '' ?>>Платина (3000 ₽/г)</option>
                            <option value="palladium" <?= (($userData['metal_type'] ?? '') == 'palladium') ? 'selected' : '' ?>>Палладий (4000 ₽/г)</option>
                        </select>
                    </div>
                    <div class="form-group"><label>Количество граммов *</label><input type="number" step="0.01" name="amount_grams" id="amount_grams" value="<?= htmlspecialchars($userData['amount_grams'] ?? '') ?>" required></div>
                    <div class="form-group full-width"><label>Комментарий (необязательно)</label><textarea name="comment" rows="2"></textarea></div>
                </div>
                <button type="submit" id="submitBtn"><?= $userData ? 'Обновить заказ' : 'Оформить заказ' ?></button>
            </form>
        </div>

        <!-- Форма входа -->
        <div id="login-tab" class="tab-content">
            <div id="loginMessage" class="message"></div>
            <form id="loginForm">
                <div class="form-grid">
                    <div class="form-group"><label>Логин</label><input type="text" name="login" id="login" required></div>
                    <div class="form-group"><label>Пароль</label><input type="password" name="password" id="password" required></div>
                </div>
                <button type="submit">Войти</button>
            </form>
            <div style="margin-top:20px; text-align:center; font-size:0.9rem;">* После первого заказа вы получите логин и пароль для входа</div>
        </div>
    </div>
    <footer>© Bullion Bank — официальные слитки. Цены фиксированы на момент заказа. Доставка курьером или самовывоз из банка.</footer>
</div>

<script>
    // Калькулятор
    const rubInput = document.getElementById('rubAmount');
    const calcMetal = document.getElementById('calcMetal');
    const gramsSpan = document.getElementById('gramsResult');
    const prices = { gold:6000, silver:80, platinum:3000, palladium:4000 };
    
    function updateCalculator() {
        let rub = parseFloat(rubInput.value);
        if (isNaN(rub)) rub = 0;
        const metal = calcMetal.value;
        const grams = rub / prices[metal];
        gramsSpan.textContent = grams.toFixed(2);
        // также синхронизируем поле amount_grams, если пользователь хочет
        document.getElementById('amount_grams').value = grams.toFixed(2);
    }
    rubInput.addEventListener('input', updateCalculator);
    calcMetal.addEventListener('change', updateCalculator);
    updateCalculator();

    // При изменении металла в форме тоже обновляем калькулятор? опционально
    const metalSelect = document.getElementById('metal_type');
    metalSelect.addEventListener('change', function() {
        // синхронизируем выбор в калькуляторе
        calcMetal.value = metalSelect.value;
        updateCalculator();
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
    document.getElementById('navRentBtn')?.addEventListener('click', (e) => {
        e.preventDefault();
        document.querySelector('[data-tab="order"]').click();
        document.querySelector('.form-container').scrollIntoView({ behavior: 'smooth' });
    });
    document.getElementById('navLoginBtn')?.addEventListener('click', (e) => {
        e.preventDefault();
        document.querySelector('[data-tab="login"]').click();
        document.querySelector('.form-container').scrollIntoView({ behavior: 'smooth' });
    });

    // Логин
    document.getElementById('loginForm').addEventListener('submit', async (e) => {
        e.preventDefault();
        const login = document.getElementById('login').value;
        const password = document.getElementById('password').value;
        const response = await fetch('api.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            body: JSON.stringify({ action: 'login', login, password })
        });
        const data = await response.json();
        if (data.status === 'ok') {
            window.location.reload();
        } else {
            showMessage('loginMessage', 'Неверный логин или пароль', 'error');
        }
    });

    // Отправка заказа
    const form = document.getElementById('orderForm');
    const submitBtn = document.getElementById('submitBtn');
    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        const formData = new FormData(form);
        const jsonData = {};
        for (let [key, val] of formData.entries()) {
            jsonData[key] = val;
        }
        // если есть поле comment
        jsonData.comment = document.querySelector('textarea[name="comment"]').value;
        // валидация граммов
        const grams = parseFloat(jsonData.amount_grams);
        if (isNaN(grams) || grams <= 0) {
            showMessage('messageBox', 'Введите корректное количество граммов', 'error');
            return;
        }
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
                    showMessage('messageBox', `✅ Заказ оформлен!<br>🔑 Логин: ${result.login}<br>🔒 Пароль: ${result.password}<br><a href="${result.profile_url}" target="_blank">📋 Ваш профиль</a><br>💰 Итого: ${result.total_price} руб.`, 'credentials');
                    form.reset();
                } else if (result.status === 'updated') {
                    showMessage('messageBox', `Заказ обновлён! Итого: ${result.total_price} руб.`, 'success');
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
            submitBtn.textContent = '<?= $userData ? 'Обновить заказ' : 'Оформить заказ' ?>';
        }
    });

    function showMessage(containerId, text, type) {
        const container = document.getElementById(containerId);
        container.innerHTML = text;
        container.className = `message ${type}`;
        container.style.display = 'block';
        setTimeout(() => container.style.display = 'none', 8000);
    }
</script>
</body>
</html>
