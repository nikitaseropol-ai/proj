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

$metals_info = [
    'gold' => ['name'=>'Золото', 'price'=>6000, 'icon'=>'🥇', 'desc'=>'Инвестиционный слиток 999.9'],
    'silver' => ['name'=>'Серебро', 'price'=>80, 'icon'=>'🥈', 'desc'=>'Чистое серебро высокой пробы'],
    'platinum' => ['name'=>'Платина', 'price'=>3000, 'icon'=>'🔘', 'desc'=>'Редкий металл, высокая ликвидность'],
    'palladium' => ['name'=>'Палладий', 'price'=>4000, 'icon'=>'⚪', 'desc'=>'Востребован в промышленности']
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
            background: #0a0e1a;
            color: #e0e0e0;
            overflow-x: hidden;
        }
        /* Фоновое изображение */
        .bg-image {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-image: url('gold-bars.jpg');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            z-index: -2;
        }
        .overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(10, 14, 26, 0.85);
            z-index: -1;
        }
        /* Анимация монет (лёгкая) */
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
            max-width: 1300px;
            margin: 0 auto;
            padding: 20px 30px;
            position: relative;
            z-index: 1;
        }
        /* Header */
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px 0;
            border-bottom: 1px solid #2c3e50;
            flex-wrap: wrap;
            gap: 15px;
        }
        .logo {
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 1.8rem;
            font-weight: 700;
            color: #c0c0c0;
            letter-spacing: 1px;
        }
        .logo span { font-size: 2rem; }
        .nav a {
            color: #ccc;
            text-decoration: none;
            margin-left: 25px;
            font-weight: 500;
            transition: 0.3s;
            padding: 8px 18px;
            border-radius: 8px;
            background: rgba(255,255,255,0.05);
        }
        .nav a:hover { background: rgba(255,255,255,0.15); color: #fff; }
        /* Hero секция (только текст) */
        .hero {
            text-align: left;
            padding: 60px 0 40px;
        }
        .hero h1 {
            font-size: 3rem;
            font-weight: 600;
            margin-bottom: 20px;
            color: #fff;
        }
        .hero p {
            font-size: 1.1rem;
            opacity: 0.8;
            line-height: 1.5;
            max-width: 700px;
        }
        /* Карточки металлов */
        .metals-section {
            margin: 60px 0;
        }
        .section-title {
            font-size: 1.8rem;
            font-weight: 500;
            margin-bottom: 30px;
            border-left: 4px solid #4a6a8a;
            padding-left: 20px;
        }
        .cards-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 25px;
        }
        .metal-card {
            background: rgba(30, 40, 55, 0.7);
            backdrop-filter: blur(6px);
            border-radius: 12px;
            padding: 25px 20px;
            text-align: center;
            transition: 0.2s;
            border: 1px solid #2c3e50;
            cursor: pointer;
        }
        .metal-card:hover {
            transform: translateY(-3px);
            border-color: #5a7a9a;
            background: rgba(40, 55, 75, 0.8);
        }
        .metal-icon { font-size: 3rem; margin-bottom: 12px; }
        .metal-name { font-size: 1.5rem; font-weight: 600; margin-bottom: 8px; }
        .metal-price { font-size: 1rem; color: #a0b0c0; margin-bottom: 10px; }
        .metal-desc { font-size: 0.85rem; opacity: 0.7; }
        /* Калькулятор */
        .calculator {
            background: #111827;
            border-radius: 12px;
            padding: 25px 30px;
            margin: 50px 0;
            border: 1px solid #2c3e50;
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
            font-weight: 500;
            color: #a0b0c0;
        }
        .calc-group input, .calc-group select {
            width: 100%;
            padding: 12px 16px;
            background: #0f1722;
            border: 1px solid #2c3e50;
            border-radius: 8px;
            color: white;
            font-size: 1rem;
        }
        .calc-group input:focus, .calc-group select:focus {
            outline: none;
            border-color: #5a7a9a;
        }
        .calc-result {
            background: #0f1722;
            padding: 12px 24px;
            border-radius: 8px;
            text-align: center;
            font-weight: 500;
            font-size: 1.1rem;
            border: 1px solid #2c3e50;
        }
        .calc-result span { color: #c0d0e0; font-weight: 700; font-size: 1.4rem; }
        /* Блок гарантий */
        .guarantees {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 25px;
            margin: 50px 0;
        }
        .guarantee-item {
            background: #111827;
            border-radius: 12px;
            padding: 25px;
            border: 1px solid #2c3e50;
            text-align: center;
        }
        .guarantee-icon { font-size: 2.5rem; margin-bottom: 15px; }
        .guarantee-title { font-size: 1.3rem; font-weight: 600; margin-bottom: 10px; }
        .guarantee-text { font-size: 0.9rem; opacity: 0.8; line-height: 1.4; }
        /* Форма */
        .form-container {
            background: #0f1722;
            border-radius: 12px;
            padding: 35px;
            margin: 40px 0;
            border: 1px solid #2c3e50;
        }
        .tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 30px;
            border-bottom: 1px solid #2c3e50;
        }
        .tab {
            padding: 12px 28px;
            cursor: pointer;
            border-radius: 8px 8px 0 0;
            transition: 0.2s;
            font-weight: 500;
        }
        .tab.active {
            background: #2c3e50;
            color: white;
        }
        .tab-content { display: none; }
        .tab-content.active { display: block; }
        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
        }
        .form-group { margin-bottom: 5px; }
        .form-group label { display: block; margin-bottom: 6px; font-weight: 500; font-size: 0.9rem; }
        .form-group input, .form-group select, .form-group textarea {
            width: 100%;
            padding: 12px 16px;
            background: #1a2533;
            border: 1px solid #2c3e50;
            border-radius: 8px;
            color: white;
            font-size: 0.95rem;
        }
        .full-width { grid-column: span 2; }
        button {
            background: #2c3e50;
            border: none;
            padding: 14px 28px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 1rem;
            cursor: pointer;
            transition: 0.2s;
            width: 100%;
            color: white;
            margin-top: 15px;
        }
        button:hover {
            background: #3a5a7a;
        }
        .message {
            padding: 12px 20px;
            border-radius: 8px;
            margin-bottom: 25px;
            display: none;
        }
        .success { background: #1e3a2f; color: #8bc34a; border-left: 4px solid #8bc34a; }
        .error { background: #3a1e1e; color: #ff8a8a; border-left: 4px solid #ff4444; }
        .credentials { background: #1e2a3a; color: #aaccff; border-left: 4px solid #5a7a9a; }
        footer {
            text-align: center;
            padding: 30px;
            border-top: 1px solid #2c3e50;
            margin-top: 50px;
            font-size: 0.8rem;
        }
        @media (max-width: 800px) {
            .form-grid { grid-template-columns: 1fr; }
            .full-width { grid-column: span 1; }
            .hero h1 { font-size: 2rem; }
        }
    </style>
</head>
<body>

<!-- Фоновое изображение -->
<div class="bg-image"></div>
<div class="overlay"></div>

<div id="coinContainer"></div>

<div class="container">
    <div class="header">
        <div class="logo"><span>🏦</span> BULLION BANK</div>
        <div class="nav">
            <?php if (isset($_SESSION['user_id'])): ?>
                <span style="margin-right:15px;">👤 <?= htmlspecialchars($_SESSION['user_login']) ?></span>
                <a href="logout.php">Выйти</a>
            <?php else: ?>
                <a href="#" id="navOrderBtn">Заказать</a>
                <a href="#" id="navLoginBtn">Вход</a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Hero секция (только текст, без заглушки с фото) -->
    <div class="hero">
        <h1>Драгоценные металлы<br>из банка на руки</h1>
        <p>Золото, серебро, платина, палладий — официальные слитки, проба 999.9. Доставка или самовывоз.</p>
    </div>

    <!-- Карточки металлов -->
    <div class="metals-section">
        <div class="section-title">Выберите металл</div>
        <div class="cards-grid">
            <?php foreach ($metals_info as $key => $metal): ?>
            <div class="metal-card" data-metal="<?= $key ?>" data-price="<?= $metal['price'] ?>">
                <div class="metal-icon"><?= $metal['icon'] ?></div>
                <div class="metal-name"><?= $metal['name'] ?></div>
                <div class="metal-price"><?= number_format($metal['price'], 0, '', ' ') ?> ₽/г</div>
                <div class="metal-desc"><?= $metal['desc'] ?></div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Калькулятор -->
    <div class="calculator">
        <div class="calc-group">
            <label>Сумма в рублях</label>
            <input type="number" id="rubAmount" placeholder="Введите сумму" value="50000">
        </div>
        <div class="calc-group">
            <label>Металл</label>
            <select id="calcMetal">
                <option value="gold">Золото (6000 ₽/г)</option>
                <option value="silver">Серебро (80 ₽/г)</option>
                <option value="platinum">Платина (3000 ₽/г)</option>
                <option value="palladium">Палладий (4000 ₽/г)</option>
            </select>
        </div>
        <div class="calc-result">
            Вы получите <span id="gramsResult">0.00</span> граммов
        </div>
    </div>

    <!-- Блок гарантий -->
    <div class="guarantees">
        <div class="guarantee-item">
            <div class="guarantee-icon">🔒</div>
            <div class="guarantee-title">Лицензия ЦБ РФ</div>
            <div class="guarantee-text">Официальная деятельность под надзором Банка России, все операции легальны.</div>
        </div>
        <div class="guarantee-item">
            <div class="guarantee-icon">📦</div>
            <div class="guarantee-title">Страхование грузов</div>
            <div class="guarantee-text">Доставка застрахована на полную стоимость, ответственность гарантируем.</div>
        </div>
        <div class="guarantee-item">
            <div class="guarantee-icon">💎</div>
            <div class="guarantee-title">Проба 999.9</div>
            <div class="guarantee-text">Слитки мировых стандартов (LBMA), полная сертификация.</div>
        </div>
        <div class="guarantee-item">
            <div class="guarantee-icon">🛡️</div>
            <div class="guarantee-title">Конфиденциальность</div>
            <div class="guarantee-text">Ваши данные защищены, информация о сделках не разглашается.</div>
        </div>
    </div>

    <!-- Форма заказа и входа -->
    <div class="form-container">
        <div class="tabs">
            <div class="tab active" data-tab="order">Оформить заказ</div>
            <div class="tab" data-tab="login">Вход для клиентов</div>
        </div>

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
                    <div class="form-group full-width"><label>Комментарий</label><textarea name="comment" rows="2"></textarea></div>
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
                <button type="submit">Войти</button>
            </form>
            <div style="margin-top:20px; text-align:center; font-size:0.85rem;">После первого заказа вы получите логин и пароль</div>
        </div>
    </div>

    <footer>
        © Bullion Bank — официальный партнёр государственных банков. Лицензия №1234. Все слитки сертифицированы.
    </footer>
</div>

<script>
    const prices = { gold:6000, silver:80, platinum:3000, palladium:4000 };
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

    const metalSelect = document.getElementById('metal_type');
    metalSelect.addEventListener('change', function() {
        calcMetal.value = metalSelect.value;
        updateCalculator();
    });
    calcMetal.addEventListener('change', function() {
        metalSelect.value = calcMetal.value;
        updateCalculator();
    });

    document.querySelectorAll('.metal-card').forEach(card => {
        card.addEventListener('click', () => {
            const metal = card.dataset.metal;
            metalSelect.value = metal;
            calcMetal.value = metal;
            updateCalculator();
            document.querySelector('[data-tab="order"]').click();
            document.querySelector('.form-container').scrollIntoView({ behavior: 'smooth' });
            for(let i=0;i<12;i++) createCoin();
        });
    });

    function createCoin() {
        const coin = document.createElement('div');
        coin.className = 'coin';
        coin.innerHTML = '💰';
        coin.style.left = Math.random() * window.innerWidth + 'px';
        coin.style.fontSize = (18 + Math.random() * 20) + 'px';
        coin.style.animationDuration = (2 + Math.random() * 2) + 's';
        document.body.appendChild(coin);
        setTimeout(() => coin.remove(), 3000);
    }

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
                    showMessage('messageBox', `✅ Заказ оформлен!<br>🔑 Логин: ${result.login}<br>🔒 Пароль: ${result.password}<br><a href="${result.profile_url}" target="_blank" style="color:#aaccff;">📋 Ваш профиль</a><br>💰 Итого: ${result.total_price.toLocaleString()} руб.`, 'credentials');
                    orderForm.reset();
                    for(let i=0;i<25;i++) createCoin();
                } else if (result.status === 'updated') {
                    showMessage('messageBox', `Заказ обновлён! Итого: ${result.total_price.toLocaleString()} руб.`, 'success');
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
