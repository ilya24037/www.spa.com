# Быстрый старт SPA Platform

## ⚡ За 5 минут

### 1. Клонирование и установка
```bash
# Клонируем репозиторий
git clone [repository-url]
cd spa-platform

# Устанавливаем зависимости
composer install
npm install
```

### 2. Настройка окружения
```bash
# Копируем .env файл
cp .env.example .env

# Генерируем ключ
php artisan key:generate

# Настраиваем БД (измените в .env)
DB_DATABASE=spa_platform
DB_USERNAME=root
DB_PASSWORD=
```

### 3. Запуск
```bash
# Миграции и сиды
php artisan migrate --seed

# Запуск серверов
php artisan serve &
npm run dev
```

### 4. Открыть в браузере
http://localhost:8000

## 🎯 Первые шаги

### 1. Регистрация
- Перейдите на `/register`
- Заполните форму
- Подтвердите email

### 2. Создание профиля мастера
- Перейдите в личный кабинет
- Заполните профиль мастера
- Загрузите фото

### 3. Создание объявления
- Нажмите "Создать объявление"
- Заполните форму
- Сохраните как черновик

### 4. Просмотр объявлений
- Перейдите на главную
- Найдите свое объявление
- Проверьте отображение

## 🔧 Полезные команды

### Laravel
```bash
# Очистка кеша
php artisan cache:clear

# Просмотр маршрутов
php artisan route:list

# Tinker для отладки
php artisan tinker
```

### Frontend
```bash
# Сборка для продакшена
npm run build

# Проверка стиля
npm run lint

# Тесты
npm test
```

## 🐛 Решение проблем

### Проблема: "Class not found"
```bash
# Очистите автозагрузку
composer dump-autoload
```

### Проблема: "Page not found"
```bash
# Очистите кеш маршрутов
php artisan route:clear
```

### Проблема: "Styles not loading"
```bash
# Пересоберите assets
npm run build
```

## 📚 Следующие шаги

### Изучение архитектуры
1. Прочитайте `.cursor/docs/architecture.md`
2. Изучите структуру папок
3. Посмотрите на примеры кода

### Разработка
1. Прочитайте `.cursor/rules/coding-standards.mdc`
2. Изучите `.cursor/rules/workflow.mdc`
3. Начните с простых задач

### Отладка
1. Прочитайте `.cursor/rules/troubleshooting.mdc`
2. Изучите логи в `storage/logs/`
3. Используйте команды для поиска

## 🎯 Готовые примеры

### Создание контроллера
```bash
php artisan make:controller TestController
```

### Создание миграции
```bash
php artisan make:migration create_test_table
```

### Создание Vue компонента
```bash
# Создайте файл в resources/js/src/shared/ui/
TestComponent.vue
```

## 🆘 Помощь

### Документация
- `.cursor/rules/` - правила для AI
- `.cursor/docs/` - техническая документация
- `docs/LESSONS/` - накопленный опыт

### Команды для поиска
```bash
# Найти функцию
grep -r "functionName" app/

# Найти компонент
find resources/js -name "*Component*"

# Найти миграцию
find database/migrations -name "*table*"
```

## 🎉 Готово!

Теперь вы можете:
- ✅ Создавать объявления
- ✅ Просматривать профили
- ✅ Работать с кодом
- ✅ Отлаживать проблемы

**Удачной разработки!** 🎉
