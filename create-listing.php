<?php
require_once 'includes/config.php';

// Проверка авторизации
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$db = Database::getInstance();

// Получение данных пользователя
$user = $db->queryOne("SELECT * FROM users WHERE id = ?", [$_SESSION['user_id']]);

// Проверка прав на размещение объявлений
if (!canCreateListing($user['level'], $user['balance_points'], $user['participant_until'])) {
    header('Location: profile.php?error=no_permission');
    exit;
}

$error = '';
$success = '';

// Получение категорий
$categories = $db->queryAll("SELECT * FROM categories WHERE parent_id IS NULL ORDER BY name");

// Обработка формы
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_listing'])) {
    $title = sanitizeInput($_POST['title']);
    $description = sanitizeInput($_POST['description']);
    $type = $_POST['type'];
    $category_id = $_POST['category_id'] ?? null;
    $price = !empty($_POST['price']) ? floatval($_POST['price']) : null;
    $currency = $_POST['currency'] ?? 'RUB';
    $location = sanitizeInput($_POST['location']);
    $contact_person = sanitizeInput($_POST['contact_person']);
    $contact_phone = sanitizeInput($_POST['contact_phone']);
    $contact_email = sanitizeInput($_POST['contact_email']);
    $is_top = isset($_POST['is_top']) ? 1 : 0;
    $is_featured = isset($_POST['is_featured']) ? 1 : 0;
    
    // Вакансии - дополнительные поля
    $salary_from = null;
    $salary_to = null;
    $employment_type = null;
    $experience_required = null;
    
    if ($type === 'jobs') {
        $salary_from = !empty($_POST['salary_from']) ? floatval($_POST['salary_from']) : null;
        $salary_to = !empty($_POST['salary_to']) ? floatval($_POST['salary_to']) : null;
        $employment_type = $_POST['employment_type'] ?? null;
        $experience_required = $_POST['experience_required'] ?? null;
    }
    
    // Валидация
    if (empty($title) || empty($description) || empty($type) || empty($location)) {
        $error = 'Пожалуйста, заполните все обязательные поля';
    } else {
        // Расчет стоимости размещения
        $totalCost = POINTS_PER_LISTING;
        if ($is_top) $totalCost += POINTS_PER_TOP;
        if ($is_featured) $totalCost += POINTS_PER_FEATURED;
        
        if ($user['balance_points'] < $totalCost) {
            $error = "Недостаточно баллов. Требуется: {$totalCost}, у вас: {$user['balance_points']}";
        } else {
            $db->beginTransaction();
            try {
                // Создание объявления
                $listingData = [
                    'user_id' => $user['id'],
                    'title' => $title,
                    'description' => $description,
                    'type' => $type,
                    'category_id' => $category_id,
                    'price' => $price,
                    'currency' => $currency,
                    'location' => $location,
                    'contact_person' => $contact_person,
                    'contact_phone' => $contact_phone,
                    'contact_email' => $contact_email,
                    'salary_from' => $salary_from,
                    'salary_to' => $salary_to,
                    'employment_type' => $employment_type,
                    'experience_required' => $experience_required,
                    'is_top' => $is_top,
                    'is_featured' => $is_featured,
                    'status' => 'active',
                    'expires_at' => date('Y-m-d H:i:s', strtotime('+30 days')),
                    'created_at' => date('Y-m-d H:i:s')
                ];
                
                $listingId = $db->insert('listings', $listingData);
                
                // Списание баллов
                $db->update('users', [
                    'balance_points' => $user['balance_points'] - $totalCost
                ], 'id = ?', [$user['id']]);
                
                // Запись транзакции
                $db->insert('point_transactions', [
                    'user_id' => $user['id'],
                    'type' => 'listing_creation',
                    'amount' => -$totalCost,
                    'description' => "Размещение объявления: {$title}",
                    'created_at' => date('Y-m-d H:i:s')
                ]);
                
                $db->commit();
                
                $success = 'Объявление успешно размещено!';
                
                // Обновление сессии
                $_SESSION['user_balance'] = $user['balance_points'] - $totalCost;
                
            } catch (Exception $e) {
                $db->rollback();
                $error = 'Ошибка при размещении объявления: ' . $e->getMessage();
            }
        }
    }
}

global $LISTING_TYPES;
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Разместить объявление - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="assets/css/main.css">
    <link rel="stylesheet" href="assets/css/auth.css">
