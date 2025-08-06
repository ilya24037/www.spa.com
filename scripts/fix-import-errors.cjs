/**
 * Скрипт для автоматического исправления проблем с импортами
 * CommonJS версия для совместимости
 */

const fs = require('fs');
const path = require('path');

// Простая функция поиска файлов (без glob)
function findFiles(dir, extensions, result = []) {
  const files = fs.readdirSync(dir);
  
  for (const file of files) {
    const fullPath = path.join(dir, file);
    const stat = fs.statSync(fullPath);
    
    if (stat.isDirectory()) {
      findFiles(fullPath, extensions, result);
    } else if (extensions.some(ext => file.endsWith(ext))) {
      result.push(fullPath);
    }
  }
  
  return result;
}

// Замены для исправления импортов
const fixes = [
  // Убираем `as any` из Vue шаблонов
  {
    from: /separator=">" as any/g,
    to: 'separator=">"',
    description: 'Fixing Vue template syntax'
  },
  
  // Исправляем проблемы с кодировкой в комментариях
  {
    from: /Р§РµСЂРЅРѕРІРёРє/g,
    to: 'Черновик',
    description: 'Fixing encoding issues'
  },
  {
    from: /РљРѕРЅС‚РµР№РЅРµСЂ РєР°Рє РЅР° РіР»Р°РІРЅРѕР№/g,
    to: 'Контейнер как на главной',
    description: 'Fixing container comments'
  },
  {
    from: /РҐР»РµР±РЅС‹Рµ РєСЂРѕС€РєРё/g,
    to: 'Хлебные крошки',
    description: 'Fixing breadcrumb comments'
  },
  
  // Убираем проблемные TypeScript импорты
  {
    from: /import { defineComponent } from 'vue'/g,
    to: '// import { defineComponent } from \'vue\' // commented out to fix build',
    description: 'Fixing defineComponent imports'
  }
];

async function processFiles() {
  console.log('🔧 Исправление ошибок импортов...');
  
  // Ищем файлы в resources/js
  const resourcesPath = path.join(process.cwd(), 'resources', 'js');
  const files = findFiles(resourcesPath, ['.vue', '.ts', '.js']);
  
  let fixedFiles = 0;
  let totalFixesApplied = 0;
  
  for (const file of files) {
    try {
      let content = fs.readFileSync(file, 'utf8');
      let modified = false;
      let fixesInFile = 0;
      
      // Применяем все исправления
      for (const fix of fixes) {
        const matches = content.match(fix.from);
        if (matches) {
          content = content.replace(fix.from, fix.to);
          modified = true;
          fixesInFile += matches.length;
          totalFixesApplied += matches.length;
        }
      }
      
      if (modified) {
        fs.writeFileSync(file, content, 'utf8');
        fixedFiles++;
        console.log(`✅ Исправлен: ${path.relative(process.cwd(), file)} (${fixesInFile} исправлений)`);
      }
      
    } catch (error) {
      console.error(`❌ Ошибка обработки ${file}:`, error.message);
    }
  }
  
  console.log(`\n📊 Результат:`);
  console.log(`   Найдено файлов: ${files.length}`);
  console.log(`   Исправлено файлов: ${fixedFiles}`);
  console.log(`   Всего исправлений: ${totalFixesApplied}`);
  
  if (fixedFiles === 0) {
    console.log('✨ Никаких исправлений не потребовалось!');
  }
}

// Запуск если файл выполняется напрямую
if (require.main === module) {
  processFiles().catch(console.error);
}

module.exports = { processFiles };