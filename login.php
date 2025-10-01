<?php
require_once 'includes/config.php';

$error = '';
$success = '';

// Обработка формы входа
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $email = sanitizeInput($_POST['email']);
    $password = $_POST['password'];
    
    if (empty($email) || empty($password)) {
        $error = 'Пожалуйста, заполните все поля';
    } else {
        $db = Database::getInstance();
        
        // Поиск пользователя
        $user = $db->queryOne(
            "SELECT * FROM users WHERE email = ? AND status = 'active'", 
            [$email]
        );
        
        if ($user && password_verify($password, $user['password'])) {
            // Проверка срока действия членства и обновление статуса
            if ($user['participant_until'] && strtotime($user['participant_until']) < time()) {
                // Истек срок участника - понижаем до наблюдателя
                $db->update('users', 
                    ['level' => USER_OBSERVER], 
                    'id = ?', 
                    [$user['id']]
                );
                $user['level'] = USER_OBSERVER;
            }
            
            // Установка сессии
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_level'] = $user['level'];
            $_SESSION['user_role'] = $user['role'];
            $_SESSION['user_balance'] = $user['balance_points'];
            $_SESSION['participant_until'] = $user['participant_until'];
            
            // Обновление времени последнего входа
            $db->update('users', 
                ['last_login' => date('Y-m-d H:i:s')], 
                'id = ?', 
                [$user['id']]
            );
            
            // Редирект в зависимости от роли
            if ($user['role'] === ADMIN_ROLE || $user['role'] === CONTENT_EDITOR_ROLE) {
                header('Location: admin/');
            } else {
                header('Location: profile.php');
            }
            exit;
        } else {
            $error = 'Неверный email или пароль';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Вход в систему - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="assets/css/main.css">
    <link rel="stylesheet" href="assets/css/auth.css">
</head>
<body>
    <div class="auth-container">
        <div class="auth-box">
            <div class="auth-header">
                <h1><a href="index.php"><?php echo SITE_NAME; ?></a></h1>
                <h2>Вход в личный кабинет</h2>
            </div>
            
            <?php if ($error): ?>
                <div class="alert alert-error">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success">
                    <?php echo htmlspecialchars($success); ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" class="auth-form">
                <div class="form-group">
                    <label for="email">Email адрес:</label>
                    <input type="email" id="email" name="email" required 
                           value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                </div>
                
                <div class="form-group">
                    <label for="password">Пароль:</label>
                    <input type="password" id="password" name="password" required>
                </div>
                
                <div class="form-group">
                    <button type="submit" name="login" class="btn btn-primary">Войти</button>
                </div>
            </form>
            
            <div class="auth-links">
                <p>Нет аккаунта? <a href="register.php">Зарегистрироваться</a></p>
                <p><a href="forgot-password.php">Забыли пароль?</a></p>
            </div>
            
            <div class="demo-accounts">
                <h3>Демо-аккаунты для тестирования:</h3>
                <div class="demo-list">
                    <div class="demo-item">
                        <strong>Администратор:</strong><br>
                        Email: admin@textilserver.ru<br>
                        Пароль: admin123
                    </div>
                    <div class="demo-item">
                        <strong>Редактор контента:</strong><br>
                        Email: editor@textilserver.ru<br>
                        Пароль: editor123
                    </div>
                    <div class="demo-item">
                        <strong>Пользователь-участник:</strong><br>
                        Email: user@textilserver.ru<br>
                        Пароль: user123
                    </div>
                </div>
            </div>
        </div>
        
        <div class="auth-footer">
            <a href="index.php">← Вернуться на главную</a>
        </div>
    </div>
</body>
</html>