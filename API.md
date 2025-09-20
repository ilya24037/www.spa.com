# 🔌 API Documentation

> Полная документация API для SPA Platform

[![API Version](https://img.shields.io/badge/API-v1.0-blue.svg)](api/v1)
[![Laravel](https://img.shields.io/badge/Laravel-12-red.svg)](https://laravel.com)
[![OpenAPI](https://img.shields.io/badge/OpenAPI-3.0-green.svg)](https://swagger.io)

## 📋 Содержание

- [Обзор API](#-обзор-api)
- [Аутентификация](#-аутентификация)
- [Авторизация](#-авторизация)
- [Базовый URL](#-базовый-url)
- [Формат данных](#-формат-данных)
- [Коды ответов](#-коды-ответов)
- [Обработка ошибок](#-обработка-ошибок)
- [Rate Limiting](#-rate-limiting)
- [Endpoints](#-endpoints)
- [Схемы данных](#-схемы-данных)
- [Примеры использования](#-примеры-использования)
- [SDK и библиотеки](#-sdk-и-библиотеки)

---

## 🌐 Обзор API

**SPA Platform API** - это RESTful API для работы с платформой услуг массажа. API позволяет создавать, читать, обновлять и удалять объявления, управлять пользователями, общаться в чате и администрировать платформу.

### Основные возможности:
- **Управление объявлениями** - CRUD операции
- **Аутентификация пользователей** - регистрация, вход, выход
- **Система ролей** - клиенты, мастера, модераторы, администраторы
- **Real-time чат** - WebSocket соединения
- **Файловые операции** - загрузка изображений и документов
- **Поиск и фильтрация** - продвинутый поиск объявлений
- **Административные функции** - модерация, аналитика, управление

### Версии API:
- **v1.0** - текущая версия (стабильная)
- **v2.0** - в разработке (планируется)

---

## 🔐 Аутентификация

### Типы аутентификации:

#### **1. Bearer Token (API)**
```http
Authorization: Bearer {token}
```

#### **2. Session Cookie (Web)**
```http
Cookie: laravel_session={session_id}
```

#### **3. CSRF Token (Forms)**
```http
X-CSRF-TOKEN: {csrf_token}
```

### Получение токена:

#### **Регистрация:**
```http
POST /api/auth/register
Content-Type: application/json

{
  "name": "Иван Иванов",
  "email": "ivan@example.com",
  "password": "password123",
  "password_confirmation": "password123",
  "phone": "+7 (999) 123-45-67"
}
```

#### **Вход:**
```http
POST /api/auth/login
Content-Type: application/json

{
  "email": "ivan@example.com",
  "password": "password123"
}
```

#### **Ответ:**
```json
{
  "success": true,
  "message": "Успешная аутентификация",
  "data": {
    "user": {
      "id": 1,
      "name": "Иван Иванов",
      "email": "ivan@example.com",
      "phone": "+7 (999) 123-45-67",
      "role": "client",
      "created_at": "2024-01-01T00:00:00.000000Z"
    },
    "token": "1|abcdef1234567890",
    "expires_at": "2024-01-08T00:00:00.000000Z"
  }
}
```

### Обновление токена:
```http
POST /api/auth/refresh
Authorization: Bearer {token}
```

### Выход:
```http
POST /api/auth/logout
Authorization: Bearer {token}
```

---

## 🛡️ Авторизация

### Роли пользователей:

#### **client** - Клиент:
- Просмотр объявлений
- Создание заказов
- Общение в чате
- Управление профилем

#### **master** - Мастер:
- Создание объявлений
- Управление объявлениями
- Общение с клиентами
- Просмотр статистики

#### **moderator** - Модератор:
- Модерация объявлений
- Управление пользователями
- Просмотр жалоб
- Доступ к аналитике

#### **admin** - Администратор:
- Полный доступ к системе
- Управление всеми данными
- Настройка системы
- Доступ к логам

### Проверка прав:
```http
GET /api/user/permissions
Authorization: Bearer {token}
```

**Ответ:**
```json
{
  "success": true,
  "data": {
    "role": "master",
    "permissions": [
      "ads.create",
      "ads.update",
      "ads.delete",
      "chat.send",
      "profile.update"
    ]
  }
}
```

---

## 🌍 Базовый URL

### Окружения:

#### **Development:**
```
http://localhost:8000/api/v1
```

#### **Staging:**
```
https://staging.spa-platform.com/api/v1
```

#### **Production:**
```
https://api.spa-platform.com/v1
```

### Версионирование:
- **URL версионирование** - `/api/v1/`
- **Header версионирование** - `Accept: application/vnd.spa-platform.v1+json`
- **Query параметр** - `?version=1.0`

---

## 📊 Формат данных

### Content-Type:
```http
Content-Type: application/json
Accept: application/json
```

### Структура ответа:

#### **Успешный ответ:**
```json
{
  "success": true,
  "message": "Операция выполнена успешно",
  "data": {
    // Данные ответа
  },
  "meta": {
    "pagination": {
      "current_page": 1,
      "per_page": 15,
      "total": 100,
      "last_page": 7
    }
  }
}
```

#### **Ошибка:**
```json
{
  "success": false,
  "message": "Произошла ошибка",
  "errors": {
    "field": ["Сообщение об ошибке"]
  },
  "code": "VALIDATION_ERROR"
}
```

### Формат дат:
```json
{
  "created_at": "2024-01-01T12:00:00.000000Z",
  "updated_at": "2024-01-01T12:00:00.000000Z"
}
```

### Формат файлов:
```json
{
  "file": {
    "id": 1,
    "name": "photo.jpg",
    "url": "https://cdn.spa-platform.com/storage/photos/1/photo.jpg",
    "size": 1024000,
    "mime_type": "image/jpeg"
  }
}
```

---

## 📋 Коды ответов

### Успешные ответы:

| Код | Описание | Пример |
|-----|----------|--------|
| **200** | OK | Успешный запрос |
| **201** | Created | Ресурс создан |
| **202** | Accepted | Запрос принят |
| **204** | No Content | Успешно, без контента |

### Ошибки клиента:

| Код | Описание | Пример |
|-----|----------|--------|
| **400** | Bad Request | Неверный запрос |
| **401** | Unauthorized | Не авторизован |
| **403** | Forbidden | Нет прав доступа |
| **404** | Not Found | Ресурс не найден |
| **422** | Unprocessable Entity | Ошибка валидации |
| **429** | Too Many Requests | Превышен лимит запросов |

### Ошибки сервера:

| Код | Описание | Пример |
|-----|----------|--------|
| **500** | Internal Server Error | Внутренняя ошибка сервера |
| **502** | Bad Gateway | Ошибка шлюза |
| **503** | Service Unavailable | Сервис недоступен |
| **504** | Gateway Timeout | Таймаут шлюза |

---

## ⚠️ Обработка ошибок

### Структура ошибки:
```json
{
  "success": false,
  "message": "Описание ошибки",
  "errors": {
    "field_name": ["Сообщение об ошибке"]
  },
  "code": "ERROR_CODE",
  "details": {
    "trace_id": "abc123",
    "timestamp": "2024-01-01T12:00:00Z"
  }
}
```

### Типы ошибок:

#### **VALIDATION_ERROR** - Ошибка валидации:
```json
{
  "success": false,
  "message": "Ошибка валидации данных",
  "errors": {
    "email": ["Поле email обязательно"],
    "password": ["Пароль должен содержать минимум 8 символов"]
  },
  "code": "VALIDATION_ERROR"
}
```

#### **AUTHENTICATION_ERROR** - Ошибка аутентификации:
```json
{
  "success": false,
  "message": "Неверные учетные данные",
  "code": "AUTHENTICATION_ERROR"
}
```

#### **AUTHORIZATION_ERROR** - Ошибка авторизации:
```json
{
  "success": false,
  "message": "Недостаточно прав для выполнения операции",
  "code": "AUTHORIZATION_ERROR"
}
```

#### **NOT_FOUND_ERROR** - Ресурс не найден:
```json
{
  "success": false,
  "message": "Объявление не найдено",
  "code": "NOT_FOUND_ERROR"
}
```

#### **RATE_LIMIT_ERROR** - Превышен лимит:
```json
{
  "success": false,
  "message": "Превышен лимит запросов. Попробуйте позже",
  "code": "RATE_LIMIT_ERROR",
  "details": {
    "retry_after": 60
  }
}
```

---

## 🚦 Rate Limiting

### Лимиты запросов:

#### **Аутентифицированные пользователи:**
- **1000 запросов/час** - общий лимит
- **100 запросов/минуту** - для создания объявлений
- **500 запросов/минуту** - для поиска

#### **Неаутентифицированные пользователи:**
- **100 запросов/час** - общий лимит
- **10 запросов/минуту** - для поиска

### Headers лимитов:
```http
X-RateLimit-Limit: 1000
X-RateLimit-Remaining: 999
X-RateLimit-Reset: 1640995200
```

### Превышение лимита:
```http
HTTP/1.1 429 Too Many Requests
Retry-After: 3600
```

---

## 🔌 Endpoints

### **Аутентификация**

#### **POST /api/auth/register**
Регистрация нового пользователя

**Параметры:**
```json
{
  "name": "string (required)",
  "email": "string (required, email)",
  "password": "string (required, min:8)",
  "password_confirmation": "string (required)",
  "phone": "string (required)"
}
```

**Ответ:**
```json
{
  "success": true,
  "data": {
    "user": "User",
    "token": "string"
  }
}
```

#### **POST /api/auth/login**
Вход в систему

**Параметры:**
```json
{
  "email": "string (required)",
  "password": "string (required)"
}
```

#### **POST /api/auth/logout**
Выход из системы

**Headers:**
```http
Authorization: Bearer {token}
```

#### **POST /api/auth/refresh**
Обновление токена

**Headers:**
```http
Authorization: Bearer {token}
```

---

### **Пользователи**

#### **GET /api/users**
Получение списка пользователей (только для админов)

**Параметры запроса:**
- `page` - номер страницы (по умолчанию: 1)
- `per_page` - количество на странице (по умолчанию: 15)
- `role` - фильтр по роли
- `search` - поиск по имени/email

**Ответ:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "name": "Иван Иванов",
      "email": "ivan@example.com",
      "role": "master",
      "created_at": "2024-01-01T00:00:00Z"
    }
  ],
  "meta": {
    "pagination": {
      "current_page": 1,
      "per_page": 15,
      "total": 100,
      "last_page": 7
    }
  }
}
```

#### **GET /api/users/{id}**
Получение пользователя по ID

#### **PUT /api/users/{id}**
Обновление пользователя

#### **DELETE /api/users/{id}**
Удаление пользователя (только для админов)

---

### **Объявления**

#### **GET /api/ads**
Получение списка объявлений

**Параметры запроса:**
- `page` - номер страницы
- `per_page` - количество на странице
- `search` - поиск по названию/описанию
- `category` - фильтр по категории
- `city` - фильтр по городу
- `price_min` - минимальная цена
- `price_max` - максимальная цена
- `rating_min` - минимальный рейтинг
- `sort` - сортировка (price, rating, created_at)

**Ответ:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "title": "Массаж спины",
      "description": "Профессиональный массаж спины",
      "price": 2000,
      "currency": "RUB",
      "category": "massage",
      "city": "Москва",
      "rating": 4.8,
      "reviews_count": 25,
      "master": {
        "id": 1,
        "name": "Иван Иванов",
        "avatar": "https://cdn.spa-platform.com/avatars/1.jpg"
      },
      "photos": [
        {
          "id": 1,
          "url": "https://cdn.spa-platform.com/photos/1.jpg",
          "is_main": true
        }
      ],
      "created_at": "2024-01-01T00:00:00Z"
    }
  ]
}
```

#### **GET /api/ads/{id}**
Получение объявления по ID

#### **POST /api/ads**
Создание нового объявления

**Параметры:**
```json
{
  "title": "string (required)",
  "description": "string (required)",
  "price": "integer (required)",
  "category": "string (required)",
  "city": "string (required)",
  "address": "string",
  "services": ["string"],
  "photos": ["file"]
}
```

#### **PUT /api/ads/{id}**
Обновление объявления

#### **DELETE /api/ads/{id}**
Удаление объявления

---

### **Чат**

#### **GET /api/chats**
Получение списка чатов

**Ответ:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "ad_id": 1,
      "ad_title": "Массаж спины",
      "participants": [
        {
          "id": 1,
          "name": "Иван Иванов",
          "avatar": "https://cdn.spa-platform.com/avatars/1.jpg"
        }
      ],
      "last_message": {
        "id": 1,
        "text": "Привет!",
        "created_at": "2024-01-01T12:00:00Z"
      },
      "unread_count": 2,
      "created_at": "2024-01-01T00:00:00Z"
    }
  ]
}
```

#### **GET /api/chats/{id}**
Получение чата по ID

#### **POST /api/chats**
Создание нового чата

**Параметры:**
```json
{
  "ad_id": "integer (required)",
  "message": "string (required)"
}
```

#### **GET /api/chats/{id}/messages**
Получение сообщений чата

**Параметры запроса:**
- `page` - номер страницы
- `per_page` - количество на странице

#### **POST /api/chats/{id}/messages**
Отправка сообщения

**Параметры:**
```json
{
  "text": "string (required)",
  "type": "text|image|file",
  "file": "file (optional)"
}
```

---

### **Поиск**

#### **GET /api/search/ads**
Поиск объявлений

**Параметры запроса:**
- `q` - поисковый запрос
- `category` - категория
- `city` - город
- `price_min` - минимальная цена
- `price_max` - максимальная цена
- `rating_min` - минимальный рейтинг
- `sort` - сортировка

#### **GET /api/search/suggestions**
Получение предложений для автодополнения

**Параметры запроса:**
- `q` - поисковый запрос
- `type` - тип предложений (cities, categories, services)

---

### **Файлы**

#### **POST /api/files/upload**
Загрузка файла

**Параметры:**
- `file` - файл (required)
- `type` - тип файла (photo, document, avatar)

**Ответ:**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "name": "photo.jpg",
    "url": "https://cdn.spa-platform.com/storage/photos/1/photo.jpg",
    "size": 1024000,
    "mime_type": "image/jpeg"
  }
}
```

#### **DELETE /api/files/{id}**
Удаление файла

---

### **Административные функции**

#### **GET /api/admin/stats**
Получение статистики (только для админов)

**Ответ:**
```json
{
  "success": true,
  "data": {
    "users": {
      "total": 1000,
      "new_today": 10,
      "active_today": 500
    },
    "ads": {
      "total": 5000,
      "new_today": 50,
      "active": 4500
    },
    "revenue": {
      "today": 10000,
      "month": 300000
    }
  }
}
```

#### **GET /api/admin/users**
Управление пользователями

#### **GET /api/admin/ads**
Управление объявлениями

#### **POST /api/admin/ads/{id}/moderate**
Модерация объявления

**Параметры:**
```json
{
  "action": "approve|reject|suspend",
  "reason": "string (optional)"
}
```

---

## 📊 Схемы данных

### **User (Пользователь)**
```json
{
  "id": "integer",
  "name": "string",
  "email": "string",
  "phone": "string",
  "role": "client|master|moderator|admin",
  "avatar": "string|null",
  "is_active": "boolean",
  "created_at": "datetime",
  "updated_at": "datetime"
}
```

### **Ad (Объявление)**
```json
{
  "id": "integer",
  "title": "string",
  "description": "text",
  "price": "integer",
  "currency": "string",
  "category": "string",
  "city": "string",
  "address": "string|null",
  "services": "array",
  "rating": "float|null",
  "reviews_count": "integer",
  "views_count": "integer",
  "status": "draft|active|inactive|archived",
  "master_id": "integer",
  "master": "User",
  "photos": "array",
  "created_at": "datetime",
  "updated_at": "datetime"
}
```

### **Chat (Чат)**
```json
{
  "id": "integer",
  "ad_id": "integer",
  "ad_title": "string",
  "participants": "array",
  "last_message": "Message|null",
  "unread_count": "integer",
  "created_at": "datetime",
  "updated_at": "datetime"
}
```

### **Message (Сообщение)**
```json
{
  "id": "integer",
  "chat_id": "integer",
  "user_id": "integer",
  "text": "string|null",
  "type": "text|image|file",
  "file": "File|null",
  "is_read": "boolean",
  "created_at": "datetime"
}
```

---

## 💡 Примеры использования

### **Создание объявления:**

```bash
curl -X POST https://api.spa-platform.com/v1/ads \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "title": "Массаж спины",
    "description": "Профессиональный массаж спины",
    "price": 2000,
    "category": "massage",
    "city": "Москва",
    "services": ["Классический массаж", "Лечебный массаж"]
  }'
```

### **Поиск объявлений:**

```bash
curl -X GET "https://api.spa-platform.com/v1/search/ads?q=массаж&city=Москва&price_max=3000" \
  -H "Accept: application/json"
```

### **Отправка сообщения:**

```bash
curl -X POST https://api.spa-platform.com/v1/chats/1/messages \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "text": "Здравствуйте! Интересует ваше объявление"
  }'
```

### **Загрузка файла:**

```bash
curl -X POST https://api.spa-platform.com/v1/files/upload \
  -H "Authorization: Bearer {token}" \
  -F "file=@photo.jpg" \
  -F "type=photo"
```

---

## 📚 SDK и библиотеки

### **JavaScript/TypeScript:**
```bash
npm install @spa-platform/api-client
```

```javascript
import { SPAPlatformAPI } from '@spa-platform/api-client';

const api = new SPAPlatformAPI({
  baseURL: 'https://api.spa-platform.com/v1',
  token: 'your-token'
});

// Получение объявлений
const ads = await api.ads.list({
  city: 'Москва',
  price_max: 3000
});

// Создание объявления
const ad = await api.ads.create({
  title: 'Массаж спины',
  description: 'Профессиональный массаж',
  price: 2000,
  category: 'massage',
  city: 'Москва'
});
```

### **PHP:**
```bash
composer require spa-platform/api-client
```

```php
use SPAPlatform\APIClient\SPAPlatformAPI;

$api = new SPAPlatformAPI([
    'base_url' => 'https://api.spa-platform.com/v1',
    'token' => 'your-token'
]);

// Получение объявлений
$ads = $api->ads->list([
    'city' => 'Москва',
    'price_max' => 3000
]);

// Создание объявления
$ad = $api->ads->create([
    'title' => 'Массаж спины',
    'description' => 'Профессиональный массаж',
    'price' => 2000,
    'category' => 'massage',
    'city' => 'Москва'
]);
```

### **Python:**
```bash
pip install spa-platform-api
```

```python
from spa_platform_api import SPAPlatformAPI

api = SPAPlatformAPI(
    base_url='https://api.spa-platform.com/v1',
    token='your-token'
)

# Получение объявлений
ads = api.ads.list(city='Москва', price_max=3000)

# Создание объявления
ad = api.ads.create(
    title='Массаж спины',
    description='Профессиональный массаж',
    price=2000,
    category='massage',
    city='Москва'
)
```

---

## 🔧 Настройка и конфигурация

### **Переменные окружения:**
```env
API_VERSION=1.0
API_RATE_LIMIT=1000
API_RATE_LIMIT_WINDOW=3600
API_CORS_ORIGINS=http://localhost:3000,https://spa-platform.com
API_WEBSOCKET_URL=wss://api.spa-platform.com/ws
```

### **Конфигурация CORS:**
```php
'cors' => [
    'allowed_origins' => ['http://localhost:3000', 'https://spa-platform.com'],
    'allowed_methods' => ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS'],
    'allowed_headers' => ['Content-Type', 'Authorization', 'X-Requested-With'],
    'max_age' => 86400,
],
```

---

## 📈 Мониторинг и аналитика

### **Метрики API:**
- Количество запросов
- Время ответа
- Коды ошибок
- Использование endpoints

### **Логирование:**
- Все запросы логируются
- Ошибки отслеживаются
- Аналитика использования

### **Алерты:**
- Высокая нагрузка
- Ошибки 5xx
- Медленные запросы
- Превышение лимитов

---

## 🚀 Roadmap API

### **Версия 1.1:**
- [ ] WebSocket API для real-time
- [ ] GraphQL endpoint
- [ ] Webhook уведомления
- [ ] Расширенная аналитика

### **Версия 2.0:**
- [ ] Микросервисная архитектура
- [ ] gRPC API
- [ ] Машинное обучение для рекомендаций
- [ ] Интеграция с внешними сервисами

---

## 📞 Поддержка

### **Контакты:**
- **Email**: api-support@spa-platform.com
- **GitHub**: https://github.com/spa-platform/api
- **Discord**: https://discord.gg/spa-platform

### **Полезные ссылки:**
- [Postman коллекция](https://www.postman.com/spa-platform/workspace/api)
- [Swagger UI](https://api.spa-platform.com/docs)
- [OpenAPI спецификация](https://api.spa-platform.com/openapi.json)

---

**Последнее обновление**: {{ date('Y-m-d') }}
**Версия API**: 1.0.0
**Автор**: Команда разработки SPA Platform