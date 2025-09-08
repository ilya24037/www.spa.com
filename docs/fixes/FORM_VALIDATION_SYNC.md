# 🎯 Синхронизация валидации формы объявления

**Дата:** 02.09.2025  
**Тип задачи:** Синхронизация валидации  
**Время на выполнение:** ~1 час  
**Применяемые уроки:** BUSINESS_LOGIC_FIRST, DATA_FLOW_MAPPING, KISS

---

## 📋 Задача

Синхронизировать валидацию между frontend и backend для формы подачи объявления.
Установить единые обязательные поля для публикации.

---

## 🔍 Анализ текущей ситуации

### Проблема (рассинхронизация валидации):
- **Frontend проверяет:** 4 поля
- **Backend требует:** 12+ полей
- **Результат:** Пользователь получает ошибки только после отправки формы

### Применяемые уроки из docs/LESSONS:

1. **BUSINESS_LOGIC_FIRST:** Сначала понять бизнес-требования, потом менять код
2. **DATA_FLOW_MAPPING:** Проверить полную цепочку данных Frontend → Backend → DB
3. **KISS принцип:** Минимальные изменения для достижения цели
4. **NEW_TASK_WORKFLOW:** Найти существующие паттерны валидации

---

## ✅ План реализации с учетом опыта

### 🎯 Обязательные поля (12 полей):

1. **Имя** (`parameters.title`)
2. **Возраст** (`parameters.age`)
3. **Рост** (`parameters.height`)
4. **Вес** (`parameters.weight`)
5. **Размер груди** (`parameters.breast_size`)
6. **Цвет волос** (`parameters.hair_color`)
7. **Телефон** (`contacts.phone`)
8. **Услуги** (`services` - минимум одна)
9. **Кто оказывает услуги** (`service_provider`)
10. **Формат работы** (`work_format`)
11. **Ваши клиенты** (`clients`)
12. **Стоимость услуг** (`prices.apartments_1h` ИЛИ `prices.outcall_1h`)

---

## 📝 Пошаговый план

### Шаг 1: Frontend валидация (adFormModel.ts)

#### Применяем уроки:
- **DATA_FLOW_MAPPING:** Проверяем snake_case vs camelCase
- **Автоскролл к ошибкам:** UX улучшение из опыта

```typescript
const validateForm = (): boolean => {
  const newErrors: Record<string, string[]> = {}
  
  // 1. Параметры мастера (6 полей)
  if (!form.parameters.title) newErrors['parameters.title'] = ['Имя обязательно']
  if (!form.parameters.age) newErrors['parameters.age'] = ['Возраст обязателен']
  if (!form.parameters.height) newErrors['parameters.height'] = ['Рост обязателен']
  if (!form.parameters.weight) newErrors['parameters.weight'] = ['Вес обязателен']
  if (!form.parameters.breast_size) newErrors['parameters.breast_size'] = ['Размер груди обязателен']
  if (!form.parameters.hair_color) newErrors['parameters.hair_color'] = ['Цвет волос обязателен']
  
  // 2. Контакты
  if (!form.contacts.phone) newErrors['contacts.phone'] = ['Телефон обязателен']
  
  // 3. Услуги (минимум одна)
  let hasSelectedService = false
  if (form.services && typeof form.services === 'object') {
    Object.values(form.services).forEach(categoryServices => {
      if (categoryServices && typeof categoryServices === 'object') {
        Object.values(categoryServices).forEach((service: any) => {
          if (service?.enabled) hasSelectedService = true
        })
      }
    })
  }
  if (!hasSelectedService) newErrors['services'] = ['Выберите хотя бы одну услугу']
  
  // 4. Основная информация
  if (!form.service_provider?.length) newErrors['service_provider'] = ['Укажите, кто оказывает услуги']
  if (!form.work_format) newErrors['work_format'] = ['Выберите формат работы']
  if (!form.clients?.length) newErrors['clients'] = ['Укажите ваших клиентов']
  
  // 5. Цены (хотя бы одна)
  const hasApartmentPrice = form.prices?.apartments_1h && form.prices.apartments_1h > 0
  const hasOutcallPrice = form.prices?.outcall_1h && form.prices.outcall_1h > 0
  if (!hasApartmentPrice && !hasOutcallPrice) {
    newErrors['prices'] = ['Укажите стоимость за 1 час (апартаменты или выезд)']
  }
  
  errors.value = newErrors
  
  // АВТОСКРОЛЛ к первой ошибке (урок из опыта)
  if (Object.keys(newErrors).length > 0) {
    const firstErrorField = Object.keys(newErrors)[0]
    const errorSection = document.querySelector(`[data-section="${firstErrorField.split('.')[0]}"]`)
    if (errorSection) {
      errorSection.scrollIntoView({ behavior: 'smooth', block: 'center' })
    }
  }
  
  return Object.keys(newErrors).length === 0
}
```

