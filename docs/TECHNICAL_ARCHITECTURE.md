# Техническая архитектура TextilServer.ru

## Технологический стек

### Frontend
- **Framework**: Next.js 14+ (React 18+)
- **Styling**: Tailwind CSS + Headless UI
- **State Management**: Zustand + React Query (TanStack Query)
- **Forms**: React Hook Form + Zod
- **Charts**: Chart.js / Recharts
- **Maps**: Leaflet / MapBox
- **File Upload**: React Dropzone

### Backend
- **Runtime**: Node.js 18+ / Bun
- **Framework**: Fastify / Express.js
- **Database**: PostgreSQL 15+
- **ORM**: Prisma / Drizzle ORM
- **Cache**: Redis 7+
- **Search**: Elasticsearch / MeiliSearch
- **File Storage**: MinIO / AWS S3
- **Queue**: BullMQ (Redis-based)

### Infrastructure
- **Container**: Docker + Docker Compose
- **Orchestration**: Kubernetes (production)
- **CDN**: CloudFlare / AWS CloudFront
- **Monitoring**: Prometheus + Grafana
- **Logging**: ELK Stack (Elasticsearch, Logstash, Kibana)
- **CI/CD**: GitHub Actions / GitLab CI

## Архитектура базы данных

### Основные сущности

```sql
-- Пользователи и система временного членства
users (
  id, email, pwd_hash, 
  user_level ENUM('observer', 'participant', 'leader'), 
  balance_points INTEGER DEFAULT 0,
  participant_until TIMESTAMP NULL, -- окончание срока участия
  pricelist_access_until TIMESTAMP NULL, -- окончание доступа к размещению прайс-листа
  is_blocked BOOLEAN DEFAULT false,
  blocked_at TIMESTAMP NULL,
  blocked_reason TEXT NULL,
  admin_role ENUM('admin', 'content_editor') NULL,
  created_at, updated_at
)

-- Транзакции баллов (только внутренняя система)
balance_transactions (
  id, user_id, amount, type ENUM('admin_grant', 'listing_payment', 'admin_adjustment', 'membership_payment', 'pricelist_payment'),
  description, listing_id, admin_id, membership_months, 
  created_at
)

-- Компании и прайс-листы  
companies (
  id, name, type, description, address, phone, website, 
  logo_url, verified, price_list_url, price_list_format ENUM('pdf', 'xls', 'xlsx'),
  created_at, updated_at
)
company_users (company_id, user_id, role, permissions)

-- Каталог продукции
categories (id, name, parent_id, type, attributes_schema)
products (id, company_id, category_id, name, description, specifications, price, currency, unit, images, status)
product_attributes (product_id, attribute_name, attribute_value)

-- Торговая площадка
listings (id, company_id, type, title, description, category_id, price, quantity, unit, location, status, expires_at)
listing_responses (id, listing_id, company_id, message, price_offer, quantity, status, created_at)

-- Новости и контент
news (id, title, content, author_id, category, tags, published_at, views_count)
articles (id, title, content, author_id, category, tags, published_at, views_count)

-- Выставки и мероприятия
events (id, title, description, type, start_date, end_date, location, organizer_id, website)
event_participants (event_id, company_id, booth_number, participation_type)

-- Биржа и цены
price_indices (id, commodity, date, price, currency, source, region)
spot_prices (id, product_type, price, currency, exchange, timestamp)

-- Рейтинги и отзывы
ratings (id, company_id, rated_by, rating, review, transaction_id, created_at)
certifications (id, company_id, certificate_type, issuer, valid_until, document_url)
```

## API архитектура

### REST API структура
```
/api/v1/
├── auth/              # Аутентификация
│   ├── login
│   ├── register
│   ├── refresh
│   └── logout
├── users/             # Управление пользователями
│   ├── profile        # Профиль текущего пользователя
│   ├── balance        # Баланс баллов
│   ├── transactions   # История транзакций
│   ├── upgrade        # Повышение уровня
│   └── block          # Блокировка (только для админов)
├── admin/             # Административные функции
│   ├── users/         # Управление пользователями
│   │   ├── [id]/block
│   │   ├── [id]/unblock
│   │   └── [id]/profile
│   ├── content/       # Управление контентом
│   │   ├── listings/edit
│   │   ├── companies/edit
│   │   └── products/catalog
│   └── analytics/     # Системная аналитика
├── companies/         # Компании
│   ├── [id]/profile
│   ├── [id]/products
│   ├── search
│   └── categories
├── products/          # Продукция
│   ├── search
│   ├── categories
│   ├── [id]
│   └── compare
├── marketplace/       # Торговая площадка
│   ├── listings
│   ├── [id]/responses
│   └── search
├── news/             # Новости
├── events/           # Мероприятия
├── prices/           # Цены и биржа
└── analytics/        # Аналитика
```

