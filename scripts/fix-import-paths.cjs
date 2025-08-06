#!/usr/bin/env node

const fs = require('fs');
const path = require('path');

console.log('🔧 Исправление импортов с неправильными путями...\n');

let fixedCount = 0;

// Исправления импортов 
const importFixes = [
  // Убираем ? из путей импортов .vue файлов
  {
    pattern: /from ['"]([^'"]+)\?\.vue['"]/g,
    replacement: "from '$1.vue'",
    description: 'Исправление путей импортов .vue'
  },
  
  // Убираем ? из путей импортов .ts файлов  
  {
    pattern: /from ['"]([^'"]+)\?\.ts['"]/g,
    replacement: "from '$1.ts'",
    description: 'Исправление путей импортов .ts'
  },
  
  // Убираем ? из путей импортов без расширения
  {
    pattern: /from ['"]([^'"]+)\?['"]/g,
    replacement: "from '$1'",
    description: 'Исправление путей импортов без расширения'
  }
];

function fixImportsInFile(filePath) {
  try {
    if (!fs.existsSync(filePath)) return false;

    let content = fs.readFileSync(filePath, 'utf8');
    const originalContent = content;
    let fixes = [];

    // Применяем исправления
    importFixes.forEach(fix => {
      if (fix.pattern.test(content)) {
        content = content.replace(fix.pattern, fix.replacement);
        fixes.push(fix.description);
      }
    });

    if (content !== originalContent) {
      fs.writeFileSync(filePath, content, 'utf8');
      console.log(`   ✅ ${path.basename(filePath)}: ${fixes.length} исправлений`);
      fixedCount++;
      return true;
    }

    return false;
  } catch (error) {
    console.log(`   ❌ ${path.basename(filePath)}: ${error.message}`);
    return false;
  }
}

// Получение всех .vue и .ts файлов
function getAllFiles(dir, exts = ['.vue', '.ts']) {
  const files = [];
  
  function walk(currentDir) {
    const items = fs.readdirSync(currentDir);
    
    for (const item of items) {
      const fullPath = path.join(currentDir, item);
      const stat = fs.statSync(fullPath);
      
      if (stat.isDirectory() && !item.startsWith('.') && item !== 'node_modules') {
        walk(fullPath);
      } else if (stat.isFile() && exts.some(ext => item.endsWith(ext))) {
        files.push(fullPath);
      }
    }
  }
  
  walk(dir);
  return files;
}

// Получаем все файлы для исправления
const filesToFix = getAllFiles('resources/js');

console.log(`🎯 Исправление импортов в ${filesToFix.length} файлах...\n`);

filesToFix.forEach(filePath => {
  fixImportsInFile(filePath);
});

console.log(`\n📊 Результат:`);
console.log(`   🔧 Исправлено файлов: ${fixedCount}`);

if (fixedCount > 0) {
  console.log(`\n✅ Импорты исправлены!`);
  console.log('🧪 Проверьте: node scripts/check-errors.cjs');
} else {
  console.log(`\n➖ Дополнительные исправления не потребовались`);
}