---

### Шаг 2: Backend валидация (CreateAdRequest.php)

#### Применяем уроки:
- **BUSINESS_LOGIC_FIRST:** Убираем лишние требования
- **KISS:** Синхронизируем с frontend, не усложняем

```php
public function rules(): array
{
    return [
        // Параметры мастера - ОБЯЗАТЕЛЬНЫЕ
        'title' => 'required|string|max:255|min:2',
        'age' => 'required|integer|min:18|max:99',
        'height' => 'required|integer|min:140|max:220',
        'weight' => 'required|integer|min:40|max:200',
        'breast_size' => 'required|string|max:10',
        'hair_color' => 'required|string|max:50',
        
        // Контакты - ОБЯЗАТЕЛЬНЫЕ
        'phone' => 'required|string|regex:/^[+]?[0-9\s\-\(\)]{10,20}$/',
        
        // Услуги - минимум одна
        'services' => 'required|array|min:1',
        'services.*' => 'array',
        
        // Основная информация - ОБЯЗАТЕЛЬНЫЕ
        'service_provider' => 'required|array|min:1',
        'work_format' => 'required|string|in:individual,duo,group',
        'clients' => 'required|array|min:1',
        
        // Цены - хотя бы одна
        'prices' => 'required|array',
        'prices.apartments_1h' => 'nullable|numeric|min:0',
        'prices.outcall_1h' => 'nullable|numeric|min:0',
        
        // УБИРАЕМ обязательность (урок KISS)
        'category' => 'nullable|string',
        'description' => 'nullable|string',
        'experience' => 'nullable|string',
        'address' => 'nullable|string',
        'travel_area' => 'nullable|string',
        
        // Остальные поля остаются опциональными
        'specialty' => 'nullable|string|max:200',
        'eye_color' => 'nullable|string|max:50',
        'nationality' => 'nullable|string|max:100',
        'bikini_zone' => 'nullable|string|max:50',
    ];
}

// Дополнительная валидация для цен
protected function withValidator($validator)
{
    $validator->after(function ($validator) {
        $prices = $this->input('prices', []);
        $hasApartmentPrice = isset($prices['apartments_1h']) && $prices['apartments_1h'] > 0;
        $hasOutcallPrice = isset($prices['outcall_1h']) && $prices['outcall_1h'] > 0;
        
        if (!$hasApartmentPrice && !$hasOutcallPrice) {
            $validator->errors()->add('prices', 'Укажите стоимость за 1 час (апартаменты или выезд)');
        }
    });
}
```

---

### Шаг 3: UI обновления (AdForm.vue)

#### Применяем уроки:
- **Визуальные индикаторы:** Звездочки для обязательных полей
- **Прогресс валидации:** Показываем что заполнено

```javascript
// Обновление checkSectionFilled для новых требований
const checkSectionFilled = (sectionKey: string): boolean => {
  switch(sectionKey) {
    case 'parameters':
      // Все 6 обязательных полей параметров
      return !!(
        form.parameters.title &&
        form.parameters.age &&
        form.parameters.height &&
        form.parameters.weight &&
        form.parameters.breast_size &&
        form.parameters.hair_color
      )
    
    case 'services':
      // Минимум одна услуга
      return getTotalSelectedServices() > 0
    
    case 'price':
      // Хотя бы одна цена за час
      return !!(
        (form.prices?.apartments_1h && form.prices.apartments_1h > 0) ||
        (form.prices?.outcall_1h && form.prices.outcall_1h > 0)
      )
    
    case 'contacts':
      return !!form.contacts?.phone
    
    case 'basic':
      return !!(
        form.service_provider?.length &&
        form.work_format &&
        form.clients?.length
      )
    
    default:
      return checkSectionFilledOriginal(sectionKey)
  }
}
```

