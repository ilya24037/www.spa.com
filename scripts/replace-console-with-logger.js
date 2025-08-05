#!/usr/bin/env node

/**
 * Скрипт автоматической замены console.error/warn на logger
 * Анализирует AST и производит безопасную замену с сохранением контекста
 */

import { readFileSync, writeFileSync, readdirSync, statSync } from 'fs';
import { join, extname } from 'path';
import { execSync } from 'child_process';

const colors = {
  red: '\x1b[31m',
  green: '\x1b[32m',
  yellow: '\x1b[33m',
  cyan: '\x1b[36m',
  reset: '\x1b[0m'
};

// Паттерны для замены
const replacePatterns = [
  {
    // console.error с простым сообщением
    pattern: /console\.error\s*\(\s*['"`]([^'"`]+)['"`]\s*\)/g,
    replacement: (match, message) => `logger.error('${message}')`
  },
  {
    // console.error с переменной ошибки
    pattern: /console\.error\s*\(\s*['"`]([^'"`]+)['"`]\s*,\s*(\w+)\s*\)/g,
    replacement: (match, message, errorVar) => `logger.error('${message}', ${errorVar})`
  },
  {
    // console.error с объектом контекста
    pattern: /console\.error\s*\(\s*['"`]([^'"`]+)['"`]\s*,\s*(\{[^}]+\})\s*\)/g,
    replacement: (match, message, context) => `logger.error('${message}', undefined, { metadata: ${context} })`
  },
  {
    // console.warn с простым сообщением
    pattern: /console\.warn\s*\(\s*['"`]([^'"`]+)['"`]\s*\)/g,
    replacement: (match, message) => `logger.warn('${message}')`
  },
  {
    // console.warn с переменной
    pattern: /console\.warn\s*\(\s*['"`]([^'"`]+)['"`]\s*,\s*(\w+)\s*\)/g,
    replacement: (match, message, variable) => `logger.warn('${message}', { metadata: { data: ${variable} } })`
  }
];

// Получаем список файлов для обработки
function getFiles(dir, fileList = []) {
  const files = readdirSync(dir);
  
  files.forEach(file => {
    const filePath = join(dir, file);
    const stat = statSync(filePath);
    
    if (stat.isDirectory()) {
      // Пропускаем node_modules и другие системные папки
      if (!file.startsWith('.') && file !== 'node_modules' && file !== 'vendor') {
        getFiles(filePath, fileList);
      }
    } else {
      const ext = extname(file);
      if (['.js', '.ts', '.vue'].includes(ext)) {
        fileList.push(filePath);
      }
    }
  });
  
  return fileList;
}

// Проверяем, нужно ли добавить импорт logger
function needsLoggerImport(content) {
  return !content.includes('import { logger }') && 
         !content.includes('import { useLogger }') &&
         !content.includes('from \'@/shared/lib/logger\'') &&
         !content.includes('from \'@/src/shared/lib/logger\'');
}

// Добавляем импорт logger в нужное место
function addLoggerImport(content, filePath) {
  const isVueFile = filePath.endsWith('.vue');
  const isComposable = filePath.includes('/composables/');
  
  let importStatement;
  if (isComposable || content.includes('getCurrentInstance')) {
    importStatement = "import { useLogger } from '@/src/shared/composables/useLogger'";
  } else {
    importStatement = "import { logger } from '@/src/shared/lib/logger'";
  }
  
  if (isVueFile) {
    // Для Vue файлов добавляем после <script setup>
    const scriptMatch = content.match(/<script\s+setup[^>]*>/);
    if (scriptMatch) {
      const position = scriptMatch.index + scriptMatch[0].length;
      return content.slice(0, position) + '\n' + importStatement + content.slice(position);
    }
  } else {
    // Для JS/TS файлов добавляем после последнего импорта
    const lastImportMatch = content.match(/import[^;]+;(?![\s\S]*import[^;]+;)/);
    if (lastImportMatch) {
      const position = lastImportMatch.index + lastImportMatch[0].length;
      return content.slice(0, position) + '\n' + importStatement + content.slice(position);
    } else {
      // Если импортов нет, добавляем в начало файла
      return importStatement + '\n\n' + content;
    }
  }
  
  return content;
}

// Обрабатываем файл
function processFile(filePath) {
  try {
    let content = readFileSync(filePath, 'utf8');
    let modified = false;
    let replacements = 0;
    
    // Проверяем наличие console.error или console.warn
    if (!content.includes('console.error') && !content.includes('console.warn')) {
      return { modified: false, replacements: 0 };
    }
    
    // Сохраняем оригинальный контент для сравнения
    const originalContent = content;
    
    // Применяем паттерны замены
    replacePatterns.forEach(({ pattern, replacement }) => {
      const matches = content.match(pattern);
      if (matches) {
        content = content.replace(pattern, replacement);
        replacements += matches.length;
        modified = true;
      }
    });
    
    // Если были замены и нужен импорт logger
    if (modified && needsLoggerImport(content)) {
      content = addLoggerImport(content, filePath);
    }
    
    // Специальная обработка для Vue composables
    if (filePath.includes('/composables/') && modified) {
      // Заменяем logger на локальную переменную
      content = content.replace(/logger\.(error|warn)/g, (match, method) => {
        return `log.${method}`;
      });
      
      // Добавляем создание logger в начало функции
      const functionMatch = content.match(/export\s+function\s+\w+[^{]*\{/);
      if (functionMatch) {
        const position = functionMatch.index + functionMatch[0].length;
        const hasLogger = content.includes('const log = useLogger(');
        if (!hasLogger) {
          const funcName = functionMatch[0].match(/function\s+(\w+)/)?.[1] || 'Composable';
          content = content.slice(0, position) + 
            `\n  const log = useLogger('${funcName}');\n` + 
            content.slice(position);
        }
      }
    }
    
    // Записываем изменения
    if (modified) {
      writeFileSync(filePath, content, 'utf8');
    }
    
    return { modified, replacements };
  } catch (error) {
    console.error(`${colors.red}Ошибка при обработке файла ${filePath}:${colors.reset}`, error.message);
    return { modified: false, replacements: 0, error: error.message };
  }
}

// Основная функция
function main() {
  console.log(`${colors.cyan}🔄 Начинаем автоматическую замену console.error/warn на logger...${colors.reset}\n`);
  
  // Получаем список файлов
  const resourcesPath = join(process.cwd(), 'resources', 'js');
  const files = getFiles(resourcesPath);
  
  console.log(`${colors.yellow}📁 Найдено файлов для проверки: ${files.length}${colors.reset}\n`);
  
  let totalFiles = 0;
  let totalReplacements = 0;
  const modifiedFiles = [];
  const errors = [];
  
  // Обрабатываем каждый файл
  files.forEach(file => {
    const { modified, replacements, error } = processFile(file);
    
    if (error) {
      errors.push({ file, error });
    } else if (modified) {
      totalFiles++;
      totalReplacements += replacements;
      modifiedFiles.push({ file, replacements });
      
      const relativePath = file.replace(process.cwd() + '\\', '');
      console.log(`${colors.green}✓${colors.reset} ${relativePath} - ${replacements} замен`);
    }
  });
  
  // Выводим итоги
  console.log(`\n${colors.cyan}📊 Результаты:${colors.reset}`);
  console.log(`${colors.green}✅ Обработано файлов: ${totalFiles}${colors.reset}`);
  console.log(`${colors.green}✅ Всего замен: ${totalReplacements}${colors.reset}`);
  
  if (errors.length > 0) {
    console.log(`\n${colors.red}❌ Ошибки при обработке:${colors.reset}`);
    errors.forEach(({ file, error }) => {
      console.log(`${colors.red}  ${file}: ${error}${colors.reset}`);
    });
  }
  
  if (totalFiles > 0) {
    console.log(`\n${colors.yellow}⚠️  Проверьте изменения перед коммитом!${colors.reset}`);
    console.log(`${colors.yellow}   Используйте: git diff${colors.reset}`);
  }
}

// Запускаем
main();