<?php
session_start();

$host = 'localhost';
$dbuser = 'u82271';        // ваше имя пользователя БД
$dbpass = '5648537';       // ваш пароль БД
$dbname = 'u82271';        // имя вашей базы данных

$mysqli = new mysqli($host, $dbuser, $dbpass, $dbname);
if ($mysqli->connect_error) {
    die("Ошибка подключения: " . $mysqli->connect_error);
}
$mysqli->set_charset("utf8");

// Создание таблиц (если они ещё не созданы)
$mysqli->query("
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    login VARCHAR(100) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    fullname VARCHAR(150) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    email VARCHAR(100) NOT NULL,
    address VARCHAR(200) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

$mysqli->query("
CREATE TABLE IF NOT EXISTS rentals (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    cat_name VARCHAR(100) NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    food BOOLEAN DEFAULT 0,
    litter BOOLEAN DEFAULT 0,
    toys BOOLEAN DEFAULT 0,
    comment TEXT,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
)");

// Остальные функции (generateUniqueLogin, generateRandomPassword, validateRentalData) остаются без изменений
function generateUniqueLogin($mysqli) {
    do {
        $login = 'catlover_' . bin2hex(random_bytes(4));
        $check = $mysqli->prepare("SELECT id FROM users WHERE login = ?");
        $check->bind_param("s", $login);
        $check->execute();
        $exists = $check->get_result()->num_rows > 0;
        $check->close();
    } while ($exists);
    return $login;
}

function generateRandomPassword($length = 10) {
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    return substr(str_shuffle($chars), 0, $length);
}

function validateRentalData($data, &$errors) {
    $errors = [];
    if (empty($data['fullname'])) $errors['fullname'] = "Введите ФИО";
    if (empty($data['phone'])) $errors['phone'] = "Введите телефон";
    if (empty($data['email'])) $errors['email'] = "Введите email";
    elseif (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) $errors['email'] = "Неверный email";
    if (empty($data['address'])) $errors['address'] = "Введите адрес";
    if (empty($data['cat_name'])) $errors['cat_name'] = "Выберите котика";
    if (empty($data['start_date'])) $errors['start_date'] = "Укажите дату начала";
    if (empty($data['end_date'])) $errors['end_date'] = "Укажите дату окончания";
    elseif (strtotime($data['end_date']) <= strtotime($data['start_date'])) $errors['end_date'] = "Дата окончания должна быть позже даты начала";
    if (strlen($data['comment'] ?? '') > 2000) $errors['comment'] = "Комментарий не более 2000 символов";
    return empty($errors);
}
?>
