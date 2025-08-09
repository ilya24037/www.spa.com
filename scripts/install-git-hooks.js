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

// Содержимое pre-commit hook (кросс‑платформенно)
// 1) Запуск скрипта проверки debug кода (не блокирует, если его нет)
// 2) Запуск PowerShell‑хука для сборки контекст‑паков (не блокирует)
const preCommitContent = `#!/bin/sh
# Pre-commit hook: debug-check + context-pack (non-blocking)

echo "🔍 debug-check..."
if command -v node >/dev/null 2>&1; then
  node scripts/check-debug-code.js || true
fi

echo "🧠 context-pack..."
if command -v pwsh >/dev/null 2>&1; then
  pwsh -NoLogo -NoProfile -File scripts/git-hooks/pre-commit.ps1 || true
elif command -v powershell >/dev/null 2>&1; then
  powershell -NoLogo -NoProfile -ExecutionPolicy Bypass -File scripts/git-hooks/pre-commit.ps1 || true
fi

exit 0
`;

// Windows версия identична — используем тот же контент
const preCommitContentWindows = preCommitContent;

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
    console.log(`${colors.yellow}📋 Pre-commit выполняет:${colors.reset}`);
    console.log(`   1) Проверку debug-кода (если есть scripts/check-debug-code.js)`);
    console.log(`   2) Сборку контекст‑паков по затронутым модулям (PowerShell)`);

  } catch (error) {
    console.error(`${colors.red}❌ Ошибка при установке hooks:${colors.reset}`, error.message);
    process.exit(1);
  }
}

// Запускаем установку
installHooks();