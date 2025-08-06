/**
 * Скрипт миграции CSS классов на БЭМ именование
 * Автоматизирует переход к новой CSS архитектуре
 */

import fs from 'fs'
import path from 'path'
import { fileURLToPath } from 'url'

const __filename = fileURLToPath(import.meta.url)
const __dirname = path.dirname(__filename)

// Карта миграции классов
const migrationMap = new Map([
  // Кнопки
  ['btn', 'button'],
  ['btn-primary', 'button button--primary'],
  ['btn-secondary', 'button button--secondary'],
  ['btn-success', 'button button--success'],
  ['btn-warning', 'button button--warning'],
  ['btn-danger', 'button button--error'],
  ['btn-sm', 'button button--small'],
  ['btn-lg', 'button button--large'],
  ['btn-block', 'button button--block'],
  
  // Карточки
  ['card', 'card'],
  ['card-header', 'card__header'],
  ['card-body', 'card__body'],
  ['card-footer', 'card__footer'],
  ['card-title', 'card__title'],
  ['card-text', 'card__description'],
  ['card-img', 'card__image'],
  
  // Состояния
  ['active', 'is-active'],
  ['disabled', 'is-disabled'],
  ['hidden', 'is-hidden'],
  ['loading', 'is-loading'],
  ['selected', 'is-selected'],
  ['visible', 'is-visible'],
  
  // Формы
  ['form-control', 'form-input'],
  ['form-group', 'form-field'],
  ['form-label', 'form-field__label'],
  ['form-error', 'form-field__error has-error'],
  ['form-success', 'form-field__success has-success'],
  
  // Утилиты
  ['text-center', 'u-text-center'],
  ['text-left', 'u-text-left'],
  ['text-right', 'u-text-right'],
  ['d-flex', 'u-flex'],
  ['d-block', 'u-block'],
  ['d-none', 'u-hidden'],
  
  // Специфичные для SPA Platform
  ['master-card', 'master-card'],
  ['service-card', 'service-card'],
  ['booking-form', 'booking-form'],
  ['profile-header', 'profile__header'],
  ['profile-avatar', 'profile__avatar'],
  ['profile-info', 'profile__info'],
])

// Конфигурация
const config = {
  sourceDir: path.join(__dirname, '../resources'),
  backupDir: path.join(__dirname, '../storage/css-migration-backup'),
  extensions: ['.vue', '.html', '.php', '.css'],
  excludeDirs: ['node_modules', '.git', 'vendor', 'storage'],
  dryRun: false, // Установить в true для тестового запуска
}

/**
 * Создание резервной копии
 */
function createBackup() {
  if (!fs.existsSync(config.backupDir)) {
    fs.mkdirSync(config.backupDir, { recursive: true })
  }
  
  const timestamp = new Date().toISOString().replace(/[:.]/g, '-')
  const backupPath = path.join(config.backupDir, `backup-${timestamp}`)
  
  console.log(`📦 Создание резервной копии в: ${backupPath}`)
  
  // Копируем весь resources директорий
  copyDirectory(config.sourceDir, backupPath)
  
  return backupPath
}

/**
 * Рекурсивное копирование директории
 */
function copyDirectory(src, dest) {
  if (!fs.existsSync(dest)) {
    fs.mkdirSync(dest, { recursive: true })
  }
  
  const entries = fs.readdirSync(src, { withFileTypes: true })
  
  for (const entry of entries) {
    const srcPath = path.join(src, entry.name)
    const destPath = path.join(dest, entry.name)
    
    if (entry.isDirectory()) {
      if (!config.excludeDirs.includes(entry.name)) {
        copyDirectory(srcPath, destPath)
      }
    } else {
      fs.copyFileSync(srcPath, destPath)
    }
  }
}

/**
 * Получение всех файлов для обработки
 */
function getAllFiles(dir, files = []) {
  const entries = fs.readdirSync(dir, { withFileTypes: true })
  
  for (const entry of entries) {
    const fullPath = path.join(dir, entry.name)
    
    if (entry.isDirectory()) {
      if (!config.excludeDirs.includes(entry.name)) {
        getAllFiles(fullPath, files)
      }
    } else {
      const ext = path.extname(entry.name)
      if (config.extensions.includes(ext)) {
        files.push(fullPath)
      }
    }
  }
  
  return files
}

/**
 * Миграция CSS классов в файле
 */
