# Исправление дублирования черновиков и отсутствия перенаправления

## Проблема
При сохранении черновика в форме создания/редактирования объявления:
1. Создавалась копия черновика вместо обновления существующего
2. Не происходило перенаправление на страницу личного кабинета `/profile/items/draft/all`

## Причины
1. **Потеря ID формы**: После сохранения черновика `form.id` не обновлялся, поэтому последующие сохранения создавали новый черновик через POST вместо обновления через PUT
2. **Неполный ответ от сервера**: Backend возвращал только `draft_id`, а не полный объект с данными
3. **Некорректная обработка ID**: В `setFormData` не гарантировалось сохранение ID

## Решение

### 1. useAdFormState.ts (строки 104-112)
```typescript
const setFormData = (data: Partial<AdForm>) => {
  // КРИТИЧЕСКИ ВАЖНО: сохраняем ID для корректного обновления
  if (data.id !== undefined) {
    form.id = data.id
  }
  // Обновляем остальные поля
  Object.assign(form, data)
  isDirty.value = false
}
```

### 2. adFormModel.ts (строки 156-170)
```typescript
const result = await saveDraft(form)

if (result.success) {
  toast.success(result.message || 'Черновик сохранен')
  
  if (result.data) {
    // КРИТИЧЕСКИ ВАЖНО: обновляем ID формы для последующих обновлений
    if (result.data.id && !form.id) {
      form.id = result.data.id
    }
    // Обновляем все данные формы
    setFormData(result.data)
    // После сохранения черновика перенаправляем в личный кабинет
    navigateAfterSave(result.data.id, true)
  }
}
```

### 3. DraftController.php (строки 639-645, 319-325)
```php
// Для обычных AJAX запросов возвращаем JSON с полным объектом
return response()->json([
    'success' => true,
    'message' => 'Черновик обновлен успешно',
    'ad' => $draft->toArray(), // КРИТИЧЕСКИ ВАЖНО: возвращаем весь объект для обновления формы
    'draft_id' => $draft->id // Для совместимости
]);
```

### 4. formDataBuilder.ts (строки 12-16)
```typescript
// ID объявления (важно для обновления)
if (form.id) {
  formData.append('id', String(form.id))
  formData.append('_method', 'PUT')
}
```

## Результат
✅ При сохранении черновика `form.id` корректно сохраняется  
✅ Последующие сохранения используют PUT `/draft/{id}` вместо POST `/draft`  
✅ Дубликаты больше не создаются  
✅ После сохранения происходит редирект на `/profile/items/draft/all`  

## Тестирование
1. Открыть черновик для редактирования: `http://spa.test/ads/{id}/edit`
2. Внести изменения и нажать "Сохранить черновик"
3. Проверить:
   - Произошел редирект на `/profile/items/draft/all`
   - В БД не создался новый черновик (ID остался прежним)
   - При повторном открытии того же черновика изменения сохранены

## Файлы изменены
- `resources/js/src/features/ad-creation/model/composables/useAdFormState.ts`
- `resources/js/src/features/ad-creation/model/adFormModel.ts`
- `resources/js/src/features/ad-creation/model/composables/useAdFormSubmission.ts`
- `app/Application/Http/Controllers/Ad/DraftController.php`