<?php
/**
 * Конфигурация портала TextilServer.ru
 * Настройки подключения к базе данных и основные константы
 */

// Включаем отображение ошибок для разработки
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Настройки часового пояса
date_default_timezone_set('Europe/Moscow');

// Настройки базы данных
define('DB_HOST', 'localhost');
define('DB_NAME', 'textilserver_db');
define('DB_USER', 'db_user');
define('DB_PASS', 'db_password_change_me');
define('DB_CHARSET', 'utf8mb4');

// Основные настройки сайта
define('SITE_NAME', 'TextilServer.ru');
define('SITE_DESCRIPTION', 'B2B портал для текстильной и легкой промышленности');
define('SITE_URL', 'http://localhost/textilserver');

// Настройки системы баллов
define('POINTS_PER_LISTING', 20);
define('POINTS_PER_TOP', 50);
define('POINTS_PER_FEATURED', 30);

// Тарифы временного членства (в баллах)
define('MEMBERSHIP_1MONTH', 120);
define('MEMBERSHIP_3MONTHS', 300);
define('MEMBERSHIP_5MONTHS', 560);
define('MEMBERSHIP_12MONTHS', 1000);
define('PRICELIST_ACCESS_12MONTHS', 1000);

// Настройки сессии
define('SESSION_LIFETIME', 7200); // 2 часа

// Папки загрузки
define('UPLOAD_DIR', 'uploads/');
define('COMPANY_LOGOS_DIR', UPLOAD_DIR . 'logos/');
define('PRODUCT_IMAGES_DIR', UPLOAD_DIR . 'products/');
define('PRICELISTS_DIR', UPLOAD_DIR . 'pricelists/');

// Максимальные размеры файлов (в байтах)
define('MAX_LOGO_SIZE', 2 * 1024 * 1024); // 2 МБ
define('MAX_IMAGE_SIZE', 5 * 1024 * 1024); // 5 МБ
define('MAX_PRICELIST_SIZE', 10 * 1024 * 1024); // 10 МБ

// Разрешенные типы файлов
define('ALLOWED_IMAGE_TYPES', ['jpg', 'jpeg', 'png', 'gif', 'webp']);
define('ALLOWED_PRICELIST_TYPES', ['pdf', 'xls', 'xlsx']);

// Статусы пользователей
define('USER_OBSERVER', 'observer');
define('USER_PARTICIPANT', 'participant');
define('USER_LEADER', 'leader');

// Административные роли
define('ADMIN_ROLE', 'admin');
define('CONTENT_EDITOR_ROLE', 'content_editor');

// Статусы объявлений
define('LISTING_ACTIVE', 'active');
define('LISTING_INACTIVE', 'inactive');
define('LISTING_EXPIRED', 'expired');
define('LISTING_SOLD', 'sold');

// Типы объявлений
define('LISTING_READY_PRODUCTS', 'ready_products');
define('LISTING_RAW_MATERIALS', 'raw_materials');
define('LISTING_EQUIPMENT', 'equipment');
define('LISTING_JOBS', 'jobs');
define('LISTING_SERVICES', 'services');
define('LISTING_RENTAL', 'rental');

// Типы компаний
define('COMPANY_PRODUCER', 'producer');
define('COMPANY_TRADING', 'trading');
define('COMPANY_SERVICE', 'service');

// Массив типов объявлений для отображения
$LISTING_TYPES = [
    LISTING_READY_PRODUCTS => 'Готовая продукция',
    LISTING_RAW_MATERIALS => 'Сырье и материалы', 
    LISTING_EQUIPMENT => 'Оборудование',
    LISTING_JOBS => 'Вакансии и работа',
    LISTING_SERVICES => 'Услуги',
    LISTING_RENTAL => 'Аренда'
];

// Массив типов компаний для отображения
$COMPANY_TYPES = [
    COMPANY_PRODUCER => 'Производители',
    COMPANY_TRADING => 'Торговые компании',
    COMPANY_SERVICE => 'Сервисные компании'
];

// Функция безопасности для очистки входных данных
function sanitizeInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

// Функция форматирования цены
function formatPrice($price, $currency = 'RUB') {
    if ($price === null || $price === '') {
        return 'Цена по запросу';
    }
    
    $formatted = number_format($price, 2, ',', ' ');
    
    switch ($currency) {
        case 'RUB':
            return $formatted . ' ₽';
        case 'USD':
            return '$' . $formatted;
        case 'EUR':
            return '€' . $formatted;
        default:
            return $formatted . ' ' . $currency;
    }
}

// Функция форматирования даты
function formatDate($date, $format = 'd.m.Y H:i') {
    if (empty($date)) return '';
    
    $timestamp = is_string($date) ? strtotime($date) : $date;
    return date($format, $timestamp);
}

// Функция создания краткого описания
function createExcerpt($text, $length = 150) {
    $text = strip_tags($text);
    if (mb_strlen($text) <= $length) {
        return $text;
    }
    
    $text = mb_substr($text, 0, $length);
    $lastSpace = mb_strrpos($text, ' ');
    if ($lastSpace !== false) {
        $text = mb_substr($text, 0, $lastSpace);
    }
    
    return $text . '...';
}

// Функция проверки прав доступа пользователя
function canAccessContacts($userLevel, $participantUntil = null) {
    if ($userLevel === USER_OBSERVER) {
        return false;
    }
    
    if ($userLevel === USER_PARTICIPANT || $userLevel === USER_LEADER) {
        // Для участников проверяем срок действия членства
        if ($participantUntil && strtotime($participantUntil) > time()) {
            return true;
        }
        return false;
    }
    
    return false;
}

// Функция проверки возможности размещения объявлений
function canCreateListing($userLevel, $balancePoints, $participantUntil = null) {
    if (!canAccessContacts($userLevel, $participantUntil)) {
        return false;
    }
    
    return $balancePoints >= POINTS_PER_LISTING;
}

// Автозагрузка классов
spl_autoload_register(function ($className) {
    $classFile = __DIR__ . '/classes/' . $className . '.php';
    if (file_exists($classFile)) {
        require_once $classFile;
    }
});

// Старт сессии
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>