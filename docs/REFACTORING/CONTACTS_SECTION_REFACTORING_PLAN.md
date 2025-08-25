# 📋 ДЕТАЛЬНЫЙ ПЛАН РЕФАКТОРИНГА ContactsSection

**Дата создания:** 21 января 2025  
**Статус:** 📝 Планирование  
**Приоритет:** 🟡 Средний (после ParametersSection)  
**Время выполнения:** 2 ч 15 мин  

---

## 🎯 ЦЕЛЬ РЕФАКТОРИНГА

Преобразовать ContactsSection с 4 отдельных v-model в единый объектный подход для:
- ✅ Упрощения кода и улучшения читабельности
- ✅ Повышения консистентности с другими секциями  
- ✅ Снижения количества props/events с 8 до 2
- ✅ Улучшения поддерживаемости кода

---

## 📊 ТЕКУЩЕЕ СОСТОЯНИЕ vs ЦЕЛЕВОЕ

### 🔴 СЕЙЧАС (4 v-model):
```vue
<!-- AdForm.vue -->
<ContactsSection 
  v-model:phone="form.phone"
  v-model:contactMethod="form.contact_method"
  v-model:whatsapp="form.whatsapp"
  v-model:telegram="form.telegram"
  :errors="errors"
/>
```

**Проблемы:**
- 4 отдельные привязки v-model
- 8 props/events (4 поля + 4 события)
- Неконсистентность с остальными секциями
- Сложность добавления новых контактных полей

### 🟢 ЦЕЛЬ (1 v-model):
```vue
<!-- AdForm.vue -->
<ContactsSection 
  v-model:contacts="form.contacts"
  :errors="errors.contacts || {}"
/>
```

**Преимущества:**
- 1 объектная привязка v-model
- 2 props/events (contacts + errors)
- Консистентность с ServiceProviderSection, ClientsSection
- Легкость добавления новых полей

---

## 🚀 ПЛАН ВЫПОЛНЕНИЯ

### 📅 ШАГ 1: ПОДГОТОВКА ДАННЫХ (25 мин)

#### 1.1. Обновить типы в adFormModel.ts

**Файл:** `resources/js/src/features/ad-creation/model/adFormModel.ts`

**ДОБАВИТЬ в AdFormData:**
```typescript
contacts: {
  phone: string
  contact_method: string
  whatsapp: string
  telegram: string
}
```

**УДАЛИТЬ отдельные поля:**
```typescript
// УДАЛИТЬ:
phone: string              // переместить в contacts
contact_method: string     // переместить в contacts
whatsapp: string          // переместить в contacts
telegram: string          // переместить в contacts
```

#### 1.2. Обновить инициализацию формы

**В reactive<AdFormData>({...}) ЗАМЕНИТЬ:**
```typescript
// СТАРОЕ:
phone: props.initialData?.phone || '',
contact_method: props.initialData?.contact_method || 'phone',
whatsapp: props.initialData?.whatsapp || '',
telegram: props.initialData?.telegram || '',

// НОВОЕ:
contacts: {
  phone: savedFormData?.contacts?.phone || props.initialData?.phone || '',
  contact_method: savedFormData?.contacts?.contact_method || props.initialData?.contact_method || 'phone',
  whatsapp: savedFormData?.contacts?.whatsapp || props.initialData?.whatsapp || '',
  telegram: savedFormData?.contacts?.telegram || props.initialData?.telegram || ''
}
```

#### 1.3. Обеспечить обратную совместимость

**Добавить миграционную логику:**
```typescript
// Обеспечиваем совместимость со старыми данными
const migrateContacts = (data: any) => {
  if (data.contacts) {
    return data.contacts // Уже в новом формате
  }
  
  // Мигрируем из старого формата
  return {
    phone: data.phone || '',
    contact_method: data.contact_method || 'phone',
    whatsapp: data.whatsapp || '',
    telegram: data.telegram || ''
  }
}

// Использовать в инициализации:
contacts: migrateContacts(savedFormData || props.initialData || {})
```

---

### 📅 ШАГ 2: РЕФАКТОРИНГ ContactsSection.vue (35 мин)

**Файл:** `resources/js/src/features/AdSections/ContactsSection/ui/ContactsSection.vue`

#### 2.1. Обновить props и emits