function migrateFile(filePath) {
  const content = fs.readFileSync(filePath, 'utf8')
  let updatedContent = content
  let changesMade = 0
  
  // Применяем все замены из карты миграции
  for (const [oldClass, newClass] of migrationMap) {
    // Паттерны для поиска классов
    const patterns = [
      // class="old-class"
      new RegExp(`class="([^"]*\\s)?${escapeRegex(oldClass)}(\\s[^"]*)?"`,'g'),
      // class='old-class'
      new RegExp(`class='([^']*\\s)?${escapeRegex(oldClass)}(\\s[^']*)?'`,'g'),
      // :class="'old-class'"
      new RegExp(`:class="'([^']*\\s)?${escapeRegex(oldClass)}(\\s[^']*)?'"`,'g'),
      // CSS селекторы .old-class
      new RegExp(`\\.${escapeRegex(oldClass)}(?![\\w-])`, 'g'),
    ]
    
    patterns.forEach(pattern => {
      const matches = updatedContent.match(pattern)
      if (matches) {
        updatedContent = updatedContent.replace(pattern, (match) => {
          changesMade++
          if (match.includes('class=')) {
            return match.replace(new RegExp(`\\b${escapeRegex(oldClass)}\\b`, 'g'), newClass)
          } else {
            // CSS селектор
            return match.replace(new RegExp(`\\.${escapeRegex(oldClass)}`, 'g'), `.${newClass.split(' ')[0]}`)
          }
        })
      }
    })
  }
  
  // Сохраняем изменения
  if (changesMade > 0 && !config.dryRun) {
    fs.writeFileSync(filePath, updatedContent, 'utf8')
  }
  
  return { changesMade, filePath }
}

/**
 * Экранирование специальных символов для RegExp
 */
function escapeRegex(string) {
  return string.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')
}

/**
 * Генерация отчета о миграции
 */
function generateReport(results, backupPath) {
  const reportPath = path.join(config.backupDir, 'migration-report.md')
  
  let report = `# CSS БЭМ Миграция - Отчет\n\n`
  report += `**Дата:** ${new Date().toLocaleString('ru-RU')}\n`
  report += `**Резервная копия:** ${backupPath}\n`
  report += `**Режим:** ${config.dryRun ? 'Тестовый запуск' : 'Применение изменений'}\n\n`
  
  const totalFiles = results.length
  const changedFiles = results.filter(r => r.changesMade > 0).length
  const totalChanges = results.reduce((sum, r) => sum + r.changesMade, 0)
  
  report += `## Статистика\n\n`
  report += `- **Обработано файлов:** ${totalFiles}\n`
  report += `- **Изменено файлов:** ${changedFiles}\n`
  report += `- **Всего замен:** ${totalChanges}\n\n`
  
  if (changedFiles > 0) {
    report += `## Измененные файлы\n\n`
    results
      .filter(r => r.changesMade > 0)
      .sort((a, b) => b.changesMade - a.changesMade)
      .forEach(result => {
        const relativePath = path.relative(config.sourceDir, result.filePath)
        report += `- **${relativePath}** - ${result.changesMade} замен\n`
      })
  }
  
  report += `\n## Карта миграции\n\n`
  report += `| Старый класс | Новый класс (БЭМ) |\n`
  report += `|--------------|-------------------|\n`
  
  for (const [oldClass, newClass] of migrationMap) {
    report += `| \`.${oldClass}\` | \`.${newClass}\` |\n`
  }
  
  report += `\n## Следующие шаги\n\n`
  report += `1. Проверьте измененные файлы\n`
  report += `2. Протестируйте приложение\n`
  report += `3. Удалите старые CSS классы\n`
  report += `4. Обновите документацию\n`
  
  fs.writeFileSync(reportPath, report, 'utf8')
  
  return reportPath
}

/**
 * Основная функция миграции
 */
async function runMigration() {
  console.log('🚀 Запуск CSS БЭМ миграции...\n')
  
  // Проверяем режим
  if (config.dryRun) {
    console.log('⚠️  ТЕСТОВЫЙ РЕЖИМ - изменения не будут применены\n')
  }
  
  // Создаем резервную копию
  const backupPath = createBackup()
  
  // Получаем все файлы
  console.log('📁 Поиск файлов для обработки...')
  const files = getAllFiles(config.sourceDir)
  console.log(`   Найдено файлов: ${files.length}\n`)
  
  // Обрабатываем файлы
  console.log('🔄 Обработка файлов...')
  const results = []
  
  for (const filePath of files) {
    const result = migrateFile(filePath)
    results.push(result)
    
    if (result.changesMade > 0) {
      const relativePath = path.relative(config.sourceDir, result.filePath)
      console.log(`   ✅ ${relativePath} - ${result.changesMade} замен`)
    }
  }
  
  // Генерируем отчет
  console.log('\n📊 Генерация отчета...')
  const reportPath = generateReport(results, backupPath)
  
  // Итоговая статистика
  const totalChanges = results.reduce((sum, r) => sum + r.changesMade, 0)
  const changedFiles = results.filter(r => r.changesMade > 0).length
  
  console.log('\n✅ Миграция завершена!')
  console.log(`   Изменено файлов: ${changedFiles}`)
  console.log(`   Всего замен: ${totalChanges}`)
  console.log(`   Отчет: ${reportPath}`)
  console.log(`   Резервная копия: ${backupPath}`)
  
  if (config.dryRun) {
    console.log('\n⚠️  Для применения изменений запустите с dryRun: false')
  } else {
    console.log('\n🎉 Изменения применены! Проверьте работу приложения.')
  }
}

// Запуск миграции
if (import.meta.url === `file://${process.argv[1]}`) {
  runMigration().catch(console.error)
}

export { runMigration, migrationMap, config }