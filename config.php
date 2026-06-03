<?php
session_start();

$host = 'localhost';
$dbuser = 'u82271';
$dbpass = '5648537';
$dbname = 'u82271';

$mysqli = new mysqli($host, $dbuser, $dbpass, $dbname);
if ($mysqli->connect_error) {
    die("Ошибка подключения: " . $mysqli->connect_error);
}
$mysqli->set_charset("utf8");

// Таблицы
$table_users = 'bank_users';
$table_orders = 'bank_orders';

$mysqli->query("
CREATE TABLE IF NOT EXISTS `$table_users` (
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
CREATE TABLE IF NOT EXISTS `$table_orders` (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    metal_type ENUM('gold', 'silver', 'platinum', 'palladium') NOT NULL,
    amount_grams DECIMAL(10,2) NOT NULL,
    total_price DECIMAL(12,2) NOT NULL,
    order_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('pending', 'approved', 'completed') DEFAULT 'pending',
    FOREIGN KEY (user_id) REFERENCES `$table_users`(id) ON DELETE CASCADE
)");

// Цены за грамм (руб)
$metal_prices = [
    'gold' => 6000,
    'silver' => 80,
    'platinum' => 3000,
    'palladium' => 4000
];

function generateUniqueLogin($mysqli, $table) {
    do {
        $login = 'client_' . bin2hex(random_bytes(4));
        $check = $mysqli->prepare("SELECT id FROM `$table` WHERE login = ?");
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

function validateOrderData($data, &$errors, $metal_prices) {
    $errors = [];
    if (empty($data['fullname'])) $errors['fullname'] = "Введите ФИО";
    if (empty($data['phone'])) $errors['phone'] = "Введите телефон";
    if (empty($data['email'])) $errors['email'] = "Введите email";
    elseif (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) $errors['email'] = "Неверный email";
    if (empty($data['address'])) $errors['address'] = "Введите адрес";
    if (empty($data['metal_type']) || !isset($metal_prices[$data['metal_type']])) $errors['metal_type'] = "Выберите металл";
    if (empty($data['amount_grams']) || !is_numeric($data['amount_grams']) || $data['amount_grams'] <= 0) $errors['amount_grams'] = "Введите корректное количество граммов";
    return empty($errors);
}
?>