### GraphQL Schema (альтернатива)
```graphql
type Company {
  id: ID!
  name: String!
  type: CompanyType!
  products: [Product!]!
  listings: [Listing!]!
  rating: Float
  certifications: [Certification!]!
}

type Product {
  id: ID!
  name: String!
  category: Category!
  specifications: JSON
  price: Price
  images: [String!]!
  company: Company!
}

type Listing {
  id: ID!
  type: ListingType!
  title: String!
  description: String!
  price: Price
  quantity: Float
  responses: [ListingResponse!]!
}

type Query {
  companies(filter: CompanyFilter, sort: Sort, pagination: Pagination): CompanyConnection!
  products(filter: ProductFilter, sort: Sort, pagination: Pagination): ProductConnection!
  searchMarketplace(query: String!, filters: MarketplaceFilter): [Listing!]!
}
```

## Микросервисная архитектура

### Основные сервисы
```
┌─────────────────┐    ┌─────────────────┐    ┌─────────────────┐
│   Web Gateway   │    │   Mobile API    │    │   Admin Panel   │
│   (Next.js)     │    │   (REST/GraphQL)│    │   (React Admin) │
└─────────────────┘    └─────────────────┘    └─────────────────┘
         │                       │                       │
         └───────────────────────┼───────────────────────┘
                                 │
                    ┌─────────────────┐
                    │  API Gateway    │
                    │  (Kong/Envoy)   │
                    └─────────────────┘
                             │
            ┌────────────────┼────────────────┐
            │                │                │
    ┌───────────────┐ ┌──────────────┐ ┌─────────────┐
    │ Auth Service  │ │ Company Srvc │ │ Product Srv │
    │ (JWT, OAuth)  │ │              │ │             │
    └───────────────┘ └──────────────┘ └─────────────┘
            │                │                │
    ┌───────────────┐ ┌──────────────┐ ┌─────────────┐
    │Marketplace Srv│ │ News Service │ │ Event Srv   │
    │               │ │              │ │             │
    └───────────────┘ └──────────────┘ └─────────────┘
            │                │                │
    ┌───────────────┐ ┌──────────────┐ ┌─────────────┐
    │ Trading Srv   │ │Search Service│ │ Analytics   │
    │ (Биржа)       │ │(Elasticsearch│ │ Service     │
    └───────────────┘ └──────────────┘ └─────────────┘
```

### Общие сервисы
```
┌─────────────────┐    ┌─────────────────┐    ┌─────────────────┐
│ Notification    │    │ File Storage    │    │ Email Service   │
│ Service         │    │ Service         │    │ (SendGrid)      │
└─────────────────┘    └─────────────────┘    └─────────────────┘

┌─────────────────┐    ┌─────────────────┐    ┌─────────────────┐
│ Balance Service │    │ Logging Service │    │ Monitoring      │
│ (Internal Only) │    │ (ELK Stack)     │    │ (Prometheus)    │
└─────────────────┘    └─────────────────┘    └─────────────────┘
```

## Безопасность

### Аутентификация и авторизация
- JWT токены (access + refresh)
- OAuth 2.0 для интеграций
- Многоуровневая система доступа (RBAC + ABAC)
- API rate limiting
- CORS настройки

#### Система уровней пользователей
```
Администраторы:
├── admin: блокировка пользователей, удаление контента
└── content_editor: редактирование любого контента, управление справочниками

Пользователи:
├── observer (0 баллов): просмотр без контактов
├── participant (500+ баллов): полный доступ, размещение объявлений  
└── leader: дополнительно прайс-листы и приоритет
```

#### Матрица прав доступа
```
Действие                    | observer | participant | leader | content_editor | admin
----------------------------|----------|-------------|--------|----------------|-------
Просмотр объявлений         |    ✓     |      ✓      |   ✓    |       ✓        |   ✓
Просмотр контактов          |    ✗     |      ✓      |   ✓    |       ✓        |   ✓
Размещение объявлений       |    ✗     |      ✓      |   ✓    |       ✓        |   ✓
Добавление компании         |    ✗     |      ✓      |   ✓    |       ✓        |   ✓
Прикрепление прайс-листа    |    ✗     |      ✗      |   ✓    |       ✓        |   ✓
Редактирование чужого контента |  ✗   |      ✗      |   ✗    |       ✓        |   ✓
Управление справочниками    |    ✗     |      ✗      |   ✗    |       ✓        |   ✓
Блокировка пользователей    |    ✗     |      ✗      |   ✗    |       ✗        |   ✓
```

