# 📸 План реализации функционала "Проверочное фото"

## 📋 Анализ существующих компонентов

### Уже есть в проекте:
1. **Система загрузки фото** (`resources/js/src/features/media/photo-upload/`)
   - PhotoUpload.vue - основной компонент
   - PhotoUploadZone.vue - зона загрузки
   - usePhotoUpload.ts - composable для логики
   
2. **Верификация мастеров** (`app/Domain/Master/Actions/VerifyMasterAction.php`)
   - Базовая логика верификации профилей
   - Хранение метаданных верификации

3. **Обработка медиа** (`app/Infrastructure/Services/MediaStorageService.php`)
   - Загрузка и оптимизация изображений
   - Поддержка watermark
   - Валидация форматов

4. **Поля в БД** (таблица `ads`)
   - photos (JSON)
   - watermark_photos (boolean)
   - Нет специальных полей для верификации

## 🎯 План реализации

### Фаза 1: База данных (Backend)

#### 1.1. Создать миграцию для добавления полей верификации
```php
// database/migrations/2025_08_25_add_verification_fields_to_ads_table.php
- verification_photo (string/null) - путь к проверочному фото
- verification_video (string/null) - путь к проверочному видео  
- verification_status (enum: 'none', 'pending', 'verified', 'rejected')
- verification_type (enum: null, 'photo', 'video', 'both')
- verified_at (timestamp/null)
- verification_expires_at (timestamp/null) - истекает через 4 месяца
- verification_comment (text/null) - комментарий модератора
- verification_metadata (json/null) - дополнительные данные
```

#### 1.2. Обновить модель Ad
```php
// app/Domain/Ad/Models/Ad.php
- Добавить fillable поля
- Добавить casts для enum и json полей
- Добавить методы:
  - isVerified()
  - isVerificationExpired()
  - getVerificationBadge()
  - needsVerificationUpdate()
```

#### 1.3. Создать сервис VerificationService
```php
// app/Domain/Ad/Services/AdVerificationService.php
- uploadVerificationPhoto(Ad $ad, UploadedFile $file)
- uploadVerificationVideo(Ad $ad, UploadedFile $file)
- verifyAd(Ad $ad, string $status, ?string $comment)
- checkVerificationExpiry()
- generateVerificationInstructions()
```

#### 1.4. Создать API endpoints
```php
// routes/api.php
Route::prefix('ads/{ad}/verification')->group(function () {
    Route::post('/photo', 'uploadVerificationPhoto');
    Route::post('/video', 'uploadVerificationVideo');
    Route::get('/status', 'getVerificationStatus');
    Route::delete('/photo', 'deleteVerificationPhoto');
});

// Admin routes
Route::prefix('admin/verification')->group(function () {
    Route::get('/', 'getPendingVerifications');
    Route::post('/{ad}/review', 'reviewVerification');
});
```

### Фаза 2: Frontend компоненты

#### 2.1. Создать feature verification-upload
```
src/features/verification-upload/
├── ui/
│   ├── VerificationSection.vue         # Основная секция
│   ├── VerificationPhotoUpload.vue     # Загрузка фото с инструкциями
│   ├── VerificationVideoUpload.vue     # Загрузка видео  
│   ├── VerificationInstructions.vue    # Инструкции с примерами
│   ├── VerificationStatus.vue          # Статус и badge
│   └── components/
│       ├── InstructionCard.vue         # Карточка инструкции
│       └── VerificationBadge.vue       # Значок верификации
├── model/
│   ├── types.ts                        # Типы TypeScript
│   └── store.ts                        # Состояние верификации
├── api/
│   └── verificationApi.ts              # API методы
└── composables/
    └── useVerification.ts               # Логика верификации
```

#### 2.2. Компонент VerificationSection.vue
```vue
<template>
  <CollapsibleSection
    title="Подтверждение фотографий"
    subtitle="Рекомендуем для повышения доверия"
    :is-open="isOpen"
    :is-optional="true"
    :badge="verificationBadge"
  >
    <!-- Предупреждение -->
    <Alert type="info" class="mb-4">
      <AlertIcon />
      <AlertDescription>
        Проверочные фотографии предназначены только для внутреннего использования.
        Внимательно прочтите условия для получения значка фото проверено!
      </AlertDescription>
    </Alert>

    <!-- Табы для выбора способа -->
    <Tabs v-model="activeTab" class="mb-4">
      <TabsList>
        <TabsTrigger value="photo">
          📸 Фото с листком
        </TabsTrigger>
        <TabsTrigger value="video">
          🎥 Видео с датой
        </TabsTrigger>
      </TabsList>

      <!-- Фото верификация -->
      <TabsContent value="photo">
        <VerificationPhotoUpload
          v-model="verificationPhoto"
          :status="verificationStatus"
          @upload="handlePhotoUpload"
        />
      </TabsContent>

      <!-- Видео верификация -->
      <TabsContent value="video">
        <VerificationVideoUpload
          v-model="verificationVideo"
          :status="verificationStatus"
          @upload="handleVideoUpload"
        />
      </TabsContent>
    </Tabs>

    <!-- Статус верификации -->
    <VerificationStatus
      v-if="verificationStatus !== 'none'"
      :status="verificationStatus"
      :comment="verificationComment"
      :expires-at="verificationExpiresAt"
    />
  </CollapsibleSection>
</template>
```

