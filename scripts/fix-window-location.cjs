const fs = require('fs');
const path = require('path');

console.log('🔧 Исправляем ошибки с window.location...\n');

const filesToFix = [
  'resources/js/Pages/Auth/Login.vue',
  'resources/js/Pages/Auth/Register.vue', 
  'resources/js/Pages/Bookings/Index.vue',
  'resources/js/Pages/Draft/Show.vue'
];

const fixes = [
  // Исправляем двойное касты window
  { 
    pattern: /\(window as any\)\.\(window as any\)\.location/g, 
    replacement: '(window as any).location',
    description: 'Исправление двойного приведения типа window'
  }
];

let totalFixed = 0;

filesToFix.forEach(filePath => {
  const fullPath = path.join(process.cwd(), filePath);
  
  if (!fs.existsSync(fullPath)) {
    console.log(`⚠️  Файл не найден: ${filePath}`);
    return;
  }
  
  let content = fs.readFileSync(fullPath, 'utf8');
  let fileFixed = 0;
  
  fixes.forEach(fix => {
    const matches = content.match(fix.pattern);
    if (matches) {
      content = content.replace(fix.pattern, fix.replacement);
      fileFixed += matches.length;
      console.log(`✅ ${filePath}: ${fix.description} (${matches.length} раз)`);
    }
  });
  
  if (fileFixed > 0) {
    fs.writeFileSync(fullPath, content, 'utf8');
    totalFixed += fileFixed;
  }
});

console.log(`\n✨ Всего исправлений: ${totalFixed}`);