#### Проверка доступа в коде
```javascript
// Middleware для проверки уровня пользователя
const requireUserLevel = (minLevel) => {
  return (req, res, next) => {
    const userLevel = req.user.user_level;
    const levels = ['observer', 'participant', 'leader'];
    
    if (levels.indexOf(userLevel) >= levels.indexOf(minLevel)) {
      return next();
    }
    
    return res.status(403).json({ 
      error: 'Insufficient user level',
      required: minLevel,
      current: userLevel 
    });
  };
};

// Middleware для проверки баланса
const requireBalance = (points) => {
  return (req, res, next) => {
    if (req.user.balance_points >= points) {
      return next();
    }
    
    return res.status(402).json({ 
      error: 'Insufficient balance',
      required: points,
      current: req.user.balance_points 
    });
  };
};
```

### Защита данных
- HTTPS everywhere
- Database encryption at rest
- PII data anonymization
- GDPR compliance
- Input validation и sanitization

### Мониторинг безопасности
- Security headers (HSTS, CSP, etc.)
- Vulnerability scanning
- Audit logging
- DDoS protection (CloudFlare)

## Производительность

### Кэширование
```
┌─────────────────┐
│   Browser       │ (Service Worker, Local Storage)
└─────────────────┘
         │
┌─────────────────┐
│   CDN           │ (CloudFlare, статические ресурсы)
└─────────────────┘
         │
┌─────────────────┐
│   Application   │ (Redis, сессии и API responses)
└─────────────────┘
         │
┌─────────────────┐
│   Database      │ (PostgreSQL query cache)
└─────────────────┘
```

### Оптимизация запросов
- Database indexing strategy
- Query optimization
- Connection pooling
- Read replicas для аналитики
- Pagination и infinite scroll

### Масштабирование
- Horizontal scaling (Kubernetes)
- Auto-scaling based on metrics
- Load balancing
- Database sharding (при необходимости)

## DevOps и развертывание

### CI/CD Pipeline
```yaml
# .github/workflows/deploy.yml
name: Deploy to Production

on:
  push:
    branches: [main]

jobs:
  test:
    runs-on: ubuntu-latest
    steps:
      - name: Run tests
        run: bun test
      - name: Lint code
        run: bun run lint
      - name: Type check
        run: bun run type-check

  build:
    needs: test
    runs-on: ubuntu-latest
    steps:
      - name: Build Docker images
        run: docker build -t textilserver:latest .
      - name: Push to registry
        run: docker push registry.example.com/textilserver:latest

  deploy:
    needs: build
    runs-on: ubuntu-latest
    steps:
      - name: Deploy to Kubernetes
        run: kubectl apply -f k8s/
```

### Environment Configuration
```bash
# Production
DATABASE_URL=${PRODUCTION_DATABASE_URL}
REDIS_URL=redis://redis:6379
ELASTICSEARCH_URL=http://elasticsearch:9200
S3_BUCKET=textilserver-files
JWT_SECRET=${YOUR_JWT_SECRET}
API_RATE_LIMIT=1000

# Development
DATABASE_URL=postgresql://localhost:5432/textilserver_dev
REDIS_URL=redis://localhost:6379
NEXT_PUBLIC_API_URL=http://localhost:3001
```

## Мониторинг и аналитика

### Метрики системы
- Response time
- Error rates
- Database performance
- Cache hit rates
- Active users

### Бизнес-метрики
- User registrations
- Listing creations
- Transaction volume
- Revenue tracking
- User engagement

### Alerting
- System downtime
- High error rates
- Database connection issues
- Payment failures
- Security incidents

---

## ВАЖНО ДЛЯ РАЗРАБОТКИ (PHP/MySQL/JS)

**⚠️ КРИТИЧЕСКОЕ ТРЕБОВАНИЕ:**
- **НЕ ИНТЕГРИРОВАТЬ** внешние платежные сервисы (Stripe, Тинькоф, банковские карты)
- **ТОЛЬКО внутренняя система баллов** - администраторы начисляют баллы вручную
- **Никаких платежных API** - все операции с балансом происходят через админ-панель
- **Баллы как внутренняя валюта** - пользователи тратят баллы на размещение объявлений

