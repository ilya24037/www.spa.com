// full-analysis.cjs - ПОЛНЫЙ анализ проекта по методике CLAUDE.md
const fs = require('fs');
const path = require('path');
const { execSync } = require('child_process');

console.log('🔍 ПОЛНЫЙ АНАЛИЗ ПРОЕКТА ПО МЕТОДИКЕ CLAUDE.md\n');
console.log('=' .repeat(60));

// 1. Собираем ВСЕ ошибки сборки
console.log('\n📦 ШАГ 1: Анализ ВСЕХ ошибок сборки...\n');

let buildErrors = [];
try {
  execSync('npx vite build', { encoding: 'utf-8', stdio: 'pipe' });
} catch (error) {
  buildErrors = error.stdout ? error.stdout.split('\n') : [];
}

// 2. Анализируем ВСЕ импорты
console.log('📦 ШАГ 2: Анализ ВСЕХ импортов в проекте...\n');

const allImports = new Set();
const missingFiles = new Set();
const brokenImports = [];

function analyzeFile(filePath) {
  if (!fs.existsSync(filePath)) return;
  
  const content = fs.readFileSync(filePath, 'utf-8');
  
  // Находим все импорты
  const importRegex = /import\s+(?:{[^}]+}|\w+)\s+from\s+['"]([^'"]+)['"]/g;
  let match;
  
  while ((match = importRegex.exec(content)) !== null) {
    const importPath = match[1];
    allImports.add(importPath);
    
    // Проверяем существование файла
    if (importPath.startsWith('@/')) {
      const realPath = importPath.replace('@/', 'resources/js/');
      const possiblePaths = [
        realPath,
        realPath + '.vue',
        realPath + '.js',
        realPath + '.ts',
        realPath + '/index.js',
        realPath + '/index.ts'
      ];
      
      const exists = possiblePaths.some(p => fs.existsSync(p));
      if (!exists) {
        missingFiles.add(importPath);
        brokenImports.push({
          file: filePath,
          import: importPath
        });
      }
    }
  }
}

// Сканируем ВСЕ файлы
function scanDirectory(dir) {
  if (!fs.existsSync(dir)) return;
  
  const files = fs.readdirSync(dir);
  files.forEach(file => {
    const fullPath = path.join(dir, file);
    const stat = fs.statSync(fullPath);
    
    if (stat.isDirectory() && !file.includes('node_modules')) {
      scanDirectory(fullPath);
    } else if (file.endsWith('.vue') || file.endsWith('.js') || file.endsWith('.ts')) {
      analyzeFile(fullPath);
    }
  });
}

scanDirectory('resources/js');

// 3. Анализируем структуру FSD
console.log('📦 ШАГ 3: Проверка структуры FSD...\n');

const fsdStructure = {
  'shared': {
    'api': [],
    'config': [],
    'layouts': ['MainLayout', 'ProfileLayout'],
    'lib': [],
    'ui': {
      'atoms': ['Button', 'Input', 'Icon', 'Spinner', 'Skeleton', 'BackButton'],
      'molecules': ['Card', 'Modal', 'Toast', 'Breadcrumbs', 'ErrorBoundary'],
      'organisms': ['Header', 'Footer', 'Sidebar', 'PageLoader', 'StarRating']
    }
  },
  'entities': {
    'master': {
      'ui': ['MasterCard', 'MasterInfo', 'MasterGallery', 'MasterServices', 'MasterReviews'],
      'model': ['masterStore'],
      'api': []
    },
    'booking': {
      'ui': ['BookingCard', 'BookingModal', 'BookingStatus', 'BookingCalendar', 'BookingWidget'],
      'model': ['bookingStore'],
      'api': []
    },
    'ad': {
      'ui': ['AdCard', 'AdForm', 'AdStatus', 'AdCardList', 'AdStatusFilter'],
      'model': ['adStore'],
      'api': []
    },
    'user': {
      'ui': ['UserProfile', 'UserAvatar'],
      'model': ['userStore'],
      'api': []
    },
    'service': {
      'ui': ['ServiceCard', 'ServiceList'],
      'model': ['serviceStore'],
      'api': []
    }
  },
  'features': {
    'masters-filter': ['FilterPanel', 'FilterModal'],
    'booking-form': ['BookingForm'],
    'auth': ['LoginForm', 'RegisterForm'],
    'gallery': ['PhotoViewer', 'PhotoGallery', 'PhotoThumbnails'],
    'map': ['MapView', 'MapMarkers'],
    'profile-navigation': ['ProfileTabs', 'ProfileStats']
  },
  'widgets': {
    'masters-catalog': ['MastersCatalog'],
    'master-profile': ['MasterProfile'],
    'profile-dashboard': ['ProfileDashboard']
  },
  'pages': [] // Страницы уже есть
};

