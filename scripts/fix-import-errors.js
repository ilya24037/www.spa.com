/**
 * Скрипт для автоматического исправления проблем с импортами
 * После рефакторинга оптимизации производительности
 */

import fs from 'fs';
import path from 'path';
import { glob } from 'glob';

// Паттерны файлов для обработки
const patterns = [
  'resources/js/**/*.vue',
  'resources/js/**/*.ts',
  'resources/js/**/*.js'
];

// Замены для исправления импортов
const fixes = [
  // Убираем `as any` из Vue шаблонов
  {
    from: /separator=">" as any/g,
    to: 'separator=">"'
  },
  
  // Исправляем проблемы с кодировкой в комментариях
  {
    from: /Р§РµСЂРЅРѕРІРёРє/g,
    to: 'Черновик'
  },
  {
    from: /РљРѕРЅС‚РµР№РЅРµСЂ РєР°Рє РЅР° РіР»Р°РІРЅРѕР№/g,
    to: 'Контейнер как на главной'
  },
  {
    from: /РҐР»РµР±РЅС‹Рµ РєСЂРѕС€РєРё/g,
    to: 'Хлебные крошки'
  },
  
  // Исправляем импорты несуществующих файлов
  {
    from: /import.*from.*['"]\.\/utils\/lazyLoading['"];?/g,
    to: '// import from utils/lazyLoading (commented out until implemented)'
  },
  {
    from: /import.*from.*['"]\.\/utils\/imageOptimization['"];?/g,
    to: '// import from utils/imageOptimization (commented out until implemented)'
  }
];

async function processFiles() {
  console.log('🔧 Исправление ошибок импортов...');
  
  let totalFiles = 0;
  let fixedFiles = 0;
  
  for (const pattern of patterns) {
    const files = await glob(pattern);
    
    for (const file of files) {
      try {
        let content = fs.readFileSync(file, 'utf8');
        let modified = false;
        
        // Применяем все исправления
        for (const fix of fixes) {
          if (fix.from.test(content)) {
            content = content.replace(fix.from, fix.to);
            modified = true;
          }
        }
        
        if (modified) {
          fs.writeFileSync(file, content, 'utf8');
          fixedFiles++;
          console.log(`✅ Исправлен: ${file}`);
        }
        
        totalFiles++;
        
      } catch (error) {
        console.error(`❌ Ошибка обработки ${file}:`, error.message);
      }
    }
  }
  
  console.log(`\n📊 Результат:`);
  console.log(`   Обработано файлов: ${totalFiles}`);
  console.log(`   Исправлено файлов: ${fixedFiles}`);
  console.log(`   Файлов без изменений: ${totalFiles - fixedFiles}`);
}

// Запуск если файл выполняется напрямую
if (import.meta.url === `file://${process.argv[1]}`) {
  processFiles().catch(console.error);
}

export { processFiles };