```vue
<script setup>
// БЫЛО:
const props = defineProps({
  phone: { type: String, default: '' },
  contactMethod: { type: String, default: 'any' },
  whatsapp: { type: String, default: '' },
  telegram: { type: String, default: '' },
  errors: { type: Object, default: () => ({}) }
})

const emit = defineEmits(['update:phone', 'update:contactMethod', 'update:whatsapp', 'update:telegram'])

// СТАЛО:
const props = defineProps({
  contacts: { 
    type: Object, 
    default: () => ({
      phone: '',
      contact_method: 'any',
      whatsapp: '',
      telegram: ''
    })
  },
  errors: { type: Object, default: () => ({}) }
})

const emit = defineEmits(['update:contacts'])
</script>
```

#### 2.2. Обновить локальные переменные

```javascript
// БЫЛО:
const localPhone = ref(props.phone)
const localContactMethod = ref(props.contactMethod)
const localWhatsapp = ref(props.whatsapp)
const localTelegram = ref(props.telegram)

// СТАЛО:
const localContacts = ref({ ...props.contacts })

// Computed для удобства доступа к полям
const localPhone = computed({
  get: () => localContacts.value.phone,
  set: (value) => updateContact('phone', value)
})

const localContactMethod = computed({
  get: () => localContacts.value.contact_method,
  set: (value) => updateContact('contact_method', value)
})

const localWhatsapp = computed({
  get: () => localContacts.value.whatsapp,
  set: (value) => updateContact('whatsapp', value)
})

const localTelegram = computed({
  get: () => localContacts.value.telegram,
  set: (value) => updateContact('telegram', value)
})
```

#### 2.3. Создать универсальную функцию обновления

```javascript
const updateContact = (field: string, value: any) => {
  localContacts.value[field] = value
  emit('update:contacts', { ...localContacts.value })
}

// ЗАМЕНИТЬ emitAll на:
const emitAll = () => {
  emit('update:contacts', { ...localContacts.value })
}
```

#### 2.4. Обновить watchers

```javascript
// БЫЛО:
watch(() => props.phone, val => { localPhone.value = val })
watch(() => props.contactMethod, val => { localContactMethod.value = val })
watch(() => props.whatsapp, val => { localWhatsapp.value = val })
watch(() => props.telegram, val => { localTelegram.value = val })

// СТАЛО:
watch(() => props.contacts, (newContacts) => {
  localContacts.value = { ...newContacts }
}, { deep: true })
```

#### 2.5. Обновить template (остается без изменений)

```vue
<template>
  <div class="bg-white rounded-lg p-5">
    <h2 class="text-lg font-semibold text-gray-900 mb-4">Контакты</h2>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
      <!-- Телефон -->
      <BaseInput
        v-model="localPhone"
        v-maska="'+7 (###) ###-##-##'"
        type="tel"
        label="Телефон"
        placeholder="+7 (999) 999-99-99"
        hint="Основной номер для связи"
        pattern="^\+7 \([0-9]{3}\) [0-9]{3}-[0-9]{2}-[0-9]{2}$"
        :error="errors.phone"
        @update:modelValue="emitAll"
      />
      
      <!-- WhatsApp -->
      <BaseInput
        v-model="localWhatsapp"
        v-maska="'+7 (###) ###-##-##'"
        type="tel"
        label="WhatsApp"
        placeholder="+7 (999) 999-99-99"
        hint="Оставьте пустым, если совпадает с телефоном"
        pattern="^\+7 \([0-9]{3}\) [0-9]{3}-[0-9]{2}-[0-9]{2}$"
        :error="errors.whatsapp"
        @update:modelValue="emitAll"
      />
      
      <!-- Telegram -->
      <BaseInput
        v-model="localTelegram"
        type="text"
        label="Telegram"
        placeholder="@username или +7 (999) 999-99-99"
        hint="Ник или номер телефона"
        :error="errors.telegram"
        @update:modelValue="emitAll"
      />
      
      <!-- Способ связи -->
      <BaseSelect
        v-model="localContactMethod"
        label="Способ связи"
        :options="contactMethodOptions"
        hint="Как клиенты могут с вами связаться"
        :error="errors.contact_method"
        @update:modelValue="emitAll"
      />
    </div>
  </div>
</template>
```

---

### 📅 ШАГ 3: ОБНОВЛЕНИЕ AdForm.vue (10 мин)

**Файл:** `resources/js/src/features/ad-creation/ui/AdForm.vue`

#### 3.1. Заменить v-model привязки

