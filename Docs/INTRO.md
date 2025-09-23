# 🚀 Добро пожаловать в SPA Platform!

Привет! Если ты читаешь это, значит ты новый разработчик в команде. Этот документ поможет тебе быстро начать работу с проектом.

## 📖 С чего начать?

### День 1: Знакомство с проектом
1. **Прочитай основные документы** (30 мин)
   - [.claude/IDEA.md](./../.claude/IDEA.md) - Бизнес-идея проекта
   - [.claude/VISION.md](./../.claude/VISION.md) - Техническое видение
   - [.claude/CONVENTIONS.md](./../.claude/CONVENTIONS.md) - Правила разработки

2. **Настрой окружение** (1-2 часа)
   ```bash
   # Клонируй репозиторий
   git clone https://github.com/spa-platform/spa.git
   cd spa

   # Установи зависимости
   composer install
   npm install

   # Настрой .env
   cp .env.example .env
   php artisan key:generate

   # База данных
   php artisan migrate:fresh --seed

   # Запусти проект
   npm run dev
   php artisan serve
   ```

3. **Исследуй интерфейс** (30 мин)
   - Открой http://spa.test
   - Зарегистрируйся как клиент
   - Создай профиль мастера
   - Попробуй основные функции

### День 2-3: Погружение в код

4. **Изучи архитектуру** (2 часа)
   - Backend: Domain-Driven Design
     ```
     app/Domain/Master/     # Пример домена
     ├── Models/           # Eloquent модели
     ├── Services/         # Бизнес-логика
     ├── Actions/          # Сложные операции
     └── DTOs/            # Data Transfer Objects
     ```

   - Frontend: Feature-Sliced Design
     ```
     resources/js/src/
     ├── shared/          # Переиспользуемые компоненты
     ├── entities/        # Бизнес-сущности
     ├── features/        # Фичи
     └── pages/          # Страницы
     ```

5. **Выполни первую задачу** (4 часа)
   - Возьми задачу с тегом `good-first-issue`
   - Создай spec в `.claude/specs/`
   - Следуй [WORKFLOW.md](./../.claude/WORKFLOW.md)
   - Создай PR

### День 4-5: Продуктивная работа

6. **Освой инструменты AI-разработки**
   - Прочитай [WORKFLOW.md](./../.claude/WORKFLOW.md)
   - Используй Claude/Cursor для кодинга
   - Примеры промптов в `.claude/prompts/`

7. **Начни работу над реальной задачей**
   - Выбери задачу из [TASKLIST.md](./../.claude/TASKLIST.md)
   - Обсуди с командой подход
   - Реализуй по spec-driven процессу

## 🛠️ Основные команды

### Ежедневная разработка
```bash
# Начало дня
git pull origin main
npm run dev
php artisan serve

# Проверка кода
npm run type-check      # TypeScript
npm run lint           # ESLint
php artisan test       # PHPUnit

# Работа с БД
php artisan tinker     # REPL для Laravel
php artisan migrate    # Миграции
```

### Полезные алиасы
```bash
# Добавь в ~/.bashrc или ~/.zshrc
alias spa-dev="npm run dev & php artisan serve"
alias spa-test="npm run test && php artisan test"
alias spa-fresh="php artisan migrate:fresh --seed"
```

## 🗺️ Карта проекта

### Ключевые функции
- **Каталог мастеров** - `/masters`
- **Бронирование** - `/booking`
- **Личный кабинет** - `/profile`
- **Объявления** - `/ads`

### Важные файлы
```
.claude/
├── CLAUDE.md          # Инструкции для AI
├── CONVENTIONS.md     # Стандарты кода
├── WORKFLOW.md        # Процесс разработки
└── specs/            # Спецификации функций

app/Domain/           # Backend бизнес-логика
resources/js/src/     # Frontend компоненты
```

## 🎯 Чек-лист первой недели

- [ ] Окружение настроено и работает
- [ ] Прочитаны основные документы
- [ ] Изучена структура проекта
- [ ] Выполнена первая задача
- [ ] Создан первый PR
- [ ] Получен первый код-ревью
- [ ] Задача смержена в main

## 🤝 Команда и коммуникация

### Кто за что отвечает
- **Backend**: @backend-lead
- **Frontend**: @frontend-lead
- **DevOps**: @devops-lead
- **QA**: @qa-lead

### Где общаемся
- **Slack**: #spa-dev (основной канал)
- **GitHub**: Issues и Pull Requests
- **Confluence**: Документация

### Процессы
- **Standup**: 10:00 ежедневно
- **Code Review**: в течение 24ч
- **Sprint Planning**: понедельник
- **Retrospective**: пятница

## 📚 Полезные материалы

### Обязательно к изучению
1. [Domain-Driven Design](https://habr.com/ru/company/oleg-bunin/blog/496880/) - основы DDD
2. [Feature-Sliced Design](https://feature-sliced.design/ru/) - архитектура frontend
3. [Laravel Best Practices](https://github.com/alexeymezenin/laravel-best-practices)
4. [Vue 3 Composition API](https://vuejs.org/guide/extras/composition-api-faq.html)

### Для углубления
- [Clean Architecture](https://blog.cleancoder.com/uncle-bob/2012/08/13/the-clean-architecture.html)
- [SOLID принципы](https://habr.com/ru/company/productivity_inside/blog/505430/)
- [AI-Driven Development](https://habr.com/ru/articles/941934/)

## 💡 Советы от команды

### От backend-разработчиков
> "Всегда используй сервисный слой. Контроллеры только для роутинга!"

> "Транзакции для множественных операций с БД - обязательно"

### От frontend-разработчиков
> "TypeScript - твой друг. Не используй any!"

> "Всегда добавляй loading и error состояния"

### От QA
> "Пиши тесты сразу, не откладывай"

> "E2E тесты для критичных путей обязательны"

## ❓ FAQ

**Q: Где взять тестовые данные?**
A: `php artisan db:seed` создаст тестовых пользователей и контент

**Q: Как работать с AI-ассистентом?**
A: Используй контекст из `.claude/` папки, следуй WORKFLOW.md

**Q: Где логи?**
A: `storage/logs/laravel.log` и консоль браузера

**Q: Как откатить миграцию?**
A: `php artisan migrate:rollback`

**Q: Почему не работает hot reload?**
A: Проверь что `npm run dev` запущен

## 🚨 Если что-то пошло не так

### Проблемы с окружением
```bash
# Очистка кешей
php artisan cache:clear
php artisan config:clear
php artisan view:clear
npm run clean

# Переустановка зависимостей
rm -rf vendor node_modules
composer install
npm install
```

### Нужна помощь?
1. Проверь документацию
2. Поищи в Slack
3. Спроси в #spa-dev
4. Создай issue в GitHub

## 🎉 Добро пожаловать в команду!

Мы рады, что ты с нами! Если есть вопросы - не стесняйся спрашивать. Мы все были новичками и понимаем, как важна поддержка в начале.

**Удачи и продуктивной работы!** 🚀

---

*Последнее обновление: 2025-01-22*
*Есть предложения по улучшению онбординга? Создай PR!*