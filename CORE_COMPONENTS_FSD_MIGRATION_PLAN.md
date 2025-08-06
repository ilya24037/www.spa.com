# 🚀 ПЛАН МИГРАЦИИ ОСНОВНЫХ КОМПОНЕНТОВ НА FSD

## 📋 АНАЛИЗ ТЕКУЩИХ КОМПОНЕНТОВ

### 📊 Структура компонентов для миграции:
```
Components/
├── Footer/
│   └── Footer.vue                      # → shared/ui/organisms/Footer
├── UI/
│   └── ConfirmModal.vue               # → shared/ui/molecules/Modal
├── Booking/
│   └── Calendar.vue                   # → features/booking/ui/Calendar
├── Form/
│   └── Sections/                      # → shared/ui/molecules/Forms
│       ├── EducationSection.vue
│       └── MediaSection.vue
└── Features/                          # → Реструктуризация
    ├── MasterShow/components/
    │   ├── ReviewsList.vue            # → entities/review/ui
    │   └── ServicesList.vue           # → entities/service/ui
    └── PhotoUploader/
        ├── VideoUploader.vue          # → shared/ui/molecules/Upload
        └── архив index.vue            # → Удалить
```

---

## 🎯 ЭТАП 1: FOOTER МИГРАЦИЯ (Приоритет 1)

### ✅ Текущий анализ:
- **Файл:** `Components/Footer/Footer.vue` (187 строк)
- **Функциональность:** Полнофункциональный футер с разделами
- **Зависимости:** Link (Inertia), простые ссылки
- **Состояние:** Готов к миграции

### 🏗️ План миграции Footer:
```
shared/ui/organisms/Footer/
├── Footer.vue                         # Основной компонент
├── components/
│   ├── FooterSection.vue              # Секция футера
│   ├── FooterLinks.vue                # Группа ссылок
│   ├── SocialIcons.vue                # Социальные сети
│   └── AppDownload.vue                # QR код + магазины
├── model/
│   └── footer.config.ts               # Конфигурация ссылок
└── index.ts
```

### 🔧 Улучшения:
- **TypeScript** типизация
- **Конфигурируемые ссылки** через config
- **Accessibility** улучшения
- **Responsive** оптимизация
- **SEO** structured data

---

## 🎯 ЭТАП 2: UI COMPONENTS МИГРАЦИЯ (Приоритет 2)

### ✅ ConfirmModal анализ:
- **Файл:** `Components/UI/ConfirmModal.vue` (43 строки)
- **Функциональность:** Модалка подтверждения
- **Состояние:** Уже TypeScript, нужна доработка

### 🏗️ План миграции ConfirmModal:
```
shared/ui/molecules/Modal/
├── ConfirmModal.vue                   # Обновленный компонент
├── BaseModal.vue                      # Базовая модалка
├── AlertModal.vue                     # Модалка уведомлений
├── composables/
│   ├── useModal.ts                    # Логика модалок
│   └── useConfirm.ts                  # Confirm hook
└── index.ts
```

### 🔧 Улучшения:
- **Focus management** (trap focus)
- **Escape** и **backdrop** закрытие
- **Animations** (enter/leave)
- **Portal/Teleport** поддержка
- **Theme** варианты (danger, warning, info)

---

## 🎯 ЭТАП 3: BOOKING МИГРАЦИЯ (Приоритет 3)

### ✅ Calendar анализ:
- **Файл:** `Components/Booking/Calendar.vue` (553 строки)
- **Функциональность:** Продвинутый календарь бронирования
- **Состояние:** Сложный компонент, требует careful migration

### 🏗️ План миграции Booking:
```
features/booking/
├── ui/
│   ├── BookingCalendar/
│   │   ├── BookingCalendar.vue        # Основной календарь
│   │   ├── components/
│   │   │   ├── CalendarHeader.vue     # Навигация
│   │   │   ├── CalendarGrid.vue       # Сетка дней
│   │   │   ├── CalendarDay.vue        # День
│   │   │   ├── DateList.vue           # Мобильный список
│   │   │   └── Legend.vue             # Легенда
│   │   └── composables/
│   │       ├── useCalendar.ts         # Календарная логика
│   │       ├── useDateSelection.ts    # Выбор дат
│   │       └── useBookingStatus.ts    # Статусы бронирования
├── model/
│   ├── booking.store.ts               # Store (уже есть в entities)
│   └── calendar.types.ts              # TypeScript типы
└── index.ts
```

