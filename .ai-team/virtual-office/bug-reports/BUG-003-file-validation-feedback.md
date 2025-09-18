# 🐛 BUG-003: Отсутствие пользовательского feedback при ошибках валидации файлов

## 📋 Метаданные
- **ID:** BUG-003
- **Приоритет:** 🟡 СРЕДНИЙ
- **Компонент:** PhotoUploadZone.vue
- **Категория:** UX / Validation
- **Статус:** Открыт
- **Дата обнаружения:** 2025-09-17

## 📝 Описание проблемы
При загрузке файлов неподдерживаемого формата или превышающих лимит размера, пользователь не получает уведомления. Ошибки только логируются в консоль.

## 📍 Локализация
```typescript
// resources/js/src/features/media/photo-upload/ui/components/PhotoUploadZone.vue (строки 92-108)
const validateFiles = (files: File[]): File[] => {
  return files.filter(file => {
    // ... проверки ...
    if (!isValidFormat || !isValidSize) {
      console.warn(`❌ Файл отклонен: ${file.name}`)  // ❌ Только консоль
    }
  })
}
```

## 🔄 Шаги воспроизведения
1. Открыть форму с загрузкой фото
2. Попытаться загрузить файл .exe или файл > 10MB
3. Файл не загружается, но пользователь не видит причину

## ❗ Ожидаемое поведение
Пользователь должен видеть понятное сообщение об ошибке.

## ⚡ Фактическое поведение
Файл молча отклоняется без уведомления.

## 🎯 Влияние на систему
- **UX:** Плохой пользовательский опыт
- **Support:** Увеличение обращений в поддержку
- **Conversion:** Пользователи могут бросить форму

## ✅ Предлагаемое решение
```typescript
// Добавить emit для ошибок
const emit = defineEmits<{
  'update:modelValue': [value: MediaItem[]]
  'validation-error': [error: ValidationError]  // ✅ Новый emit
}>()

interface ValidationError {
  fileName: string
  reason: 'format' | 'size' | 'count'
  message: string
}

const validateFiles = (files: File[]): File[] => {
  const validFiles: File[] = []
  const errors: ValidationError[] = []

  files.forEach(file => {
    const isValidFormat = props.acceptedFormats.some(format =>
      file.type.startsWith(format.split('/*')[0])
    )
    const isValidSize = file.size <= props.maxFileSize

    if (!isValidFormat) {
      errors.push({
        fileName: file.name,
        reason: 'format',
        message: `Файл ${file.name} имеет неподдерживаемый формат`
      })
    } else if (!isValidSize) {
      errors.push({
        fileName: file.name,
        reason: 'size',
        message: `Файл ${file.name} превышает максимальный размер ${props.maxFileSize / 1024 / 1024}MB`
      })
    } else {
      validFiles.push(file)
    }
  })

  // Отправляем ошибки родительскому компоненту
  if (errors.length > 0) {
    errors.forEach(error => {
      emit('validation-error', error)
    })
  }

  return validFiles
}
```

## 🧪 Тест-кейсы для проверки исправления
1. **Неверный формат:** Загрузка .exe файла → показать "Неподдерживаемый формат"
2. **Большой размер:** Файл > 10MB → показать "Превышен размер"
3. **Множественные ошибки:** 3 невалидных файла → показать 3 сообщения
4. **Успешная загрузка:** Валидный файл → без ошибок

## 📊 Метрики для мониторинга
- Процент успешных загрузок файлов
- Количество попыток загрузки невалидных файлов
- Время на успешную загрузку (от первой попытки)