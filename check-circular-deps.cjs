// Проверка циклических зависимостей в organisms
const fs = require('fs');
const path = require('path');

const organismsPath = './resources/js/src/shared/ui/organisms';

function findImports(filePath) {
  const content = fs.readFileSync(filePath, 'utf8');
  const imports = [];
  
  // Находим все импорты
  const importRegex = /import\s+(?:.*?\s+from\s+)?['"](.+?)['"]/g;
  let match;
  
  while ((match = importRegex.exec(content)) !== null) {
    imports.push(match[1]);
  }
  
  return imports;
}

function checkFile(filePath, relativePath) {
  console.log(`\nПроверяю: ${relativePath}`);
  const imports = findImports(filePath);
  
  imports.forEach(imp => {
    if (imp.includes('molecules')) {
      console.log(`  ⚠️  Импорт из molecules: ${imp}`);
    }
    if (imp.includes('@/src/shared/ui') && !imp.includes('atoms')) {
      console.log(`  📦 Импорт из shared/ui: ${imp}`);
    }
  });
}

function walkDir(dir, baseDir = dir) {
  const files = fs.readdirSync(dir);
  
  files.forEach(file => {
    const filePath = path.join(dir, file);
    const stat = fs.statSync(filePath);
    
    if (stat.isDirectory()) {
      walkDir(filePath, baseDir);
    } else if (file.endsWith('.vue') || file.endsWith('.ts')) {
      const relativePath = path.relative(baseDir, filePath);
      checkFile(filePath, relativePath);
    }
  });
}

console.log('=== Поиск потенциальных циклических зависимостей в organisms ===\n');
walkDir(organismsPath);
console.log('\n=== Проверка завершена ===');