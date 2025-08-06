#!/usr/bin/env node

const fs = require('fs');
const path = require('path');

console.log('🔧 Исправление TS2779 errors (optional property assignments)...\n');

let fixedCount = 0;

// Паттерны для исправления TS2779 errors
const assignmentFixes = [
  // Исправляем присваивания к ref объектам
  {
    pattern: /(\w+)\?\.(value)\s*=\s*/g,
    replacement: '$1.$2 = ',
    description: 'Убираем ? из присваиваний к ref.value'
  },
  
  // Исправляем присваивания к массивам ref
  {
    pattern: /(\w+)\?\.(value)\[([^\]]+)\]\s*=\s*/g,
    replacement: '$1.$2[$3] = ',
    description: 'Убираем ? из присваиваний к ref.value[index]'
  },
  
  // Исправляем push/unshift операции к ref arrays
  {
    pattern: /(\w+)\?\.(value)\.push\(/g,
    replacement: '$1.$2.push(',
    description: 'Убираем ? из ref.value.push()'
  },
  
  {
    pattern: /(\w+)\?\.(value)\.unshift\(/g,
    replacement: '$1.$2.unshift(',
    description: 'Убираем ? из ref.value.unshift()'
  },
  
  // Исправляем Object.assign к reactive объектам
  {
    pattern: /Object\?\.(assign)\(/g,
    replacement: 'Object.$1(',
    description: 'Убираем ? из Object.assign()'
  },
  
  // Исправляем increment/decrement операции
  {
    pattern: /(\w+)\?\.(value)\+\+/g,
    replacement: '$1.$2++',
    description: 'Убираем ? из ref.value++'
  },
  
  {
    pattern: /(\w+)\?\.(value)--/g,
    replacement: '$1.$2--',
    description: 'Убираем ? из ref.value--'
  },
  
  {
    pattern: /\+\+(\w+)\?\.(value)/g,
    replacement: '++$1.$2',
    description: 'Убираем ? из ++ref.value'
  },
  
  {
    pattern: /--(\w+)\?\.(value)/g,
    replacement: '--$1.$2',
    description: 'Убираем ? из --ref.value'
  },
  
  // Исправляем += -= *= /= операции
  {
    pattern: /(\w+)\?\.(value)\s*\+=\s*/g,
    replacement: '$1.$2 += ',
    description: 'Убираем ? из ref.value +='
  },
  
  {
    pattern: /(\w+)\?\.(value)\s*-=\s*/g,
    replacement: '$1.$2 -= ',
    description: 'Убираем ? из ref.value -='
  }
];

function fixAssignmentsInFile(filePath) {
  try {
    if (!fs.existsSync(filePath)) return false;

    let content = fs.readFileSync(filePath, 'utf8');
    const originalContent = content;
    let fixes = [];

    // Применяем исправления
    assignmentFixes.forEach(fix => {
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

// Получение файлов с TS2779 ошибками
function getFilesWithTS2779Errors() {
  const files = [];
  
  // Основные проблемные файлы из нашего анализа
  const knownProblemFiles = [
    'resources/js/src/entities/ad/model/adStore.ts',
    'resources/js/src/entities/booking/model/bookingStore.ts', 
    'resources/js/src/entities/master/model/masterStore.ts',
    'resources/js/src/shared/composables/useForm.ts',
    'resources/js/src/shared/ui/molecules/Forms/composables/useForm.ts'
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
const filesToFix = getFilesWithTS2779Errors();

console.log(`🎯 Исправление TS2779 errors в ${filesToFix.length} файлах...\n`);

filesToFix.forEach(filePath => {
  console.log(`🔧 ${filePath}`);
  fixAssignmentsInFile(filePath);
});

console.log(`\n📊 Результат:`);
console.log(`   🔧 Исправлено файлов: ${fixedCount}`);

if (fixedCount > 0) {
  console.log(`\n✅ TS2779 errors исправлены!`);
  console.log('🧪 Проверьте результат: node scripts/check-errors.cjs');
} else {
  console.log(`\n➖ Дополнительные исправления не потребовались`);
}