-- База данных TextilServer.ru
-- Система платного временного членства для B2B портала текстильной индустрии

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- Создание базы данных
CREATE DATABASE IF NOT EXISTS `textilserver` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `textilserver`;

-- ============================================================================
-- ТАБЛИЦЫ ПОЛЬЗОВАТЕЛЕЙ И СИСТЕМЫ ВРЕМЕННОГО ЧЛЕНСТВА
-- ============================================================================

-- Пользователи и система временного членства
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `email` varchar(255) NOT NULL UNIQUE,
  `pwd_hash` varchar(255) NOT NULL,
  `user_level` enum('observer','participant','leader') NOT NULL DEFAULT 'observer',
  `balance_points` int(11) NOT NULL DEFAULT 0,
  `participant_until` timestamp NULL DEFAULT NULL COMMENT 'окончание срока участия',
  `pricelist_access_until` timestamp NULL DEFAULT NULL COMMENT 'окончание доступа к прайс-листу',
  `is_blocked` tinyint(1) NOT NULL DEFAULT 0,
  `blocked_at` timestamp NULL DEFAULT NULL,
  `blocked_reason` text NULL,
  `admin_role` enum('admin','content_editor') NULL DEFAULT NULL,
  `first_name` varchar(100) NULL,
  `last_name` varchar(100) NULL,
  `phone` varchar(20) NULL,
  `company_name` varchar(255) NULL,
  `position` varchar(100) NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user_level` (`user_level`),
  KEY `idx_participant_until` (`participant_until`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Транзакции баллов (только внутренняя система)
CREATE TABLE `balance_transactions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `amount` int(11) NOT NULL COMMENT 'положительное для начисления, отрицательное для списания',
  `type` enum('admin_grant','listing_payment','admin_adjustment','membership_payment','pricelist_payment') NOT NULL,
  `description` text NOT NULL,
  `listing_id` int(11) NULL,
  `admin_id` int(11) NULL,
  `membership_months` int(11) NULL COMMENT 'количество месяцев членства',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_type` (`type`),
  KEY `idx_created_at` (`created_at`),
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`admin_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- КОМПАНИИ И ПРАЙС-ЛИСТЫ
-- ============================================================================

-- Компании и прайс-листы
CREATE TABLE `companies` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `type` enum('producer','trading','service') NOT NULL,
  `description` text NULL,
  `address` text NULL,
  `phone` varchar(50) NULL,
  `email` varchar(255) NULL,
  `website` varchar(255) NULL,
  `logo_url` varchar(500) NULL,
  `verified` tinyint(1) NOT NULL DEFAULT 0,
  `price_list_url` varchar(500) NULL,
  `price_list_format` enum('pdf','xls','xlsx') NULL,
  `region` varchar(100) NULL,
  `city` varchar(100) NULL,
  `legal_form` varchar(50) NULL COMMENT 'ООО, ИП, ЗАО и т.д.',
  `inn` varchar(20) NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_type` (`type`),
  KEY `idx_verified` (`verified`),
  KEY `idx_region` (`region`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Связь компаний и пользователей
CREATE TABLE `company_users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `company_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `role` enum('owner','manager','employee') NOT NULL DEFAULT 'employee',
  `permissions` json NULL COMMENT 'дополнительные права доступа',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_company_user` (`company_id`, `user_id`),
  FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- КАТАЛОГ ПРОДУКЦИИ
-- ============================================================================

-- Категории продукции
CREATE TABLE `categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `parent_id` int(11) NULL,
  `type` enum('raw_materials','fabrics','equipment','finished_products','chemicals') NOT NULL,
  `attributes_schema` json NULL COMMENT 'схема атрибутов для данной категории',
  `description` text NULL,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `idx_parent_id` (`parent_id`),
  KEY `idx_type` (`type`),
  KEY `idx_sort_order` (`sort_order`),
  FOREIGN KEY (`parent_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Продукты
CREATE TABLE `products` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `company_id` int(11) NOT NULL,
  `category_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text NULL,
  `specifications` text NULL,
  `price` decimal(15,2) NULL,
  `currency` varchar(3) NOT NULL DEFAULT 'RUB',
  `unit` varchar(50) NULL COMMENT 'единица измерения',
  `images` json NULL COMMENT 'массив URL изображений',
  `status` enum('active','inactive','out_of_stock') NOT NULL DEFAULT 'active',
  `views_count` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_company_id` (`company_id`),
  KEY `idx_category_id` (`category_id`),
  KEY `idx_status` (`status`),
  KEY `idx_created_at` (`created_at`),
  FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Атрибуты продуктов
CREATE TABLE `product_attributes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `product_id` int(11) NOT NULL,
  `attribute_name` varchar(255) NOT NULL,
  `attribute_value` text NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_product_id` (`product_id`),
  KEY `idx_attribute_name` (`attribute_name`),
  FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- ТОРГОВАЯ ПЛОЩАДКА (ДОСКА ОБЪЯВЛЕНИЙ)