```vue
<!-- БЫЛО: -->
<ContactsSection 
  v-model:phone="form.phone"
  v-model:contactMethod="form.contact_method"
  v-model:whatsapp="form.whatsapp"
  v-model:telegram="form.telegram"
  :errors="errors"
/>

<!-- СТАЛО: -->
<ContactsSection 
  v-model:contacts="form.contacts"
  :errors="errors.contacts || {}"
/>
```

#### 3.2. Обновить функции валидации

```javascript
// В checkSectionFilled обновить логику:
const checkSectionFilled = (section: string): boolean => {
  switch (section) {
    case 'contacts':
      const contacts = form.contacts
      return !!(contacts.phone) // Только телефон обязателен
    // ... остальные секции без изменений
  }
}

// В getFilledCount обновить логику:
const getFilledCount = (section: string): number => {
  switch (section) {
    case 'contacts':
      const contacts = form.contacts
      let count = 0
      if (contacts.phone) count++
      if (contacts.whatsapp) count++
      if (contacts.telegram) count++
      if (contacts.contact_method && contacts.contact_method !== 'any') count++
      return count
    // ... остальные секции без изменений
  }
}
```

#### 3.3. Обновить конфигурацию секций

```javascript
// В sectionsConfig обновить:
{
  key: 'contacts',
  title: 'Контакты',
  required: true,
  fields: ['phone', 'contact_method', 'whatsapp', 'telegram'] // Для справки
}
```

---

### 📅 ШАГ 4: ОБНОВЛЕНИЕ BACKEND (25 мин)

#### 4.1. Обновить adApi.js prepareFormData

**Файл:** `resources/js/utils/adApi.js`

```javascript
// В prepareFormData ЗАМЕНИТЬ:
// СТАРОЕ:
phone: form.phone || '',
contact_method: form.contact_method || 'messages',
whatsapp: form.whatsapp || '',
telegram: form.telegram || '',

// НОВОЕ:
phone: form.contacts?.phone || '',
contact_method: form.contacts?.contact_method || 'messages',
whatsapp: form.contacts?.whatsapp || '',
telegram: form.contacts?.telegram || '',
```

#### 4.2. Проверить AdResource.php

**Файл:** `app/Application/Http/Resources/Ad/AdResource.php`

Убедиться что поля `phone`, `contact_method`, `whatsapp`, `telegram` правильно возвращаются в AdResource для загрузки в форму.

**Проверить наличие полей в toArray():**
```php
// В toArray() методе должны быть:
'phone' => $this->phone,
'contact_method' => $this->contact_method,
'whatsapp' => $this->whatsapp,
'telegram' => $this->telegram,

// В секции 'contact':
'contact' => [
    'phone' => $this->phone,
    'contact_method' => $this->contact_method,
    'whatsapp' => $this->whatsapp,
    'telegram' => $this->telegram,
],
```

#### 4.3. Проверить модель Ad.php

**Файл:** `app/Domain/Ad/Models/Ad.php`

Убедиться что поля присутствуют в `$fillable`:
```php
protected $fillable = [
    // ... другие поля
    'phone',
    'contact_method',
    'whatsapp',
    'telegram',
    // ... другие поля
];
```

#### 4.4. Обновить валидацию (если есть)

Проверить файлы валидации контактных данных и убедиться что они корректно обрабатывают новую структуру.

---

### 📅 ШАГ 5: ТЕСТИРОВАНИЕ (25 мин)

#### 5.1. Функциональное тестирование

**5.1.1. Создание нового черновика:**
1. Открыть: `http://spa.test/ads/create`
2. Заполнить все поля контактов:
   - Телефон: `+7 (999) 123-45-67`
   - WhatsApp: `+7 (999) 123-45-68`
   - Telegram: `@testuser`
   - Способ связи: `Любой способ`
3. Нажать "Сохранить черновик"
4. Проверить что данные сохранились в БД
5. Команда проверки: `SELECT phone, contact_method, whatsapp, telegram FROM ads WHERE id = LAST_INSERT_ID();`

**5.1.2. Редактирование существующего черновика:**
1. Открыть существующий черновик
2. Проверить что все контактные поля загрузились корректно
3. Изменить несколько полей (например, изменить способ связи)
4. Сохранить и проверить изменения в БД

**5.1.3. Редактирование активного объявления:**
1. Открыть активное объявление для редактирования
2. Проверить загрузку всех контактных полей
3. Изменить контактные данные и сохранить
4. Убедиться что изменения применились

