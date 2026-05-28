<?php
require_once 'config.php';

// Определяем тип запроса и входные данные
$input = file_get_contents('php://input');
$contentType = $_SERVER['CONTENT_TYPE'] ?? '';
$acceptType = $_SERVER['HTTP_ACCEPT'] ?? 'application/json';

$data = null;
if (strpos($contentType, 'application/json') !== false) {
    $data = json_decode($input, true);
} elseif (strpos($contentType, 'application/xml') !== false || strpos($contentType, 'text/xml') !== false) {
    $xml = simplexml_load_string($input);
    if ($xml) {
        $data = json_decode(json_encode($xml), true);
    }
} else {
    http_response_code(415);
    outputResponse(['error' => 'Unsupported Media Type. Use JSON or XML.'], $acceptType);
    exit;
}

if (!$data) {
    http_response_code(400);
    outputResponse(['error' => 'Invalid input data'], $acceptType);
    exit;
}

// Проверяем, авторизован ли пользователь
$user_id = $_SESSION['user_id'] ?? null;
$isAuth = !!$user_id;

// Валидация
$errors = [];
if (!validateRentalData($data, $errors)) {
    http_response_code(422);
    outputResponse(['errors' => $errors], $acceptType);
    exit;
}

if (!$isAuth) {
    // --- НОВЫЙ ПОЛЬЗОВАТЕЛЬ ---
    $login = generateUniqueLogin($mysqli);
    $plainPassword = generateRandomPassword();
    $passwordHash = password_hash($plainPassword, PASSWORD_DEFAULT);
    
    $mysqli->begin_transaction();
    try {
        $stmt = $mysqli->prepare("INSERT INTO users (login, password_hash, fullname, phone, email, address) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssss", $login, $passwordHash, $data['fullname'], $data['phone'], $data['email'], $data['address']);
        $stmt->execute();
        $newUserId = $mysqli->insert_id;
        $stmt->close();
        
        $stmt = $mysqli->prepare("INSERT INTO rentals (user_id, cat_name, start_date, end_date, food, litter, toys, comment) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $food = isset($data['food']) ? 1 : 0;
        $litter = isset($data['litter']) ? 1 : 0;
        $toys = isset($data['toys']) ? 1 : 0;
        $comment = $data['comment'] ?? '';
        $stmt->bind_param("isssiiis", $newUserId, $data['cat_name'], $data['start_date'], $data['end_date'], $food, $litter, $toys, $comment);
        $stmt->execute();
        $stmt->close();
        
        $mysqli->commit();
        
        $profileUrl = (isset($_SERVER['HTTPS']) ? "https://" : "http://") . $_SERVER['HTTP_HOST'] . dirname($_SERVER['SCRIPT_NAME']) . "/index.php?login=" . urlencode($login);
        outputResponse([
            'status' => 'created',
            'login' => $login,
            'password' => $plainPassword,
            'profile_url' => $profileUrl
        ], $acceptType);
    } catch (Exception $e) {
        $mysqli->rollback();
        http_response_code(500);
        outputResponse(['error' => 'Server error: ' . $e->getMessage()], $acceptType);
    }
} else {
    // --- АВТОРИЗОВАННЫЙ ПОЛЬЗОВАТЕЛЬ (обновление данных) ---
    $mysqli->begin_transaction();
    try {
        // Обновляем личные данные (кроме логина/пароля)
        $stmt = $mysqli->prepare("UPDATE users SET fullname=?, phone=?, email=?, address=? WHERE id=?");
        $stmt->bind_param("ssssi", $data['fullname'], $data['phone'], $data['email'], $data['address'], $user_id);
        $stmt->execute();
        $stmt->close();
        
        // Обновляем или создаём аренду (у пользователя может быть только одна активная аренда)
        $stmt = $mysqli->prepare("SELECT id FROM rentals WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $exists = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        $food = isset($data['food']) ? 1 : 0;
        $litter = isset($data['litter']) ? 1 : 0;
        $toys = isset($data['toys']) ? 1 : 0;
        $comment = $data['comment'] ?? '';
        
        if ($exists) {
            $stmt = $mysqli->prepare("UPDATE rentals SET cat_name=?, start_date=?, end_date=?, food=?, litter=?, toys=?, comment=? WHERE user_id=?");
            $stmt->bind_param("sssiiiis", $data['cat_name'], $data['start_date'], $data['end_date'], $food, $litter, $toys, $comment, $user_id);
        } else {
            $stmt = $mysqli->prepare("INSERT INTO rentals (user_id, cat_name, start_date, end_date, food, litter, toys, comment) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("isssiiis", $user_id, $data['cat_name'], $data['start_date'], $data['end_date'], $food, $litter, $toys, $comment);
        }
        $stmt->execute();
        $stmt->close();
        
        $mysqli->commit();
        outputResponse(['status' => 'updated'], $acceptType);
    } catch (Exception $e) {
        $mysqli->rollback();
        http_response_code(500);
        outputResponse(['error' => 'Update failed: ' . $e->getMessage()], $acceptType);
    }
}

// Функция вывода ответа в нужном формате
function outputResponse($data, $acceptType) {
    if (strpos($acceptType, 'application/xml') !== false || strpos($acceptType, 'text/xml') !== false) {
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
            if (is_numeric($key)) $key = 'item';
            $subnode = $xml->addChild($key);
            array_to_xml($value, $subnode);
        } else {
            $xml->addChild($key, htmlspecialchars($value));
        }
    }
}
?>