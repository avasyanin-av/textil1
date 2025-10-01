<?php
require_once '../includes/config.php';

// Проверка авторизации и прав администратора
if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] !== ADMIN_ROLE && $_SESSION['user_role'] !== CONTENT_EDITOR_ROLE)) {
    header('Location: ../login.php');
    exit;
}

$db = Database::getInstance();

// Получение статистики
$stats = [
    'total_users' => $db->count('users'),
    'active_users' => $db->count('users', 'status = ?', ['active']),
    'new_users_today' => $db->count('users', 'DATE(created_at) = CURDATE()'),
    'total_listings' => $db->count('listings'),
    'active_listings' => $db->count('listings', 'status = ?', ['active']),
    'new_listings_today' => $db->count('listings', 'DATE(created_at) = CURDATE()'),
    'total_companies' => $db->count('companies'),
    'active_companies' => $db->count('companies', 'status = ?', ['active']),
    'total_points_issued' => $db->queryOne('SELECT SUM(amount) as total FROM point_transactions WHERE amount > 0')['total'] ?? 0,
    'total_points_spent' => $db->queryOne('SELECT SUM(ABS(amount)) as total FROM point_transactions WHERE amount < 0')['total'] ?? 0
];

// Последние действия пользователей
$recentUsers = $db->getLatest('users', 10, 'created_at DESC');
$recentListings = $db->getLatest('listings', 10, 'created_at DESC');
$recentTransactions = $db->getLatest('point_transactions', 10, 'created_at DESC');
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Административная панель - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="../assets/css/main.css">
    <link rel="stylesheet" href="../assets/css/auth.css">
    <link rel="stylesheet" href="assets/css/admin.css">