#### 5.2. UI/UX тестирование

**5.2.1. Валидация полей:**
- Проверить обязательное поле "Телефон"
- Проверить маску телефона: `+7 (###) ###-##-##`
- Проверить валидацию формата телефона
- Проверить что форма не отправляется без телефона

**5.2.2. Функциональность полей:**
- Проверить работу масок для телефона и WhatsApp
- Убедиться что Telegram принимает как @username, так и номер
- Проверить выбор способа связи из выпадающего списка
- Проверить подсказки (hints) у полей

**5.2.3. Адаптивность:**
- Проверить отображение на мобильных устройствах
- Убедиться что grid корректно адаптируется (1 колонка на мобильных, 2 на десктопе)
- Проверить читабельность подсказок

#### 5.3. Производительность

**5.3.1. Проверить отсутствие лишних ререндеров:**
- Открыть Vue DevTools
- Следить за ContactsSection при изменении полей
- Убедиться что обновляются только измененные поля

**5.3.2. Проверить работу масок:**
- Убедиться что маски не вызывают задержек при вводе
- Проверить что маски корректно работают при программном изменении значений

#### 5.4. Совместимость

**5.4.1. Обратная совместимость:**
- Открыть старые черновики (созданные до рефакторинга)
- Убедиться что контактные данные корректно загружаются
- Проверить что сохранение работает без потери данных

**5.4.2. Интеграция с другими секциями:**
- Убедиться что изменения в ContactsSection не влияют на другие секции
- Проверить общую валидацию формы
- Проверить подсчет прогресса заполнения формы

---

### 📅 ШАГ 6: ОТКАТ И РЕЗЕРВНОЕ КОПИРОВАНИЕ (15 мин)

#### 6.1. Создать бэкапы

```bash
# Создать папку для бэкапов
mkdir backup/contacts-refactor-$(date +%Y%m%d_%H%M%S)

# Сохранить оригинальные файлы
cp resources/js/src/features/AdSections/ContactsSection/ui/ContactsSection.vue backup/contacts-refactor-*/ContactsSection.vue.backup
cp resources/js/src/features/ad-creation/ui/AdForm.vue backup/contacts-refactor-*/AdForm.vue.backup
cp resources/js/src/features/ad-creation/model/adFormModel.ts backup/contacts-refactor-*/adFormModel.ts.backup
cp resources/js/utils/adApi.js backup/contacts-refactor-*/adApi.js.backup
```

#### 6.2. Подготовить план отката

```bash
# Команды для быстрого отката если что-то пойдет не так

# Вариант 1: Git stash
git add .
git stash push -m "contacts-refactor-backup"

# Вариант 2: Git commit + reset
git add .
git commit -m "WIP: contacts refactor backup"
# Для отката: git reset --hard HEAD~1

# Вариант 3: Ручное восстановление из бэкапов
# cp backup/contacts-refactor-*/ContactsSection.vue.backup resources/js/src/features/AdSections/ContactsSection/ui/ContactsSection.vue
# cp backup/contacts-refactor-*/AdForm.vue.backup resources/js/src/features/ad-creation/ui/AdForm.vue
# cp backup/contacts-refactor-*/adFormModel.ts.backup resources/js/src/features/ad-creation/model/adFormModel.ts
# cp backup/contacts-refactor-*/adApi.js.backup resources/js/utils/adApi.js
```

#### 6.3. Создать чеклист для отката

```markdown
ЧЕКЛИСТ ОТКАТА:
□ Остановить npm run dev
□ Восстановить файлы из бэкапа
□ Очистить кеш: php artisan cache:clear
□ Перезапустить npm run dev
□ Проверить что сайт работает
□ Проверить создание/редактирование черновиков
□ Проверить активные объявления
□ Проверить отправку контактных данных
```

---

## 📊 ВРЕМЕННЫЕ ЗАТРАТЫ

| Этап | Время | Сложность | Критичность |
|------|-------|-----------|-------------|
| Подготовка данных | 25 мин | 🟡 Средняя | 🔴 Высокая |
| Рефакторинг компонента | 35 мин | 🟡 Средняя | 🔴 Высокая |
| Обновление AdForm | 10 мин | 🟢 Низкая | 🟡 Средняя |
| Обновление Backend | 25 мин | 🟡 Средняя | 🔴 Высокая |
| Тестирование | 25 мин | 🟡 Средняя | 🔴 Высокая |
| Бэкапы и откат | 15 мин | 🟢 Низкая | 🟡 Средняя |
| **ИТОГО** | **2 ч 15 мин** | **🟡 Средняя** | **🔴 Высокая** |

