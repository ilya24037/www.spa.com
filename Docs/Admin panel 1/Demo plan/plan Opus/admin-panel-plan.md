# План разработки админ-панели SPA Platform

## 📌 Основные принципы
- **KISS** - Keep it simple (делай просто)
- **YAGNI** - No overengineering (без переусложнения)  
- **DRY** - Don't repeat (не повторяйся)
- **SOLID** - принципы проектирования

## 🎯 Цель админ-панели
Создать минимально необходимый функционал для управления платформой массажных услуг, который сможет обслуживать один человек с помощью ИИ-ассистента.

---

## 📊 Структура функционала

### 1. Модерация объявлений (MVP)

#### 1.1 Статусы объявлений
```php
enum AdStatus {
    DRAFT = 'draft';           // Черновик
    PENDING = 'pending';       // На модерации  
    ACTIVE = 'active';         // Опубликовано
    REJECTED = 'rejected';     // Отклонено
    ARCHIVED = 'archived';     // В архиве
}
```

#### 1.2 Функции модерации
- [ ] Очередь объявлений на проверку
- [ ] Просмотр деталей объявления
- [ ] Кнопки быстрых действий (Одобрить/Отклонить)
- [ ] Выбор причины отклонения из списка
- [ ] Фильтр по статусам
- [ ] Поиск по ID/названию

#### 1.3 Автоматические проверки (простые)
- [ ] Запрещенные слова в тексте
- [ ] Телефоны/email в описании
- [ ] Проверка на дубликаты
- [ ] Минимальная длина описания

### 2. Управление пользователями

#### 2.1 Роли пользователей
```php
enum UserRole {
    CLIENT = 'client';     // Клиент
    MASTER = 'master';     // Мастер  
    ADMIN = 'admin';       // Администратор
}
```

#### 2.2 Функции управления
- [ ] Список всех пользователей с пагинацией
- [ ] Фильтр по ролям (клиенты/мастера)
- [ ] Фильтр по статусу (активные/заблокированные)
- [ ] Просмотр профиля пользователя
- [ ] Блокировка/разблокировка аккаунта
- [ ] История действий пользователя
- [ ] Поиск по имени/email/телефону

### 3. Отзывы и жалобы

#### 3.1 Модерация отзывов
- [ ] Очередь отзывов на проверку
- [ ] Автопроверка на спам (простая)
- [ ] Скрытие/удаление отзыва
- [ ] Просмотр контекста (объявление, мастер)

#### 3.2 Обработка жалоб
```php
enum ComplaintPriority {
    HIGH = 'high';       // Критично (мошенничество)
    MEDIUM = 'medium';   // Средне (качество услуги)
    LOW = 'low';         // Низко (неверная категория)
}
```

- [ ] Очередь жалоб с приоритетами
- [ ] Просмотр деталей жалобы
- [ ] Принятие мер (блокировка/предупреждение)
- [ ] Закрытие жалобы с комментарием

### 4. Статистика (базовая)

#### 4.1 Дашборд метрики
- [ ] Всего объявлений (активные/на модерации)
- [ ] Пользователи (новые за период)
- [ ] Бронирования (количество/сумма)
- [ ] Конверсия (просмотры → бронирования)

#### 4.2 Графики
- [ ] График регистраций по дням
- [ ] График бронирований по дням
- [ ] Топ-5 мастеров по бронированиям

### 5. Настройки системы

#### 5.1 Базовые настройки
- [ ] Управление стоп-словами
- [ ] Шаблоны причин отклонения
- [ ] Email уведомления админу
- [ ] Лимиты (макс. объявлений на пользователя)

---

## 🏗 Техническая реализация

### Backend структура

```
app/Domain/Admin/
├── Controllers/
│   ├── DashboardController.php
│   ├── ModerationController.php
│   ├── UserManagementController.php
│   └── ComplaintController.php
├── Services/
│   ├── ModerationService.php
│   ├── UserService.php
│   └── StatisticsService.php
├── DTOs/
│   ├── ModerateAdDTO.php
│   └── BlockUserDTO.php
└── Middleware/
    └── AdminOnly.php
```

### Frontend структура

```
resources/js/src/pages/admin/
├── Dashboard.vue
├── moderation/
│   ├── AdQueue.vue
│   ├── AdDetail.vue
│   └── components/
│       ├── AdCard.vue
│       └── RejectModal.vue
├── users/
│   ├── UserList.vue
│   ├── UserProfile.vue
│   └── components/
│       └── UserCard.vue
└── components/
    ├── StatsWidget.vue
    └── AdminSidebar.vue
```

### База данных

```sql
-- Таблица модераций
CREATE TABLE moderation_logs (
    id BIGINT PRIMARY KEY,
    moderatable_type VARCHAR(255),
    moderatable_id BIGINT,
    action ENUM('approve', 'reject', 'block'),
    reason VARCHAR(255),
    moderated_by BIGINT,
    created_at TIMESTAMP
);

-- Таблица жалоб
CREATE TABLE complaints (
    id BIGINT PRIMARY KEY,
    complainable_type VARCHAR(255),
    complainable_id BIGINT,
    user_id BIGINT,
    priority ENUM('high', 'medium', 'low'),
    message TEXT,
    status ENUM('pending', 'resolved', 'dismissed'),
    resolved_by BIGINT,
    resolved_at TIMESTAMP,
    created_at TIMESTAMP
);
```

---

## 📅 План внедрения

### Фаза 1: MVP (2 недели)
**Цель:** Базовый функционал для запуска

- [ ] **Неделя 1:**
  - [ ] Backend: Контроллеры и сервисы модерации
  - [ ] Frontend: Страница очереди модерации
  - [ ] Интеграция: API endpoints