-- ============================================================================

-- Объявления на торговой площадке
CREATE TABLE `listings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `company_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `type` enum('ready_products','raw_materials','equipment','jobs','services','rental') NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `category_id` int(11) NULL,
  `price` decimal(15,2) NULL,
  `quantity` decimal(10,3) NULL,
  `unit` varchar(50) NULL,
  `location` varchar(255) NULL,
  `contact_person` varchar(255) NULL,
  `contact_phone` varchar(50) NULL,
  `contact_email` varchar(255) NULL,
  `status` enum('active','inactive','expired','sold') NOT NULL DEFAULT 'active',
  `is_featured` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'выделенное объявление',
  `is_top` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'поднято в топ',
  `expires_at` timestamp NULL DEFAULT NULL,
  `views_count` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_company_id` (`company_id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_type` (`type`),
  KEY `idx_category_id` (`category_id`),
  KEY `idx_status` (`status`),
  KEY `idx_created_at` (`created_at`),
  KEY `idx_is_featured` (`is_featured`),
  KEY `idx_is_top` (`is_top`),
  FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Добавляем foreign key constraint для balance_transactions после создания таблицы listings
ALTER TABLE `balance_transactions` ADD FOREIGN KEY (`listing_id`) REFERENCES `listings` (`id`) ON DELETE SET NULL;

-- Отклики на объявления
CREATE TABLE `listing_responses` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `listing_id` int(11) NOT NULL,
  `company_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `message` text NOT NULL,
  `price_offer` decimal(15,2) NULL,
  `quantity` decimal(10,3) NULL,
  `status` enum('pending','accepted','rejected') NOT NULL DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_listing_id` (`listing_id`),
  KEY `idx_company_id` (`company_id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_status` (`status`),
  FOREIGN KEY (`listing_id`) REFERENCES `listings` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- КОНТЕНТ И НОВОСТИ
-- ============================================================================

-- Новости портала
CREATE TABLE `news` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(500) NOT NULL,
  `content` text NOT NULL,
  `excerpt` text NULL COMMENT 'краткое описание',
  `author_id` int(11) NOT NULL,
  `category` varchar(100) NULL,
  `tags` json NULL,
  `image_url` varchar(500) NULL,
  `is_published` tinyint(1) NOT NULL DEFAULT 0,
  `published_at` timestamp NULL DEFAULT NULL,
  `views_count` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_author_id` (`author_id`),
  KEY `idx_published_at` (`published_at`),
  KEY `idx_category` (`category`),
  FOREIGN KEY (`author_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Статьи и аналитика
CREATE TABLE `articles` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(500) NOT NULL,
  `content` text NOT NULL,
  `excerpt` text NULL,
  `author_id` int(11) NOT NULL,
  `category` varchar(100) NULL,
  `tags` json NULL,
  `image_url` varchar(500) NULL,
  `is_published` tinyint(1) NOT NULL DEFAULT 0,
  `published_at` timestamp NULL DEFAULT NULL,
  `views_count` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_author_id` (`author_id`),
  KEY `idx_published_at` (`published_at`),
  KEY `idx_category` (`category`),
  FOREIGN KEY (`author_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- ВЫСТАВКИ И МЕРОПРИЯТИЯ
-- ============================================================================

-- Выставки и мероприятия
CREATE TABLE `events` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `type` enum('exhibition','conference','seminar','webinar','meeting') NOT NULL,
  `start_date` timestamp NOT NULL,
  `end_date` timestamp NULL DEFAULT NULL,
  `location` varchar(255) NULL,
  `address` text NULL,
  `organizer_id` int(11) NOT NULL,
  `website` varchar(255) NULL,
  `registration_url` varchar(255) NULL,
  `price` decimal(10,2) NULL,
  `capacity` int(11) NULL,
  `image_url` varchar(500) NULL,
  `is_published` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_start_date` (`start_date`),
  KEY `idx_type` (`type`),
  KEY `idx_organizer_id` (`organizer_id`),
  FOREIGN KEY (`organizer_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Участники мероприятий
CREATE TABLE `event_participants` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `event_id` int(11) NOT NULL,
  `company_id` int(11) NOT NULL,
  `booth_number` varchar(50) NULL,
  `participation_type` enum('exhibitor','visitor','sponsor','speaker') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_event_company` (`event_id`, `company_id`),
  FOREIGN KEY (`event_id`) REFERENCES `events` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- НАСТРОЙКИ И СИСТЕМНЫЕ ТАБЛИЦЫ
-- ============================================================================

-- Настройки портала
CREATE TABLE `settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(255) NOT NULL UNIQUE,
  `setting_value` text NULL,
  `description` text NULL,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_setting_key` (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Логи системы
CREATE TABLE `system_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NULL,
  `action` varchar(255) NOT NULL,
  `table_name` varchar(100) NULL,
  `record_id` int(11) NULL,
  `old_values` json NULL,
  `new_values` json NULL,
  `ip_address` varchar(45) NULL,
  `user_agent` text NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_action` (`action`),
  KEY `idx_created_at` (`created_at`),
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- ВСТАВКА ТЕСТОВЫХ ДАННЫХ
-- ============================================================================

-- Основные категории продукции
INSERT INTO `categories` (`name`, `parent_id`, `type`, `sort_order`) VALUES
('Готовая продукция', NULL, 'finished_products', 1),
('Сырье и материалы', NULL, 'raw_materials', 2),
('Оборудование', NULL, 'equipment', 3),
('Химикаты и красители', NULL, 'chemicals', 4);

-- Подкатегории готовой продукции
INSERT INTO `categories` (`name`, `parent_id`, `type`, `sort_order`) VALUES
('Одежда мужская', 1, 'finished_products', 1),
('Одежда женская', 1, 'finished_products', 2),
('Одежда детская', 1, 'finished_products', 3),
('Домашний текстиль', 1, 'finished_products', 4),
('Спецодежда', 1, 'finished_products', 5);

-- Подкатегории сырья
INSERT INTO `categories` (`name`, `parent_id`, `type`, `sort_order`) VALUES
('Натуральные волокна', 2, 'raw_materials', 1),
('Химические волокна', 2, 'raw_materials', 2),
('Пряжа и нити', 2, 'raw_materials', 3),
('Ткани тканые', 2, 'raw_materials', 4),
('Ткани трикотажные', 2, 'raw_materials', 5);

-- Подкатегории оборудования
INSERT INTO `categories` (`name`, `parent_id`, `type`, `sort_order`) VALUES
('Прядильное оборудование', 3, 'equipment', 1),
('Ткацкое оборудование', 3, 'equipment', 2),
('Трикотажное оборудование', 3, 'equipment', 3),
('Швейное оборудование', 3, 'equipment', 4),
('Красильно-отделочное оборудование', 3, 'equipment', 5);

-- Системные настройки
INSERT INTO `settings` (`setting_key`, `setting_value`, `description`) VALUES
('site_name', 'TextilServer.ru', 'Название портала'),
('site_description', 'B2B портал для текстильной и легкой промышленности', 'Описание портала'),
('points_per_listing', '20', 'Стоимость размещения объявления в баллах'),
('points_per_top', '50', 'Стоимость поднятия в топ в баллах'),
('points_per_featured', '30', 'Стоимость выделения объявления в баллах'),
('membership_1month', '120', 'Стоимость членства на 1 месяц в баллах'),
('membership_3months', '300', 'Стоимость членства на 3 месяца в баллах'),
('membership_5months', '560', 'Стоимость членства на 5 месяцев в баллах'),
('membership_12months', '1000', 'Стоимость членства на 12 месяцев в баллах'),
('pricelist_access_12months', '1000', 'Стоимость доступа к прайс-листам на 12 месяцев в баллах');

-- Тестовый администратор
INSERT INTO `users` (`email`, `pwd_hash`, `user_level`, `admin_role`, `first_name`, `last_name`, `balance_points`) VALUES
('admin@textilserver.ru', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'participant', 'admin', 'Администратор', 'Портала', 10000);

-- Тестовые компании
INSERT INTO `companies` (`name`, `type`, `description`, `city`, `phone`, `email`) VALUES
('ТексТрейд ООО', 'trading', 'Оптовая торговля текстильными материалами', 'Москва', '+7 495 123-45-67', 'info@textrade.ru'),
('Ивановская ткацкая фабрика', 'producer', 'Производство хлопчатобумажных тканей', 'Иваново', '+7 4932 55-66-77', 'sales@itf.ru'),
('СпецОдеждаПроф', 'producer', 'Производство специальной одежды', 'Санкт-Петербург', '+7 812 987-65-43', 'order@specprof.ru');

-- Тестовые объявления
INSERT INTO `listings` (`company_id`, `user_id`, `type`, `title`, `description`, `price`, `unit`, `location`, `contact_phone`) VALUES
(1, 1, 'raw_materials', 'Хлопок 100% для производства постельного белья', 'Предлагаем высококачественный хлопок для производства постельного белья. Сертифицированное сырье.', 250.00, 'кг', 'Москва', '+7 495 123-45-67'),
(2, 1, 'ready_products', 'Ткань бязь ГОСТ 100% хлопок', 'Бязь отбеленная, ширина 220 см, плотность 142 г/м². Подходит для постельного белья.', 180.00, 'п.м.', 'Иваново', '+7 4932 55-66-77'),
(3, 1, 'ready_products', 'Костюмы рабочие утепленные', 'Костюмы рабочие зимние. Ткань смесовая, утеплитель синтепон. Размеры 48-62.', 2500.00, 'шт', 'Санкт-Петербург', '+7 812 987-65-43');

SET FOREIGN_KEY_CHECKS = 1;