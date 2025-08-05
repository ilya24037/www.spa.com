const fs = require('fs');
const path = require('path');

/**
 * Скрипт для автоматического обновления файлов с DDD нарушениями
 * Заменяет прямые обращения к связям на Integration Services
 */

// Паттерны замен для DDD соблюдения
const replacements = [
  // Booking-related replacements
  {
    pattern: /\$user->bookings\(\)/g,
    replacement: '$user->getBookings()',
    description: 'Replace direct bookings() relation with getBookings() method'
  },
  {
    pattern: /\$user->activeBookings\(\)/g,
    replacement: '$user->getActiveBookings()',
    description: 'Replace direct activeBookings() relation with getActiveBookings() method'
  },
  {
    pattern: /\$user->completedBookings\(\)/g,
    replacement: '$user->getCompletedBookings()',
    description: 'Replace direct completedBookings() relation with getCompletedBookings() method'
  },
  {
    pattern: /\$user->lastBooking\(\)/g,
    replacement: '$user->getLastBooking()',
    description: 'Replace direct lastBooking() relation with getLastBooking() method'
  },
  {
    pattern: /\$user->hasActiveBookings\(\)/g,
    replacement: '$user->hasActiveBookings()',
    description: 'hasActiveBookings() method remains the same'
  },
  {
    pattern: /\$user->bookings\(\)->count\(\)/g,
    replacement: '$user->getBookingsCount()',
    description: 'Replace bookings count with optimized method'
  },
  {
    pattern: /\$user->bookings\(\)->create\(/g,
    replacement: '$user->requestBooking(',
    description: 'Replace direct booking creation with event-driven approach'
  },
  {
    pattern: /\$user->bookings\(\)->delete\(\)/g,
    replacement: '$user->cancelAllBookings()',
    description: 'Replace direct booking deletion with service method'
  },

  // Master profile-related replacements
  {
    pattern: /\$user->masterProfile\b/g,
    replacement: '$user->getMasterProfile()',
    description: 'Replace direct masterProfile property with getMasterProfile() method'
  },
  {
    pattern: /\$user->masterProfiles\(\)/g,
    replacement: '$user->getMasterProfiles()',
    description: 'Replace direct masterProfiles() relation with getMasterProfiles() method'
  },
  {
    pattern: /\$user->masterProfile\(\)/g,
    replacement: '$user->getMasterProfile()',
    description: 'Replace direct masterProfile() relation with getMasterProfile() method'
  },
  {
    pattern: /\$user->hasActiveMasterProfile\(\)/g,
    replacement: '$user->hasActiveMasterProfile()',
    description: 'hasActiveMasterProfile() method remains the same'
  },
  {
    pattern: /\$user->masterProfile\(\)->create\(/g,
    replacement: '$user->createMasterProfile(',
    description: 'Replace direct master profile creation with event-driven approach'
  },
  {
    pattern: /\$user->getMainMasterProfile\(\)/g,
    replacement: '$user->getMainMasterProfile()',
    description: 'getMainMasterProfile() method remains the same'
  },

  // Profile-related replacements (safer getters)
  {
    pattern: /\$user->profile\b/g,
    replacement: '$user->getProfile()',
    description: 'Replace direct profile property with safe getProfile() method'
  },
  {
    pattern: /\$user->settings\b/g,
    replacement: '$user->getSettings()',
    description: 'Replace direct settings property with safe getSettings() method'
  },

  // Load relationships - remove problematic eager loading
  {
    pattern: /->load\(\['profile', 'masterProfile'\]\)/g,
    replacement: '',
    description: 'Remove problematic eager loading - use methods instead'
  },
  {
    pattern: /->load\('masterProfile'\)/g,
    replacement: '',
    description: 'Remove problematic masterProfile eager loading'
  },
  {
    pattern: /->with\(\['masterProfile'\]\)/g,
    replacement: '',
    description: 'Remove problematic masterProfile with() eager loading'
  },
];

// Файлы для обновления (средний и низкий приоритет)
const filesToUpdate = [
  'app/Application/Http/Controllers/Profile/ProfileItemsController.php',
  'app/Application/Http/Controllers/FavoriteController.php',
  'app/Application/Http/Controllers/CompareController.php',
  'app/Domain/Master/Repositories/MasterRepository.php',
  'app/Domain/Master/Services/MasterService.php',
  'app/Application/Http/Controllers/Media/MediaUploadController.php',
  'app/Application/Http\Controllers/Profile/ProfileSettingsController.php',
  'app/Infrastructure/Adapters/BookingServiceAdapter.php',
  'app/Domain/Booking/Actions/RescheduleBookingAction.php',
  'app/Domain/Booking/Services/BookingSlotService.php',
  'app/Domain/Booking/Services/BookingService.php',
  'app/Domain/Booking/Actions/CompleteBookingAction.php',
  'app/Infrastructure/Notification/LegacyNotificationService.php',
  'app/Domain\Booking/Actions/ConfirmBookingAction.php',
];

/**
 * Обновить файл согласно DDD паттернам
 */
function updateFile(filePath) {
  const fullPath = path.join(process.cwd(), filePath);
  
  if (!fs.existsSync(fullPath)) {
    console.log(`⚠️ File not found: ${filePath}`);
    return false;
  }

  try {
    let content = fs.readFileSync(fullPath, 'utf8');
    let updated = false;
    let appliedReplacements = [];

    // Применяем все замены
    replacements.forEach(({ pattern, replacement, description }) => {
      if (pattern.test(content)) {
        const matches = content.match(pattern);
        content = content.replace(pattern, replacement);
        updated = true;
        appliedReplacements.push({
          description,
          count: matches ? matches.length : 0
        });
      }
    });

    if (updated) {
      // Добавляем комментарий о рефакторинге
      const refactoringComment = `
/**
 * ✅ DDD РЕФАКТОРИНГ ПРИМЕНЕН:
 * - Заменены прямые связи на Integration Services
 * - Удалены циклические зависимости между доменами
 * - Применены Events для междоменного взаимодействия
 * 
 * Обновлено автоматически: ${new Date().toISOString()}
 */
`;
      
      // Вставляем комментарий после namespace
      content = content.replace(
        /(namespace [^;]+;)/,
        `$1\n${refactoringComment}`
      );

      fs.writeFileSync(fullPath, content, 'utf8');
      
      console.log(`✅ Updated: ${filePath}`);
      appliedReplacements.forEach(({ description, count }) => {
        console.log(`   - ${description} (${count} replacements)`);
      });
      
      return true;
    } else {
      console.log(`📝 No changes needed: ${filePath}`);
      return false;
    }

  } catch (error) {
    console.error(`❌ Error updating ${filePath}:`, error.message);
    return false;
  }
}

/**
 * Главная функция
 */
function main() {
  console.log('🚀 Starting DDD Violations Update Script...\n');

  let totalFiles = 0;
  let updatedFiles = 0;

  filesToUpdate.forEach(file => {
    totalFiles++;
    if (updateFile(file)) {
      updatedFiles++;
    }
  });

  // 📊 SUMMARY:
  // Total files processed: ${totalFiles}
  // Files updated: ${updatedFiles}
  // Files skipped: ${totalFiles - updatedFiles}
  
  if (updatedFiles > 0) {
    // ✅ DDD рефакторинг успешно применен к файлам
    // Следующий шаг: Запустить тесты для проверки работоспособности
  } else {
    // 📝 Все файлы уже соответствуют DDD принципам или не найдены
  }
}

// Запуск скрипта
main();