---

### Шаг 4: Параметры секция (ParametersSection.vue)

#### Добавление звездочек к 6 обязательным полям:

```vue
<!-- Обязательные поля со звездочками -->
<BaseInput
  v-model="localParameters.title"
  label="Имя"
  placeholder="Введите ваше имя"
  :required="true"
  :error="errors?.title?.[0]"
/>

<BaseInput
  v-model="localParameters.age"
  label="Возраст"
  type="number"
  :required="true"
  :error="errors?.age?.[0]"
/>

<BaseInput
  v-model="localParameters.height"
  label="Рост (см)"
  type="number"
  :required="true"
  :error="errors?.height?.[0]"
/>

<BaseInput
  v-model="localParameters.weight"
  label="Вес (кг)"
  type="number"
  :required="true"
  :error="errors?.weight?.[0]"
/>

<BaseSelect
  v-model="localParameters.breast_size"
  label="Размер груди"
  :options="breastSizeOptions"
  :required="true"
  :error="errors?.breast_size?.[0]"
/>

<BaseSelect
  v-model="localParameters.hair_color"
  label="Цвет волос"
  :options="hairColorOptions"
  :required="true"
  :error="errors?.hair_color?.[0]"
/>

<!-- Необязательные поля БЕЗ звездочек -->
<BaseSelect
  v-model="localParameters.eye_color"
  label="Цвет глаз"
  :options="eyeColorOptions"
  :required="false"
/>
```

---

## 🚨 Критические моменты (из опыта)

### 1. Data Flow проверка (урок DATA_FLOW_MAPPING):
- ✅ Frontend отправляет данные в правильном формате
- ✅ Backend принимает и валидирует корректно
- ✅ БД сохраняет все поля ($fillable проверить!)
- ✅ При загрузке данные восстанавливаются (snake_case vs camelCase)

### 2. Тестирование цепочки:
```bash
# 1. Проверка отправки
console.log('Отправляемые данные:', submitData)

# 2. Проверка получения
Log::info('Полученные данные:', $request->validated())

# 3. Проверка сохранения
Log::info('Сохранено в БД:', $ad->fresh()->toArray())
```

### 3. Обработка ошибок:
- Frontend показывает ошибки до отправки
- Автоскролл к первому незаполненному полю
- Понятные сообщения на русском языке

---

## 📊 Ожидаемый результат

1. **Синхронизация:** Frontend и backend проверяют одинаковые поля
2. **UX улучшение:** Пользователь видит ошибки сразу, не после отправки
3. **Производительность:** Меньше лишних запросов к серверу
4. **Поддержка:** Легче добавлять новые поля в будущем

---

## ⏱️ Время выполнения

- Frontend валидация: 20 мин
- Backend валидация: 15 мин
- UI обновления: 15 мин
- Тестирование: 10 мин
- **Итого:** ~1 час

---

## 📝 Чек-лист проверки

- [ ] Frontend validateForm() проверяет все 12 полей
- [ ] Backend CreateAdRequest проверяет те же 12 полей
- [ ] UI показывает звездочки у обязательных полей
- [ ] Автоскролл работает при ошибке валидации
- [ ] Сообщения об ошибках на русском языке
- [ ] Тесты пройдены (создание и редактирование)
- [ ] Data Flow проверен (Frontend → Backend → DB → Frontend)

---

## 🔗 Связанные файлы

1. `resources/js/src/features/ad-creation/model/adFormModel.ts`
2. `app/Application/Http/Requests/Ad/CreateAdRequest.php`
3. `resources/js/src/features/ad-creation/ui/AdForm.vue`
4. `resources/js/src/features/AdSections/ParametersSection/ui/ParametersSection.vue`

---

## 📚 Использованные уроки

- `docs/LESSONS/APPROACHES/BUSINESS_LOGIC_FIRST.md`
- `docs/LESSONS/DATA_FLOW_MAPPING.md`
- `docs/LESSONS/WORKFLOWS/NEW_TASK_WORKFLOW.md`
- `docs/LESSONS/QUICK_REFERENCE.md`