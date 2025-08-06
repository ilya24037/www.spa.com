#!/usr/bin/env node

const fs = require('fs');
const path = require('path');

console.log('🔧 Исправление TS2777 errors (increment/decrement operations)...\n');

let fixedCount = 0;

// Паттерны для исправления TS2777 errors
const incrementFixes = [
  // Исправляем ++variable?.property на ++variable.property
  {
    pattern: /\+\+(\w+)\?\./g,
    replacement: '++$1.',
    description: 'Убираем ? из ++variable?.property'
  },
  
  // Исправляем --variable?.property на --variable.property  
  {
    pattern: /--(\w+)\?\./g,
    replacement: '--$1.',
    description: 'Убираем ? из --variable?.property'
  },
  
  // Исправляем variable?.property++ на variable.property++
  {
    pattern: /(\w+)\?\.(\w+)\+\+/g,
    replacement: '$1.$2++',
    description: 'Убираем ? из variable?.property++'
  },
  
  // Исправляем variable?.property-- на variable.property--
  {
    pattern: /(\w+)\?\.(\w+)--/g,
    replacement: '$1.$2--',
    description: 'Убираем ? из variable?.property--'
  },
  
  // Исправляем более сложные случаи с вложенными свойствами
  {
    pattern: /(\w+)\?\.(\w+)\?\.(\w+)\+\+/g,
    replacement: '$1.$2.$3++',
    description: 'Убираем ? из variable?.prop?.prop++'
  },
  
  {
    pattern: /(\w+)\?\.(\w+)\?\.(\w+)--/g,
    replacement: '$1.$2.$3--',
    description: 'Убираем ? из variable?.prop?.prop--'
  }
];

function fixIncrementsInFile(filePath) {
  try {
    if (!fs.existsSync(filePath)) return false;

    let content = fs.readFileSync(filePath, 'utf8');
    const originalContent = content;
    let fixes = [];

    // Применяем исправления
    incrementFixes.forEach(fix => {
      const matches = content.match(fix.pattern);
      if (matches && matches.length > 0) {
        content = content.replace(fix.pattern, fix.replacement);
        fixes.push(`${fix.description} (${matches.length})`);
      }
    });

    if (content !== originalContent) {
      fs.writeFileSync(filePath, content, 'utf8');
      console.log(`   ✅ ${path.basename(filePath)}: ${fixes.length} типов исправлений`);
      fixes.forEach(fix => console.log(`      • ${fix}`));
      fixedCount++;
      return true;
    }

    return false;
  } catch (error) {
    console.log(`   ❌ ${path.basename(filePath)}: ${error.message}`);
    return false;
  }
}

// Получение файлов с TS2777 ошибками
function getFilesWithTS2777Errors() {
  const files = [];
  
  // Основные проблемные файлы из нашего анализа
  const knownProblemFiles = [
    'resources/js/src/entities/ad/model/adStore.ts',
    'resources/js/src/entities/booking/model/bookingStore.ts', 
    'resources/js/src/entities/master/model/masterStore.ts'
  ];
  
  // Проверяем существование файлов
  knownProblemFiles.forEach(filePath => {
    if (fs.existsSync(filePath)) {
      files.push(filePath);
    }
  });
  
  return files;
}

// Получаем файлы для исправления
const filesToFix = getFilesWithTS2777Errors();

console.log(`🎯 Исправление TS2777 errors в ${filesToFix.length} файлах...\n`);

filesToFix.forEach(filePath => {
  console.log(`🔧 ${filePath}`);
  fixIncrementsInFile(filePath);
});

console.log(`\n📊 Результат:`);
console.log(`   🔧 Исправлено файлов: ${fixedCount}`);

if (fixedCount > 0) {
  console.log(`\n✅ TS2777 errors исправлены!`);
  console.log('🧪 Проверьте результат: node scripts/check-errors.cjs');
} else {
  console.log(`\n➖ Дополнительные исправления не потребовались`);
}