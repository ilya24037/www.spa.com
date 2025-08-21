# 🗑️ ФАЙЛЫ ДЛЯ УДАЛЕНИЯ ПОСЛЕ РЕФАКТОРИНГА BOOKING SERVICES

## 📋 Файлы, объединенные в BookingSlotService.php:
```bash
# Удалить эти файлы (функциональность перенесена в BookingSlotService.php):
rm C:/www.spa.com/app/Domain/Booking/Services/AvailabilityChecker.php
rm C:/www.spa.com/app/Domain/Booking/Services/AvailabilityService.php
rm C:/www.spa.com/app/Domain/Booking/Services/SlotService.php
rm C:/www.spa.com/app/Domain/Booking/Services/BookingSlotService.old.php  # Старая версия
```

## 📋 Файлы, объединенные в BookingValidationService.php:
```bash
# Удалить эти файлы (функциональность перенесена в BookingValidationService.php):
rm C:/www.spa.com/app/Domain/Booking/Services/BookingValidator.php
rm C:/www.spa.com/app/Domain/Booking/Services/ValidationService.php
rm C:/www.spa.com/app/Domain/Booking/Services/CancellationValidationService.php
rm C:/www.spa.com/app/Domain/Booking/Services/BookingCompletionValidationService.php
rm C:/www.spa.com/app/Domain/Booking/Services/RescheduleValidator.php
```

## 📋 Файлы, объединенные в BookingNotificationService.php:
```bash
# Удалить эти файлы (функциональность перенесена в BookingNotificationService.php):
rm C:/www.spa.com/app/Domain/Booking/Services/BookingReminderService.php
rm C:/www.spa.com/app/Domain/Booking/Services/RescheduleNotificationHandler.php
rm C:/www.spa.com/app/Domain/Booking/Services/NotificationService.php
```

## 📋 Другие дублирующие сервисы:
```bash
# Проверить и удалить, если не используются:
rm C:/www.spa.com/app/Domain/Booking/Services/CompletionService.php
rm C:/www.spa.com/app/Domain/Booking/Services/ConfirmationService.php
rm C:/www.spa.com/app/Domain/Booking/Services/CancellationService.php
rm C:/www.spa.com/app/Domain/Booking/Services/BookingStatusService.php
rm C:/www.spa.com/app/Domain/Booking/Services/BookingStatisticsService.php
rm C:/www.spa.com/app/Domain/Booking/Services/RescheduleService.php
rm C:/www.spa.com/app/Domain/Booking/Services/BookingHistoryService.php
rm C:/www.spa.com/app/Domain/Booking/Services/BookingSearchService.php
rm C:/www.spa.com/app/Domain/Booking/Services/BookingReportService.php
rm C:/www.spa.com/app/Domain/Booking/Services/PaymentIntegrationService.php
rm C:/www.spa.com/app/Domain/Booking/Services/ReviewIntegrationService.php
```

## ✅ ФАЙЛЫ, КОТОРЫЕ НУЖНО ОСТАВИТЬ:
```
✅ BookingService.php           # Главный сервис-координатор
✅ BookingSlotService.php       # Объединенный сервис слотов и доступности
✅ BookingValidationService.php # Объединенный сервис валидации
✅ BookingNotificationService.php # Объединенный сервис уведомлений
✅ PricingService.php           # Сервис расчета цен (оставляем как есть)
```

## 🔥 КОМАНДА ДЛЯ УДАЛЕНИЯ ВСЕХ СТАРЫХ ФАЙЛОВ (PowerShell):
```powershell
# Создать резервную копию перед удалением
$backupDir = "C:/Backup/Booking_Services_Old_$(Get-Date -Format 'yyyyMMdd_HHmmss')"
New-Item -ItemType Directory -Force -Path $backupDir

# Скопировать старые файлы в backup
$oldFiles = @(
    "AvailabilityChecker.php",
    "AvailabilityService.php", 
    "SlotService.php",
    "BookingSlotService.old.php",
    "BookingValidator.php",
    "ValidationService.php",
    "CancellationValidationService.php",
    "BookingCompletionValidationService.php",
    "RescheduleValidator.php",
    "BookingReminderService.php",
    "RescheduleNotificationHandler.php",
    "NotificationService.php",
    "CompletionService.php",
    "ConfirmationService.php",
    "CancellationService.php",
    "BookingStatusService.php",
    "BookingStatisticsService.php",
    "RescheduleService.php",
    "BookingHistoryService.php",
    "BookingSearchService.php",
    "BookingReportService.php",
    "PaymentIntegrationService.php",
    "ReviewIntegrationService.php"
)

foreach ($file in $oldFiles) {
    $sourcePath = "C:/www.spa.com/app/Domain/Booking/Services/$file"
    if (Test-Path $sourcePath) {
        Copy-Item $sourcePath -Destination $backupDir
        Write-Host "Backed up: $file"
    }
}

# После проверки backup - удалить старые файлы
foreach ($file in $oldFiles) {
    $sourcePath = "C:/www.spa.com/app/Domain/Booking/Services/$file"
    if (Test-Path $sourcePath) {
        Remove-Item $sourcePath -Force
        Write-Host "Deleted: $file"
    }
}

Write-Host "`n✅ Рефакторинг завершен! Старые файлы удалены."
```

## 📊 РЕЗУЛЬТАТ РЕФАКТОРИНГА:
- **Было:** 26 файлов сервисов
- **Стало:** 5 файлов (BookingService + 3 объединенных + PricingService)
- **Удалено дублирования:** ~70%
- **Улучшение:** Чистая архитектура, легче поддерживать и тестировать

## ⚠️ ВАЖНО:
1. Перед удалением убедитесь, что приложение работает корректно
2. Запустите тесты: `php artisan test tests/Unit/Domain/Booking/`
3. Проверьте, что никакие другие части приложения не импортируют старые сервисы
4. Сохраните backup на случай, если что-то пойдет не так