// create-deletion-plan.cjs - Создание плана удаления legacy компонентов
const fs = require('fs');
const path = require('path');

// Читаем отчет о дублировании
const duplicationReport = JSON.parse(fs.readFileSync('duplication-report.json', 'utf-8'));

// Категории для удаления
const deletionPlan = {
  timestamp: new Date().toISOString(),
  summary: {
    totalToDelete: 0,
    safeToDelete: 0,
    requiresReview: 0,
    mustKeep: 0
  },
  categories: {
    duplicates: {
      description: "Компоненты полностью мигрированные на FSD",
      files: [],
      status: "safe"
    },
    obsolete: {
      description: "Устаревшие компоненты не используемые в проекте",
      files: [],
      status: "safe"
    },
    migrated: {
      description: "Компоненты успешно перенесенные в FSD структуру",
      files: [],
      status: "safe"
    },
    review: {
      description: "Требуют ручной проверки перед удалением",
      files: [],
      status: "review"
    },
    keep: {
      description: "Должны быть сохранены (активно используются)",
      files: [],
      status: "keep"
    }
  },
  commands: {
    windows: [],
    unix: []
  }
};

// Анализируем дубликаты из отчета
duplicationReport.duplicates.forEach(dup => {
  dup.locations.forEach(loc => {
    if (loc.type === 'legacy') {
      // Проверяем есть ли FSD версия
      const hasFsdVersion = dup.locations.some(l => l.type === 'fsd');
      if (hasFsdVersion) {
        deletionPlan.categories.duplicates.files.push({
          path: loc.path,
          reason: `Мигрирован в FSD как ${dup.name}`,
          lines: loc.lines
        });
      }
    }
  });
});

// Определяем явно устаревшие компоненты
const obsoletePatterns = [
  'Old',
  'Legacy',
  'Deprecated',
  'Backup',
  'Copy',
  'Test',
  '_old',
  '_backup'
];

// Сканируем legacy директорию
function scanLegacyComponents(dir) {
  const components = [];

  if (!fs.existsSync(dir)) {
    return components;
  }

  const files = fs.readdirSync(dir);

  files.forEach(file => {
    const filePath = path.join(dir, file);
    const stat = fs.statSync(filePath);

    if (stat.isDirectory()) {
      components.push(...scanLegacyComponents(filePath));
    } else if (file.endsWith('.vue')) {
      const component = {
        path: filePath,
        name: path.basename(file, '.vue'),
        size: stat.size
      };

      // Проверяем на устаревшие паттерны
      const isObsolete = obsoletePatterns.some(pattern => 
        component.name.includes(pattern) || filePath.includes(pattern)
      );

      if (isObsolete) {
        deletionPlan.categories.obsolete.files.push({
          path: filePath,
          reason: 'Содержит маркер устаревшего кода',
          size: component.size
        });
      } else {
        // Проверяем использование
        const content = fs.readFileSync(filePath, 'utf-8');
        const hasExports = content.includes('export default') || content.includes('export {');
        const isUsed = checkComponentUsage(component.name);

        if (!isUsed && hasExports) {
          deletionPlan.categories.review.files.push({
            path: filePath,
            reason: 'Не найдено использований, требует проверки',
            size: component.size
          });
        }
      }

      components.push(component);
    }
  });

  return components;
}

// Проверка использования компонента
function checkComponentUsage(componentName) {
  // Упрощенная проверка - ищем импорты
  const pagesDir = 'resources/js/Pages';
  const srcDir = 'resources/js/src';

  const searchDirs = [pagesDir, srcDir];

  for (const dir of searchDirs) {
    if (fs.existsSync(dir)) {
      const usage = findInFiles(dir, componentName);
      if (usage.length > 0) {
        return true;
      }
    }
  }

  return false;
}

// Поиск в файлах
function findInFiles(dir, searchTerm) {
  const results = [];

  const files = fs.readdirSync(dir);

  files.forEach(file => {
    const filePath = path.join(dir, file);
    const stat = fs.statSync(filePath);

    if (stat.isDirectory()) {
      results.push(...findInFiles(filePath, searchTerm));
    } else if (file.endsWith('.vue') || file.endsWith('.js') || file.endsWith('.ts')) {
      const content = fs.readFileSync(filePath, 'utf-8');
      if (content.includes(searchTerm)) {
        results.push(filePath);
      }
    }
  });

  return results;
}

// Определяем безопасные для удаления на основе миграции
const migrationMap = {
  'BookingForm': 'features/booking-form',
  'Card': 'shared/ui/organisms/Cards',
  'MasterInfo': 'entities/master/ui/MasterInfo',
  'BookingWidget': 'entities/booking/ui/BookingWidget',
  'MediaUploader': 'shared/ui/molecules/MediaUploader',
  'Toast': 'shared/ui/molecules/Toast',
  'Breadcrumbs': 'shared/ui/molecules/Breadcrumbs',
  'ErrorBoundary': 'shared/ui/molecules/ErrorBoundary',
  'Spinner': 'shared/ui/atoms/Spinner',
  'Skeleton': 'shared/ui/atoms/Skeleton'
};

