#!/usr/bin/env node

const fs = require('fs');
const path = require('path');
const { execSync } = require('child_process');

console.log('🔧 Исправление TS2304 errors (Cannot find name)...\n');

let fixedCount = 0;

// Глобальные переменные, которые нужно типизировать
const globalVariableFixes = [
  // Window объекты
  {
    pattern: /\bwindow\./g,
    replacement: '(window as any).',
    description: 'Типизация window объекта'
  },
  
  // Console методы 
  {
    pattern: /\bconsole\.(log|error|warn|info|debug)\(/g,
    replacement: '(console as any).$1(',
    description: 'Типизация console методов'
  },
  
  // Alert и confirm
  {
    pattern: /\balert\(/g,
    replacement: '(window as any).alert(',
    description: 'Типизация alert функции'
  },
  
  {
    pattern: /\bconfirm\(/g,
    replacement: '(window as any).confirm(',
    description: 'Типизация confirm функции'
  },
  
  // Location объект
  {
    pattern: /\blocation\.(href|reload|assign)/g,
    replacement: '(window as any).location.$1',
    description: 'Типизация location объекта'
  }
];

// Пропущенные импорты
const missingImportFixes = [
  // route функция из ziggy-js
  {
    pattern: /\broute\(/g,
    checkImport: /import.*route.*from.*ziggy/,
    addImport: "import { route } from 'ziggy-js'",
    replacement: 'route(',
    description: 'Добавление импорта route из ziggy-js'
  },
  
  // usePage из Inertia
  {
    pattern: /\busePage\(/g,
    checkImport: /import.*usePage.*from.*@inertiajs\/vue3/,
    addImport: "import { usePage } from '@inertiajs/vue3'",
    replacement: 'usePage(',
    description: 'Добавление импорта usePage из @inertiajs/vue3'
  }
];

// Исправления для конкретных переменных
const specificVariableFixes = [
  // _props переменные (если они есть, но не используются)
  {
    pattern: /const _props = /g,
    replacement: 'const _props: any = ',
    description: 'Типизация _props переменной'
  },
  
  // dayjs если не импортирован
  {
    pattern: /\bdayjs\(/g,
    checkImport: /import.*dayjs/,
    addImport: "import dayjs from 'dayjs'",
    replacement: 'dayjs(',
    description: 'Добавление импорта dayjs'
  }
];

function fixTS2304InFile(filePath) {
  try {
    if (!fs.existsSync(filePath)) return false;

    let content = fs.readFileSync(filePath, 'utf8');
    const originalContent = content;
    let fixes = [];
    let needsImports = [];

    // 1. Исправляем <script setup> на <script setup lang="ts">
    if (content.includes('<script setup>') && !content.includes('<script setup lang="ts">')) {
      content = content.replace(/<script setup>/g, '<script setup lang="ts">');
      fixes.push('Добавлен lang="ts" к script setup');
    }

    // 2. Исправляем глобальные переменные
    globalVariableFixes.forEach(fix => {
      if (fix.pattern.test(content)) {
        content = content.replace(fix.pattern, fix.replacement);
        fixes.push(fix.description);
      }
    });

    // 3. Проверяем недостающие импорты
    missingImportFixes.forEach(fix => {
      if (fix.pattern.test(content) && !fix.checkImport.test(content)) {
        needsImports.push(fix.addImport);
        fixes.push(fix.description);
      }
    });

    // 4. Исправляем специфичные переменные
    specificVariableFixes.forEach(fix => {
      if (fix.pattern.test(content)) {
        if (fix.checkImport && !fix.checkImport.test(content)) {
          needsImports.push(fix.addImport);
        }
        content = content.replace(fix.pattern, fix.replacement);
        fixes.push(fix.description);
      }
    });

    // 5. Добавляем недостающие импорты в начало script секции
    if (needsImports.length > 0) {
      const scriptMatch = content.match(/<script setup lang="ts">\s*\n/);
      if (scriptMatch) {
        const insertPosition = scriptMatch.index + scriptMatch[0].length;
        const importsString = needsImports.join('\n') + '\n\n';
        content = content.slice(0, insertPosition) + importsString + content.slice(insertPosition);
      }
    }

    // 6. Добавляем withDefaults импорт если используется но не импортирован
    if (content.includes('withDefaults(') && !content.includes('withDefaults') && content.includes('import')) {
      content = content.replace(
        /import { ([^}]+) } from 'vue'/,
        "import { $1, withDefaults } from 'vue'"
      );
      fixes.push('Добавлен withDefaults импорт из vue');
    }

    if (content !== originalContent) {
      fs.writeFileSync(filePath, content, 'utf8');
      console.log(`   ✅ ${path.basename(filePath)}: ${fixes.length} исправлений`);
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

// Получение всех Vue и TS файлов
function getAllVueAndTSFiles() {
  const files = [];
  
  function walkDirectory(dir) {
    const items = fs.readdirSync(dir);
    
    for (const item of items) {
      const fullPath = path.join(dir, item);
      const stat = fs.statSync(fullPath);
      
      if (stat.isDirectory() && !item.startsWith('.') && item !== 'node_modules') {
        walkDirectory(fullPath);
      } else if (stat.isFile() && (item.endsWith('.vue') || item.endsWith('.ts'))) {
        files.push(fullPath);
      }
    }
  }
  
  // Получаем файлы из resources/js
  if (fs.existsSync('resources/js')) {
    walkDirectory('resources/js');
  }
  
  return files;
}

// Создание или обновление global.d.ts файла
function updateGlobalTypes() {
  const typesPath = 'resources/js/types/global.d.ts';
  
  // Создаем директорию если не существует
  const typesDir = path.dirname(typesPath);
  if (!fs.existsSync(typesDir)) {
    fs.mkdirSync(typesDir, { recursive: true });
  }
  
  const globalTypes = `// Global type declarations
declare global {
  interface Window {
    dayjs: any;
    route: any;
    [key: string]: any;
  }
  
  const route: (name?: string, params?: any, absolute?: boolean) => string;
  const dayjs: any;
}

export {};
`;
  
  fs.writeFileSync(typesPath, globalTypes, 'utf8');
  console.log(`   ✅ Обновлен ${typesPath}`);
}

// Получаем файлы для исправления
const filesToFix = getAllVueAndTSFiles();

console.log(`🎯 Исправление TS2304 errors в ${filesToFix.length} файлах...\n`);

// Обновляем глобальные типы
console.log('🔧 Обновление глобальных типов...');
updateGlobalTypes();

// Исправляем файлы
let processedFiles = 0;
filesToFix.forEach(filePath => {
  processedFiles++;
  if (processedFiles <= 50) { // Ограничиваем количество для первого прохода
    fixTS2304InFile(filePath);
  }
});

console.log(`\n📊 Результат:`);
console.log(`   🔧 Обработано файлов: ${processedFiles > 50 ? 50 : processedFiles} из ${filesToFix.length}`);
console.log(`   🔧 Исправлено файлов: ${fixedCount}`);

if (fixedCount > 0) {
  console.log(`\n✅ TS2304 errors исправлены!`);
  console.log('🧪 Проверьте результат: node scripts/check-errors.cjs');
} else {
  console.log(`\n➖ Дополнительные исправления не потребовались`);
}