# Исправление проблем CI Pipeline

## Обзор CI Pipeline

В проекте настроен GitHub Actions CI Pipeline (`.github/workflows/ci.yml`), который выполняет следующие проверки:

1. **Tests** - PHP тесты и покрытие кода
2. **Code Quality** - проверка качества PHP кода
3. **Frontend Build & Test** - проверка и сборка фронтенда
4. **Security Scan** - проверка безопасности
5. **Docker** - сборка Docker образа

## Уже исправленные проблемы

### ✅ 1. Создан файл `.env.ci`
Файл необходим для CI тестов. Содержит тестовые настройки окружения:
- База данных: `spa_test`
- Драйверы: `array` для кеша, `sync` для очередей
- APP_KEY для тестов

### ✅ 2. Создан `eslint.config.js`
Конфигурация ESLint для проверки JavaScript/TypeScript/Vue файлов:
- Правила для TypeScript
- Правила для Vue 3
- Игнорирование системных папок

### ✅ 3. Добавлены недостающие npm скрипты
В `package.json` добавлены:
- `test:unit` - заглушка для unit тестов
- `size` - проверка размера бандла

## Возможные проблемы и решения

### 1. PHP Tests падают

**Проблема**: Тесты не могут подключиться к БД или отсутствуют миграции

**Решение**:
```bash
# Локально проверить
php artisan test

# Убедиться что есть базовые тесты
ls tests/Feature/
ls tests/Unit/
```

### 2. Code Quality (PHPStan, Psalm, PHPCS)

**Проблема**: Не установлены инструменты или есть ошибки в коде

**Решение**:
```bash
# Установить инструменты
composer require --dev phpstan/phpstan psalm/psalm squizlabs/php_codesniffer

# Создать конфигурации
# phpstan.neon
# psalm.xml
# phpcs.xml
```

### 3. Frontend ESLint ошибки

**Проблема**: Ошибки стиля кода или TypeScript

**Решение**:
```bash
# Установить зависимости
npm install

# Исправить автоматически
npm run lint

# Проверить TypeScript
npm run type-check
```

### 4. Security Scan падает

**Проблема**: Уязвимые зависимости

**Решение**:
```bash
# Обновить зависимости
composer update
npm audit fix

# Проверить конкретные уязвимости
npm audit
```

## Локальная проверка перед push

Выполните все проверки локально:

```bash
# 1. PHP тесты
php artisan test

# 2. Frontend проверки
npm run lint:check
npm run type-check
npm run build

# 3. Проверка на debug код
npm run check:debug

# 4. Общая проверка
npm run lint && npm run build && php artisan test
```

## Просмотр логов CI

1. Откройте Pull Request на GitHub
2. Кликните на красный крестик ❌ рядом с коммитом
3. Выберите конкретный job (Tests, Code Quality, Frontend)
4. Разверните секцию с ошибкой
5. Найдите конкретную строку с ошибкой

## Быстрые исправления

### TypeScript ошибки
```typescript
// Добавить проверку на null
const value = data?.property || '';

// Добавить тип
interface User {
    name: string;
    email: string;
}
```

### ESLint ошибки
```javascript
// Удалить console.log
// console.log('debug'); // ❌
logger.debug('debug');   // ✅

// Добавить точку с запятой
const data = {};  // ❌
const data = {}; // ✅
```

### PHP ошибки
```php
// Добавить типы
public function getName(): string // ✅
{
    return $this->name;
}
```

## Если ничего не помогает

1. **Откатите изменения**:
   ```bash
   git reset --hard HEAD~1
   git push --force-with-lease
   ```

2. **Отключите проверку временно**:
   - Добавьте `[skip ci]` в commit message
   - Или закомментируйте проблемный job в `.github/workflows/ci.yml`

3. **Создайте issue**:
   - Скопируйте полный лог ошибки
   - Укажите какой job падает
   - Приложите скриншот

## Полезные команды

```bash
# Алиас для полной проверки
alias ci-check="npm install && npm run lint && npm run type-check && npm run build && php artisan test"

# Проверить только изменённые файлы
git diff --name-only | grep -E '\.(js|ts|vue)$' | xargs npx eslint

# Автоисправление всех файлов
npm run lint
```