// 4. Создаем список ВСЕХ недостающих компонентов
const missingComponents = [];

function checkComponent(basePath, component) {
  const possiblePaths = [
    `${basePath}/${component}.vue`,
    `${basePath}/${component}/${component}.vue`,
    `${basePath}/${component}/index.ts`,
    `${basePath}/${component}/index.js`
  ];
  
  const exists = possiblePaths.some(p => fs.existsSync(p));
  if (!exists) {
    missingComponents.push({
      name: component,
      path: `${basePath}/${component}/${component}.vue`
    });
  }
}

// Проверяем shared/ui
Object.entries(fsdStructure.shared.ui).forEach(([type, components]) => {
  components.forEach(comp => {
    checkComponent(`resources/js/src/shared/ui/${type}`, comp);
  });
});

// Проверяем entities
Object.entries(fsdStructure.entities).forEach(([entity, structure]) => {
  structure.ui?.forEach(comp => {
    checkComponent(`resources/js/src/entities/${entity}/ui`, comp);
  });
  structure.model?.forEach(store => {
    const storePath = `resources/js/src/entities/${entity}/model/${store}.js`;
    if (!fs.existsSync(storePath)) {
      missingComponents.push({
        name: store,
        path: storePath
      });
    }
  });
});

// Проверяем features
Object.entries(fsdStructure.features).forEach(([feature, components]) => {
  components.forEach(comp => {
    checkComponent(`resources/js/src/features/${feature}/ui`, comp);
  });
});

// Проверяем widgets
Object.entries(fsdStructure.widgets).forEach(([widget, components]) => {
  components.forEach(comp => {
    checkComponent(`resources/js/src/widgets/${widget}`, comp);
  });
});

// 5. Генерируем отчет
const report = {
  timestamp: new Date().toISOString(),
  summary: {
    totalImports: allImports.size,
    missingImports: missingFiles.size,
    brokenFiles: new Set(brokenImports.map(b => b.file)).size,
    missingComponents: missingComponents.length
  },
  missingComponents,
  brokenImports: brokenImports.slice(0, 20), // Первые 20
  missingFiles: Array.from(missingFiles).slice(0, 20) // Первые 20
};

fs.writeFileSync('full-analysis-report.json', JSON.stringify(report, null, 2));

// 6. Выводим результаты
console.log('📊 РЕЗУЛЬТАТЫ АНАЛИЗА:');
console.log('=' .repeat(60));
console.log(`\n✅ Всего импортов: ${report.summary.totalImports}`);
console.log(`❌ Недостающих импортов: ${report.summary.missingImports}`);
console.log(`❌ Файлов с ошибками: ${report.summary.brokenFiles}`);
console.log(`❌ Недостающих компонентов: ${report.summary.missingComponents}`);

console.log('\n📝 НЕДОСТАЮЩИЕ КОМПОНЕНТЫ (топ 10):');
missingComponents.slice(0, 10).forEach(comp => {
  console.log(`   - ${comp.name} -> ${comp.path}`);
});

console.log('\n📝 СЛОМАННЫЕ ИМПОРТЫ (топ 5):');
brokenImports.slice(0, 5).forEach(broken => {
  console.log(`   - ${broken.file}:`);
  console.log(`     импортирует: ${broken.import}`);
});

console.log('\n✅ Полный отчет сохранен в full-analysis-report.json');
console.log('\n🎯 СЛЕДУЮЩИЙ ШАГ: Создать ВСЕ недостающие компоненты одним скриптом');