---

## ⚠️ РИСКИ И МИТИГАЦИЯ

### 🔴 ВЫСОКИЕ РИСКИ:

**1. Поломка существующих контактных данных**
- **Вероятность:** 25%
- **Воздействие:** Критическое (потеря связи с клиентами)
- **Митигация:** 
  - Обратная совместимость в adFormModel.ts
  - Детальное тестирование на существующих данных
  - Проверка сохранения телефонов в правильном формате
- **План Б:** Быстрый откат через git reset

**2. Проблемы с валидацией телефонных номеров**
- **Вероятность:** 20%
- **Воздействие:** Высокое
- **Митигация:**
  - Тщательная проверка масок телефонов
  - Тестирование различных форматов ввода
  - Проверка регулярных выражений для валидации
- **План Б:** Временно отключить строгую валидацию

**3. Потеря контактных данных при сохранении**
- **Вероятность:** 15%
- **Воздействие:** Критическое
- **Митигация:**
  - Тщательная проверка adApi.js prepareFormData
  - Логирование всех операций с контактными данными
  - Проверка что все поля присутствуют в $fillable
- **План Б:** Восстановление из бэкапа БД

### 🟡 СРЕДНИЕ РИСКИ:

**1. Проблемы с масками ввода (vMaska)**
- **Вероятность:** 15%
- **Воздействие:** Среднее
- **Митигация:**
  - Проверка совместимости с новой архитектурой
  - Тестирование на разных браузерах
  - Альтернативные решения для масок
- **План Б:** Использование альтернативной библиотеки масок

**2. UI баги в адаптивной сетке**
- **Вероятность:** 10%
- **Воздействие:** Среднее
- **Митигация:**
  - Тестирование на мобильных устройствах
  - Проверка Tailwind CSS grid
  - Тестирование различных размеров экрана
- **План Б:** Фиксы стилей в отдельном коммите

**3. Конфликты с другими компонентами**
- **Вероятность:** 10%
- **Воздействие:** Среднее
- **Митигация:**
  - Проверка зависимостей в других частях системы
  - Тестирование интеграции с формой
  - Проверка валидации на уровне формы
- **План Б:** Поэтапный рефакторинг с тестированием

### 🟢 НИЗКИЕ РИСКИ:

**1. Проблемы с TypeScript типами**
- **Вероятность:** 5%
- **Воздействие:** Низкое
- **Митигация:** Тщательная типизация контактного объекта
- **План Б:** Временное использование any типов

**2. Проблемы с производительностью**
- **Вероятность:** 5%
- **Воздействие:** Низкое
- **Митигация:** Оптимизация computed свойств и watchers
- **План Б:** Профилирование и оптимизация

---

## 🎯 КРИТЕРИИ УСПЕХА

### ✅ ОБЯЗАТЕЛЬНЫЕ КРИТЕРИИ:

1. **Функциональность:**
   - ✅ Все существующие контактные данные загружаются без ошибок
   - ✅ Создание новых объявлений с контактами работает
   - ✅ Редактирование контактных данных работает без потери
   - ✅ Валидация телефонных номеров работает корректно
   - ✅ Маски ввода работают на всех полях

2. **Совместимость:**
   - ✅ Обратная совместимость с существующими данными
   - ✅ Корректная работа во всех поддерживаемых браузерах
   - ✅ Адаптивность на мобильных устройствах

3. **Безопасность данных:**
   - ✅ Контактные данные не теряются при сохранении
   - ✅ Валидация предотвращает некорректные данные
   - ✅ Форматирование телефонов работает корректно

### 🏆 ЖЕЛАТЕЛЬНЫЕ КРИТЕРИИ:

1. **Качество кода:**
   - 🎯 Сокращение кода в AdForm.vue с 4 до 1 строки для ContactsSection
   - 🎯 Единообразие с другими секциями
   - 🎯 Улучшенная типизация TypeScript
   - 🎯 Более чистая архитектура компонента

2. **Поддерживаемость:**
   - 🎯 Легче добавлять новые контактные поля
   - 🎯 Проще тестировать компонент изолированно
   - 🎯 Меньше вероятность ошибок при изменениях

