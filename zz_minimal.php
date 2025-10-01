<?php
require_once 'includes/config.php';

// Получение экземпляра базы данных
$db = Database::getInstance();

// Получение последних объявлений по категориям
$recentListings = [
    'ready_products' => $db->getLatest('listings', 3, 'created_at DESC', 'type = ? AND status = ?', ['ready_products', 'active']),
    'raw_materials' => $db->getLatest('listings', 3, 'created_at DESC', 'type = ? AND status = ?', ['raw_materials', 'active']),
    'equipment' => $db->getLatest('listings', 3, 'created_at DESC', 'type = ? AND status = ?', ['equipment', 'active']),
    'jobs' => $db->getLatest('listings', 3, 'created_at DESC', 'type = ? AND status = ?', ['jobs', 'active']),
    'services' => $db->getLatest('listings', 3, 'created_at DESC', 'type = ? AND status = ?', ['services', 'active']),
    'rental' => $db->getLatest('listings', 3, 'created_at DESC', 'type = ? AND status = ?', ['rental', 'active'])
];

// Получение последних компаний
$recentCompanies = $db->getLatest('companies', 3, 'created_at DESC', 'status = ?', ['active']);

// Статистика портала
$stats = [
    'total_listings' => $db->count('listings', 'status = ?', ['active']),
    'total_companies' => $db->count('companies', 'status = ?', ['active']),
    'total_categories' => $db->count('categories', 'parent_id IS NULL'),
    'total_users' => $db->count('users', 'status = ?', ['active'])
];
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo SITE_NAME; ?> - <?php echo SITE_DESCRIPTION; ?></title>
    <meta name="description" content="<?php echo SITE_DESCRIPTION; ?>">
    <meta name="keywords" content="текстиль, легкая промышленность, B2B, производители, оборудование, сырье">
    
    <link rel="stylesheet" href="assets/css/main.css">
    <!-- Google Fonts removed for security compliance -->