// Проверяем мигрированные компоненты
Object.entries(migrationMap).forEach(([legacyName, fsdPath]) => {
  const legacyPath = `resources/js/Components/${legacyName}`;
  const fullFsdPath = `resources/js/src/${fsdPath}`;

  // Ищем legacy версии
  const possiblePaths = [
    `${legacyPath}.vue`,
    `${legacyPath}/index.vue`,
    `resources/js/Components/${legacyName}/${legacyName}.vue`
  ];

  possiblePaths.forEach(path => {
    if (fs.existsSync(path)) {
      // Проверяем что FSD версия существует
      if (fs.existsSync(fullFsdPath) || fs.existsSync(`${fullFsdPath}.vue`)) {
        deletionPlan.categories.migrated.files.push({
          path: path,
          reason: `Мигрирован в ${fsdPath}`,
          fsdPath: fullFsdPath
        });
      }
    }
  });
});

// Компоненты которые точно нужно сохранить
const mustKeep = [
  'App.vue',
  'AppLayout.vue',
  'MainLayout.vue',
  'GuestLayout.vue',
  'AuthenticatedLayout.vue'
];

// Сканируем все legacy компоненты
const legacyComponents = scanLegacyComponents('resources/js/Components');

// Фильтруем компоненты для сохранения
legacyComponents.forEach(comp => {
  const shouldKeep = mustKeep.some(keep => 
    comp.path.includes(keep) || comp.name === keep.replace('.vue', '')
  );

  if (shouldKeep) {
    const alreadyListed = [
      ...deletionPlan.categories.duplicates.files,
      ...deletionPlan.categories.obsolete.files,
      ...deletionPlan.categories.migrated.files,
      ...deletionPlan.categories.review.files
    ].some(f => f.path === comp.path);

    if (!alreadyListed) {
      deletionPlan.categories.keep.files.push({
        path: comp.path,
        reason: 'Критический компонент для работы приложения',
        size: comp.size
      });
    }
  }
});

// Подсчитываем статистику
deletionPlan.summary.safeToDelete = 
  deletionPlan.categories.duplicates.files.length +
  deletionPlan.categories.obsolete.files.length +
  deletionPlan.categories.migrated.files.length;

deletionPlan.summary.requiresReview = deletionPlan.categories.review.files.length;
deletionPlan.summary.mustKeep = deletionPlan.categories.keep.files.length;
deletionPlan.summary.totalToDelete = deletionPlan.summary.safeToDelete;

// Генерируем команды удаления
const safeToDelete = [
  ...deletionPlan.categories.duplicates.files,
  ...deletionPlan.categories.obsolete.files,
  ...deletionPlan.categories.migrated.files
];

// Windows команды
safeToDelete.forEach(file => {
  const winPath = file.path.replace(/\//g, '\\');
  deletionPlan.commands.windows.push(`del /F "${winPath}"`);
});

// Unix команды
safeToDelete.forEach(file => {
  deletionPlan.commands.unix.push(`rm -f "${file.path}"`);
});

// Добавляем команды для удаления пустых директорий
deletionPlan.commands.windows.push(':: Удаление пустых директорий');
deletionPlan.commands.windows.push('for /f "delims=" %d in (\'dir /s /b /ad resources\\js\\Components ^| sort /r\') do rd "%d" 2>nul');

deletionPlan.commands.unix.push('# Удаление пустых директорий');
deletionPlan.commands.unix.push('find resources/js/Components -type d -empty -delete');

// Сохраняем план
fs.writeFileSync('deletion-plan.json', JSON.stringify(deletionPlan, null, 2));

// Создаем исполняемые скрипты
const batchScript = `@echo off
echo ========================================
echo   УДАЛЕНИЕ LEGACY КОМПОНЕНТОВ
echo ========================================
echo.
echo Будет удалено: ${deletionPlan.summary.safeToDelete} файлов
echo.
pause

${deletionPlan.commands.windows.join('\n')}

echo.
echo ========================================
echo   УДАЛЕНИЕ ЗАВЕРШЕНО
echo ========================================
pause
`;

const shellScript = `#!/bin/bash
echo "========================================"
echo "   УДАЛЕНИЕ LEGACY КОМПОНЕНТОВ"
echo "========================================"
echo ""
echo "Будет удалено: ${deletionPlan.summary.safeToDelete} файлов"
echo ""
read -p "Нажмите Enter для продолжения..."

${deletionPlan.commands.unix.join('\n')}

echo ""
echo "========================================"
echo "   УДАЛЕНИЕ ЗАВЕРШЕНО"
echo "========================================"
`;

fs.writeFileSync('delete-legacy.bat', batchScript);
fs.writeFileSync('delete-legacy.sh', shellScript);

// Выводим сводку

