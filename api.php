// ... после require_once
// Если пришёл запрос на логин
if (isset($data['action']) && $data['action'] === 'login') {
    $login = $data['login'] ?? '';
    $password = $data['password'] ?? '';
    $stmt = $mysqli->prepare("SELECT id, password_hash FROM users WHERE login = ?");
    $stmt->bind_param("s", $login);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    if ($user && password_verify($password, $user['password_hash'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_login'] = $login;
        outputResponse(['status' => 'ok'], $acceptType);
    } else {
        http_response_code(401);
        outputResponse(['status' => 'error', 'message' => 'Invalid credentials'], $acceptType);
    }
    exit;
}
// ... остальной код api.php