</head>
<body>
    <!-- Навигация -->
    <div class="top-bar">
        <div class="container">
            <div class="top-bar-content">
                <div class="contact-info">
                    <a href="index.php">← Вернуться на главную</a>
                </div>
                <div class="user-panel">
                    <span>Баланс: <?php echo number_format($user['balance_points']); ?> баллов</span>
                    <a href="profile.php">Личный кабинет</a>
                </div>
            </div>
        </div>
    </div>

    <div class="profile-container">
        <div class="profile-header">
            <h1>Разместить объявление</h1>
            <p>Заполните форму для размещения нового объявления на портале</p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-error">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success">
                <?php echo htmlspecialchars($success); ?>
                <p><a href="my-listings.php">Перейти к моим объявлениям</a></p>
            </div>
        <?php else: ?>

        <div class="dashboard-card">
            <form method="POST" class="listing-form">
                <!-- Основная информация -->
                <div class="form-section">
                    <h3>Основная информация</h3>
                    
                    <div class="form-group">
                        <label for="type">Тип объявления: *</label>
                        <select name="type" id="type" required>
                            <option value="">Выберите тип</option>
                            <?php foreach ($LISTING_TYPES as $key => $label): ?>
                                <option value="<?php echo $key; ?>" <?php echo (isset($_POST['type']) && $_POST['type'] === $key) ? 'selected' : ''; ?>>
                                    <?php echo $label; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="category_id">Категория:</label>
                        <select name="category_id" id="category_id">
                            <option value="">Выберите категорию</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?php echo $category['id']; ?>" <?php echo (isset($_POST['category_id']) && $_POST['category_id'] == $category['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($category['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="title">Заголовок объявления: *</label>
                        <input type="text" name="title" id="title" required maxlength="255"
                               value="<?php echo isset($_POST['title']) ? htmlspecialchars($_POST['title']) : ''; ?>">
                        <small>Краткое и понятное описание предложения</small>
                    </div>

                    <div class="form-group">
                        <label for="description">Подробное описание: *</label>
                        <textarea name="description" id="description" required rows="6"><?php echo isset($_POST['description']) ? htmlspecialchars($_POST['description']) : ''; ?></textarea>
                        <small>Детальное описание товара/услуги, условий, требований</small>
                    </div>

                    <div class="form-group">
                        <label for="location">Местоположение: *</label>
                        <input type="text" name="location" id="location" required
                               value="<?php echo isset($_POST['location']) ? htmlspecialchars($_POST['location']) : $user['city']; ?>">
                        <small>Город, регион</small>
                    </div>
                </div>

                <!-- Цена и условия -->
                <div class="form-section" id="price-section">
                    <h3>Цена и условия</h3>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="price">Цена:</label>
                            <input type="number" name="price" id="price" step="0.01" min="0"
                                   value="<?php echo isset($_POST['price']) ? $_POST['price'] : ''; ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="currency">Валюта:</label>
                            <select name="currency" id="currency">
                                <option value="RUB" <?php echo (!isset($_POST['currency']) || $_POST['currency'] === 'RUB') ? 'selected' : ''; ?>>Рубли (₽)</option>
                                <option value="USD" <?php echo (isset($_POST['currency']) && $_POST['currency'] === 'USD') ? 'selected' : ''; ?>>Доллары ($)</option>
                                <option value="EUR" <?php echo (isset($_POST['currency']) && $_POST['currency'] === 'EUR') ? 'selected' : ''; ?>>Евро (€)</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Поля для вакансий -->
                <div class="form-section" id="job-fields" style="display: none;">
                    <h3>Информация о вакансии</h3>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="salary_from">Зарплата от:</label>
                            <input type="number" name="salary_from" id="salary_from" min="0"
                                   value="<?php echo isset($_POST['salary_from']) ? $_POST['salary_from'] : ''; ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="salary_to">Зарплата до:</label>
                            <input type="number" name="salary_to" id="salary_to" min="0"
                                   value="<?php echo isset($_POST['salary_to']) ? $_POST['salary_to'] : ''; ?>">
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="employment_type">Тип занятости:</label>
                            <select name="employment_type" id="employment_type">
                                <option value="">Не указано</option>
                                <option value="full_time">Полная занятость</option>
                                <option value="part_time">Частичная занятость</option>
                                <option value="contract">Договор</option>
                                <option value="freelance">Фриланс</option>
                                <option value="internship">Стажировка</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="experience_required">Требуемый опыт:</label>
                            <select name="experience_required" id="experience_required">
                                <option value="">Не указано</option>
                                <option value="no_experience">Без опыта</option>
                                <option value="1_3_years">1-3 года</option>
                                <option value="3_6_years">3-6 лет</option>
                                <option value="6_plus_years">Более 6 лет</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Контактная информация -->
                <div class="form-section">
                    <h3>Контактная информация</h3>
                    
                    <div class="form-group">
                        <label for="contact_person">Контактное лицо:</label>
                        <input type="text" name="contact_person" id="contact_person"
                               value="<?php echo isset($_POST['contact_person']) ? htmlspecialchars($_POST['contact_person']) : htmlspecialchars($user['name']); ?>">
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="contact_phone">Телефон:</label>
                            <input type="tel" name="contact_phone" id="contact_phone"
                                   value="<?php echo isset($_POST['contact_phone']) ? htmlspecialchars($_POST['contact_phone']) : htmlspecialchars($user['phone']); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="contact_email">Email:</label>
                            <input type="email" name="contact_email" id="contact_email"
                                   value="<?php echo isset($_POST['contact_email']) ? htmlspecialchars($_POST['contact_email']) : htmlspecialchars($user['email']); ?>">
                        </div>
                    </div>
                </div>

                <!-- Дополнительные опции -->
                <div class="form-section">
                    <h3>Дополнительные опции</h3>
                    
                    <div class="pricing-options">
                        <div class="pricing-item">
                            <label class="checkbox-group">
                                <input type="checkbox" name="is_top" value="1" <?php echo isset($_POST['is_top']) ? 'checked' : ''; ?>>
                                <span class="checkmark"></span>
                                <div class="option-details">
                                    <strong>Поднятие в ТОП (+<?php echo POINTS_PER_TOP; ?> баллов)</strong>
                                    <p>Объявление будет показываться в начале списка</p>
                                </div>
                            </label>
                        </div>
                        
                        <div class="pricing-item">
                            <label class="checkbox-group">
                                <input type="checkbox" name="is_featured" value="1" <?php echo isset($_POST['is_featured']) ? 'checked' : ''; ?>>
                                <span class="checkmark"></span>
                                <div class="option-details">
                                    <strong>Выделение цветом (+<?php echo POINTS_PER_FEATURED; ?> баллов)</strong>
                                    <p>Объявление будет выделено цветным фоном</p>
                                </div>
                            </label>
                        </div>
                    </div>
                    
                    <div class="total-cost">
                        <strong>Итоговая стоимость: <span id="total-cost"><?php echo POINTS_PER_LISTING; ?></span> баллов</strong>
                        <p>Ваш баланс: <?php echo number_format($user['balance_points']); ?> баллов</p>
                    </div>
                </div>

                <div class="form-actions">
                    <button type="submit" name="create_listing" class="btn btn-primary">Разместить объявление</button>
                    <a href="profile.php" class="btn">Отмена</a>
                </div>
            </form>
        </div>

        <?php endif; ?>
    </div>

    <script>
        // Переключение полей в зависимости от типа объявления
        document.getElementById('type').addEventListener('change', function() {
            const jobFields = document.getElementById('job-fields');
            const priceSection = document.getElementById('price-section');
            
            if (this.value === 'jobs') {
                jobFields.style.display = 'block';
                priceSection.style.display = 'none';
            } else {
                jobFields.style.display = 'none';
                priceSection.style.display = 'block';
            }
        });

        // Расчет итоговой стоимости
        function updateTotalCost() {
            let total = <?php echo POINTS_PER_LISTING; ?>;
            
            if (document.querySelector('input[name="is_top"]').checked) {
                total += <?php echo POINTS_PER_TOP; ?>;
            }
            
            if (document.querySelector('input[name="is_featured"]').checked) {
                total += <?php echo POINTS_PER_FEATURED; ?>;
            }
            
            document.getElementById('total-cost').textContent = total;
        }

        document.querySelector('input[name="is_top"]').addEventListener('change', updateTotalCost);
        document.querySelector('input[name="is_featured"]').addEventListener('change', updateTotalCost);

        // Инициализация при загрузке
        document.addEventListener('DOMContentLoaded', function() {
            const typeSelect = document.getElementById('type');
            if (typeSelect.value) {
                typeSelect.dispatchEvent(new Event('change'));
            }
        });
    </script>

    <style>
        .listing-form .form-section {
            background: #f9f9f9;
            padding: 20px;
            margin-bottom: 20px;
            border-radius: 8px;
            border-left: 4px solid #3498db;
        }
        
        .listing-form .form-section h3 {
            margin: 0 0 15px 0;
            color: #2c3e50;
            font-size: 16px;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }
        
        .pricing-options {
            margin-bottom: 20px;
        }
        
        .pricing-item {
            margin-bottom: 15px;
        }
        
        .checkbox-group {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            cursor: pointer;
        }
        
        .option-details strong {
            display: block;
            color: #2c3e50;
            margin-bottom: 3px;
        }
        
        .option-details p {
            margin: 0;
            color: #666;
            font-size: 13px;
        }
        
        .total-cost {
            background: #e8f5e8;
            padding: 15px;
            border-radius: 5px;
            border-left: 4px solid #27ae60;
        }
        
        .form-actions {
            display: flex;
            gap: 10px;
            justify-content: flex-end;
            margin-top: 30px;
        }
    </style>
</body>
</html>