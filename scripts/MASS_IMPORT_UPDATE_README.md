# 🔧 Mass Import Update Scripts

Автоматические скрипты для массового обновления импортов при переходе на Feature-Sliced Design архитектуру.

## 📋 Доступные Скрипты

### 1. `mass-import-update.ps1` - Основной скрипт замены
Автоматически заменяет старые импорты на новые согласно FSD архитектуре.

### 2. `check-import-status.ps1` - Проверка статуса
Показывает текущее состояние импортов и прогресс миграции.

## 🚀 Быстрый Старт

### Шаг 1: Проверить текущее состояние
```powershell
cd C:\www.spa.com\scripts
.\check-import-status.ps1
```

### Шаг 2: Сначала запустить в тестовом режиме
```powershell
.\mass-import-update.ps1 -DryRun
```

### Шаг 3: Применить изменения
```powershell
.\mass-import-update.ps1
```

### Шаг 4: Проверить результат
```powershell
.\check-import-status.ps1 -Detailed
```

## ⚙️ Параметры Скриптов

### mass-import-update.ps1
- `-DryRun` - Показать что будет изменено, но не применять изменения
- `-Verbose` - Подробный вывод всех операций

### check-import-status.ps1  
- `-Detailed` - Показать список всех файлов для каждой категории

## 🔄 Что Обновляется

### ✅ UI Forms Компоненты
```javascript
// Было:
import InputError from '@/Components/UI/Forms/InputError.vue'
import InputLabel from '@/Components/UI/Forms/InputLabel.vue'
import PrimaryButton from '@/Components/UI/Forms/PrimaryButton.vue'
import SecondaryButton from '@/Components/UI/Forms/SecondaryButton.vue'
import TextInput from '@/Components/UI/Forms/TextInput.vue'

// Стало:
import { InputError, InputLabel, PrimaryButton, SecondaryButton, TextInput } from '@/src/shared/ui/atoms'
```

### ✅ Base UI Компоненты
```javascript
// Было:
import BaseInput from '@/Components/UI/BaseInput.vue'
import BaseSelect from '@/Components/UI/BaseSelect.vue'
import CheckboxGroup from '@/Components/UI/CheckboxGroup.vue'

// Стало:
import { BaseInput, BaseSelect, CheckboxGroup } from '@/src/shared/ui/atoms'
```

### ✅ Auth Компоненты
```javascript
// Было:
import AuthModal from '@/Components/Auth/AuthModal.vue'
import RegisterModal from '@/Components/Auth/RegisterModal.vue'

// Стало:
import { AuthModal, RegisterModal } from '@/src/features/auth'
```

## 🛡️ Безопасность

- **Автоматические бэкапы**: Все изменённые файлы сохраняются в `scripts/backup-imports/`
- **DryRun режим**: Позволяет увидеть изменения без их применения
- **Подробное логирование**: Все операции записываются в `scripts/import-update.log`

## 📊 Отчёты и Логи

### Лог файл
```
scripts/import-update.log
```

### Бэкапы
```
scripts/backup-imports/*.vue.backup
```

### Проверка Git изменений
После запуска скрипта проверьте изменения:
```bash
git diff
git status
```

## 🔧 После Выполнения

1. **Проверить линтер**:
   ```bash
   npm run lint
   ```

2. **Собрать проект**:
   ```bash
   npm run build
   ```

3. **Запустить dev сервер**:
   ```bash
   npm run dev
   ```

4. **Протестировать функциональность**:
   - Авторизация/регистрация
   - Формы в профиле
   - UI компоненты

## ⚠️ Возможные Проблемы

### Если что-то пошло не так:
1. Восстановить из бэкапов в `scripts/backup-imports/`
2. Откатить через Git: `git checkout -- .`
3. Запустить скрипт заново с `-DryRun`

### Если остались старые импорты:
1. Запустить `check-import-status.ps1 -Detailed`
2. Обновить оставшиеся вручную
3. Добавить новые паттерны в скрипт

## 🎯 Результат

После успешного выполнения:
- ✅ Все UI компоненты используют новую структуру
- ✅ Auth компоненты используют features
- ✅ Консистентные импорты по всему проекту  
- ✅ Готовность к дальнейшему развитию FSD архитектуры

---

💡 **Совет**: Запускайте скрипты поэтапно и проверяйте результат на каждом этапе!