#!/usr/bin/env node

const { execSync } = require('child_process');

console.log('🔍 Проверка TypeScript ошибок...\n');

try {
  // Пробуем vue-tsc без --skipLibCheck
  const output = execSync('npx vue-tsc --noEmit', { 
    encoding: 'utf8', 
    stdio: 'pipe',
    timeout: 30000
  });
  console.log('✅ Ошибок TypeScript не найдено!');
} catch (error) {
  const stderr = error.stderr || error.stdout || '';
  
  if (stderr.includes('parseJsonConfigFileContent')) {
    console.log('⚠️ vue-tsc имеет проблемы, попробуем альтернативный способ...\n');
    
    // Используем более простую проверку
    try {
      const simpleCheck = execSync('npx tsc --noEmit --allowJs false --checkJs false', {
        encoding: 'utf8',
        stdio: 'pipe',
        timeout: 20000
      });
      console.log('✅ Базовая проверка TypeScript пройдена!');
    } catch (tscError) {
      const tscOutput = tscError.stderr || tscError.stdout || '';
      const lines = tscOutput.split('\n');
      const errorLines = lines.filter(line => line.includes(': error TS'));
      
      console.log(`📊 Найдено ошибок TypeScript: ${errorLines.length}`);
      
      if (errorLines.length > 0) {
        console.log('\n🔻 Первые 10 ошибок:');
        errorLines.slice(0, 10).forEach((line, index) => {
          console.log(`${index + 1}. ${line}`);
        });
        
        if (errorLines.length > 10) {
          console.log(`\n... и еще ${errorLines.length - 10} ошибок`);
        }
      }
    }
  } else {
    // Обрабатываем вывод vue-tsc
    const lines = stderr.split('\n');
    const errorLines = lines.filter(line => line.includes(': error TS'));
    
    console.log(`📊 Найдено ошибок TypeScript: ${errorLines.length}`);
    
    if (errorLines.length > 0) {
      console.log('\n🔻 Первые 10 ошибок:');
      errorLines.slice(0, 10).forEach((line, index) => {
        console.log(`${index + 1}. ${line}`);
      });
      
      if (errorLines.length > 10) {
        console.log(`\n... и еще ${errorLines.length - 10} ошибок`);
      }
      
      // Анализ типов ошибок
      const errorTypes = {};
      errorLines.forEach(line => {
        const match = line.match(/error (TS\d+):/);
        if (match) {
          const code = match[1];
          errorTypes[code] = (errorTypes[code] || 0) + 1;
        }
      });
      
      console.log('\n📈 Статистика по типам ошибок:');
      Object.entries(errorTypes)
        .sort(([,a], [,b]) => b - a)
        .forEach(([code, count]) => {
          console.log(`   ${code}: ${count} ошибок`);
        });
    }
  }
}