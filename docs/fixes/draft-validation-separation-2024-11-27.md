# 📋 ПЛАН: Разделение валидации для черновиков и публикации

**Дата:** 27.11.2024  
**Проблема:** При сохранении черновика требуются обязательные поля (цена, телефон, город)  
**Решение:** Разделить валидацию - для черновиков БЕЗ проверок, для публикации ПОЛНАЯ проверка

## 📊 Текущая проблема

При попытке сохранить черновик система требует заполнить:
- ❌ География (город, адрес) 
- ❌ Контакты (телефон)
- ❌ Цена

Это неправильно - черновик должен сохраняться в любом состоянии.

## 🎯 Требования

1. **"Сохранить черновик"** - сохранять БЕЗ валидации
2. **"Разместить объявление"** - проверять ВСЕ обязательные поля

## 📝 План реализации

### Шаг 1: Создать функцию полной валидации

**Файл:** `resources/js/src/features/ad-creation/model/adFormModel.ts`

```typescript
// Полная валидация для публикации
const validateForPublish = (): boolean => {
  const newErrors: any = {}
  
  // Обязательные поля для публикации
  if (!form.parameters.title?.trim()) {
    newErrors['parameters.title'] = 'Укажите имя'
  }
  
  if (!form.price || form.price <= 0) {
    newErrors.price = 'Укажите цену'
  }
  
  if (!form.contacts.phone?.trim()) {
    newErrors['contacts.phone'] = 'Укажите телефон'
  }
  
  if (!form.geo.city) {
    newErrors['geo.city'] = 'Выберите город'
  }
  
  if (!form.geo.address?.trim()) {
    newErrors['geo.address'] = 'Укажите адрес'
  }
  
  // Проверка медиа
  if (!form.photos?.length) {
    newErrors.photos = 'Добавьте хотя бы одно фото'
  }
  
  errors.value = newErrors
  
  // Если есть ошибки - показываем уведомление
  if (Object.keys(newErrors).length > 0) {
    const errorCount = Object.keys(newErrors).length
    console.error(`❌ Найдено ${errorCount} ошибок валидации:`, newErrors)
    
    // TODO: Показать toast уведомление
    // showNotification(`Заполните ${errorCount} обязательных полей`, 'error')
    
    // TODO: Прокрутить к первой ошибке
    // scrollToFirstError()
  }
  
  return Object.keys(newErrors).length === 0
}
```

### Шаг 2: Обновить handleSaveDraft - убрать валидацию

```typescript
const handleSaveDraft = async () => {
  // Защита от двойного клика
  if (saving.value) {
    console.log('⚠️ handleSaveDraft: Уже идет сохранение')
    return
  }
  
  try {
    saving.value = true
    
    // БЕЗ ВАЛИДАЦИИ! Сохраняем черновик как есть
    console.log('💾 Сохранение черновика БЕЗ валидации')
    
    // Создаем FormData для отправки
    const formData = new FormData()
    
    // ... остальная логика сохранения без изменений
  } catch (error) {
    saving.value = false
    console.error('Ошибка сохранения черновика:', error)
  }
}
```

### Шаг 3: Обновить handlePublish - использовать полную валидацию

```typescript
const handlePublish = async () => {
  // Защита от двойного клика
  if (saving.value) {
    console.log('⚠️ handlePublish: Уже идет сохранение')
    return
  }
  
  if (!authStore.isAuthenticated) {
    router.visit('/login')
    return
  }
  
  // ПОЛНАЯ ВАЛИДАЦИЯ для публикации
  if (!validateForPublish()) {
    console.log('❌ Валидация не прошла для публикации')
    return
  }
  
  console.log('✅ Валидация прошла, публикуем объявление')
  saving.value = true
  
  // ... остальная логика публикации
}
```

### Шаг 4: Обновить handleSubmit для активных объявлений

```typescript
const handleSubmit = async () => {
  // Защита от двойного клика
  if (saving.value) {
    console.log('⚠️ handleSubmit: Уже идет сохранение')
    return
  }
  
  // Для активных объявлений используем полную валидацию
  if (!validateForPublish()) {
    console.log('❌ Валидация не прошла')
    return
  }
  
  console.log('✅ Валидация прошла успешно')
  saving.value = true
  
  // ... остальная логика сохранения
}
```

### Шаг 5: Добавить визуальную индикацию ошибок (опционально)

**Файл:** `resources/js/src/features/ad-creation/ui/AdForm.vue`

```typescript
// Метод для прокрутки к первой ошибке
const scrollToFirstError = () => {
  const firstError = document.querySelector('.has-error')
  if (firstError) {
    firstError.scrollIntoView({ 
      behavior: 'smooth', 
      block: 'center' 
    })
  }
}

// Добавить классы ошибок к секциям
const sectionHasError = (section: string): boolean => {
  return Object.keys(errors).some(key => key.startsWith(section))
}
```

## 📂 Файлы для изменения

1. **`resources/js/src/features/ad-creation/model/adFormModel.ts`**
   - Добавить `validateForPublish()`
   - Обновить `handleSaveDraft()` - убрать валидацию
   - Обновить `handlePublish()` - использовать `validateForPublish()`
   - Обновить `handleSubmit()` - использовать `validateForPublish()`

2. **`resources/js/src/features/ad-creation/ui/AdForm.vue`** (опционально)
   - Добавить визуальную индикацию ошибок
   - Добавить метод прокрутки к ошибкам

## ✅ Ожидаемый результат

1. **"Сохранить черновик"** - сохраняет ВСЕГДА, даже полностью пустую форму
2. **"Разместить объявление"** - проверяет обязательные поля:
   - ✅ Имя (title)
   - ✅ Цена (price)
   - ✅ Телефон (contacts.phone)
   - ✅ Город (geo.city)
   - ✅ Адрес (geo.address)
   - ✅ Хотя бы одно фото (photos)

3. При ошибках валидации:
   - Показывает в консоли какие поля не заполнены
   - Не дает опубликовать объявление
   - (Опционально) Прокручивает к первому незаполненному полю

## 🧪 Тестирование

1. Открыть форму создания объявления
2. Нажать "Сохранить черновик" с пустой формой - должно сохранить
3. Нажать "Разместить объявление" с пустой формой - должно показать ошибки
4. Заполнить обязательные поля
5. Нажать "Разместить объявление" - должно опубликовать

## 📝 Примечания

- Валидация для черновиков полностью отключена
- Можно сохранить даже полностью пустой черновик
- При публикации проверяются ВСЕ обязательные поля
- Список обязательных полей можно расширить в функции `validateForPublish()`