</head>
<body>
    <!-- Верхняя панель -->
    <div class="top-bar">
        <div class="container">
            <div class="top-bar-content">
                <div class="contact-info">
                    <span class="phone">+7 (XXX) XXX-XX-XX</span>
                    <span class="email">contact[at]domain[dot]com</span>
                </div>
                <div class="user-panel">
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <span class="welcome">Добро пожаловать, <?php echo htmlspecialchars($_SESSION['user_name']); ?>!</span>
                        <a href="profile.php" class="profile-link">Личный кабинет</a>
                        <a href="logout.php" class="logout-link">Выход</a>
                    <?php else: ?>
                        <a href="login.php" class="login-link">Вход</a>
                        <a href="register.php" class="register-link">Регистрация</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Основной заголовок -->
    <header class="main-header">
        <div class="container">
            <div class="header-content">
                <div class="logo">
                    <a href="index.php">
                        <h1><?php echo SITE_NAME; ?></h1>
                        <p class="tagline"><?php echo SITE_DESCRIPTION; ?></p>
                    </a>
                </div>
                <div class="header-stats">
                    <div class="stat-item">
                        <span class="number"><?php echo number_format($stats['total_listings']); ?></span>
                        <span class="label">объявлений</span>
                    </div>
                    <div class="stat-item">
                        <span class="number"><?php echo number_format($stats['total_companies']); ?></span>
                        <span class="label">компаний</span>
                    </div>
                    <div class="stat-item">
                        <span class="number"><?php echo number_format($stats['total_categories']); ?></span>
                        <span class="label">категорий</span>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <!-- Навигационное меню -->
    <nav class="main-nav">
        <div class="container">
            <ul class="nav-menu">
                <li><a href="index.php" class="active">Главная</a></li>
                <li><a href="listings.php">Доска объявлений</a></li>
                <li><a href="companies.php">Компании</a></li>
                <li><a href="catalog.php">Каталог продукции</a></li>
                <li><a href="analytics.php">Отраслевая аналитика</a></li>
                <li><a href="events.php">События и выставки</a></li>
                <li><a href="about.php">О проекте</a></li>
            </ul>
        </div>
    </nav>

    <!-- Основной контент -->
    <main class="main-content">
        <div class="container">
            <!-- Категории доски объявлений -->
            <section class="marketplace-categories">
                <h2>Доска объявлений текстильной отрасли</h2>
                
                <div class="categories-grid">
                    <!-- Готовая продукция -->
                    <div class="category-card">
                        <div class="category-header">
                            <h3>
                                <a href="listings.php?type=ready_products">Готовая продукция</a>
                            </h3>
                            <span class="listing-count"><?php echo count($recentListings['ready_products']); ?> новых</span>
                        </div>
                        <div class="category-listings">
                            <?php foreach ($recentListings['ready_products'] as $listing): ?>
                                <div class="listing-item">
                                    <h4><a href="listing.php?id=<?php echo $listing['id']; ?>"><?php echo htmlspecialchars($listing['title']); ?></a></h4>
                                    <p class="price"><?php echo formatPrice($listing['price'], $listing['currency']); ?></p>
                                    <p class="excerpt"><?php echo createExcerpt($listing['description']); ?></p>
                                    <p class="meta">
                                        <span class="location"><?php echo htmlspecialchars($listing['location']); ?></span>
                                        <span class="date"><?php echo formatDate($listing['created_at'], 'd.m.Y'); ?></span>
                                    </p>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Сырье и материалы -->
                    <div class="category-card">
                        <div class="category-header">
                            <h3>
                                <a href="listings.php?type=raw_materials">Сырье и материалы</a>
                            </h3>
                            <span class="listing-count"><?php echo count($recentListings['raw_materials']); ?> новых</span>
                        </div>
                        <div class="category-listings">
                            <?php foreach ($recentListings['raw_materials'] as $listing): ?>
                                <div class="listing-item">
                                    <h4><a href="listing.php?id=<?php echo $listing['id']; ?>"><?php echo htmlspecialchars($listing['title']); ?></a></h4>
                                    <p class="price"><?php echo formatPrice($listing['price'], $listing['currency']); ?></p>
                                    <p class="excerpt"><?php echo createExcerpt($listing['description']); ?></p>
                                    <p class="meta">
                                        <span class="location"><?php echo htmlspecialchars($listing['location']); ?></span>
                                        <span class="date"><?php echo formatDate($listing['created_at'], 'd.m.Y'); ?></span>
                                    </p>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Оборудование -->
                    <div class="category-card">
                        <div class="category-header">
                            <h3>
                                <a href="listings.php?type=equipment">Оборудование</a>
                            </h3>
                            <span class="listing-count"><?php echo count($recentListings['equipment']); ?> новых</span>
                        </div>
                        <div class="category-listings">
                            <?php foreach ($recentListings['equipment'] as $listing): ?>
                                <div class="listing-item">
                                    <h4><a href="listing.php?id=<?php echo $listing['id']; ?>"><?php echo htmlspecialchars($listing['title']); ?></a></h4>
                                    <p class="price"><?php echo formatPrice($listing['price'], $listing['currency']); ?></p>
                                    <p class="excerpt"><?php echo createExcerpt($listing['description']); ?></p>
                                    <p class="meta">
                                        <span class="location"><?php echo htmlspecialchars($listing['location']); ?></span>
                                        <span class="date"><?php echo formatDate($listing['created_at'], 'd.m.Y'); ?></span>
                                    </p>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Вакансии и работа -->
                    <div class="category-card">
                        <div class="category-header">
                            <h3>
                                <a href="listings.php?type=jobs">Вакансии и работа</a>
                            </h3>
                            <span class="listing-count"><?php echo count($recentListings['jobs']); ?> новых</span>
                        </div>
                        <div class="category-listings">
                            <?php foreach ($recentListings['jobs'] as $listing): ?>
                                <div class="listing-item">
                                    <h4><a href="listing.php?id=<?php echo $listing['id']; ?>"><?php echo htmlspecialchars($listing['title']); ?></a></h4>
                                    <p class="price"><?php echo formatPrice($listing['salary_from'], $listing['currency']) . ' - ' . formatPrice($listing['salary_to'], $listing['currency']); ?></p>
                                    <p class="excerpt"><?php echo createExcerpt($listing['description']); ?></p>
                                    <p class="meta">
                                        <span class="location"><?php echo htmlspecialchars($listing['location']); ?></span>
                                        <span class="date"><?php echo formatDate($listing['created_at'], 'd.m.Y'); ?></span>
                                    </p>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Услуги -->
                    <div class="category-card">
                        <div class="category-header">
                            <h3>
                                <a href="listings.php?type=services">Услуги</a>
                            </h3>
                            <span class="listing-count"><?php echo count($recentListings['services']); ?> новых</span>
                        </div>
                        <div class="category-listings">
                            <?php foreach ($recentListings['services'] as $listing): ?>
                                <div class="listing-item">
                                    <h4><a href="listing.php?id=<?php echo $listing['id']; ?>"><?php echo htmlspecialchars($listing['title']); ?></a></h4>
                                    <p class="price"><?php echo formatPrice($listing['price'], $listing['currency']); ?></p>
                                    <p class="excerpt"><?php echo createExcerpt($listing['description']); ?></p>
                                    <p class="meta">
                                        <span class="location"><?php echo htmlspecialchars($listing['location']); ?></span>
                                        <span class="date"><?php echo formatDate($listing['created_at'], 'd.m.Y'); ?></span>
                                    </p>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Аренда -->
                    <div class="category-card">
                        <div class="category-header">
                            <h3>
                                <a href="listings.php?type=rental">Аренда</a>
                            </h3>
                            <span class="listing-count"><?php echo count($recentListings['rental']); ?> новых</span>
                        </div>
                        <div class="category-listings">
                            <?php foreach ($recentListings['rental'] as $listing): ?>
                                <div class="listing-item">
                                    <h4><a href="listing.php?id=<?php echo $listing['id']; ?>"><?php echo htmlspecialchars($listing['title']); ?></a></h4>
                                    <p class="price"><?php echo formatPrice($listing['price'], $listing['currency']); ?></p>
                                    <p class="excerpt"><?php echo createExcerpt($listing['description']); ?></p>
                                    <p class="meta">
                                        <span class="location"><?php echo htmlspecialchars($listing['location']); ?></span>
                                        <span class="date"><?php echo formatDate($listing['created_at'], 'd.m.Y'); ?></span>
                                    </p>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Новые компании -->
            <section class="new-companies">
                <h2>Новые компании в каталоге</h2>
                <div class="companies-grid">
                    <?php foreach ($recentCompanies as $company): ?>
                        <div class="company-card">
                            <div class="company-logo">
                                <?php if (!empty($company['logo'])): ?>
                                    <img src="<?php echo COMPANY_LOGOS_DIR . $company['logo']; ?>" alt="<?php echo htmlspecialchars($company['name']); ?>">
                                <?php else: ?>
                                    <div class="logo-placeholder"><?php echo mb_substr($company['name'], 0, 1); ?></div>
                                <?php endif; ?>
                            </div>
                            <div class="company-info">
                                <h3><a href="company.php?id=<?php echo $company['id']; ?>"><?php echo htmlspecialchars($company['name']); ?></a></h3>
                                <p class="company-type"><?php echo $COMPANY_TYPES[$company['type']] ?? 'Компания'; ?></p>
                                <p class="company-description"><?php echo createExcerpt($company['description'], 100); ?></p>
                                <p class="company-location"><?php echo htmlspecialchars($company['city']); ?></p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="section-footer">
                    <a href="companies.php" class="view-all-link">Посмотреть все компании →</a>
                </div>
            </section>
        </div>
    </main>

    <!-- Подвал -->
    <footer class="main-footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-section">
                    <h4><?php echo SITE_NAME; ?></h4>
                    <p><?php echo SITE_DESCRIPTION; ?></p>
                    <p>Ведущий B2B портал для специалистов текстильной и легкой промышленности России.</p>
                </div>
                
                <div class="footer-section">
                    <h4>Разделы</h4>
                    <ul>
                        <li><a href="listings.php">Доска объявлений</a></li>
                        <li><a href="companies.php">Каталог компаний</a></li>
                        <li><a href="catalog.php">Каталог продукции</a></li>
                        <li><a href="analytics.php">Отраслевая аналитика</a></li>
                    </ul>
                </div>
                
                <div class="footer-section">
                    <h4>Информация</h4>
                    <ul>
                        <li><a href="about.php">О портале</a></li>
                        <li><a href="rules.php">Правила использования</a></li>
                        <li><a href="privacy.php">Политика конфиденциальности</a></li>
                        <li><a href="contacts.php">Контакты</a></li>
                    </ul>
                </div>
                
                <div class="footer-section">
                    <h4>Контакты</h4>
                    <p>Телефон: +7 (XXX) XXX-XX-XX</p>
                    <p>Email: contact[at]domain[dot]com</p>
                    <p>Адрес: г. Москва, ул. Example, 10</p>
                </div>
            </div>
            
            <div class="footer-bottom">
                <p>&copy; <?php echo date('Y'); ?> <?php echo SITE_NAME; ?>. Все права защищены.</p>
            </div>
        </div>
    </footer>

    <script src="assets/js/main.js"></script>
</body>
</html>