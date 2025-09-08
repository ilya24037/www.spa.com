# 🔍 Проверка сигнатур методов репозитория

**Дата создания:** 01.09.2025  
**Источник:** Ошибка 500 при восстановлении объявления из архива  
**Время на исправление:** 5 минут (могло быть 0 если проверить заранее)  
**Категория:** Type Safety, Backend Integration

---

## 🚨 Проблема

При вызове метода репозитория передан неправильный тип параметра:
```
AdRepository::update(): Argument #1 ($id) must be of type int, App\Domain\Ad\Models\Ad given
```

### Контекст ошибки:
- Задача: восстановление объявления из архива
- Место: `AdService::restore()` вызывает `AdRepository::update()`
- Причина: метод `update()` ожидает `int $id`, а передается объект `Ad`

---

## 🔍 Анализ причины

### Что произошло:
```php
// В AdService::restore() было написано:
public function restore(Ad $ad): Ad {
    return $this->adRepository->update($ad, [  // ❌ Передаем объект
        'status' => AdStatus::ACTIVE->value,
        'archived_at' => null
    ]);
}

// А метод в репозитории ожидает:
public function update(int $id, array $data): bool  // ⚠️ Ожидает int!
```

### Почему это произошло:
1. Предположение что метод `update()` универсальный
2. Не проверили сигнатуру метода перед использованием
3. Полагались на название метода, а не на его реализацию

---

## ✅ Решение

### Шаг 1: ВСЕГДА проверять сигнатуры ПЕРЕД использованием

```bash
# Найти все методы с похожими именами:
grep -n "public function update" app/Domain/*/Repositories/
grep -n "public function restore" app/Domain/*/Services/
```

### Шаг 2: Изучить типы параметров

```php
// Часто есть несколько методов для разных случаев:
public function update(int $id, array $data): bool     // принимает ID
public function updateModel(Model $model, array $data) // принимает объект
public function updateAd(Ad $ad, array $data): Ad      // специфичный для Ad
```

### Шаг 3: Проверить через IDE или tinker

```bash
php artisan tinker
>>> $repo = app(AdRepository::class);
>>> get_class_methods($repo); // список всех методов
>>> (new ReflectionMethod($repo, 'update'))->getParameters(); // параметры метода
```

### Шаг 4: Использовать правильный метод

```php
// Правильное решение:
public function restore(Ad $ad): Ad {
    return $this->adRepository->updateAd($ad, [  // ✅ Используем правильный метод
        'status' => AdStatus::ACTIVE->value,
        'archived_at' => null
    ]);
}
```

---

## 🎯 Правила на будущее

### При использовании любого репозитория:
1. **Проверить сигнатуру метода** - не полагаться на название
2. **Убедиться в типах параметров** - особенно ID vs Object
3. **Использовать IDE с поддержкой типов** - автодополнение покажет типы
4. **При сомнениях - протестировать в tinker** - лучше проверить заранее

### Чек-лист перед вызовом метода репозитория:
- [ ] Знаю точную сигнатуру метода?
- [ ] Проверил типы всех параметров?
- [ ] Проверил что метод возвращает?
- [ ] Есть альтернативные методы с похожими именами?

---

## 💡 Быстрые проверки

### В сервисе ПЕРЕД написанием кода:
```php
// Посмотреть все методы репозитория
dd(get_class_methods($this->repository)); 

// Проверить параметры конкретного метода
dd((new \ReflectionMethod($this->repository, 'update'))->getParameters());

// Проверить возвращаемый тип
dd((new \ReflectionMethod($this->repository, 'update'))->getReturnType());
```

### В tinker для быстрой проверки:
```bash
php artisan tinker
>>> $r = app(AdRepository::class);
>>> (new ReflectionClass($r))->getMethods(); // все методы с деталями
```

---

## 📊 Паттерны именования в репозиториях

### Типичные соглашения (но всегда проверяйте!):
```php
// Методы принимающие ID
find(int $id)
update(int $id, array $data)
delete(int $id)

// Методы принимающие объект
save(Model $model)
updateModel(Model $model, array $data)
updateAd(Ad $ad, array $data)  // специфичные для модели
deleteModel(Model $model)

// Методы принимающие критерии
findBy(array $criteria)
updateWhere(array $conditions, array $data)
deleteWhere(array $conditions)
```

---

## 🚀 Результат применения

- **Время на отладку:** сократилось с 30+ минут до 5 минут
- **Предотвращение ошибок:** проверка сигнатур займет 1 минуту
- **Качество кода:** использование правильных типов с первого раза

---

## 🔗 Связанные уроки

- [BUSINESS_LOGIC_FIRST.md](../APPROACHES/BUSINESS_LOGIC_FIRST.md) - проверка backend перед frontend
- [FRONTEND_FIRST_DEBUGGING.md](../ANTI_PATTERNS/FRONTEND_FIRST_DEBUGGING.md) - почему нельзя начинать с UI
- [NEW_TASK_WORKFLOW.md](../WORKFLOWS/NEW_TASK_WORKFLOW.md) - правильный порядок работы

---

## 💭 Ключевая мысль

> **"Имя метода - это обещание. Сигнатура - это контракт. Всегда проверяй контракт!"**

Не полагайтесь на интуицию или названия методов. Даже если метод называется `update`, он может принимать ID, объект, массив или что-то еще. Проверка сигнатуры занимает секунды, а отладка ошибок типов - минуты или часы.