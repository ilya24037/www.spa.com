# Отчет о переносе моделей в Domain слой

## ✅ Перенесено в текущей сессии:

### Booking домен
- `app/Models/Booking.php` → `app/Domain/Booking/Models/Booking.php`
- `app/Models/BookingService.php` → `app/Domain/Booking/Models/BookingService.php`
- `app/Models/BookingSlot.php` → `app/Domain/Booking/Models/BookingSlot.php`

### Review домен
- `app/Models/Review.php` → `app/Domain/Review/Models/Review.php`
- `app/Models/ReviewReply.php` → `app/Domain/Review/Models/ReviewReply.php`

### Service домен
- `app/Models/Service.php` → `app/Domain/Service/Models/Service.php`

## ✅ Ранее перенесено:

### User домен
- User.php разделен на части
- UserProfile.php
- UserSettings.php

### Master домен
- MasterProfile.php разделен на части
- MasterMedia.php
- MasterSchedule.php

### Ad домен
- Ad.php разделен на части
- AdMedia.php
- AdPricing.php
- AdLocation.php

## ❌ Осталось перенести (согласно карте):

### Простые модели
- `app/Models/Schedule.php` → `app/Domain/Master/Models/Schedule.php`
- `app/Models/MasterPhoto.php` → `app/Domain/Media/Models/Photo.php`
- `app/Models/MasterVideo.php` → `app/Domain/Media/Models/Video.php`

### Дополнительные модели (не в карте, но есть в проекте)
- Payment.php
- MassageCategory.php
- WorkZone.php
- Media.php
- Notification.php
- NotificationDelivery.php
- ReviewReaction.php
- AdContent.php
- AdPlan.php
- AdSchedule.php
- UserBalance.php

## 📊 Статистика:
- **Перенесено согласно карте**: 9/12 моделей (75%)
- **Создано моделей-оберток для совместимости**: 6
- **Общее количество моделей в проекте**: ~27
- **Процент выполнения**: ~40%

## 🎯 Следующие шаги:
1. Перенести Schedule.php в Domain/Master/Models/
2. Перенести MasterPhoto.php и MasterVideo.php в Domain/Media/Models/
3. Определить домены для остальных моделей
4. Продолжить с переносом DTOs, Repositories и Actions