#!/usr/bin/env node

/**
 * Chrome DevTools MCP Helper Script
 * Вспомогательный скрипт для работы с Chrome DevTools MCP
 */

const { execSync } = require('child_process');
const fs = require('fs');
const path = require('path');

// Команды для работы с Chrome DevTools MCP
const commands = {
  // Проверить производительность
  performance: () => {
    console.log('📊 Для проверки производительности используйте Claude:');
    console.log('');
    console.log('Команда: "Check the performance of http://localhost:8000"');
    console.log('');
    console.log('Claude автоматически:');
    console.log('- Откроет Chrome браузер');
    console.log('- Запишет performance trace');
    console.log('- Проанализирует Core Web Vitals (LCP, CLS, INP)');
    console.log('- Предложит улучшения');
  },

  // Тестировать компоненты
  test: () => {
    console.log('🧪 Примеры тестовых сценариев:');
    console.log('');
    console.log('1. Booking Calendar:');
    console.log('   "Test booking calendar at http://localhost:8000/masters/1"');
    console.log('');
    console.log('2. Search:');
    console.log('   "Test search functionality at http://localhost:8000"');
    console.log('');
    console.log('3. Mobile View:');
    console.log('   "Test mobile responsiveness at http://localhost:8000"');
  },

  // Headless режим
  headless: () => {
    console.log('🤖 Запуск в headless режиме:');
    console.log('');
    try {
      execSync('npx chrome-devtools-mcp@latest --headless', { stdio: 'inherit' });
    } catch (error) {
      console.error('Ошибка при запуске:', error.message);
    }
  },

  // Показать статус
  status: () => {
    console.log('✅ Chrome DevTools MCP Status:');
    console.log('');

    // Проверить Node.js версию
    const nodeVersion = process.version;
    console.log(`Node.js: ${nodeVersion} (требуется 22+)`);

    // Проверить установку MCP
    const claudeConfigPath = path.join(process.env.USERPROFILE || process.env.HOME, '.claude.json');
    if (fs.existsSync(claudeConfigPath)) {
      const config = JSON.parse(fs.readFileSync(claudeConfigPath, 'utf8'));
      if (config.mcpServers && config.mcpServers['chrome-devtools']) {
        console.log('MCP Server: ✅ Установлен');
        console.log('Command:', config.mcpServers['chrome-devtools'].command,
                    config.mcpServers['chrome-devtools'].args.join(' '));
      } else {
        console.log('MCP Server: ❌ Не найден');
        console.log('Установите командой: claude mcp add chrome-devtools npx chrome-devtools-mcp@latest');
      }
    } else {
      console.log('Claude Config: ❌ Не найден');
    }

    // Проверить Chrome
    try {
      execSync('where chrome', { stdio: 'pipe' });
      console.log('Chrome: ✅ Установлен');
    } catch {
      console.log('Chrome: ⚠️  Проверьте установку Chrome браузера');
    }
  },

  // Показать помощь
  help: () => {
    console.log('🚀 Chrome DevTools MCP Helper');
    console.log('');
    console.log('Использование: npm run browser:[command]');
    console.log('');
    console.log('Доступные команды:');
    console.log('  performance - Инструкции для проверки производительности');
    console.log('  test        - Примеры тестовых сценариев');
    console.log('  headless    - Запуск в headless режиме');
    console.log('  status      - Проверка статуса установки');
    console.log('  help        - Показать эту справку');
    console.log('');
    console.log('Примеры:');
    console.log('  npm run browser:performance');
    console.log('  npm run browser:test');
    console.log('  npm run browser:status');
  }
};

// Получить команду из аргументов
const command = process.argv[2] || 'help';

// Выполнить команду
if (commands[command]) {
  commands[command]();
} else {
  console.log(`❌ Неизвестная команда: ${command}`);
  console.log('');
  commands.help();
}