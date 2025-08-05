#!/bin/bash
# 🗑️ СКРИПТ БЕЗОПАСНОГО УДАЛЕНИЯ LEGACY КОМПОНЕНТОВ
# Сгенерирован автоматически анализатором дубликатов

# Дата создания: 04.08.2025, 18:02:12
# Найдено дубликатов: 14
# Безопасно удалить: 2
# Требует проверки: 12

echo "🚀 Начинаем безопасное удаление legacy компонентов..."
echo ""

# Создаем резервную копию
echo "💾 Создаем резервную копию..."
BACKUP_DIR="backup-legacy-$(date +%Y%m%d-%H%M%S)"
mkdir -p "$BACKUP_DIR"

# Функция безопасного удаления
safe_delete() {
  local file="$1"
  local reason="$2"
  
  if [ -f "$file" ]; then
    echo "🗑️  Удаляем: $file ($reason)"
    cp "$file" "$BACKUP_DIR/"
    rm "$file"
  else
    echo "⚠️  Файл не найден: $file"
  fi
}

# БЕЗОПАСНЫЕ ДЛЯ УДАЛЕНИЯ ФАЙЛЫ
echo "🟢 Удаляем безопасные файлы..."

safe_delete "resources\js\Components\Features\MasterShow\components\BookingWidget.vue" "Мигрирован на FSD, низкий риск"
safe_delete "resources\js\Components\MediaUpload\MediaUploader.stories.ts" "Мигрирован на FSD, низкий риск"

# ФАЙЛЫ ТРЕБУЮЩИЕ РУЧНОЙ ПРОВЕРКИ (закомментированы)
echo ""
echo "⚠️  Следующие файлы требуют ручной проверки:"

echo "❌ ПРОВЕРИТЬ: resources\js\Components\Booking\BookingForm.stories.ts (Требует проверки: high риск, 0 использований)"
# safe_delete "resources\js\Components\Booking\BookingForm.stories.ts" "Требует проверки: high риск, 0 использований"
echo "❌ ПРОВЕРИТЬ: resources\js\Components\Booking\BookingSuccessModal.stories.ts (Требует проверки: medium риск, 0 использований)"
# safe_delete "resources\js\Components\Booking\BookingSuccessModal.stories.ts" "Требует проверки: medium риск, 0 использований"
echo "❌ ПРОВЕРИТЬ: resources\js\Components\Cards\Card.vue (Требует проверки: medium риск, 0 использований)"
# safe_delete "resources\js\Components\Cards\Card.vue" "Требует проверки: medium риск, 0 использований"
echo "❌ ПРОВЕРИТЬ: resources\js\Components\Features\MasterShow\components\MasterInfo.vue (Требует проверки: low риск, 0 использований)"
# safe_delete "resources\js\Components\Features\MasterShow\components\MasterInfo.vue" "Требует проверки: low риск, 0 использований"
echo "❌ ПРОВЕРИТЬ: resources\js\Components\Features\MasterShow\index.vue (Требует проверки: low риск, 0 использований)"
# safe_delete "resources\js\Components\Features\MasterShow\index.vue" "Требует проверки: low риск, 0 использований"
echo "❌ ПРОВЕРИТЬ: resources\js\Components\Modals\BookingModal.vue (Требует проверки: medium риск, 0 использований)"
# safe_delete "resources\js\Components\Modals\BookingModal.vue" "Требует проверки: medium риск, 0 использований"
echo "❌ ПРОВЕРИТЬ: resources\js\Components\Modals\PhoneModal.stories.ts (Требует проверки: medium риск, 0 использований)"
# safe_delete "resources\js\Components\Modals\PhoneModal.stories.ts" "Требует проверки: medium риск, 0 использований"
echo "❌ ПРОВЕРИТЬ: resources\js\Components\UI\Forms\InputError.vue (Требует проверки: low риск, 0 использований)"
# safe_delete "resources\js\Components\UI\Forms\InputError.vue" "Требует проверки: low риск, 0 использований"
echo "❌ ПРОВЕРИТЬ: resources\js\Components\UI\Forms\InputLabel.vue (Требует проверки: low риск, 0 использований)"
# safe_delete "resources\js\Components\UI\Forms\InputLabel.vue" "Требует проверки: low риск, 0 использований"
echo "❌ ПРОВЕРИТЬ: resources\js\Components\UI\Forms\PrimaryButton.vue (Требует проверки: low риск, 0 использований)"
# safe_delete "resources\js\Components\UI\Forms\PrimaryButton.vue" "Требует проверки: low риск, 0 использований"
echo "❌ ПРОВЕРИТЬ: resources\js\Components\UI\Forms\SecondaryButton.vue (Требует проверки: low риск, 0 использований)"
# safe_delete "resources\js\Components\UI\Forms\SecondaryButton.vue" "Требует проверки: low риск, 0 использований"
echo "❌ ПРОВЕРИТЬ: resources\js\Components\UI\Forms\TextInput.vue (Требует проверки: low риск, 0 использований)"
# safe_delete "resources\js\Components\UI\Forms\TextInput.vue" "Требует проверки: low риск, 0 использований"

echo ""
echo "✅ Безопасное удаление завершено!"
echo "📁 Резервная копия сохранена в: $BACKUP_DIR"
echo "📋 Проверьте файлы, отмеченные для ручной проверки"
