// fix-syntax-errors.cjs - Исправление синтаксических ошибок после замен
const fs = require('fs');
const path = require('path');

// Исправляем конкретные ошибки
const fixes = [
  // Исправляем пустые импорты { ref } -> ref
  {
    pattern: /import\s*\{\s*,\s*/g,
    replacement: 'import { ',
    description: 'Fixing empty imports'
  },

  // Исправляем неправильные ref декларации
  {
    pattern: /const\s+(\w+)\s*=\s*<([^>]+)>\s*\(/g,
    replacement: 'const $1 = ref<$2>(',
    description: 'Fixing ref declarations'
  },

  // Исправляем неправильные readonly
  {
    pattern: /\s+Readonly/g,
    replacement: ' readonly',
    description: 'Fixing readonly keywords'
  },

  // Исправляем href -> href
  {
    pattern: /\.h\s*=/g,
    replacement: '.href =',
    description: 'Fixing href assignments'
  },

  // Исправляем :h= -> :href=
  {
    pattern: /:h\s*=/g,
    replacement: ':href=',
    description: 'Fixing href attributes'
  },

  // Исправляем ref="input" -> ref="input"
  {
    pattern: /class="[^"]*"="input"/g,
    replacement: 'class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" ref="input"',
    description: 'Fixing ref attributes'
  },

  // Исправляем perred -> preferred
  {
    pattern: /:perred-countries/g,
    replacement: ':preferred-countries',
    description: 'Fixing preferred countries'
  }
];

// Находим все файлы для исправления
function getAllFiles(dir, extensions = ['.vue', '.ts', '.js']) {
  let files = [];

  if (!fs.existsSync(dir)) return files;

  const items = fs.readdirSync(dir);

  items.forEach(item => {
    const fullPath = path.join(dir, item);
    const stat = fs.statSync(fullPath);

    if (stat.isDirectory() && !item.includes('node_modules')) {
      files = files.concat(getAllFiles(fullPath, extensions));
    } else if (extensions.some(ext => item.endsWith(ext))) {
      files.push(fullPath);
    }
  });

  return files;
}

// Исправляем файлы
const filesToFix = [
  ...getAllFiles('resources/js/Pages'),
  ...getAllFiles('resources/js/Components'),
  ...getAllFiles('resources/js/src'),
  ...getAllFiles('resources/js/types')
];

let fixedCount = 0;

filesToFix.forEach(filePath => {
  if (!fs.existsSync(filePath)) return;

  let content = fs.readFileSync(filePath, 'utf8');
  let modified = false;

  fixes.forEach(({ pattern, replacement, description }) => {
    if (pattern.test(content)) {
      content = content.replace(pattern, replacement);
      modified = true;
    }
  });

  if (modified) {
    fs.writeFileSync(filePath, content);

    fixedCount++;
  }
});