</head>
<body>
    <div class="admin-container">
        <!-- Боковая панель -->
        <nav class="admin-sidebar">
            <div class="sidebar-header">
                <h2><?php echo SITE_NAME; ?></h2>
                <p>Администрирование</p>
            </div>
            
            <ul class="sidebar-menu">
                <li><a href="index.php" class="active">Главная панель</a></li>
                <li><a href="users.php">Управление пользователями</a></li>
                <li><a href="listings.php">Управление объявлениями</a></li>
                <li><a href="companies.php">Управление компаниями</a></li>
                <li><a href="categories.php">Категории</a></li>
                <li><a href="points.php">Управление баллами</a></li>
                <li><a href="settings.php">Настройки системы</a></li>
                <li><a href="reports.php">Отчеты</a></li>
                <li class="separator"></li>
                <li><a href="../profile.php">Мой профиль</a></li>
                <li><a href="../index.php">Вернуться на сайт</a></li>
                <li><a href="../logout.php">Выйти</a></li>
            </ul>
        </nav>

        <!-- Основной контент -->
        <main class="admin-main">
            <div class="admin-header">
                <h1>Панель управления</h1>
                <div class="admin-user-info">
                    <span>Добро пожаловать, <?php echo htmlspecialchars($_SESSION['user_name']); ?>!</span>
                    <span class="role-badge <?php echo $_SESSION['user_role']; ?>">
                        <?php echo $_SESSION['user_role'] === ADMIN_ROLE ? 'Администратор' : 'Редактор'; ?>
                    </span>
                </div>
            </div>

            <!-- Статистические карточки -->
            <div class="stats-grid">
                <div class="stat-card users">
                    <div class="stat-icon">👥</div>
                    <div class="stat-info">
                        <h3><?php echo number_format($stats['total_users']); ?></h3>
                        <p>Всего пользователей</p>
                        <small><?php echo $stats['new_users_today']; ?> новых сегодня</small>
                    </div>
                </div>

                <div class="stat-card listings">
                    <div class="stat-icon">📋</div>
                    <div class="stat-info">
                        <h3><?php echo number_format($stats['total_listings']); ?></h3>
                        <p>Объявлений</p>
                        <small><?php echo $stats['active_listings']; ?> активных</small>
                    </div>
                </div>

                <div class="stat-card companies">
                    <div class="stat-icon">🏢</div>
                    <div class="stat-info">
                        <h3><?php echo number_format($stats['total_companies']); ?></h3>
                        <p>Компаний</p>
                        <small><?php echo $stats['active_companies']; ?> активных</small>
                    </div>
                </div>

                <div class="stat-card points">
                    <div class="stat-icon">💎</div>
                    <div class="stat-info">
                        <h3><?php echo number_format($stats['total_points_issued']); ?></h3>
                        <p>Баллов выдано</p>
                        <small><?php echo number_format($stats['total_points_spent']); ?> потрачено</small>
                    </div>
                </div>
            </div>

            <!-- Быстрые действия -->
            <div class="quick-actions">
                <h2>Быстрые действия</h2>
                <div class="actions-grid">
                    <a href="users.php?action=add" class="action-card">
                        <div class="action-icon">➕👤</div>
                        <div class="action-title">Добавить пользователя</div>
                    </a>
                    
                    <a href="points.php?action=add" class="action-card">
                        <div class="action-icon">💰</div>
                        <div class="action-title">Начислить баллы</div>
                    </a>
                    
                    <a href="listings.php?status=pending" class="action-card">
                        <div class="action-icon">⏳</div>
                        <div class="action-title">Модерация объявлений</div>
                    </a>
                    
                    <a href="reports.php" class="action-card">
                        <div class="action-icon">📊</div>
                        <div class="action-title">Создать отчет</div>
                    </a>
                </div>
            </div>

            <!-- Последняя активность -->
            <div class="recent-activity">
                <div class="activity-section">
                    <h3>Новые пользователи</h3>
                    <div class="activity-list">
                        <?php foreach (array_slice($recentUsers, 0, 5) as $user): ?>
                            <div class="activity-item">
                                <div class="activity-avatar">
                                    <?php echo mb_substr($user['name'], 0, 1); ?>
                                </div>
                                <div class="activity-details">
                                    <strong><?php echo htmlspecialchars($user['name']); ?></strong>
                                    <p><?php echo htmlspecialchars($user['email']); ?></p>
                                    <small><?php echo formatDate($user['created_at'], 'd.m.Y H:i'); ?></small>
                                </div>
                                <div class="activity-actions">
                                    <a href="users.php?id=<?php echo $user['id']; ?>" class="btn-small">Просмотр</a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <a href="users.php" class="view-all">Посмотреть всех пользователей →</a>
                </div>

                <div class="activity-section">
                    <h3>Новые объявления</h3>
                    <div class="activity-list">
                        <?php foreach (array_slice($recentListings, 0, 5) as $listing): ?>
                            <div class="activity-item">
                                <div class="activity-details">
                                    <strong><?php echo htmlspecialchars($listing['title']); ?></strong>
                                    <p><?php echo $LISTING_TYPES[$listing['type']] ?? $listing['type']; ?></p>
                                    <small><?php echo formatDate($listing['created_at'], 'd.m.Y H:i'); ?></small>
                                </div>
                                <div class="activity-status">
                                    <span class="status-badge <?php echo $listing['status']; ?>">
                                        <?php 
                                        switch($listing['status']) {
                                            case 'active': echo 'Активно'; break;
                                            case 'pending': echo 'На модерации'; break;
                                            case 'inactive': echo 'Неактивно'; break;
                                            default: echo $listing['status'];
                                        }
                                        ?>
                                    </span>
                                </div>
                                <div class="activity-actions">
                                    <a href="listings.php?id=<?php echo $listing['id']; ?>" class="btn-small">Просмотр</a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <a href="listings.php" class="view-all">Посмотреть все объявления →</a>
                </div>
            </div>

            <!-- Операции с баллами -->
            <div class="recent-transactions">
                <h3>Последние операции с баллами</h3>
                <div class="transactions-table">
                    <table>
                        <thead>
                            <tr>
                                <th>Дата</th>
                                <th>Пользователь</th>
                                <th>Тип операции</th>
                                <th>Сумма</th>
                                <th>Описание</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach (array_slice($recentTransactions, 0, 8) as $transaction): ?>
                                <?php 
                                $user = $db->queryOne('SELECT name FROM users WHERE id = ?', [$transaction['user_id']]);
                                ?>
                                <tr>
                                    <td><?php echo formatDate($transaction['created_at'], 'd.m.Y H:i'); ?></td>
                                    <td><?php echo htmlspecialchars($user['name'] ?? 'Неизвестный'); ?></td>
                                    <td><?php echo htmlspecialchars($transaction['type']); ?></td>
                                    <td class="amount <?php echo $transaction['amount'] > 0 ? 'positive' : 'negative'; ?>">
                                        <?php echo $transaction['amount'] > 0 ? '+' : ''; ?><?php echo $transaction['amount']; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($transaction['description']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <a href="points.php" class="view-all">Посмотреть все операции →</a>
            </div>
        </main>
    </div>
</body>
</html>