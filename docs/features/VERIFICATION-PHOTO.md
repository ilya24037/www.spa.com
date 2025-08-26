# Проверочное фото - Документация

## Описание функции

Функция "Проверочное фото" позволяет мастерам подтвердить подлинность своих фотографий путем загрузки специального фото с листком, на котором написана текущая дата и "для SPA".

## Реализация

### Frontend компоненты

#### VerificationPhotoSection.vue
- **Путь:** `resources/js/src/features/verification-upload/ui/VerificationPhotoSection.vue`
- **Описание:** Основной компонент для загрузки проверочного фото
- **Особенности:**
  - UI стилизован под существующий PhotoUploadZone
  - Поддержка drag&drop
  - Сохранение в base64 для избежания проблем с авторизацией
  - Показ статуса верификации (pending/verified)

### Backend

#### Миграции базы данных
1. **2025_08_25_145811_add_verification_fields_to_ads_table.php** - добавляет поля верификации
2. **2025_08_26_fix_verification_photo_field_type.php** - изменяет тип поля на MEDIUMTEXT для хранения base64

#### Поля в таблице ads
- `verification_photo` (MEDIUMTEXT) - base64 изображение
- `verification_status` (ENUM) - статус: none, pending, verified, rejected
- `verification_video` (MEDIUMTEXT) - для видео верификации (опционально)
- `verification_comment` (TEXT) - комментарий модератора
- `verification_expires_at` (TIMESTAMP) - срок действия верификации

#### Модель Ad
- **Путь:** `app/Domain/Ad/Models/Ad.php`
- Добавлены поля в `$fillable`
- Метод `isVerified()` для проверки статуса

#### DraftController
- **Путь:** `app/Application/Http/Controllers/Ad/DraftController.php`
- Обработка полей верификации в методах `store()` и `update()`

### Интеграция в AdForm

Проверочное фото интегрировано как раскрывающаяся подсекция в разделе МЕДИА:
```vue
<!-- В AdForm.vue строка 249-281 -->
<div class="media-category mb-6">
  <div class="collapsible-header" @click="toggleVerificationSection">
    <h3>Проверочное фото <span>(повышает доверие)</span></h3>
  </div>
  <div v-show="isVerificationExpanded">
    <VerificationPhotoSection 
      v-model:photo="form.verification_photo" 
      :status="form.verification_status"
    />
  </div>
</div>
```

## Технические детали

### Хранение изображений
- Используется base64 кодирование
- Максимальный размер: 16MB (ограничение MEDIUMTEXT)
- Поддерживаемые форматы: JPG, PNG

### Процесс верификации
1. Пользователь загружает фото с листком
2. Фото сохраняется в base64 в черновике
3. Статус устанавливается в "pending"
4. Модератор проверяет и меняет статус на "verified" или "rejected"

## Тестирование

Для тестирования функции:
1. Перейдите на http://spa.test/ad/create
2. В разделе МЕДИА раскройте "Проверочное фото"
3. Загрузите тестовое изображение
4. Сохраните черновик

## Известные ограничения

1. API загрузка отключена из-за проблем с авторизацией (401)
2. Используется только base64 хранение
3. Модерация пока ручная (нет админ-панели)

## Будущие улучшения

- [ ] Админ-панель для модерации
- [ ] Автоматическое распознавание даты на фото
- [ ] Отображение бейджа верификации в карточках
- [ ] Напоминания о истечении срока верификации