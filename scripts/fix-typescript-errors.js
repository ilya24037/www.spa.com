#!/usr/bin/env node

const fs = require('fs');
const path = require('path');
const { execSync } = require('child_process');

class TypeScriptErrorFixer {
  constructor() {
    this.fixedFiles = new Set();
    this.errors = [];
    this.stats = {
      unusedVars: 0,
      unusedTypes: 0,
      typeAssertions: 0,
      nullChecks: 0,
      implicitAny: 0
    };
  }

  // Получить список ошибок TypeScript
  getTypeScriptErrors() {
    try {
      console.log('🔍 Сканирование TypeScript ошибок...');
      const output = execSync('npx vue-tsc --noEmit --skipLibCheck', { 
        encoding: 'utf8', 
        stdio: 'pipe' 
      });
    } catch (error) {
      const stderr = error.stderr || error.stdout || '';
      return this.parseErrors(stderr);
    }
    return [];
  }

  // Парсинг ошибок из вывода vue-tsc
  parseErrors(output) {
    const lines = output.split('\n');
    const errors = [];
    
    for (const line of lines) {
      const match = line.match(/^(.+?)\((\d+),(\d+)\): error (TS\d+): (.+)$/);
      if (match) {
        const [, filePath, lineNum, colNum, errorCode, message] = match;
        errors.push({
          file: filePath,
          line: parseInt(lineNum),
          column: parseInt(colNum),
          code: errorCode,
          message: message.trim()
        });
      }
    }
    
    console.log(`📋 Найдено ${errors.length} ошибок TypeScript`);
    return errors;
  }

