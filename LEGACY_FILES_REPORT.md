# 🚨 ОТЧЁТ: Оставшиеся Legacy файлы до рефакторинга

## 📊 Анализ текущего состояния

После миграции на FSD архитектуру остались следующие legacy файлы в директории `Components/`:

## 🗂️ Структура legacy файлов

```
resources/js/Components/
├── Booking/
│   └── Calendar.vue                  ⚠️ Используется в TimeSlotPicker.vue
├── Features/
│   └── MasterShow/
│       └── components/
│           ├── ReviewsList.vue       ❌ Не используется
│           └── ServicesList.vue      ❌ Не используется
│   └── PhotoUploader/
│       ├── VideoUploader.vue         ❌ Не используется
│       └── архив index.vue           ❌ Архив
├── Footer/
│   └── Footer.vue                    ⚠️ Используется в AppLayout.vue
├── Form/
│   └── Sections/
│       ├── EducationSection.vue      ✅ Заменён на EducationForm.vue
│       └── MediaSection.vue          ✅ Заменён на MediaForm.vue
├── Header/
│   ├── Navbar.vue                    ⚠️ Используется в AppLayout.vue
│   └── UserMenu.vue                  ❌ Не используется
└── UI/
    └── ConfirmModal.vue              ⚠️ Используется в Draft/Show.vue
```

## 🔗 Зависимости и использование

### ⚠️ Активно используются (требуют замены):

1. **Components/Booking/Calendar.vue**
   - Используется в: `src/entities/booking/ui/BookingWidget/TimeSlotPicker.vue`
   - Замена: ✅ Уже создан `src/features/booking/ui/BookingCalendar/`
   - Действие: Обновить импорт в TimeSlotPicker.vue

2. **Components/Header/Navbar.vue**
   - Используется в: `Layouts/AppLayout.vue`
   - Замена: ✅ Уже создан в FSD структуре
   - Действие: Обновить импорт в AppLayout.vue

3. **Components/Footer/Footer.vue**
   - Используется в: `Layouts/AppLayout.vue`
   - Замена: ✅ Уже создан `src/shared/ui/organisms/Footer/`
   - Действие: Обновить импорт в AppLayout.vue

4. **Components/UI/ConfirmModal.vue**
   - Используется в: `Pages/Draft/Show.vue`
   - Замена: ✅ Уже создан `src/shared/ui/molecules/Modal/ConfirmModal.vue`
   - Действие: Обновить импорт в Draft/Show.vue

### ✅ Уже заменены (можно удалить):

1. **Components/Form/Sections/EducationSection.vue**
   - Заменён на: `src/shared/ui/molecules/Forms/features/EducationForm.vue`
   - Статус: Безопасно удалить

2. **Components/Form/Sections/MediaSection.vue**
   - Заменён на: `src/shared/ui/molecules/Forms/features/MediaForm.vue`
   - Статус: Безопасно удалить

### ❌ Не используются (можно удалить):

1. **Components/Features/MasterShow/components/ReviewsList.vue**
2. **Components/Features/MasterShow/components/ServicesList.vue**
3. **Components/Features/PhotoUploader/VideoUploader.vue**
4. **Components/Features/PhotoUploader/архив index.vue**
5. **Components/Header/UserMenu.vue**

## 📋 План действий по очистке

### Шаг 1: Обновить импорты в использующих файлах

```bash
# 1. TimeSlotPicker.vue
# Заменить:
import Calendar from '@/Components/Booking/Calendar.vue'
# На:
import { BookingCalendar } from '@/src/features/booking'

# 2. AppLayout.vue
# Заменить:
import Navbar from '@/Components/Header/Navbar.vue'
import Footer from '@/Components/Footer/Footer.vue'
# На:
import { Header } from '@/src/shared/ui/organisms/Header'
import { Footer } from '@/src/shared/ui/organisms/Footer'

# 3. Draft/Show.vue
# Заменить:
import ConfirmModal from '@/Components/UI/ConfirmModal.vue'
# На:
import { ConfirmModal } from '@/src/shared/ui/molecules/Modal'
```

### Шаг 2: Проверить работоспособность после замены импортов

```bash
npm run dev
# Протестировать:
# - TimeSlotPicker с новым календарём
# - AppLayout с новыми Header и Footer
# - Draft/Show с новым ConfirmModal
```

### Шаг 3: Удалить неиспользуемые файлы

```bash
# Безопасные для удаления (не используются):
rm -rf Components/Features/MasterShow/
rm -rf Components/Features/PhotoUploader/
rm Components/Header/UserMenu.vue

# После обновления импортов можно удалить:
rm -rf Components/Form/Sections/
rm Components/Booking/Calendar.vue
rm Components/Header/Navbar.vue
rm Components/Footer/Footer.vue
rm Components/UI/ConfirmModal.vue
```

### Шаг 4: Финальная очистка пустых директорий

```bash
# После удаления всех файлов:
rm -rf Components/
```

## 📊 Статистика миграции

| Категория | До рефакторинга | После | Статус |
|-----------|-----------------|--------|---------|
| Компоненты в Components/ | 53 | 11 | 🔄 79% удалено |
| Активно используемые | 4 | 4 | ⚠️ Требуют замены импортов |
| Заменённые | 2 | 0 | ✅ Готовы к удалению |
| Неиспользуемые | 5 | 0 | ✅ Готовы к удалению |

## ⚡ Быстрые команды для миграции

```bash
# 1. Обновить все импорты автоматически
find . -name "*.vue" -type f -exec sed -i 's|@/Components/Booking/Calendar|@/src/features/booking|g' {} \;
find . -name "*.vue" -type f -exec sed -i 's|@/Components/Header/Navbar|@/src/shared/ui/organisms/Header|g' {} \;
find . -name "*.vue" -type f -exec sed -i 's|@/Components/Footer/Footer|@/src/shared/ui/organisms/Footer|g' {} \;
find . -name "*.vue" -type f -exec sed -i 's|@/Components/UI/ConfirmModal|@/src/shared/ui/molecules/Modal|g' {} \;

# 2. Удалить все legacy файлы одной командой (ПОСЛЕ проверки!)
rm -rf resources/js/Components/
```

## ✅ Проверочный чек-лист

Перед удалением Components/ убедитесь:
- [ ] TimeSlotPicker работает с новым BookingCalendar
- [ ] AppLayout корректно отображает Header и Footer
- [ ] Draft/Show правильно использует ConfirmModal
- [ ] Запущены тесты: `npm test`
- [ ] Проверена сборка: `npm run build`
- [ ] Нет ошибок в консоли браузера
- [ ] Создан backup: `git add . && git commit -m "backup before legacy cleanup"`

## 🎯 Результат

После выполнения всех шагов:
- **100% миграция на FSD архитектуру**
- **Удаление всей директории Components/**
- **Чистая кодовая база без legacy кода**
- **Улучшенная производительность сборки**

---

**Статус:** Готово к финальной очистке! 🚀