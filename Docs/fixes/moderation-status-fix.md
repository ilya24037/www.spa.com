# Исправление системы модерации объявлений

## Дата: 2025-01-30

## Проблемы и их решения

### 1. Бейдж "На проверке" не отображался в личном кабинете

**Проблема:**
- Новые объявления на модерации не показывали бейдж "На проверке"
- Объявления после редактирования тоже не показывали бейдж

**Причина:**
- ItemCard.vue импортировал неправильный компонент ItemContent из `shared/ui/molecules/`
- Правильный компонент с логикой бейджей находился в `./components/ItemContent.vue`

**Решение:**
```javascript
// ItemCard.vue - исправлен импорт
import ItemContent from './components/ItemContent.vue' // вместо '@/src/shared/ui/molecules/ItemContent.vue'
```

### 2. Дублирование бейджа "На проверке"

**Проблема:**
- Бейдж отображался дважды: слева на фото и справа

**Причина:**
- ItemContent.vue показывал бейдж для `pending_moderation`
- ItemStats.vue тоже показывал бейдж для `active && !is_published`

**Решение:**
- Удален бейдж из ItemContent.vue
- Оставлен только в ItemStats.vue с правильным условием:
```javascript
// ItemStats.vue
v-if="(item.status === 'pending_moderation') || (item.status === 'active' && item.is_published === false)"
```

### 3. Объявления после редактирования не попадали на модерацию в админ-панели

**Проблема:**
- После редактирования активного объявления оно оставалось со статусом `active`
- В админ-панели такие объявления не попадали в раздел "На модерации"

**Причина:**
- DraftService блокировал изменение статуса для активных объявлений
- Код удалял статус из данных: `unset($data['status'])`

**Решение в DraftService.php:**
```php
// Разрешаем переход active -> pending_moderation при редактировании
if ($existingAd && isset($data['status'])) {
    if ($existingAd->status === AdStatus::ACTIVE &&
        $data['status'] === AdStatus::PENDING_MODERATION->value) {
        // Разрешаем переход - статус остается в $data
    } elseif ($existingAd->status !== AdStatus::DRAFT &&
             !in_array($existingAd->status, $waitingStatuses)) {
        // Для остальных случаев не меняем статус
        unset($data['status']);
    }
}
```

### 4. TypeError при редактировании объявления

**Проблема:**
```
TypeError: Return value must be of type Illuminate\Http\RedirectResponse, Illuminate\Http\Response returned
```

**Причина:**
- Метод `update()` в AdController имел тип возврата `RedirectResponse`
- Но использовал `Inertia::location()`, который возвращает `Response`

**Решение в AdController.php:**
```php
// Было:
return Inertia::location('/profile/items/active/all');

// Стало:
return redirect('/profile/items/active/all');
```

## Логика работы системы модерации

### Статусы и их значение:
- `pending_moderation` - новое объявление на модерации
- `active` + `is_published: false` - отредактированное активное на повторной модерации
- `active` + `is_published: true` - опубликованное активное объявление

### Поток данных:
1. **Новое объявление** → `status: pending_moderation`, `is_published: false`
2. **После одобрения** → `status: active`, `is_published: true`
3. **После редактирования** → `status: pending_moderation`, `is_published: false`
4. **После повторного одобрения** → `status: active`, `is_published: true`

## Файлы, которые были изменены:

1. `resources/js/src/entities/ad/ui/ItemCard/ItemCard.vue` - исправлен импорт
2. `resources/js/src/entities/ad/ui/ItemCard/components/ItemContent.vue` - удален дублирующий бейдж
3. `resources/js/src/entities/ad/ui/ItemCard/components/ItemStats.vue` - исправлено условие отображения
4. `app/Domain/Ad/Services/DraftService.php` - разрешен переход active → pending_moderation
5. `app/Application/Http/Controllers/Ad/AdController.php` - исправлен тип возврата

## Уроки и рекомендации:

1. **Всегда проверяйте импорты** - неправильный путь к компоненту может привести к неработающему функционалу
2. **Избегайте дублирования логики** - один бейдж должен отображаться в одном месте
3. **Следите за типами возврата** - TypeScript/PHP типы должны соответствовать реальным возвращаемым значениям
4. **Понимайте бизнес-логику** - статус может быть составным (status + is_published)
5. **Тестируйте весь поток** - от создания до модерации и редактирования

## Принципы CLAUDE.md применённые в решении:

- **KISS** - простые решения вместо сложных (redirect вместо Inertia::location)
- **YAGNI** - не добавляли лишней логики, только исправили существующую
- **DRY** - убрали дублирование бейджа, оставили в одном месте