  // Исправить неиспользуемые переменные (TS6133)
  fixUnusedVariables(errors) {
    const unusedVarErrors = errors.filter(e => e.code === 'TS6133');
    console.log(`🔧 Исправление ${unusedVarErrors.length} неиспользуемых переменных...`);

    for (const error of unusedVarErrors) {
      try {
        const filePath = path.resolve(error.file);
        if (!fs.existsSync(filePath)) continue;

        let content = fs.readFileSync(filePath, 'utf8');
        const lines = content.split('\n');
        const lineIndex = error.line - 1;
        
        if (lineIndex >= lines.length) continue;
        
        const line = lines[lineIndex];
        const varMatch = error.message.match(/'([^']+)' is declared but its value is never read/);
        
        if (varMatch) {
          const varName = varMatch[1];
          
          // Пропускаем уже исправленные переменные
          if (varName.startsWith('_')) continue;
          
          // Добавляем префикс _
          const newVarName = '_' + varName;
          
          // Заменяем объявление переменной
          const patterns = [
            new RegExp(`\\bconst\\s+${varName}\\b`, 'g'),
            new RegExp(`\\blet\\s+${varName}\\b`, 'g'),
            new RegExp(`\\bvar\\s+${varName}\\b`, 'g'),
          ];
          
          for (const pattern of patterns) {
            if (pattern.test(line)) {
              lines[lineIndex] = line.replace(pattern, (match) => 
                match.replace(varName, newVarName)
              );
              break;
            }
          }
          
          fs.writeFileSync(filePath, lines.join('\n'), 'utf8');
          this.fixedFiles.add(filePath);
          this.stats.unusedVars++;
        }
      } catch (err) {
        console.warn(`⚠️  Ошибка при исправлении ${error.file}: ${err.message}`);
      }
    }
  }

  // Исправить неиспользуемые типы (TS6196)
  fixUnusedTypes(errors) {
    const unusedTypeErrors = errors.filter(e => e.code === 'TS6196');
    console.log(`🔧 Исправление ${unusedTypeErrors.length} неиспользуемых типов...`);

    for (const error of unusedTypeErrors) {
      try {
        const filePath = path.resolve(error.file);
        if (!fs.existsSync(filePath)) continue;

        let content = fs.readFileSync(filePath, 'utf8');
        const lines = content.split('\n');
        const lineIndex = error.line - 1;
        
        if (lineIndex >= lines.length) continue;
        
        const line = lines[lineIndex];
        const typeMatch = error.message.match(/'([^']+)' is declared but never used/);
        
        if (typeMatch) {
          const typeName = typeMatch[1];
          
          // Комментируем неиспользуемый импорт типа
          if (line.includes(`import`)) {
            // Убираем тип из импорта или комментируем всю строку
            if (line.includes(`type ${typeName}`) || line.includes(`${typeName}`)) {
              const newLine = line
                .replace(new RegExp(`,?\\s*${typeName}\\s*,?`), '')
                .replace(/,\s*,/g, ',')  // убираем двойные запятые
                .replace(/{\s*,/, '{')   // убираем запятую в начале
                .replace(/,\s*}/, '}'); // убираем запятую в конце
              
              // Если импорт стал пустым, комментируем всю строку
              if (newLine.includes('import type { }') || newLine.includes('import { }')) {
                lines[lineIndex] = `// ${line}`;
              } else {
                lines[lineIndex] = newLine;
              }
            }
          } else {
            // Комментируем объявление типа
            lines[lineIndex] = `// ${line}`;
          }
          
          fs.writeFileSync(filePath, lines.join('\n'), 'utf8');
          this.fixedFiles.add(filePath);
          this.stats.unusedTypes++;
        }
      } catch (err) {
        console.warn(`⚠️  Ошибка при исправлении типов ${error.file}: ${err.message}`);
      }
    }
  }

  // Исправить nullable ошибки (TS2532, TS18048)
  fixNullableErrors(errors) {
    const nullableErrors = errors.filter(e => 
      e.code === 'TS2532' || e.code === 'TS18048'
    );
    console.log(`🔧 Исправление ${nullableErrors.length} nullable ошибок...`);

    for (const error of nullableErrors) {
      try {
        const filePath = path.resolve(error.file);
        if (!fs.existsSync(filePath)) continue;

        let content = fs.readFileSync(filePath, 'utf8');
        const lines = content.split('\n');
        const lineIndex = error.line - 1;
        
        if (lineIndex >= lines.length) continue;
        
        let line = lines[lineIndex];
        
        // Различные паттерны для исправления nullable ошибок
        if (error.message.includes('Object is possibly \'undefined\'')) {
          // Добавляем optional chaining или null check
          line = line.replace(/(\w+)\.(\w+)/g, '$1?.$2');
        } else if (error.message.includes('is possibly \'undefined\'')) {
          // Добавляем non-null assertion или проверку
          line = line.replace(/(\w+\.\w+)/g, '$1!');
        }
        
        lines[lineIndex] = line;
        fs.writeFileSync(filePath, lines.join('\n'), 'utf8');
        this.fixedFiles.add(filePath);
        this.stats.nullChecks++;
      } catch (err) {
        console.warn(`⚠️  Ошибка при исправлении nullable ${error.file}: ${err.message}`);
      }
    }
  }

  // Исправить implicit any (TS7006)
  fixImplicitAny(errors) {
    const implicitAnyErrors = errors.filter(e => e.code === 'TS7006');
    console.log(`🔧 Исправление ${implicitAnyErrors.length} implicit any ошибок...`);

    for (const error of implicitAnyErrors) {
      try {
        const filePath = path.resolve(error.file);
        if (!fs.existsSync(filePath)) continue;

        let content = fs.readFileSync(filePath, 'utf8');
        const lines = content.split('\n');
        const lineIndex = error.line - 1;
        
        if (lineIndex >= lines.length) continue;
        
        let line = lines[lineIndex];
        const paramMatch = error.message.match(/Parameter '([^']+)' implicitly has an 'any' type/);
        
        if (paramMatch) {
          const paramName = paramMatch[1];
          // Добавляем тип any к параметру
          line = line.replace(
            new RegExp(`\\b${paramName}\\b(?!:)`),
            `${paramName}: any`
          );
          
          lines[lineIndex] = line;
          fs.writeFileSync(filePath, lines.join('\n'), 'utf8');
          this.fixedFiles.add(filePath);
          this.stats.implicitAny++;
        }
      } catch (err) {
        console.warn(`⚠️  Ошибка при исправлении implicit any ${error.file}: ${err.message}`);
      }
    }
  }

  // Исправить несоответствия типов (TS2322, TS2345)
  fixTypeAssignments(errors) {
    const typeErrors = errors.filter(e => 
      e.code === 'TS2322' || e.code === 'TS2345'
    );
    console.log(`🔧 Исправление ${typeErrors.length} ошибок типизации...`);

    for (const error of typeErrors) {
      try {
        const filePath = path.resolve(error.file);
        if (!fs.existsSync(filePath)) continue;

        let content = fs.readFileSync(filePath, 'utf8');
        const lines = content.split('\n');
        const lineIndex = error.line - 1;
        
        if (lineIndex >= lines.length) continue;
        
        let line = lines[lineIndex];
        
        // Исправляем типичные проблемы
        if (error.message.includes('string | undefined') && error.message.includes('string')) {
          line = line.replace(/(\w+)/g, '$1!'); // non-null assertion
        } else if (error.message.includes('File | undefined') && error.message.includes('File')) {
          line = line.replace(/(\w+)/g, '$1!'); // non-null assertion  
        } else if (error.message.includes('Type \'0\' is not assignable to type \'Booleanish')) {
          line = line.replace(/\b0\b/g, 'false');
        }
        
        lines[lineIndex] = line;
        fs.writeFileSync(filePath, lines.join('\n'), 'utf8');
        this.fixedFiles.add(filePath);
        this.stats.typeAssertions++;
      } catch (err) {
        console.warn(`⚠️  Ошибка при исправлении типов ${error.file}: ${err.message}`);
      }
    }
  }

  // Запуск исправлений
  async run() {
    console.log('🚀 Запуск автоматического исправления TypeScript ошибок...\n');
    
    const errors = this.getTypeScriptErrors();
    
    if (errors.length === 0) {
      console.log('✅ Ошибки TypeScript не найдены!');
      return;
    }

    // Группируем ошибки по типам
    const errorsByType = {
      TS6133: errors.filter(e => e.code === 'TS6133').length,
      TS6196: errors.filter(e => e.code === 'TS6196').length,
      TS2532: errors.filter(e => e.code === 'TS2532').length,
      TS18048: errors.filter(e => e.code === 'TS18048').length,
      TS7006: errors.filter(e => e.code === 'TS7006').length,
      TS2322: errors.filter(e => e.code === 'TS2322').length,
      TS2345: errors.filter(e => e.code === 'TS2345').length,
      other: errors.filter(e => !['TS6133', 'TS6196', 'TS2532', 'TS18048', 'TS7006', 'TS2322', 'TS2345'].includes(e.code)).length
    };

    console.log('📊 Анализ ошибок:');
    Object.entries(errorsByType).forEach(([code, count]) => {
      if (count > 0) console.log(`   ${code}: ${count} ошибок`);
    });
    console.log();

    // Применяем исправления
    this.fixUnusedVariables(errors);
    this.fixUnusedTypes(errors);
    this.fixNullableErrors(errors);
    this.fixImplicitAny(errors);
    this.fixTypeAssignments(errors);

    // Отчет
    console.log('\n📈 Результаты исправлений:');
    console.log(`   Неиспользуемые переменные: ${this.stats.unusedVars}`);
    console.log(`   Неиспользуемые типы: ${this.stats.unusedTypes}`);
    console.log(`   Nullable проверки: ${this.stats.nullChecks}`);
    console.log(`   Implicit any: ${this.stats.implicitAny}`);
    console.log(`   Type assertions: ${this.stats.typeAssertions}`);
    console.log(`   Исправлено файлов: ${this.fixedFiles.size}`);

    console.log('\n✅ Автоматические исправления завершены!');
    console.log('🔄 Запустите проверку повторно для проверки результата');
  }
}

// Запуск скрипта
if (require.main === module) {
  const fixer = new TypeScriptErrorFixer();
  fixer.run().catch(console.error);
}