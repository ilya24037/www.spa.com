#!/usr/bin/env node

const fs = require('fs');
const path = require('path');

console.log('🔧 Исправление синтаксических ошибок...\n');

let fixedCount = 0;

// Неправильные паттерны, которые добавил предыдущий скрипт
const syntaxFixes = [
  // Исправляем new Intl?. -> new Intl.
  {
    pattern: /new Intl\?\./g,
    replacement: 'new Intl.',
    description: 'Исправление new Intl?. -> new Intl.'
  },
  
  // Исправляем Math?. -> Math.
  {
    pattern: /Math\?\./g,
    replacement: 'Math.',
    description: 'Исправление Math?. -> Math.'
  },
  
  // Исправляем Array?. -> Array.
  {
    pattern: /Array\?\./g,
    replacement: 'Array.',
    description: 'Исправление Array?. -> Array.'
  },
  
  // Исправляем URL?. -> URL.
  {
    pattern: /URL\?\./g,
    replacement: 'URL.',
    description: 'Исправление URL?. -> URL.'
  },
  
  // Исправляем console?. -> console.
  {
    pattern: /console\?\./g,
    replacement: 'console.',
    description: 'Исправление console?. -> console.'
  },
  
  // Исправляем parseFloat -> parseFloat
  {
    pattern: /parseFloat\(/g,
    replacement: 'parseFloat(',
    description: 'Исправление parseFloat'
  },
  
  // Исправляем двойные вопросительные знаки в вычислениях
  {
    pattern: /(\d+)\?\?(\d+)/g,
    replacement: '$1.$2',
    description: 'Исправление чисел с ?'
  }
];

function fixSyntaxInFile(filePath) {
  try {
    if (!fs.existsSync(filePath)) return false;

    let content = fs.readFileSync(filePath, 'utf8');
    const originalContent = content;
    let fixes = [];

    // Применяем исправления
    syntaxFixes.forEach(fix => {
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

// Файлы с синтаксическими ошибками
const problemFiles = [
  'resources/js/src/entities/ad/ui/AdCard/AdCard.vue',
  'resources/js/src/entities/ad/ui/AdCard/AdCardListItem.vue', 
  'resources/js/src/entities/booking/model/bookingStore.ts',
  'resources/js/src/shared/ui/molecules/Forms/features/MediaForm.vue'
];

console.log('🎯 Исправление синтаксических ошибок в файлах...\n');

problemFiles.forEach(filePath => {
  if (fs.existsSync(filePath)) {
    console.log(`🔧 ${filePath}`);
    fixSyntaxInFile(filePath);
  } else {
    console.log(`⚠️  Файл не найден: ${filePath}`);
  }
});

console.log(`\n📊 Результат:`);
console.log(`   🔧 Исправлено файлов: ${fixedCount}`);

if (fixedCount > 0) {
  console.log(`\n✅ Синтаксические ошибки исправлены!`);
  console.log('🧪 Проверьте: node scripts/check-errors.cjs');
} else {
  console.log(`\n➖ Дополнительные исправления не потребовались`);
}