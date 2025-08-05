#!/usr/bin/env node

/**
 * Скрипт установки git hooks для проекта
 * Создает pre-commit hook для проверки debug кода
 */

import { existsSync, mkdirSync, writeFileSync, chmodSync } from 'fs';
import { join, resolve } from 'path';
import { platform } from 'os';

const colors = {
  red: '\x1b[31m',
  green: '\x1b[32m',
  yellow: '\x1b[33m',
  reset: '\x1b[0m'
};

// Путь к папке .git/hooks
const gitHooksPath = join(process.cwd(), '.git', 'hooks');
const preCommitPath = join(gitHooksPath, 'pre-commit');

// Содержимое pre-commit hook
const preCommitContent = `#!/bin/sh
# Pre-commit hook для проверки debug кода

echo "🔍 Запуск проверки debug кода..."

# Запускаем скрипт проверки
node scripts/check-debug-code.js

# Передаем код возврата
exit $?
`;

// Windows версия pre-commit hook
const preCommitContentWindows = `#!/bin/sh
# Pre-commit hook для проверки debug кода (Windows)

echo "🔍 Запуск проверки debug кода..."

# Запускаем скрипт проверки через node
node.exe scripts/check-debug-code.js

# Передаем код возврата
exit $?
`;

function installHooks() {
  console.log(`${colors.yellow}📦 Установка git hooks...${colors.reset}\n`);

  // Проверяем наличие .git
  if (!existsSync(join(process.cwd(), '.git'))) {
    console.error(`${colors.red}❌ Ошибка: Папка .git не найдена!${colors.reset}`);
    console.error(`${colors.red}   Убедитесь, что вы находитесь в корне git репозитория.${colors.reset}`);
    process.exit(1);
  }

  // Создаем папку hooks если её нет
  if (!existsSync(gitHooksPath)) {
    console.log(`${colors.yellow}📁 Создаем папку .git/hooks...${colors.reset}`);
    mkdirSync(gitHooksPath, { recursive: true });
  }

  // Проверяем существующий pre-commit hook
  if (existsSync(preCommitPath)) {
    console.log(`${colors.yellow}⚠️  Pre-commit hook уже существует, обновляем...${colors.reset}`);
  }

  try {
    // Определяем контент в зависимости от ОС
    const isWindows = platform() === 'win32';
    const content = isWindows ? preCommitContentWindows : preCommitContent;

    // Записываем pre-commit hook
    writeFileSync(preCommitPath, content, 'utf8');

    // На Unix-системах делаем файл исполняемым
    if (!isWindows) {
      chmodSync(preCommitPath, 0o755);
      console.log(`${colors.green}✅ Установлены права на выполнение${colors.reset}`);
    }

    console.log(`${colors.green}✅ Git hooks успешно установлены!${colors.reset}\n`);
    console.log(`${colors.yellow}📋 Проверка будет выполняться перед каждым коммитом:${colors.reset}`);
    console.log(`   - PHP: dd(), dump(), var_dump(), print_r(), die(), exit()`);
    console.log(`   - JS/TS/Vue: console.log(), debugger`);
    console.log(`\n${colors.yellow}💡 Подсказка: console.error() и console.warn() заменены на logger${colors.reset}`);

  } catch (error) {
    console.error(`${colors.red}❌ Ошибка при установке hooks:${colors.reset}`, error.message);
    process.exit(1);
  }
}

// Запускаем установку
installHooks();