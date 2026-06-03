<?php
require_once 'config.php';

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

// Массив для карточек металлов
$metals_info = [
    'gold' => ['name'=>'Золото', 'price'=>6000, 'icon'=>'🥇', 'color'=>'#FFD700', 'desc'=>'Слиток 999.9 пробы, инвестиционное золото'],
    'silver' => ['name'=>'Серебро', 'price'=>80, 'icon'=>'🥈', 'color'=>'#C0C0C0', 'desc'=>'Чистое серебро, лучший подарок'],
    'platinum' => ['name'=>'Платина', 'price'=>3000, 'icon'=>'🔘', 'color'=>'#E5E4E2', 'desc'=>'Редкий металл, высокая ликвидность'],
    'palladium' => ['name'=>'Палладий', 'price'=>4000, 'icon'=>'⚪', 'color'=>'#B0C4DE', 'desc'=>'Востребован в промышленности и инвестициях']
];
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
            background: radial-gradient(circle at 10% 20%, #0a0f1e, #05070f);
            color: #e0e0e0;
            overflow-x: hidden;
        }
        /* Анимации */
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(50px); }
            to { opacity: 1; transform: translateY(0); }
        }
        @keyframes float {
            0% { transform: translateY(0px); }
            50% { transform: translateY(-10px); }
            100% { transform: translateY(0px); }
        }
        @keyframes shine {
            0% { background-position: -200% center; }
            100% { background-position: 200% center; }
        }
        @keyframes coinFall {
            0% { transform: translateY(-100px) rotate(0deg); opacity: 1; }
            100% { transform: translateY(100vh) rotate(360deg); opacity: 0; }
        }
        .coin {
            position: fixed;
            font-size: 24px;
            pointer-events: none;
            z-index: 9999;
            animation: coinFall 3s linear forwards;
        }
        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px 30px;
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
            animation: fadeInUp 0.6s ease;
        }
        .logo {
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 2rem;
            font-weight: 800;
            background: linear-gradient(135deg, #FFD700, #B8860B, #FFA500);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            text-shadow: 0 0 10px rgba(255,215,0,0.3);
        }
        .logo span { font-size: 2.5rem; }
        .nav a {
            color: #ddd;
            text-decoration: none;
            margin-left: 25px;
            font-weight: 500;
            transition: 0.3s;
            padding: 8px 18px;
            border-radius: 40px;
            background: rgba(255,215,0,0.05);
        }
        .nav a:hover { background: rgba(255,215,0,0.2); color: #FFD700; transform: scale(1.05); }
        /* Hero */
        .hero {
            text-align: center;
            padding: 80px 20px 60px;
            animation: fadeInUp 0.8s ease;
        }
        .hero h1 {
            font-size: 3.8rem;
            background: linear-gradient(135deg, #fff, #FFD700, #FFA500);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            margin-bottom: 20px;
        }
        .hero p { font-size: 1.3rem; opacity: 0.9; max-width: 700px; margin: 0 auto; }
        /* Карточки металлов */
        .metals-section {
            margin: 60px 0;
            animation: fadeInUp 0.8s ease 0.2s backwards;
        }
        .section-title {
            text-align: center;
            font-size: 2rem;
            margin-bottom: 40px;
            color: #FFD700;
        }
        .cards-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 30px;
        }
        .metal-card {
            background: rgba(20, 30, 45, 0.6);
            backdrop-filter: blur(12px);
            border-radius: 32px;
            padding: 30px 20px;
            text-align: center;
            transition: all 0.4s;
            border: 1px solid rgba(255,215,0,0.2);
            cursor: pointer;
            animation: float 3s infinite ease-in-out;
        }
        .metal-card:hover {
            transform: scale(1.03) translateY(-5px);
            border-color: #FFD700;
            box-shadow: 0 20px 40px rgba(255,215,0,0.2);
        }
        .metal-icon { font-size: 4rem; margin-bottom: 15px; }
        .metal-name { font-size: 1.8rem; font-weight: bold; margin-bottom: 10px; }
        .metal-price { font-size: 1.2rem; color: #FFD700; margin-bottom: 15px; }
        .metal-desc { font-size: 0.9rem; opacity: 0.8; }
        /* Калькулятор */
        .calculator {
            background: linear-gradient(135deg, rgba(0,0,0,0.6), rgba(20,30,45,0.8));
            backdrop-filter: blur(15px);
            border-radius: 48px;
            padding: 30px 40px;
            margin: 50px 0;
            border: 1px solid rgba(255,215,0,0.4);
            display: flex;
            flex-wrap: wrap;
            align-items: flex-end;
            gap: 25px;
            justify-content: center;
        }
        .calc-group {
            flex: 1;
            min-width: 200px;
        }
        .calc-group label {
            display: block;
            margin-bottom: 10px;
            font-weight: 600;
            color: #FFD700;
        }
        .calc-group input, .calc-group select {
            width: 100%;
            padding: 14px 20px;
            background: #0f1a24;
            border: 1px solid #2c3e50;
            border-radius: 60px;
            color: white;
            font-size: 1rem;
        }
        .calc-group input:focus, .calc-group select:focus {
            outline: none;
            border-color: #FFD700;
            box-shadow: 0 0 15px rgba(255,215,0,0.4);
        }
        .calc-result {
            background: #0f1a24;
            padding: 14px 28px;
            border-radius: 60px;
            text-align: center;
            font-weight: bold;
            font-size: 1.3rem;
            border: 1px solid #FFD700;
        }
        .calc-result span { color: #FFD700; font-size: 1.8rem; }
        /* Форма */
        .form-container {
            background: rgba(10, 18, 30, 0.85);
            backdrop-filter: blur(15px);
            border-radius: 48px;
            padding: 40px;
            margin: 40px 0;
            border: 1px solid rgba(255,215,0,0.3);
            animation: fadeInUp 0.8s ease 0.4s backwards;
        }
        .tabs {
            display: flex;
            gap: 20px;
            margin-bottom: 35px;
            border-bottom: 1px solid #2c3e50;
        }
        .tab {
            padding: 14px 32px;
            cursor: pointer;
            border-radius: 60px 60px 0 0;
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
            gap: 25px;
        }
        .form-group { margin-bottom: 5px; }
        .form-group label { display: block; margin-bottom: 8px; font-weight: 500; }
        .form-group input, .form-group select, .form-group textarea {
            width: 100%;
            padding: 14px 20px;
            background: #0f1a24;
            border: 1px solid #2c3e50;
            border-radius: 40px;
            color: white;
            font-size: 1rem;
        }
        .full-width { grid-column: span 2; }
        button {
            background: linear-gradient(135deg, #FFD700, #B8860B);
            border: none;
            padding: 16px 32px;
            border-radius: 60px;
            font-weight: bold;
            font-size: 1.1rem;
            cursor: pointer;
            transition: 0.3s;
            width: 100%;
            color: #0a0f1e;
            margin-top: 15px;
        }
        button:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 25px rgba(255,215,0,0.4);
        }
        .message {
            padding: 15px 25px;
            border-radius: 60px;
            margin-bottom: 25px;
            display: none;
        }
        .success { background: #1e3a2f; color: #8bc34a; border-left: 5px solid #8bc34a; }
        .error { background: #3a1e1e; color: #ff8a8a; border-left: 5px solid #ff4444; }
        .credentials { background: #2a2a1a; color: #ffd966; border-left: 5px solid #ffd700; }
        footer {
            text-align: center;
            padding: 40px 20px;
            border-top: 1px solid rgba(255,215,0,0.2);
            margin-top: 60px;
            font-size: 0.9rem;
        }
        @media (max-width: 800px) {
            .form-grid { grid-template-columns: 1fr; }
            .full-width { grid-column: span 1; }
            .hero h1 { font-size: 2.2rem; }
            .container { padding: 15px; }
        }
    </style>
</head>
<body>

<!-- Анимированные монеты -->
<div id="coinContainer"></div>

<div class="container">
    <div class="header">
        <div class="logo"><span>🏦</span> BULLION BANK</div>
        <div class="nav">
            <?php if (isset($_SESSION['user_id'])): ?>
                <span style="margin-right:15px;">👤 <?= htmlspecialchars($_SESSION['user_login']) ?></span>
                <a href="logout.php">🚪 Выйти</a>
            <?php else: ?>
                <a href="#" id="navOrderBtn">📦 Заказать</a>
                <a href="#" id="navLoginBtn">🔐 Вход</a>
            <?php endif; ?>
        </div>
    </div>

    <div class="hero">
        <h1>Драгоценные металлы<br>из банка на руки</h1>
        <p>Золото, серебро, платина, палладий — официальные слитки, проба 999.9. Доставка или самовывоз.</p>
    </div>

    <!-- Секция карточек металлов -->
    <div class="metals-section">
        <div class="section-title">✨ Выберите ваш металл ✨</div>
        <div class="cards-grid">
            <?php foreach ($metals_info as $key => $metal): ?>
            <div class="metal-card" data-metal="<?= $key ?>" data-price="<?= $metal['price'] ?>">
                <div class="metal-icon"><?= $metal['icon'] ?></div>
                <div class="metal-name"><?= $metal['name'] ?></div>
                <div class="metal-price"><?= number_format($metal['price'], 0, '', ' ') ?> ₽/грамм</div>
                <div class="metal-desc"><?= $metal['desc'] ?></div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Калькулятор рублей → граммы -->
    <div class="calculator">
        <div class="calc-group">
            <label>💰 Сумма в рублях</label>
            <input type="number" id="rubAmount" placeholder="Введите сумму" value="50000">
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

    <!-- Форма заказа и входа -->
    <div class="form-container">
        <div class="tabs">
            <div class="tab active" data-tab="order">📄 Оформить заказ</div>
            <div class="tab" data-tab="login">🔐 Вход для клиентов</div>
        </div>

        <div id="order-tab" class="tab-content active">
            <div id="messageBox" class="message"></div>
            <form id="orderForm">
                <div class="form-grid">
                    <div class="form-group"><label>Ваше ФИО *</label><input type="text" name="fullname" id="fullname" value="<?= htmlspecialchars($userData['fullname'] ?? '') ?>" required></div>
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
                    <div class="form-group full-width"><label>Комментарий (особенности доставки)</label><textarea name="comment" rows="2"></textarea></div>
                </div>
                <button type="submit" id="submitBtn"><?= $userData ? 'Обновить заказ' : 'Оформить заказ' ?></button>
            </form>
        </div>

        <div id="login-tab" class="tab-content">
            <div id="loginMessage" class="message"></div>
            <form id="loginForm">
                <div class="form-grid">
                    <div class="form-group"><label>Логин</label><input type="text" name="login" id="login" required></div>
                    <div class="form-group"><label>Пароль</label><input type="password" name="password" id="password" required></div>
                </div>
                <button type="submit">Войти в личный кабинет</button>
            </form>
            <div style="margin-top:25px; text-align:center; font-size:0.9rem;">📌 После первого заказа вы получите логин и пароль для входа и изменения данных.</div>
        </div>
    </div>

    <footer>
        🏦 Bullion Bank — официальный партнёр государственных банков. Все слитки сертифицированы. <br>
        🚚 Доставка по всей России застрахованными курьерами. Оплата при получении.
    </footer>
</div>

<script>
    // Цены
    const prices = { gold:6000, silver:80, platinum:3000, palladium:4000 };
    // Элементы калькулятора
    const rubInput = document.getElementById('rubAmount');
    const calcMetal = document.getElementById('calcMetal');
    const gramsSpan = document.getElementById('gramsResult');
    
    function updateCalculator() {
        let rub = parseFloat(rubInput.value);
        if (isNaN(rub)) rub = 0;
        const metal = calcMetal.value;
        const grams = rub / prices[metal];
        gramsSpan.textContent = grams.toFixed(2);
        document.getElementById('amount_grams').value = grams.toFixed(2);
    }
    rubInput.addEventListener('input', updateCalculator);
    calcMetal.addEventListener('change', updateCalculator);
    updateCalculator();

    // Синхронизация выбора металла в форме и калькуляторе
    const metalSelect = document.getElementById('metal_type');
    metalSelect.addEventListener('change', function() {
        calcMetal.value = metalSelect.value;
        updateCalculator();
    });
    calcMetal.addEventListener('change', function() {
        metalSelect.value = calcMetal.value;
        updateCalculator();
    });

    // Клик по карточке металла
    document.querySelectorAll('.metal-card').forEach(card => {
        card.addEventListener('click', () => {
            const metal = card.dataset.metal;
            metalSelect.value = metal;
            calcMetal.value = metal;
            updateCalculator();
            document.querySelector('[data-tab="order"]').click();
            document.querySelector('.form-container').scrollIntoView({ behavior: 'smooth' });
            // Анимация монет
            for(let i=0;i<15;i++) createCoin();
        });
    });

    // Анимация падающих монет
    function createCoin() {
        const coin = document.createElement('div');
        coin.className = 'coin';
        coin.innerHTML = '💰';
        coin.style.left = Math.random() * window.innerWidth + 'px';
        coin.style.fontSize = (20 + Math.random() * 20) + 'px';
        coin.style.animationDuration = (2 + Math.random() * 2) + 's';
        document.body.appendChild(coin);
        setTimeout(() => coin.remove(), 3000);
    }

    // Переключение вкладок
    document.querySelectorAll('.tab').forEach(tab => {
        tab.addEventListener('click', function() {
            document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
            this.classList.add('active');
            document.querySelectorAll('.tab-content').forEach(tc => tc.classList.remove('active'));
            document.getElementById(this.dataset.tab + '-tab').classList.add('active');
        });
    });
    document.getElementById('navOrderBtn')?.addEventListener('click', (e) => {
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
    const loginForm = document.getElementById('loginForm');
    loginForm.addEventListener('submit', async (e) => {
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
    const orderForm = document.getElementById('orderForm');
    const submitBtn = document.getElementById('submitBtn');
    orderForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        const formData = new FormData(orderForm);
        const jsonData = {};
        for (let [key, val] of formData.entries()) {
            jsonData[key] = val;
        }
        jsonData.comment = document.querySelector('textarea[name="comment"]').value;
        const grams = parseFloat(jsonData.amount_grams);
        if (isNaN(grams) || grams <= 0) {
            showMessage('messageBox', 'Введите корректное количество граммов', 'error');
            return;
        }
        submitBtn.disabled = true;
        submitBtn.textContent = 'Обработка...';
        try {
            const response = await fetch('api.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                body: JSON.stringify(jsonData)
            });
            const result = await response.json();
            if (response.status === 200 || response.status === 201) {
                if (result.status === 'created') {
                    showMessage('messageBox', `✅ Заказ оформлен!<br>🔑 Логин: ${result.login}<br>🔒 Пароль: ${result.password}<br><a href="${result.profile_url}" target="_blank">📋 Ваш профиль</a><br>💰 Итого к оплате: ${result.total_price.toLocaleString()} руб.`, 'credentials');
                    orderForm.reset();
                    for(let i=0;i<30;i++) createCoin();
                } else if (result.status === 'updated') {
                    showMessage('messageBox', `🔄 Заказ обновлён! Итого: ${result.total_price.toLocaleString()} руб.`, 'success');
                    for(let i=0;i<15;i++) createCoin();
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
        setTimeout(() => container.style.display = 'none', 10000);
    }
</script>
</body>
</html>
