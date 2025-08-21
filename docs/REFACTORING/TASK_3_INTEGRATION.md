# 🔗 ЗАДАЧА 3: ИНТЕГРАЦИЯ И ФИНАЛИЗАЦИЯ

## 👤 Исполнитель: Координатор (после выполнения задач 1 и 2)

## 📊 ЗАВИСИМОСТИ
- ✅ TASK_1_PHOTO_UPLOAD_REFACTORING.md должна быть завершена
- ✅ TASK_2_VIDEO_UPLOAD_REFACTORING.md должна быть завершена

## 🎯 ЦЕЛЬ
Интегрировать новые компоненты в AdForm.vue и провести полное тестирование.

## 📝 ПОШАГОВАЯ ИНСТРУКЦИЯ

### ШАГ 1: Создать общий index.ts для media
```typescript
// resources/js/src/features/media/index.ts
export * from './photo-upload'
export * from './video-upload'
```

### ШАГ 2: Обновить импорты в AdForm.vue

#### Найти старые импорты:
```typescript
// СТАРЫЕ (строки 231-232):
import PhotoUpload from '@/src/features/media/PhotoUpload.vue'
import VideoUpload from '@/src/features/media/VideoUpload.vue'
```

#### Заменить на новые:
```typescript
// НОВЫЕ:
import { PhotoUpload } from '@/src/features/media/photo-upload'
import { VideoUpload } from '@/src/features/media/video-upload'
```

### ШАГ 3: Проверить использование в template

#### Текущее использование PhotoUpload (строки 114-120):
```vue
<PhotoUpload 
  v-model:photos="form.photos" 
  v-model:show-additional-info="form.media_settings.show_additional_info"
  v-model:show-services="form.media_settings.show_services"
  v-model:show-prices="form.media_settings.show_prices"
  :errors="errors"
/>
```
✅ Это правильно - новый компонент поддерживает эти props!

#### Текущее использование VideoUpload (строки 131-133):
```vue
<VideoUpload 
  v-model:videos="form.video" 
  :errors="errors"
/>
```
✅ Это правильно - новый компонент поддерживает эти props!

### ШАГ 4: Удалить старые монолитные файлы
```bash
# Убедиться что новые компоненты работают, затем:
rm resources/js/src/features/media/PhotoUpload.vue
rm resources/js/src/features/media/VideoUpload.vue
rm resources/js/src/features/media/index.ts  # старый index
```

### ШАГ 5: Очистить архив (опционально)
```bash
# После успешного тестирования можно удалить архив:
rm -rf resources/js/src/features/_archive/MediaUpload
rm -rf resources/js/src/features/_archive/MediaSection
rm -rf resources/js/src/features/_archive/PhotosSection
rm -rf resources/js/src/features/_archive/VideosSection
```

## 🧪 КОМПЛЕКСНОЕ ТЕСТИРОВАНИЕ

### 1. Тестирование PhotoUpload:
- [ ] Загрузка фото через диалог
- [ ] Drag & drop фото
- [ ] Поворот фото работает
- [ ] Удаление фото работает
- [ ] Перетаскивание для изменения порядка
- [ ] Метка "Основное" на первом фото
- [ ] **КРИТИЧНО:** Все 3 чекбокса работают:
  - [ ] "Показывать дополнительную информацию"
  - [ ] "Показывать услуги"
  - [ ] "Показывать цены"

### 2. Тестирование VideoUpload:
- [ ] Загрузка видео через диалог
- [ ] Drag & drop видео
- [ ] Отображение информации о видео
- [ ] Прогресс загрузки
- [ ] Удаление видео
- [ ] Ограничения по размеру
- [ ] Предупреждения о формате

### 3. Интеграционное тестирование:
- [ ] Создание нового объявления
- [ ] Сохранение черновика
- [ ] Загрузка существующего черновика
- [ ] Редактирование активного объявления
- [ ] Проверка сохранения в БД:
  ```sql
  SELECT photos, video, media_settings FROM ads WHERE id = ?;
  ```

### 4. Проверка сборки:
```bash
npm run build
# Не должно быть ошибок TypeScript
# Не должно быть ошибок импортов
```

## 📊 МЕТРИКИ УСПЕХА

| Компонент | Было (строк) | Стало (строк) | Цель |
|-----------|--------------|---------------|------|
| PhotoUpload.vue | 680 | 150 | ✅ |
| VideoUpload.vue | 590 | 150 | ✅ |
| MediaSettings.vue | 0 | 80 | ✅ |
| Всего компонентов | 2 | 11 | ✅ |
| Покрытие типами | 20% | 100% | ✅ |

## ✅ ФИНАЛЬНЫЙ ЧЕКЛИСТ

### Структура:
- [ ] photo-upload/ соответствует FSD
- [ ] video-upload/ соответствует FSD
- [ ] Типы изолированы в model/types.ts
- [ ] Composables переиспользованы из архива

### Функциональность:
- [ ] Все функции PhotoUpload работают
- [ ] Все функции VideoUpload работают
- [ ] Чекбоксы настроек сохраняются
- [ ] Интеграция с AdForm.vue работает

### Качество кода:
- [ ] Компоненты < 150 строк
- [ ] 100% TypeScript типизация
- [ ] Нет console.log в production
- [ ] Нет дублирования кода

### Документация:
- [ ] README обновлен (если есть)
- [ ] Комментарии в сложных местах
- [ ] JSDoc для публичных интерфейсов

## 🚀 ДЕПЛОЙ

После успешного тестирования:

1. Commit изменений:
```bash
git add .
git commit -m "refactor(media): migrate photo and video upload to FSD architecture

- Split monolithic components into modular structure
- Add MediaSettings component with checkboxes
- Reuse composables from archive
- Full TypeScript coverage
- Components now under 150 lines each"
```

2. Push в ветку:
```bash
git push origin feature/media-fsd-refactoring
```

3. Создать Pull Request с описанием:
```markdown
## Описание
Рефакторинг media компонентов согласно FSD архитектуре.

## Изменения
- PhotoUpload разделен на 5 компонентов
- VideoUpload разделен на 5 компонентов  
- Добавлены чекбоксы настроек отображения
- Полная типизация TypeScript
- Переиспользование готовых composables

## Тестирование
- [x] Загрузка фото/видео
- [x] Drag & drop
- [x] Чекбоксы настроек
- [x] Сохранение в БД
```

## 📞 ОТЧЕТ О ВЫПОЛНЕНИИ

После завершения всех задач создать отчет:

```markdown
# ОТЧЕТ О РЕФАКТОРИНГЕ MEDIA КОМПОНЕНТОВ

## Выполнено:
1. ✅ PhotoUpload мигрирован на FSD (680 → 150 строк)
2. ✅ VideoUpload мигрирован на FSD (590 → 150 строк)
3. ✅ Добавлены чекбоксы настроек отображения
4. ✅ Интеграция с AdForm.vue
5. ✅ Полное тестирование

## Результаты:
- Соответствие FSD: 100%
- Покрытие типами: 100%
- Размер компонентов: < 150 строк
- Переиспользование кода: 85%

## Проблемы и решения:
[Описать если были]

## Время выполнения:
- Задача 1 (Photo): X часов
- Задача 2 (Video): X часов
- Интеграция: X часов
- Всего: X часов
```

---
**Статус:** Ожидает выполнения задач 1 и 2  
**Приоритет:** Высокий  
**Время:** ~2 часа