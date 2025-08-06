const { execSync } = require('child_process');

console.log('🔍 Анализ самых проблемных файлов по TypeScript ошибкам...\n');

try {
  // Получаем все ошибки
  const output = execSync('npm run build', { 
    encoding: 'utf8', 
    stdio: 'pipe',
    timeout: 60000 
  });
  console.log('✅ Ошибок не найдено!');
} catch (error) {
  const stderr = error.stderr || error.stdout || '';
  const lines = stderr.split('\n');
  const errorLines = lines.filter(line => line.includes(': error TS'));
  
  console.log(`📊 Всего найдено ошибок: ${errorLines.length}\n`);
  
  // Группируем ошибки по файлам
  const fileErrors = {};
  
  errorLines.forEach(line => {
    const match = line.match(/^(.+?)\(\d+,\d+\):\s*error\s*(TS\d+):\s*(.+)$/);
    if (match) {
      const [, filePath, errorCode, errorMessage] = match;
      const fileName = filePath.replace(/^.*[\\\/]/, ''); // Получаем только имя файла
      
      if (!fileErrors[filePath]) {
        fileErrors[filePath] = {
          fileName,
          fullPath: filePath,
          errors: [],
          count: 0
        };
      }
      
      fileErrors[filePath].errors.push({
        code: errorCode,
        message: errorMessage.substring(0, 80) + '...'
      });
      fileErrors[filePath].count++;
    }
  });
  
  // Сортируем файлы по количеству ошибок
  const sortedFiles = Object.values(fileErrors)
    .sort((a, b) => b.count - a.count)
    .slice(0, 15); // Топ-15 файлов
  
  console.log('🔥 ТОП-15 самых проблемных файлов:\n');
  
  sortedFiles.forEach((file, index) => {
    console.log(`${index + 1}. 📁 ${file.fileName}`);
    console.log(`   📍 ${file.fullPath}`);
    console.log(`   ❌ ${file.count} ошибок`);
    
    // Показываем топ-3 типа ошибок в файле
    const errorCounts = {};
    file.errors.forEach(err => {
      errorCounts[err.code] = (errorCounts[err.code] || 0) + 1;
    });
    
    const topErrors = Object.entries(errorCounts)
      .sort(([,a], [,b]) => b - a)
      .slice(0, 3);
    
    console.log(`   🔸 Основные ошибки: ${topErrors.map(([code, count]) => `${code}(${count})`).join(', ')}`);
    console.log('');
  });
  
  // Анализ по типам ошибок
  console.log('📈 Статистика по типам ошибок:');
  const errorTypeStats = {};
  errorLines.forEach(line => {
    const match = line.match(/error (TS\d+):/);
    if (match) {
      const code = match[1];
      errorTypeStats[code] = (errorTypeStats[code] || 0) + 1;
    }
  });
  
  const sortedErrorTypes = Object.entries(errorTypeStats)
    .sort(([,a], [,b]) => b - a)
    .slice(0, 10);
  
  sortedErrorTypes.forEach(([code, count]) => {
    const percentage = ((count / errorLines.length) * 100).toFixed(1);
    console.log(`   ${code}: ${count} ошибок (${percentage}%)`);
  });
  
  console.log(`\n🎯 Рекомендация: начать рефакторинг с файла "${sortedFiles[0].fileName}"`);
  console.log(`   Исправив его, уберем ${sortedFiles[0].count} ошибок (${((sortedFiles[0].count / errorLines.length) * 100).toFixed(1)}% от общего числа)`);
}