### 🔧 Улучшения:
- **Разбивка** на мелкие компоненты
- **TypeScript** строгая типизация
- **Accessibility** календаря
- **Touch gestures** для мобильных
- **Keyboard navigation**

---

## 🎯 ЭТАП 4: FORMS МИГРАЦИЯ (Приоритет 4)

### ✅ Form Sections анализ:
- **Файлы:** `EducationSection.vue`, `MediaSection.vue`
- **Функциональность:** Секции форм
- **Состояние:** Требует анализ содержимого

### 🏗️ План миграции Forms:
```
shared/ui/molecules/Forms/
├── FormSection.vue                    # Базовая секция
├── EducationForm.vue                  # Секция образования
├── MediaUploadForm.vue               # Секция медиа
├── FormField.vue                     # Поле формы
├── FormGroup.vue                     # Группа полей
├── composables/
│   ├── useFormSection.ts             # Логика секций
│   ├── useFormValidation.ts          # Валидация
│   └── useFormState.ts               # Состояние
└── index.ts
```

---

## 🎯 ЭТАП 5: FEATURES РЕСТРУКТУРИЗАЦИЯ (Приоритет 5)

### ✅ Features анализ:
- **MasterShow/components/** → `entities/`
- **PhotoUploader/** → `shared/ui/molecules/Upload/`

### 🏗️ План реструктуризации:
```
# MasterShow компоненты → entities
entities/review/ui/ReviewsList/        # Из MasterShow/components/
entities/service/ui/ServicesList/     # Из MasterShow/components/

# PhotoUploader → shared
shared/ui/molecules/Upload/
├── PhotoUploader.vue
├── VideoUploader.vue                 # Из Features/PhotoUploader/
├── FileDropzone.vue
├── UploadProgress.vue
└── composables/useUpload.ts
```

---

## 📅 ВРЕМЕННАЯ ШКАЛА МИГРАЦИИ

### 🗓️ Неделя 1: Footer + UI
- **Дни 1-3:** Footer миграция
- **Дни 4-5:** ConfirmModal + BaseModal 
- **Тестирование и интеграция**

### 🗓️ Неделя 2: Booking Calendar  
- **Дни 1-3:** Calendar разбивка на компоненты
- **Дни 4-5:** Composables и типизация
- **Тестирование функциональности**

### 🗓️ Неделя 3: Forms + Features
- **Дни 1-2:** Form sections миграция
- **Дни 3-4:** Features реструктуризация
- **День 5:** Cleanup и оптимизация

---

## 🔧 ТЕХНИЧЕСКИЕ ПРИНЦИПЫ

### ✅ Обязательные требования:
1. **100% TypeScript** типизация
2. **Полная Accessibility** поддержка
3. **Mobile-first** responsive
4. **Composition API** только
5. **Pinia stores** где необходимо
6. **Error boundaries** обработка
7. **Loading states** для всех операций
8. **Unit tests** для ключевой функциональности

### ✅ Архитектурные принципы:
1. **Single Responsibility** - один компонент = одна ответственность
2. **Reusability** - максимальная переиспользуемость
3. **Configurability** - настраиваемость через props
4. **Extensibility** - возможность расширения
5. **Performance** - оптимизированный код

---

## 📊 ОЖИДАЕМЫЕ РЕЗУЛЬТАТЫ

### 🎯 По завершению миграции:
- **Footer:** Modern, configurable, SEO-optimized
- **UI Components:** Complete modal system
- **Booking:** Advanced calendar with full features
- **Forms:** Reusable form components
- **Features:** Properly structured entities

### 📈 Метрики успеха:
- **100%** компонентов в FSD структуре
- **0** legacy компонентов в Components/
- **+50%** reusability кода
- **+30%** performance улучшения  
- **100%** TypeScript coverage
- **100%** accessibility compliance

---

## 🚀 ГОТОВНОСТЬ К СТАРТУ

**Все компоненты проанализированы и готовы к FSD миграции!**

**Начинаем с Footer как наиболее простого и важного компонента.**

---

*План создан: 06.08.2025*