3. **Пользовательский опыт:**
   - 🎯 Плавная работа масок ввода
   - 🎯 Интуитивная валидация полей
   - 🎯 Быстрая инициализация компонента

### 📊 МЕТРИКИ УСПЕХА:

1. **Количественные:**
   - Сокращение строк кода в AdForm.vue с 4 до 1 для ContactsSection
   - Сокращение количества props с 5 до 2
   - Сокращение количества emits с 4 до 1
   - 100% совместимость с существующими контактными данными

2. **Качественные:**
   - Отсутствие регрессий в функциональности контактов
   - Сохранение всех возможностей валидации и масок
   - Улучшение читабельности кода

---

## 🚀 ГОТОВНОСТЬ К ВЫПОЛНЕНИЮ

### ✅ ПРЕДВАРИТЕЛЬНЫЕ УСЛОВИЯ:

1. **Техническая готовность:**
   - ✅ Проект SPA Platform запущен и работает
   - ✅ npm run dev активен для hot reload
   - ✅ База данных доступна и содержит контактные данные
   - ✅ Git репозиторий в чистом состоянии

2. **Зависимости:**
   - ✅ Библиотека vMaska работает корректно
   - ✅ BaseInput и BaseSelect компоненты стабильны
   - ✅ Tailwind CSS grid функционирует

3. **Тестовые данные:**
   - ✅ Есть черновики с заполненными контактами
   - ✅ Есть активные объявления с контактными данными
   - ✅ Есть тестовые телефонные номера для проверки

### 📋 ЧЕКЛИСТ ПЕРЕД НАЧАЛОМ:

- [ ] Создать резервную копию БД с контактными данными
- [ ] Убедиться что ContactsSection работает в текущем состоянии
- [ ] Проверить что нет незакоммиченных изменений в контактных компонентах
- [ ] Подготовить тестовые номера телефонов для проверки
- [ ] Проверить работу масок vMaska на текущей версии

---

## 📞 ПОДДЕРЖКА И ВОПРОСЫ

**При возникновении проблем:**

1. **Проблемы с масками:** Проверить документацию vMaska и совместимость
2. **Проблемы с контактными данными:** Проверить логи Laravel и консоль браузера  
3. **Критические ошибки:** Немедленно выполнить откат по плану
4. **Проблемы с валидацией:** Временно отключить строгую валидацию

**Контакты для экстренной связи:**
- Git репозиторий: локальные бэкапы в папке backup/
- Документация: docs/REFACTORING/ 
- Логи: storage/logs/laravel.log
- Тестовые данные: Бэкап БД перед началом работ

---

## 🔍 ОТЛИЧИЯ ОТ ParametersSection

### 📊 СРАВНЕНИЕ СЛОЖНОСТИ:

| Аспект | ParametersSection | ContactsSection |
|--------|-------------------|-----------------|
| Количество полей | 8 | 4 |
| Типы данных | Смешанные (строки, числа) | Только строки |
| Валидация | Сложная (возраст, рост, вес) | Простая (телефон) |
| Внешние зависимости | Нет | vMaska для масок |
| Условное отображение | Да (showFields) | Нет |
| Время рефакторинга | 2 ч 45 мин | 2 ч 15 мин |

### 🎯 УПРОЩЕНИЯ:

1. **Меньше полей:** 4 вместо 8 - проще рефакторинг
2. **Единый тип данных:** Все поля строковые - проще типизация
3. **Нет условного отображения:** Все поля всегда видны
4. **Простая валидация:** Только телефон обязателен

### ⚠️ ДОПОЛНИТЕЛЬНЫЕ СЛОЖНОСТИ:

1. **Маски ввода:** Нужно сохранить работу vMaska
2. **Критичность данных:** Контакты нельзя потерять
3. **Форматирование:** Телефоны должны сохранять формат

---

## 📝 ЗАКЛЮЧЕНИЕ

План рефакторинга ContactsSection проще чем ParametersSection за счет меньшего количества полей и более простой логики. Основное внимание следует уделить сохранению работы масок ввода и обеспечению безопасности контактных данных.

**Рекомендация:** Выполнять после успешного рефакторинга ParametersSection для накопления опыта. Особое внимание уделить тестированию сохранения телефонных номеров.

**Статус плана:** ✅ Готов к выполнению после ParametersSection

---

*Документ создан: 21 января 2025*  
*Версия: 1.0*  
*Автор: AI Assistant*
