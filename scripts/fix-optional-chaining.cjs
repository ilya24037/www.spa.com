/**
 * Скрипт для исправления проблем с optional chaining assignment
 * Исправляет синтаксис currentImage?.value = newValue
 */

const fs = require('fs');
const path = require('path');

// Простая функция поиска файлов
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

// Исправления для optional chaining assignment
const fixes = [
  // currentImage?.value = newIndex
  {
    from: /(\w+)\?\.(value)\s*=\s*([^;\n]+)/g,
    to: (match, varName, prop, value) => {
      return `if (${varName}.${prop} !== undefined) {\n      ${varName}.${prop} = ${value}\n    }`
    },
    description: 'Fixing optional chaining assignment'
  },
  
  // window?.something = value
  {
    from: /window\?\.\(/g,
    to: 'if (typeof window !== \'undefined\') {\n        window(',
    description: 'Fixing window optional chaining'
  },
  
  // Исправляем неправильные пути с вопросительными знаками
  {
    from: /from\s+['"]\.\/([^'"]*)\?\.(types|vue|js|ts)['"]/g,
    to: (match, filename, extension) => {
      return `from './${filename}.${extension}'`
    },
    description: 'Fixing paths with question marks'
  },
  
  // Исправляем комментарии с вопросительными знаками
  {
    from: /<!--\s*([^>]*)\?\.(vue|ts|js)\s*-->/g,
    to: (match, filename, extension) => {
      return `<!-- ${filename}.${extension} -->`
    },
    description: 'Fixing comments with question marks'
  },
  
  // Исправляем кодировку в строках
  {
    from: /РљРѕРЅС‚Р°РєС‚С‹ Р±СѓРґСѓС‚ РґРѕСЃС‚СѓРїРЅС‹ РїРѕСЃР»Рµ Р·Р°РїРёСЃРё/g,
    to: 'Контакты будут доступны после записи',
    description: 'Fixing encoding in contact message'
  },
  
  // Убираем проблемные тестовые импорты
  {
    from: /import\s+\w+\s+from\s+['"]@\/test-[^'"]+['"]/g,
    to: '// Removed problematic test import',
    description: 'Removing problematic test imports'
  },
  
  // Исправляем CSS theme функции с optional chaining
  {
    from: /theme\(['"]colors\?\./g,
    to: "theme('colors.",
    description: 'Fixing CSS theme functions with optional chaining'
  },
  
  // Другие варианты optional chaining assignment
  {
    from: /(\w+\.\w+)\?\.(value|length|type)\s*=\s*([^;\n]+)/g,
    to: (match, varName, prop, value) => {
      return `if (${varName}.${prop} !== undefined) {\n      ${varName}.${prop} = ${value}\n    }`
    },
    description: 'Fixing nested optional chaining assignment'
  }
];

async function processFiles() {
  console.log('🔧 Исправление optional chaining assignment...');
  
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
        const originalContent = content;
        
        if (typeof fix.to === 'function') {
          content = content.replace(fix.from, fix.to);
        } else {
          content = content.replace(fix.from, fix.to);
        }
        
        if (content !== originalContent) {
          modified = true;
          fixesInFile++;
          totalFixesApplied++;
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