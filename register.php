<?php
require_once 'includes/config.php';

$error = '';
$success = '';

// Обработка формы регистрации
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register'])) {
    $name = sanitizeInput($_POST['name']);
    $email = sanitizeInput($_POST['email']);
    $password = $_POST['password'];
    $password_confirm = $_POST['password_confirm'];
    $phone = sanitizeInput($_POST['phone']);
    $city = sanitizeInput($_POST['city']);
    $company = sanitizeInput($_POST['company']);
    $position = sanitizeInput($_POST['position']);
    
    // Валидация
    if (empty($name) || empty($email) || empty($password) || empty($city)) {
        $error = 'Пожалуйста, заполните все обязательные поля';
    } elseif ($password !== $password_confirm) {
        $error = 'Пароли не совпадают';
    } elseif (strlen($password) < 6) {
        $error = 'Пароль должен содержать минимум 6 символов';
    } else {
        $db = Database::getInstance();
        
        // Проверка существования email
        $existingUser = $db->queryOne("SELECT id FROM users WHERE email = ?", [$email]);
        
        if ($existingUser) {
            $error = 'Пользователь с таким email уже существует';
        } else {
            // Создание пользователя
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            
            $userData = [
                'name' => $name,
                'email' => $email,
                'password' => $hashedPassword,
                'phone' => $phone,
                'city' => $city,
                'company' => $company,
                'position' => $position,
                'level' => USER_OBSERVER,
                'role' => 'user',
                'balance_points' => 0,
                'status' => 'active',
                'created_at' => date('Y-m-d H:i:s')
            ];
            
            $userId = $db->insert('users', $userData);
            
            if ($userId) {
                $success = 'Регистрация успешно завершена! Теперь вы можете войти в систему.';
            } else {
                $error = 'Ошибка при создании аккаунта. Попробуйте еще раз.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Регистрация - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="assets/css/main.css">
    <link rel="stylesheet" href="assets/css/auth.css">
</head>
<body>
    <div class="auth-container">
        <div class="auth-box">
            <div class="auth-header">
                <h1><a href="index.php"><?php echo SITE_NAME; ?></a></h1>
                <h2>Регистрация нового пользователя</h2>
            </div>
            
            <?php if ($error): ?>
                <div class="alert alert-error">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success">
                    <?php echo htmlspecialchars($success); ?>
                    <p><a href="login.php">Перейти к входу в систему</a></p>
                </div>
            <?php else: ?>
                
            <div class="membership-info">
                <h4>Система уровней пользователей:</h4>
                <ul>
                    <li><strong>Наблюдатель (0 баллов):</strong> Просмотр объявлений без контактов</li>
                    <li><strong>Участник:</strong> Доступ к контактам, размещение объявлений (требуется оплата баллами)</li>
                    <li><strong>Лидер:</strong> Расширенные возможности размещения</li>
                </ul>
                <p><strong>Тарифы временного членства:</strong></p>
                <ul>
                    <li>1 месяц - 120 баллов</li>
                    <li>3 месяца - 300 баллов</li>
                    <li>5 месяцев - 560 баллов</li>
                    <li>1 год - 1000 баллов</li>
                </ul>
            </div>
            
            <form method="POST" class="auth-form">
                <div class="form-row">
                    <div class="form-group">
                        <label for="name">Имя и фамилия: *</label>
                        <input type="text" id="name" name="name" required 
                               value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Email адрес: *</label>
                        <input type="email" id="email" name="email" required 
                               value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="password">Пароль: *</label>
                        <input type="password" id="password" name="password" required minlength="6">
                        <small>Минимум 6 символов</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="password_confirm">Подтверждение пароля: *</label>
                        <input type="password" id="password_confirm" name="password_confirm" required minlength="6">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="phone">Телефон:</label>
                        <input type="tel" id="phone" name="phone" 
                               value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="city">Город: *</label>
                        <input type="text" id="city" name="city" required 
                               value="<?php echo isset($_POST['city']) ? htmlspecialchars($_POST['city']) : ''; ?>">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="company">Компания:</label>
                        <input type="text" id="company" name="company" 
                               value="<?php echo isset($_POST['company']) ? htmlspecialchars($_POST['company']) : ''; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="position">Должность:</label>
                        <input type="text" id="position" name="position" 
                               value="<?php echo isset($_POST['position']) ? htmlspecialchars($_POST['position']) : ''; ?>">
                    </div>
                </div>
                
                <div class="form-group">
                    <div class="checkbox-group">
                        <input type="checkbox" id="agree_terms" name="agree_terms" required>
                        <label for="agree_terms">
                            Я соглашаюсь с <a href="terms.php" target="_blank">правилами использования</a> 
                            и <a href="privacy.php" target="_blank">политикой конфиденциальности</a>
                        </label>
                    </div>
                </div>
                
                <div class="form-group">
                    <button type="submit" name="register" class="btn btn-primary">Зарегистрироваться</button>
                </div>
            </form>
            
            <?php endif; ?>
            
            <div class="auth-links">
                <p>Уже есть аккаунт? <a href="login.php">Войти в систему</a></p>
            </div>
        </div>
        
        <div class="auth-footer">
            <a href="index.php">← Вернуться на главную</a>
        </div>
    </div>
    
    <script>
        // Проверка совпадения паролей
        document.getElementById('password_confirm').addEventListener('input', function() {
            const password = document.getElementById('password').value;
            const confirmPassword = this.value;
            
            if (password !== confirmPassword) {
                this.setCustomValidity('Пароли не совпадают');
            } else {
                this.setCustomValidity('');
            }
        });
    </script>
</body>
</html>