#### 2.3. Компонент VerificationPhotoUpload.vue
```vue
<template>
  <div class="verification-photo-upload">
    <!-- Инструкции -->
    <div class="instructions-grid grid md:grid-cols-2 gap-4 mb-6">
      <InstructionCard number="1" title="Подготовка">
        <p>На листке бумаги напишите от руки:</p>
        <div class="example-text">
          <strong>{{ currentDate }}</strong><br>
          <strong>для FEIPITER</strong>
        </div>
        <small>Надписи должны быть написаны от руки</small>
      </InstructionCard>

      <InstructionCard number="2" title="Съёмка">
        <p>Сделайте фото с листком:</p>
        <ul>
          - Лицо и листок должны быть видны
          - Фото в полный рост приветствуется
          - Хорошее освещение
        </ul>
        <img src="/images/verification-example.jpg" alt="Пример" />
      </InstructionCard>
    </div>

    <!-- Зона загрузки -->
    <PhotoUploadZone
      ref="uploadZone"
      :max-size="10 * 1024 * 1024"
      :accepted-formats="['.jpg', '.jpeg', '.png']"
      :single-file="true"
      @files-selected="handleFilesSelected"
    >
      <template #title>
        Загрузить проверочное фото
      </template>
      <template #subtitle>
        JPG, PNG до 10MB
      </template>
    </PhotoUploadZone>

    <!-- Предпросмотр -->
    <div v-if="currentPhoto" class="preview mt-4">
      <img :src="currentPhoto.url" class="rounded-lg max-h-64" />
      <Button @click="removePhoto" variant="ghost" size="sm">
        Удалить
      </Button>
    </div>

    <!-- Важные замечания -->
    <Alert type="warning" class="mt-4">
      <AlertIcon />
      <AlertDescription>
        <ul class="space-y-1">
          <li>• Фотосессия должна быть актуальной</li>
          <li>• Проверочное фото действует 4 месяца</li>
          <li>• Типажные фотографии запрещены</li>
        </ul>
      </AlertDescription>
    </Alert>
  </div>
</template>
```

### Фаза 3: Интеграция в AdForm

#### 3.1. Добавить секцию в AdForm.vue
```vue
// После секции "Медиа" добавить:
<VerificationSection
  v-model:verification-photo="form.verification_photo"
  v-model:verification-video="form.verification_video"
  :verification-status="form.verification_status"
  :verification-comment="form.verification_comment"
  :verification-expires-at="form.verification_expires_at"
  @update="updateVerification"
/>
```

#### 3.2. Обновить adFormModel.ts
```typescript
interface AdFormData {
  // ... existing fields
  verification_photo?: string | null
  verification_video?: string | null
  verification_status?: VerificationStatus
  verification_type?: VerificationType | null
  verification_comment?: string | null
  verification_expires_at?: string | null
}
```

### Фаза 4: Админ-панель для модерации

#### 4.1. Создать страницу модерации
```vue
// resources/js/Pages/Admin/Verifications.vue
- Список ожидающих проверки
- Просмотр фото/видео
- Кнопки: Одобрить / Отклонить
- Поле для комментария
```

### Фаза 5: Отображение статуса верификации

#### 5.1. Badge в карточке объявления
```vue
// resources/js/src/entities/ad/ui/AdCard/AdCard.vue
<VerificationBadge 
  v-if="ad.verification_status === 'verified'"
  :expires-at="ad.verification_expires_at"
/>
```

#### 5.2. Индикатор в профиле
```vue
// В профиле показывать статус:
✅ Фото проверено (до 25.12.2025)
⏳ На проверке
❌ Отклонено: [причина]
⚠️ Требуется обновление (истекает)
```

## 📊 Приоритеты реализации

1. **Высокий приоритет:**
   - Миграция БД
   - API endpoints
   - Базовая загрузка фото

2. **Средний приоритет:**
   - UI компоненты
   - Интеграция в форму
   - Отображение badge

3. **Низкий приоритет:**
   - Видео верификация
   - Админ-панель
   - Автоматические уведомления

## ⏱️ Оценка времени

- **Фаза 1 (Backend):** 2-3 часа
- **Фаза 2 (Frontend):** 3-4 часа  
- **Фаза 3 (Интеграция):** 1-2 часа
- **Фаза 4 (Админка):** 2-3 часа
- **Фаза 5 (UI):** 1 час

**Итого:** 9-13 часов

## 🚀 Начало работы

1. Создать миграцию для БД
2. Обновить модель Ad
3. Создать базовый API
4. Реализовать загрузку фото
5. Интегрировать в форму
6. Добавить отображение статуса

## ✅ Критерии готовности

- [ ] Пользователь может загрузить проверочное фото
- [ ] Фото сохраняется в защищённой директории
- [ ] Администратор может одобрить/отклонить
- [ ] Показывается badge верификации
- [ ] Работает срок действия (4 месяца)
- [ ] Есть инструкции с примерами