// fix-all-imports.cjs - Исправление всех неправильных импортов
const fs = require('fs');
const { execSync } = require('child_process');

console.log('🔧 ИСПРАВЛЕНИЕ ВСЕХ НЕПРАВИЛЬНЫХ ИМПОРТОВ\n');

// Находим все Vue и TS файлы с неправильными импортами
const commands = [
  'find resources/js -name "*.vue" -exec grep -l "import useToast from" {} \\;',
  'find resources/js -name "*.vue" -exec grep -l "import usePageLoading from" {} \\;'
];

let allFiles = new Set();

commands.forEach(cmd => {
  try {
    const result = execSync(cmd, { encoding: 'utf-8' });
    result.split('\n').filter(f => f).forEach(file => allFiles.add(file));
  } catch (err) {
    // Игнорируем ошибки grep если файлы не найдены
  }
});

console.log(`Найдено ${allFiles.size} файлов с неправильными импортами`);

let fixedCount = 0;

// Исправляем каждый файл
Array.from(allFiles).forEach(file => {
  if (!fs.existsSync(file)) return;
  
  let content = fs.readFileSync(file, 'utf-8');
  let changed = false;
  
  // Исправляем useToast импорты
  if (content.includes('import useToast from')) {
    content = content.replace(/import useToast from/g, 'import { useToast } from');
    changed = true;
    console.log(`   ✅ useToast import: ${file}`);
  }
  
  // Исправляем usePageLoading импорты
  if (content.includes('import usePageLoading from')) {
    content = content.replace(/import usePageLoading from/g, 'import { usePageLoading } from');
    changed = true;
    console.log(`   ✅ usePageLoading import: ${file}`);
  }
  
  if (changed) {
    fs.writeFileSync(file, content);
    fixedCount++;
  }
});

console.log(`\n✅ ИСПРАВЛЕНО: ${fixedCount} файлов`);
console.log('\n🎯 СЛЕДУЮЩИЙ ШАГ: Запустить сборку проекта');