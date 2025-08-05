#!/usr/bin/env node

/**
 * Скрипт проверки наличия debug кода перед коммитом
 * Блокирует коммит при наличии запрещенных паттернов
 */

import { execSync } from 'child_process';
import { readFileSync } from 'fs';
import { resolve } from 'path';

// Цвета для консоли
const colors = {
  red: '\x1b[31m',
  green: '\x1b[32m',
  yellow: '\x1b[33m',
  reset: '\x1b[0m'
};

// Паттерны для поиска debug кода
const debugPatterns = {
  php: [
    { pattern: /\bdd\s*\(/g, name: 'dd()' },
    { pattern: /\bdump\s*\(/g, name: 'dump()' },
    { pattern: /\bvar_dump\s*\(/g, name: 'var_dump()' },
    { pattern: /\bprint_r\s*\(/g, name: 'print_r()' },
    { pattern: /\bvar_export\s*\(/g, name: 'var_export()' },
    { pattern: /\bdie\s*\(/g, name: 'die()' },
    { pattern: /\bexit\s*\(/g, name: 'exit()' }
  ],
  js: [
    { pattern: /console\s*\.\s*log\s*\(/g, name: 'console.log()' },
    { pattern: /debugger[\s;]/g, name: 'debugger' }
  ],
  cjs: [
    { pattern: /console\s*\.\s*log\s*\(/g, name: 'console.log()' },
    { pattern: /debugger[\s;]/g, name: 'debugger' }
  ],
  mjs: [
    { pattern: /console\s*\.\s*log\s*\(/g, name: 'console.log()' },
    { pattern: /debugger[\s;]/g, name: 'debugger' }
  ],
  vue: [
    { pattern: /console\s*\.\s*log\s*\(/g, name: 'console.log()' },
    { pattern: /debugger[\s;]/g, name: 'debugger' }
  ],
  ts: [
    { pattern: /console\s*\.\s*log\s*\(/g, name: 'console.log()' },
    { pattern: /debugger[\s;]/g, name: 'debugger' }
  ],
  tsx: [
    { pattern: /console\s*\.\s*log\s*\(/g, name: 'console.log()' },
    { pattern: /debugger[\s;]/g, name: 'debugger' }
  ]
};

// Получаем список измененных файлов
function getStagedFiles() {
  try {
    const files = execSync('git diff --cached --name-only --diff-filter=ACM')
      .toString()
      .trim()
      .split('\n')
      .filter(file => file.length > 0);
    return files;
  } catch (error) {
    console.error(`${colors.red}Ошибка при получении списка файлов:${colors.reset}`, error.message);
    process.exit(1);
  }
}

// Исключения - файлы где debug код разрешен
const allowedDebugFiles = [
  'scripts/replace-console-with-logger.js',
  'scripts/update-ddd-violations.cjs',
  'scripts/check-debug-code.js',
  'scripts/install-git-hooks.js'
];

// Проверяем файл на наличие debug кода
function checkFile(filePath) {
  // Пропускаем файлы из списка исключений
  if (allowedDebugFiles.includes(filePath)) {
    return [];
  }
  
  const extension = filePath.split('.').pop().toLowerCase();
  const patterns = debugPatterns[extension];
  
  if (!patterns) {
    return { hasDebug: false, matches: [] };
  }

  try {
    const content = readFileSync(filePath, 'utf8');
    const lines = content.split('\n');
    const matches = [];

    patterns.forEach(({ pattern, name }) => {
      lines.forEach((line, index) => {
        // Пропускаем закомментированные строки
        const trimmedLine = line.trim();
        if (trimmedLine.startsWith('//') || 
            trimmedLine.startsWith('*') || 
            trimmedLine.startsWith('/*') ||
            trimmedLine.startsWith('#')) {
          return;
        }

        const lineMatches = line.match(pattern);
        if (lineMatches) {
          matches.push({
            file: filePath,
            line: index + 1,
            code: line.trim(),
            debugType: name
          });
        }
      });
    });

    return { hasDebug: matches.length > 0, matches };
  } catch (error) {
    console.error(`${colors.red}Ошибка при чтении файла ${filePath}:${colors.reset}`, error.message);
    return { hasDebug: false, matches: [] };
  }
}

// Основная функция
function main() {
  console.log(`${colors.yellow}🔍 Проверка наличия debug кода...${colors.reset}\n`);

  const stagedFiles = getStagedFiles();
  
  if (stagedFiles.length === 0) {
    console.log(`${colors.green}✅ Нет файлов для проверки${colors.reset}`);
    process.exit(0);
  }

  let totalMatches = [];

  // Проверяем каждый файл
  stagedFiles.forEach(file => {
    const { hasDebug, matches } = checkFile(file);
    if (hasDebug) {
      totalMatches = totalMatches.concat(matches);
    }
  });

  // Выводим результаты
  if (totalMatches.length > 0) {
    console.log(`${colors.red}❌ Найден debug код в следующих файлах:${colors.reset}\n`);
    
    totalMatches.forEach(match => {
      console.log(`${colors.red}${match.file}:${match.line}${colors.reset}`);
      console.log(`   ${match.debugType}: ${match.code}`);
      console.log('');
    });

    console.log(`${colors.red}Всего найдено: ${totalMatches.length} случаев${colors.reset}`);
    console.log(`${colors.yellow}Удалите debug код перед коммитом!${colors.reset}`);
    
    // Подсказка для исключения проверки
    console.log(`\n${colors.yellow}Подсказка: используйте 'git commit --no-verify' чтобы пропустить проверку${colors.reset}`);
    
    process.exit(1);
  }

  console.log(`${colors.green}✅ Debug код не найден. Коммит разрешен!${colors.reset}`);
  process.exit(0);
}

// Запускаем проверку
main();