### Схема работы системы баллов для PHP:
```php
// Начисление баллов (только админы)
function grantPoints($userId, $amount, $adminId, $reason) {
    $db->insert('balance_transactions', [
        'user_id' => $userId,
        'amount' => $amount,
        'type' => 'admin_grant',
        'description' => $reason,
        'admin_id' => $adminId
    ]);
    
    $db->query('UPDATE users SET balance_points = balance_points + ? WHERE id = ?', 
        [$amount, $userId]);
}

// Проверка и обновление статуса пользователя при входе в систему
function checkAndUpdateUserStatus($userId) {
    global $db;
    
    $user = $db->queryOne('SELECT * FROM users WHERE id = ?', [$userId]);
    $now = date('Y-m-d H:i:s');
    
    // Проверяем истечение срока участия
    if ($user['participant_until'] && $user['participant_until'] < $now) {
        $db->query('UPDATE users SET user_level = ?, participant_until = NULL WHERE id = ?', 
            ['observer', $userId]);
        $user['user_level'] = 'observer';
        $user['participant_until'] = null;
    }
    
    // Проверяем истечение доступа к прайс-листу
    if ($user['pricelist_access_until'] && $user['pricelist_access_until'] < $now) {
        $db->query('UPDATE users SET pricelist_access_until = NULL WHERE id = ?', [$userId]);
        $user['pricelist_access_until'] = null;
    }
    
    return $user;
}

// Покупка членства (оплата баллами)
function purchaseMembership($userId, $months) {
    global $db;
    
    $prices = [
        1 => 120,   // 1 месяц
        3 => 300,   // 3 месяца  
        5 => 560,   // 5 месяцев
        12 => 1000  // 1 год
    ];
    
    if (!isset($prices[$months])) {
        return ['success' => false, 'error' => 'Invalid membership period'];
    }
    
    $cost = $prices[$months];
    $user = $db->queryOne('SELECT * FROM users WHERE id = ?', [$userId]);
    
    if ($user['balance_points'] < $cost) {
        return ['success' => false, 'error' => 'Insufficient balance'];
    }
    
    // Вычисляем новую дату окончания
    $currentEnd = $user['participant_until'] ? $user['participant_until'] : date('Y-m-d H:i:s');
    $newEnd = date('Y-m-d H:i:s', strtotime($currentEnd . " +{$months} months"));
    
    // Списываем баллы
    $db->insert('balance_transactions', [
        'user_id' => $userId,
        'amount' => -$cost,
        'type' => 'membership_payment',
        'description' => "Membership for {$months} months",
        'membership_months' => $months
    ]);
    
    // Обновляем пользователя
    $db->query('UPDATE users SET balance_points = balance_points - ?, user_level = ?, participant_until = ? WHERE id = ?', 
        [$cost, 'participant', $newEnd, $userId]);
    
    return ['success' => true, 'expires_at' => $newEnd];
}

// Покупка доступа к размещению прайс-листа (1000 баллов на 1 год)
function purchasePricelistAccess($userId) {
    global $db;
    
    $cost = 1000;
    $user = $db->queryOne('SELECT * FROM users WHERE id = ?', [$userId]);
    
    // Проверяем, что пользователь имеет статус Участника
    if ($user['user_level'] !== 'participant' && $user['user_level'] !== 'leader') {
        return ['success' => false, 'error' => 'Must be a participant to purchase pricelist access'];
    }
    
    if ($user['balance_points'] < $cost) {
        return ['success' => false, 'error' => 'Insufficient balance'];
    }
    
    // Вычисляем новую дату окончания доступа к прайс-листу
    $currentEnd = $user['pricelist_access_until'] ? $user['pricelist_access_until'] : date('Y-m-d H:i:s');
    $newEnd = date('Y-m-d H:i:s', strtotime($currentEnd . " +1 year"));
    
    // Списываем баллы
    $db->insert('balance_transactions', [
        'user_id' => $userId,
        'amount' => -$cost,
        'type' => 'pricelist_payment',
        'description' => "Pricelist access for 1 year"
    ]);
    
    // Обновляем пользователя
    $db->query('UPDATE users SET balance_points = balance_points - ?, pricelist_access_until = ? WHERE id = ?', 
        [$cost, $newEnd, $userId]);
    
    return ['success' => true, 'expires_at' => $newEnd];
}

// Проверка доступа к контактам
function canAccessContacts($user) {
    return in_array($user['user_level'], ['participant', 'leader']);
}

// Проверка возможности размещения объявлений
function canCreateListing($user) {
    if (!in_array($user['user_level'], ['participant', 'leader'])) {
        return false;
    }
    
    return $user['balance_points'] >= 20; // стоимость объявления
}

// Проверка возможности размещения прайс-листа
function canUploadPricelist($user) {
    return $user['pricelist_access_until'] && $user['pricelist_access_until'] > date('Y-m-d H:i:s');
}

// Списание баллов за объявление
function deductPointsForListing($userId, $listingId) {
    $cost = 20; // стоимость объявления
    $user = $db->queryOne('SELECT * FROM users WHERE id = ?', [$userId]);
    
    if (canCreateListing($user)) {
        $db->insert('balance_transactions', [
            'user_id' => $userId,
            'amount' => -$cost,
            'type' => 'listing_payment',
            'listing_id' => $listingId
        ]);
        
        $db->query('UPDATE users SET balance_points = balance_points - ? WHERE id = ?', 
            [$cost, $userId]);
        return true;
    }
    return false;
}
```

---

*Техническая документация для разработки портала TextilServer.ru*