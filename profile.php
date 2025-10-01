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

// Проверка срока действия членства
$membershipExpired = false;
if ($user['participant_until'] && strtotime($user['participant_until']) < time()) {
    $membershipExpired = true;
    // Автоматическое понижение до наблюдателя
    if ($user['level'] !== USER_OBSERVER) {
        $db->update('users', ['level' => USER_OBSERVER], 'id = ?', [$user['id']]);
        $user['level'] = USER_OBSERVER;
        $_SESSION['user_level'] = USER_OBSERVER;
    }
}

// Обработка покупки членства
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['buy_membership'])) {
    $membershipType = $_POST['membership_type'];
    $cost = 0;
    $months = 0;
    
    switch ($membershipType) {
        case '1month':
            $cost = MEMBERSHIP_1MONTH;
            $months = 1;
            break;
        case '3months':
            $cost = MEMBERSHIP_3MONTHS;
            $months = 3;
            break;
        case '5months':
            $cost = MEMBERSHIP_5MONTHS;
            $months = 5;
            break;
        case '12months':
            $cost = MEMBERSHIP_12MONTHS;
            $months = 12;
            break;
    }
    
    if ($cost > 0 && $user['balance_points'] >= $cost) {
        // Вычисление новой даты окончания членства
        $currentUntil = $user['participant_until'] && strtotime($user['participant_until']) > time() 
            ? $user['participant_until'] 
            : date('Y-m-d H:i:s');
        
        $newUntil = date('Y-m-d H:i:s', strtotime($currentUntil . ' +' . $months . ' months'));
        
        // Обновление данных пользователя
        $db->beginTransaction();
        try {
            $db->update('users', [
                'balance_points' => $user['balance_points'] - $cost,
                'participant_until' => $newUntil,
                'level' => USER_PARTICIPANT
            ], 'id = ?', [$user['id']]);
            
            // Запись транзакции
            $db->insert('point_transactions', [
                'user_id' => $user['id'],
                'type' => 'membership_purchase',
                'amount' => -$cost,
                'description' => "Покупка членства на {$months} мес.",
                'created_at' => date('Y-m-d H:i:s')
            ]);
            
            $db->commit();
            
            // Обновление данных в сессии
            $_SESSION['user_level'] = USER_PARTICIPANT;
            $_SESSION['user_balance'] = $user['balance_points'] - $cost;
            $_SESSION['participant_until'] = $newUntil;
            
            header('Location: profile.php?success=membership');
            exit;
        } catch (Exception $e) {
            $db->rollback();
            $error = 'Ошибка при покупке членства';
        }
    } else {
        $error = 'Недостаточно баллов для покупки';
    }
}

// Получение статистики пользователя
$userListings = $db->count('listings', 'user_id = ?', [$user['id']]);
$activeListings = $db->count('listings', 'user_id = ? AND status = ?', [$user['id'], 'active']);
$recentTransactions = $db->getLatest('point_transactions', 5, 'created_at DESC', 'user_id = ?', [$user['id']]);

global $LISTING_TYPES;
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Личный кабинет - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="assets/css/main.css">
    <link rel="stylesheet" href="assets/css/auth.css">
