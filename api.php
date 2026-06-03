<?php
require_once 'config.php';

$table_users = 'bank_users';
$table_orders = 'bank_orders';
$metal_prices = [
    'gold' => 6000,
    'silver' => 80,
    'platinum' => 3000,
    'palladium' => 4000
];

$input = file_get_contents('php://input');
$contentType = $_SERVER['CONTENT_TYPE'] ?? '';
$acceptType = $_SERVER['HTTP_ACCEPT'] ?? 'application/json';

$data = null;
if (strpos($contentType, 'application/json') !== false) {
    $data = json_decode($input, true);
} elseif (strpos($contentType, 'application/xml') !== false || strpos($contentType, 'text/xml') !== false) {
    $xml = simplexml_load_string($input);
    if ($xml) $data = json_decode(json_encode($xml), true);
} else {
    http_response_code(415);
    outputResponse(['error' => 'Unsupported Media Type'], $acceptType);
}

if (!$data) {
    http_response_code(400);
    outputResponse(['error' => 'Invalid input'], $acceptType);
}

// --- Логин ---
if (isset($data['action']) && $data['action'] === 'login') {
    $login = $data['login'] ?? '';
    $password = $data['password'] ?? '';
    $stmt = $mysqli->prepare("SELECT id, password_hash FROM `$table_users` WHERE login = ?");
    $stmt->bind_param("s", $login);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    if ($user && password_verify($password, $user['password_hash'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_login'] = $login;
        outputResponse(['status' => 'ok'], $acceptType);
    } else {
        http_response_code(401);
        outputResponse(['status' => 'error', 'message' => 'Неверный логин или пароль'], $acceptType);
    }
    exit;
}

// --- Создание или обновление заказа ---
$user_id = $_SESSION['user_id'] ?? null;
$isAuth = !!$user_id;

$errors = [];
if (!validateOrderData($data, $errors, $metal_prices)) {
    http_response_code(422);
    outputResponse(['errors' => $errors], $acceptType);
}

$metal = $data['metal_type'];
$grams = (float)$data['amount_grams'];
$total_price = $grams * $metal_prices[$metal];

if (!$isAuth) {
    // НОВЫЙ КЛИЕНТ
    $login = generateUniqueLogin($mysqli, $table_users);
    $plainPassword = generateRandomPassword();
    $passwordHash = password_hash($plainPassword, PASSWORD_DEFAULT);
    
    $mysqli->begin_transaction();
    try {
        $stmt = $mysqli->prepare("INSERT INTO `$table_users` (login, password_hash, fullname, phone, email, address) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssss", $login, $passwordHash, $data['fullname'], $data['phone'], $data['email'], $data['address']);
        $stmt->execute();
        $newUserId = $mysqli->insert_id;
        $stmt->close();
        
        $stmt = $mysqli->prepare("INSERT INTO `$table_orders` (user_id, metal_type, amount_grams, total_price) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("isid", $newUserId, $metal, $grams, $total_price);
        $stmt->execute();
        $stmt->close();
        
        $mysqli->commit();
        
        $profileUrl = (isset($_SERVER['HTTPS']) ? "https://" : "http://") . $_SERVER['HTTP_HOST'] . dirname($_SERVER['SCRIPT_NAME']) . "/index.php?login=" . urlencode($login);
        outputResponse([
            'status' => 'created',
            'login' => $login,
            'password' => $plainPassword,
            'profile_url' => $profileUrl,
            'total_price' => $total_price
        ], $acceptType);
    } catch (Exception $e) {
        $mysqli->rollback();
        http_response_code(500);
        outputResponse(['error' => 'Ошибка сервера: ' . $e->getMessage()], $acceptType);
    }
} else {
    // ОБНОВЛЕНИЕ ЗАКАЗА (авторизованный пользователь)
    $mysqli->begin_transaction();
    try {
        // Обновляем личные данные
        $stmt = $mysqli->prepare("UPDATE `$table_users` SET fullname=?, phone=?, email=?, address=? WHERE id=?");
        $stmt->bind_param("ssssi", $data['fullname'], $data['phone'], $data['email'], $data['address'], $user_id);
        $stmt->execute();
        $stmt->close();
        
        // Обновляем или создаём заказ
        $stmt = $mysqli->prepare("SELECT id FROM `$table_orders` WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $exists = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        if ($exists) {
            $stmt = $mysqli->prepare("UPDATE `$table_orders` SET metal_type=?, amount_grams=?, total_price=? WHERE user_id=?");
            $stmt->bind_param("sidi", $metal, $grams, $total_price, $user_id);
        } else {
            $stmt = $mysqli->prepare("INSERT INTO `$table_orders` (user_id, metal_type, amount_grams, total_price) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("isid", $user_id, $metal, $grams, $total_price);
        }
        $stmt->execute();
        $stmt->close();
        
        $mysqli->commit();
        outputResponse(['status' => 'updated', 'total_price' => $total_price], $acceptType);
    } catch (Exception $e) {
        $mysqli->rollback();
        http_response_code(500);
        outputResponse(['error' => 'Ошибка обновления: ' . $e->getMessage()], $acceptType);
    }
}

function outputResponse($data, $acceptType) {
    if (strpos($acceptType, 'xml') !== false) {
        header('Content-Type: application/xml; charset=utf-8');
        $xml = new SimpleXMLElement('<response/>');
        array_to_xml($data, $xml);
        echo $xml->asXML();
    } else {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
    }
    exit;
}

function array_to_xml($data, &$xml) {
    foreach ($data as $key => $value) {
        if (is_array($value)) {
            $subnode = $xml->addChild($key);
            array_to_xml($value, $subnode);
        } else {
            $xml->addChild($key, htmlspecialchars((string)$value));
        }
    }
}
?>
