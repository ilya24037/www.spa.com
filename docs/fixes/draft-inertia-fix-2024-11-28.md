# Исправление сохранения черновика - Inertia Router
**Дата**: 28 ноября 2024
**Файл**: `resources/js/src/features/ad-creation/model/composables/useAdFormSubmission.ts`

## Проблема
После рефакторинга `adFormModel.ts` на модульную архитектуру перестало работать сохранение полей в черновике. При нажатии на "Сохранить черновик" появлялась ошибка:
- "Не удалось загрузить XHR: POST http://spa.test/draft/85"
- Поля не сохранялись в базу данных

## Причина
В процессе рефакторинга была изменена логика отправки данных:
- **Было**: Inertia router с условной логикой (POST для файлов, PUT для данных)
- **Стало**: axios с XMLHttpRequest заголовками
- Это привело к несовместимости с backend обработкой

## Решение
Восстановлена оригинальная логика из backup файлов:

### 1. Добавлена функция проверки файлов
```typescript
const hasFiles = (form: AdForm): boolean => {
  const hasPhotoFiles = form.photos?.some((p: any) => {
    if (p instanceof File) return true
    if (typeof p === 'object' && p?.file instanceof File) return true
    return false
  }) || false
  
  const hasVideoFiles = form.video?.some((v: any) => {
    if (v instanceof File) return true
    if (typeof v === 'object' && v?.file instanceof File) return true
    if (typeof v === 'string' && v.startsWith('data:video/')) return true
    return false
  }) || false
  
  return hasPhotoFiles || hasVideoFiles
}
```

### 2. Заменен axios на Inertia router
```typescript
// Если есть файлы - используем POST с FormData
if (filesPresent) {
  formData.append('_method', 'PUT')
  router.post(`/draft/${adId}`, formData, {
    preserveScroll: true,
    forceFormData: true,
    onSuccess: (response) => { ... }
  })
} else {
  // Если нет файлов - используем PUT с объектом
  const plainData = convertFormDataToPlainObject(formData)
  router.put(`/draft/${adId}`, plainData, {
    preserveScroll: true,
    onSuccess: (response) => { ... }
  })
}
```

### 3. Promise-обертка для async/await
```typescript
const saveDraft = async (form: AdForm): Promise<SubmissionResult> => {
  return new Promise((resolve) => {
    // Inertia router вызовы с onSuccess/onError колбэками
  })
}
```

## Результат
✅ Сохранение черновика работает корректно
✅ Поля сохраняются в базу данных
✅ Нет ошибок XHR в консоли
✅ Обратная совместимость сохранена

## Уроки
1. При рефакторинге важно сохранять рабочую логику
2. Inertia router и axios имеют разные подходы к отправке данных
3. Принцип KISS - использовать проверенные решения

## Тестирование
1. Откройте http://spa.test/ads/{id}/edit
2. Заполните поля формы
3. Нажмите "Сохранить черновик"
4. Проверьте консоль - должно быть "✅ saveDraft: Черновик успешно обновлен"
5. Обновите страницу - данные должны сохраниться