</head>
<body>
    <div class="profile-container">
        <!-- Шапка профиля -->
        <div class="profile-header">
            <div class="profile-info">
                <div class="user-avatar">
                    <?php echo mb_substr($user['name'], 0, 1); ?>
                </div>
                <div class="user-details">
                    <h1><?php echo htmlspecialchars($user['name']); ?></h1>
                    <p class="user-level">
                        <?php 
                        switch ($user['level']) {
                            case USER_OBSERVER: echo 'Наблюдатель'; break;
                            case USER_PARTICIPANT: echo 'Участник'; break;
                            case USER_LEADER: echo 'Лидер'; break;
                        }
                        ?>
                    </p>
                    <p><?php echo htmlspecialchars($user['email']); ?></p>
                    <?php if ($user['company']): ?>
                        <p><?php echo htmlspecialchars($user['company']); ?></p>
                    <?php endif; ?>
                </div>
                <div class="user-stats">
                    <div class="balance-points"><?php echo number_format($user['balance_points']); ?> баллов</div>
                    <div class="membership-status">
                        <?php if ($user['participant_until'] && strtotime($user['participant_until']) > time()): ?>
                            Членство до: <?php echo formatDate($user['participant_until'], 'd.m.Y'); ?>
                        <?php elseif ($membershipExpired): ?>
                            <span style="color: #e74c3c;">Членство истекло</span>
                        <?php else: ?>
                            Без активного членства
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <?php if (isset($_GET['success']) && $_GET['success'] === 'membership'): ?>
            <div class="alert alert-success">
                Членство успешно приобретено!
            </div>
        <?php endif; ?>

        <?php if (isset($error)): ?>
            <div class="alert alert-error">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <!-- Основная панель управления -->
        <div class="dashboard-grid">
            <!-- Быстрые действия -->
            <div class="dashboard-card">
                <h3>Быстрые действия</h3>
                <div class="quick-actions">
                    <?php if (canCreateListing($user['level'], $user['balance_points'], $user['participant_until'])): ?>
                        <a href="create-listing.php" class="btn btn-primary">Разместить объявление</a>
                    <?php else: ?>
                        <button class="btn" disabled title="Требуется активное членство">Разместить объявление</button>
                    <?php endif; ?>
                    
                    <a href="my-listings.php" class="btn btn-success">Мои объявления (<?php echo $activeListings; ?>)</a>
                    <a href="edit-profile.php" class="btn">Редактировать профиль</a>
                    <a href="logout.php" class="btn">Выйти</a>
                </div>
            </div>

            <!-- Покупка членства -->
            <?php if ($user['level'] === USER_OBSERVER || $membershipExpired): ?>
            <div class="dashboard-card">
                <h3>Приобрести членство</h3>
                <p>Для размещения объявлений и доступа к контактам необходимо активное членство.</p>
                
                <form method="POST">
                    <div class="form-group">
                        <label for="membership_type">Выберите тариф:</label>
                        <select name="membership_type" id="membership_type" required>
                            <option value="">Выберите тариф</option>
                            <option value="1month" <?php echo $user['balance_points'] < MEMBERSHIP_1MONTH ? 'disabled' : ''; ?>>
                                1 месяц - <?php echo MEMBERSHIP_1MONTH; ?> баллов
                            </option>
                            <option value="3months" <?php echo $user['balance_points'] < MEMBERSHIP_3MONTHS ? 'disabled' : ''; ?>>
                                3 месяца - <?php echo MEMBERSHIP_3MONTHS; ?> баллов
                            </option>
                            <option value="5months" <?php echo $user['balance_points'] < MEMBERSHIP_5MONTHS ? 'disabled' : ''; ?>>
                                5 месяцев - <?php echo MEMBERSHIP_5MONTHS; ?> баллов
                            </option>
                            <option value="12months" <?php echo $user['balance_points'] < MEMBERSHIP_12MONTHS ? 'disabled' : ''; ?>>
                                1 год - <?php echo MEMBERSHIP_12MONTHS; ?> баллов
                            </option>
                        </select>
                    </div>
                    
                    <?php if ($user['balance_points'] >= MEMBERSHIP_1MONTH): ?>
                        <button type="submit" name="buy_membership" class="btn btn-primary">Купить членство</button>
                    <?php else: ?>
                        <p style="color: #e74c3c;">Недостаточно баллов. Обратитесь к администратору для пополнения баланса.</p>
                    <?php endif; ?>
                </form>
            </div>
            <?php endif; ?>

            <!-- Статистика -->
            <div class="dashboard-card">
                <h3>Моя статистика</h3>
                <ul>
                    <li>Всего объявлений: <strong><?php echo $userListings; ?></strong></li>
                    <li>Активных объявлений: <strong><?php echo $activeListings; ?></strong></li>
                    <li>Баланс баллов: <strong><?php echo number_format($user['balance_points']); ?></strong></li>
                    <li>Дата регистрации: <strong><?php echo formatDate($user['created_at'], 'd.m.Y'); ?></strong></li>
                </ul>
            </div>

            <!-- История операций -->
            <div class="dashboard-card">
                <h3>Последние операции</h3>
                <?php if (empty($recentTransactions)): ?>
                    <p>Операций пока нет</p>
                <?php else: ?>
                    <div class="transactions-list">
                        <?php foreach ($recentTransactions as $transaction): ?>
                            <div class="transaction-item">
                                <div class="transaction-desc">
                                    <?php echo htmlspecialchars($transaction['description']); ?>
                                </div>
                                <div class="transaction-amount" style="color: <?php echo $transaction['amount'] > 0 ? '#27ae60' : '#e74c3c'; ?>">
                                    <?php echo $transaction['amount'] > 0 ? '+' : ''; ?><?php echo $transaction['amount']; ?>
                                </div>
                                <div class="transaction-date">
                                    <?php echo formatDate($transaction['created_at'], 'd.m.Y H:i'); ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Навигация -->
            <div class="dashboard-card">
                <h3>Навигация</h3>
                <ul>
                    <li><a href="index.php">Главная страница</a></li>
                    <li><a href="listings.php">Доска объявлений</a></li>
                    <li><a href="companies.php">Каталог компаний</a></li>
                    <li><a href="catalog.php">Каталог продукции</a></li>
                    <?php if ($user['role'] === ADMIN_ROLE || $user['role'] === CONTENT_EDITOR_ROLE): ?>
                        <li><a href="admin/">Административная панель</a></li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </div>

    <style>
        .transactions-list {
            max-height: 200px;
            overflow-y: auto;
        }
        
        .transaction-item {
            display: grid;
            grid-template-columns: 1fr auto auto;
            gap: 10px;
            padding: 8px 0;
            border-bottom: 1px solid #eee;
            font-size: 12px;
        }
        
        .transaction-item:last-child {
            border-bottom: none;
        }
        
        .transaction-desc {
            font-weight: 500;
        }
        
        .transaction-amount {
            font-weight: 600;
        }
        
        .transaction-date {
            color: #666;
        }
    </style>
</body>
</html>