- [ ] **Неделя 2:**
  - [ ] Backend: Управление пользователями
  - [ ] Frontend: Список пользователей
  - [ ] Тестирование базового функционала

### Фаза 2: Расширение (2 недели)
**Цель:** Добавить отзывы и жалобы

- [ ] **Неделя 3:**
  - [ ] Модерация отзывов
  - [ ] Система жалоб

- [ ] **Неделя 4:**
  - [ ] Базовая статистика
  - [ ] Дашборд с метриками

### Фаза 3: Оптимизация (после запуска)
**Цель:** Улучшения на основе обратной связи

- [ ] Автоматизация рутинных проверок
- [ ] Расширенные фильтры и поиск
- [ ] Экспорт данных в Excel
- [ ] Email-уведомления о важных событиях

---

## 🚫 Что НЕ делаем (YAGNI)

### Избегаем переусложнения:
- ❌ Machine Learning для модерации
- ❌ Сложные системы скоринга
- ❌ Real-time аналитика
- ❌ Интеграция с внешними API для проверок
- ❌ Многоуровневая система ролей
- ❌ A/B тестирование
- ❌ Динамическое ценообразование
- ❌ Elasticsearch (используем MySQL LIKE)

### Откладываем на потом:
- ⏰ Массовые операции (batch)
- ⏰ Детальная аналитика
- ⏰ API для внешних систем
- ⏰ Мобильное приложение админки
- ⏰ Биометрическая верификация

---

## 📝 Примеры кода

### ModerationService.php
```php
class ModerationService
{
    public function moderateAd(Ad $ad, string $action, ?string $reason = null): Ad
    {
        return DB::transaction(function() use ($ad, $action, $reason) {
            $ad->status = $action === 'approve' ? 'active' : 'rejected';
            $ad->rejection_reason = $reason;
            $ad->moderated_at = now();
            $ad->moderated_by = auth()->id();
            $ad->save();
            
            ModerationLog::create([
                'moderatable_type' => Ad::class,
                'moderatable_id' => $ad->id,
                'action' => $action,
                'reason' => $reason,
                'moderated_by' => auth()->id()
            ]);
            
            event(new AdModerated($ad));
            
            return $ad;
        });
    }
    
    public function checkForbiddenWords(string $text): bool
    {
        $forbiddenWords = Cache::remember('forbidden_words', 3600, function() {
            return Setting::where('key', 'forbidden_words')->value('value');
        });
        
        foreach ($forbiddenWords as $word) {
            if (stripos($text, $word) !== false) {
                return false;
            }
        }
        
        return true;
    }
}
```

### AdQueue.vue
```vue
<template>
  <div class="p-6">
    <!-- Статистика -->
    <div class="grid grid-cols-4 gap-4 mb-6">
      <StatsCard
        title="На модерации"
        :value="stats.pending"
        color="yellow"
      />
      <StatsCard
        title="Одобрено сегодня"
        :value="stats.approvedToday"
        color="green"
      />
      <StatsCard
        title="Отклонено сегодня"
        :value="stats.rejectedToday"
        color="red"
      />
      <StatsCard
        title="Среднее время"
        :value="stats.avgTime"
        suffix="мин"
      />
    </div>

    <!-- Фильтры -->
    <div class="bg-white rounded-lg shadow mb-6 p-4">
      <div class="flex gap-4">
        <select v-model="filters.status" class="form-select">
          <option value="pending">На модерации</option>
          <option value="all">Все</option>
        </select>
        <input 
          v-model="filters.search"
          type="text" 
          placeholder="Поиск..."
          class="form-input flex-1"
        >
      </div>
    </div>

    <!-- Очередь -->
    <div class="space-y-4">
      <AdModerationCard
        v-for="ad in ads"
        :key="ad.id"
        :ad="ad"
        @approve="approveAd"
        @reject="openRejectModal"
      />
    </div>

    <!-- Модал отклонения -->
    <RejectModal
      v-model="showRejectModal"
      :ad="selectedAd"
      @confirm="rejectAd"
    />
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useAdminStore } from '@/stores/admin'

const adminStore = useAdminStore()
const ads = ref([])
const stats = ref({
  pending: 0,
  approvedToday: 0,
  rejectedToday: 0,
  avgTime: 15
})

const filters = ref({
  status: 'pending',
  search: ''
})

const showRejectModal = ref(false)
const selectedAd = ref(null)

const loadAds = async () => {
  const response = await adminStore.fetchModerationQueue(filters.value)
  ads.value = response.data
  stats.value = response.stats
}

const approveAd = async (id: number) => {
  await adminStore.moderateAd(id, 'approve')
  await loadAds()
}

const rejectAd = async (id: number, reason: string) => {
  await adminStore.moderateAd(id, 'reject', reason)
  showRejectModal.value = false
  await loadAds()
}

onMounted(() => {
  loadAds()
})
</script>
```

---

## ✅ Критерии готовности

### MVP готов когда:
- [ ] Админ может просматривать очередь модерации
- [ ] Админ может одобрять/отклонять объявления
- [ ] Админ может блокировать пользователей
- [ ] Админ видит базовую статистику
- [ ] Система логирует все действия админа

### Метрики успеха:
- Время модерации одного объявления < 30 секунд
- Все критичные действия логируются
- Интерфейс работает без багов
- Нагрузка до 100 объявлений в день

---

## 🔗 Связанные документы
- @doc/aidd/idea.md - Бизнес-идея проекта
- @doc/aidd/vision.md - Техническое видение
- @doc/aidd/tasklist.md - План разработки
- @doc/aidd/conventions.md - Стандарты кода

---

*Документ обновлен: 2025-01-22*
*Версия: 1.0*