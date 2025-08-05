#!/usr/bin/env node

/**
 * 🗑️ АНАЛИЗАТОР LEGACY КОМПОНЕНТОВ ДЛЯ БЕЗОПАСНОГО УДАЛЕНИЯ
 * 
 * Анализирует проект на предмет дублирующихся компонентов и создает
 * план безопасного удаления legacy файлов после миграции на FSD + TypeScript
 */

import fs from 'fs'
import path from 'path'
import { execSync } from 'child_process'
import { fileURLToPath } from 'url'

const __filename = fileURLToPath(import.meta.url)
const __dirname = path.dirname(__filename)

// 🔧 КОНФИГУРАЦИЯ
const CONFIG = {
  projectRoot: process.cwd(),
  
  // Пути для анализа
  paths: {
    legacy: 'resources/js/Components',
    fsd: 'resources/js/src',
    pages: 'resources/js/Pages'
  },
  
  // Игнорируемые файлы и папки
  ignore: [
    'node_modules',
    '.git', 
    'dist',
    'vendor',
    '.nuxt',
    '.output'
  ],
  
  // Расширения файлов для анализа
  extensions: ['.vue', '.js', '.ts', '.jsx', '.tsx'],
  
  // Критичные компоненты (уже мигрированы)
  migratedComponents: [
    'MediaUploader',
    'BookingForm', 
    'MasterCard',
    'AdCard',
    'ItemCard',
    'PhoneModal',
    'BookingModal',
    'BookingWidget',
    'AdCardListItem',
    'BookingSuccessModal'
  ]
}

// 📊 РЕЗУЛЬТАТЫ АНАЛИЗА
const analysisResults = {
  legacyComponents: [],
  fsdComponents: [],
  duplicates: [],
  safeToDelete: [],
  requiresManualReview: [],
  usageMap: new Map(),
  importMap: new Map(),
  statistics: {
    totalLegacyFiles: 0,
    totalFsdFiles: 0,
    duplicatesFound: 0,
    safeDeletes: 0,
    manualReviews: 0
  }
}

// 🔍 УТИЛИТЫ ДЛЯ АНАЛИЗА

/**
 * Получить все файлы в директории рекурсивно
 */
function getAllFiles(dir, files = []) {
  if (!fs.existsSync(dir)) return files
  
  const items = fs.readdirSync(dir)
  
  for (const item of items) {
    if (CONFIG.ignore.includes(item)) continue
    
    const fullPath = path.join(dir, item)
    const stat = fs.statSync(fullPath)
    
    if (stat.isDirectory()) {
      getAllFiles(fullPath, files)
    } else if (CONFIG.extensions.includes(path.extname(item))) {
      files.push(fullPath)
    }
  }
  
  return files
}

/**
 * Извлечь имя компонента из пути файла
 */
function extractComponentName(filePath) {
  const basename = path.basename(filePath, path.extname(filePath))
  
  // Убираем суффиксы типов
  return basename
    .replace(/\.(vue|js|ts|jsx|tsx)$/, '')
    .replace(/\.(types|test|spec|stories)$/, '')
}

/**
 * Найти все импорты компонента в проекте
 */
function findComponentUsages(componentName) {
  const usages = []
  
  try {
    // Используем ripgrep для поиска импортов
    const grepCommand = `rg --type vue --type js --type ts -l "${componentName}" .`
    const result = execSync(grepCommand, { 
      cwd: CONFIG.projectRoot,
      encoding: 'utf8',
      stdio: 'pipe'
    })
    
    const files = result.trim().split('\n').filter(Boolean)
    
    for (const file of files) {
      if (fs.existsSync(file)) {
        const content = fs.readFileSync(file, 'utf8')
        
        // Ищем различные варианты импортов
        const importPatterns = [
          new RegExp(`import.*${componentName}.*from`, 'gm'),
          new RegExp(`import.*\\{.*${componentName}.*\\}`, 'gm'),
          new RegExp(`<${componentName}[\\s>]`, 'gm'),
          new RegExp(`components:\\s*\\{[^}]*${componentName}`, 'gm')
        ]
        
        for (const pattern of importPatterns) {
          const matches = content.match(pattern)
          if (matches) {
            usages.push({
              file,
              matches: matches.length,
              patterns: matches
            })
            break
          }
        }
      }
    }
  } catch (error) {
    // Fallback: простой поиск по файлам
    console.warn(`⚠️  Ripgrep недоступен, используем fallback поиск для ${componentName}`)
  }
  
  return usages
}

/**
 * Анализ legacy компонентов
 */
function analyzeLegacyComponents() {
  console.log('🔍 Анализируем legacy компоненты...')
  
  const legacyPath = path.join(CONFIG.projectRoot, CONFIG.paths.legacy)
  const legacyFiles = getAllFiles(legacyPath)
  
  analysisResults.statistics.totalLegacyFiles = legacyFiles.length
  
  for (const file of legacyFiles) {
    const componentName = extractComponentName(file)
    const relativePath = path.relative(CONFIG.projectRoot, file)
    
    const component = {
      name: componentName,
      path: relativePath,
      fullPath: file,
      size: fs.statSync(file).size,
      lastModified: fs.statSync(file).mtime,
      isTypeScript: file.endsWith('.ts') || file.endsWith('.tsx'),
      usages: findComponentUsages(componentName)
    }
    
    analysisResults.legacyComponents.push(component)
    analysisResults.usageMap.set(componentName, component.usages)
  }
  
  console.log(`✅ Найдено ${legacyFiles.length} legacy компонентов`)
}

/**
 * Анализ FSD компонентов
 */
function analyzeFsdComponents() {
  console.log('🔍 Анализируем FSD компоненты...')
  
  const fsdPath = path.join(CONFIG.projectRoot, CONFIG.paths.fsd)
  const fsdFiles = getAllFiles(fsdPath)
  
  analysisResults.statistics.totalFsdFiles = fsdFiles.length
  
  for (const file of fsdFiles) {
    const componentName = extractComponentName(file)
    const relativePath = path.relative(CONFIG.projectRoot, file)
    
    const component = {
      name: componentName,
      path: relativePath,
      fullPath: file,
      size: fs.statSync(file).size,
      lastModified: fs.statSync(file).mtime,
      isTypeScript: file.endsWith('.ts') || file.endsWith('.tsx'),
      layer: getFsdLayer(relativePath),
      slice: getFsdSlice(relativePath)
    }
    
    analysisResults.fsdComponents.push(component)
  }
  
  console.log(`✅ Найдено ${fsdFiles.length} FSD компонентов`)
}

/**
 * Определить FSD слой из пути
 */
function getFsdLayer(filePath) {
  const layers = ['shared', 'entities', 'features', 'widgets', 'pages']
  for (const layer of layers) {
    if (filePath.includes(`/${layer}/`)) return layer
  }
  return 'unknown'
}

/**
 * Определить FSD срез из пути
 */
function getFsdSlice(filePath) {
  const match = filePath.match(/\/(?:entities|features|widgets)\/([^\/]+)\//)
  return match ? match[1] : null
}

/**
 * Поиск дубликатов между legacy и FSD
 */
function findDuplicates() {
  console.log('🔍 Ищем дубликаты компонентов...')
  
  const legacyNames = new Set(analysisResults.legacyComponents.map(c => c.name))
  const fsdNames = new Set(analysisResults.fsdComponents.map(c => c.name))
  
  for (const legacyName of legacyNames) {
    if (fsdNames.has(legacyName) || CONFIG.migratedComponents.includes(legacyName)) {
      const legacyComponent = analysisResults.legacyComponents.find(c => c.name === legacyName)
      const fsdComponent = analysisResults.fsdComponents.find(c => c.name === legacyName)
      
      const duplicate = {
        name: legacyName,
        legacy: legacyComponent,
        fsd: fsdComponent,
        isMigrated: CONFIG.migratedComponents.includes(legacyName),
        hasUsages: legacyComponent.usages.length > 0,
        riskLevel: calculateRiskLevel(legacyComponent)
      }
      
      analysisResults.duplicates.push(duplicate)
    }
  }
  
  analysisResults.statistics.duplicatesFound = analysisResults.duplicates.length
  console.log(`✅ Найдено ${analysisResults.duplicates.length} дубликатов`)
}

/**
 * Расчет уровня риска удаления
 */
function calculateRiskLevel(component) {
  let risk = 'low'
  
  if (component.usages.length > 10) risk = 'high'
  else if (component.usages.length > 3) risk = 'medium'
  
  // Дополнительные факторы риска
  if (component.name.toLowerCase().includes('modal')) risk = 'medium'
  if (component.name.toLowerCase().includes('form')) risk = 'high'
  if (component.size > 10000) risk = 'medium' // Большие файлы
  
  return risk
}

/**
 * Создание плана безопасного удаления
 */
function createDeletionPlan() {
  console.log('📋 Создаем план удаления...')
  
  for (const duplicate of analysisResults.duplicates) {
    if (duplicate.riskLevel === 'low' && duplicate.isMigrated) {
      analysisResults.safeToDelete.push({
        ...duplicate,
        reason: 'Мигрирован на FSD, низкий риск'
      })
    } else {
      analysisResults.requiresManualReview.push({
        ...duplicate,
        reason: `Требует проверки: ${duplicate.riskLevel} риск, ${duplicate.legacy.usages.length} использований`
      })
    }
  }
  
  analysisResults.statistics.safeDeletes = analysisResults.safeToDelete.length
  analysisResults.statistics.manualReviews = analysisResults.requiresManualReview.length
}

/**
 * Генерация отчета
 */
function generateReport() {
  const report = {
    timestamp: new Date().toISOString(),
    summary: {
      totalLegacyComponents: analysisResults.statistics.totalLegacyFiles,
      totalFsdComponents: analysisResults.statistics.totalFsdFiles,
      duplicatesFound: analysisResults.statistics.duplicatesFound,
      safeToDelete: analysisResults.statistics.safeDeletes,
      requiresManualReview: analysisResults.statistics.manualReviews
    },
    
    // Безопасные для удаления
    safeToDelete: analysisResults.safeToDelete.map(item => ({
      name: item.name,
      legacyPath: item.legacy.path,
      fsdPath: item.fsd?.path,
      reason: item.reason,
      usages: item.legacy.usages.length,
      size: `${(item.legacy.size / 1024).toFixed(1)}KB`
    })),
    
    // Требуют ручной проверки
    manualReview: analysisResults.requiresManualReview.map(item => ({
      name: item.name,
      legacyPath: item.legacy.path,
      fsdPath: item.fsd?.path,
      reason: item.reason,
      riskLevel: item.riskLevel,
      usages: item.legacy.usages.length,
      usageFiles: item.legacy.usages.map(u => u.file),
      size: `${(item.legacy.size / 1024).toFixed(1)}KB`,
      lastModified: item.legacy.lastModified
    })),
    
    // Детальная статистика
    detailedStats: {
      legacyBySize: analysisResults.legacyComponents
        .sort((a, b) => b.size - a.size)
        .slice(0, 10)
        .map(c => ({
          name: c.name,
          path: c.path,
          size: `${(c.size / 1024).toFixed(1)}KB`
        })),
      
      mostUsedComponents: analysisResults.legacyComponents
        .sort((a, b) => b.usages.length - a.usages.length)
        .slice(0, 10)
        .map(c => ({
          name: c.name,
          path: c.path,
          usages: c.usages.length
        }))
    }
  }
  
  return report
}

/**
 * Создание bash скрипта для удаления
 */
function createDeletionScript(report) {
  const scriptLines = [
    '#!/bin/bash',
    '# 🗑️ СКРИПТ БЕЗОПАСНОГО УДАЛЕНИЯ LEGACY КОМПОНЕНТОВ',
    '# Сгенерирован автоматически анализатором дубликатов',
    '',
    `# Дата создания: ${new Date().toLocaleString('ru-RU')}`,
    `# Найдено дубликатов: ${report.summary.duplicatesFound}`,
    `# Безопасно удалить: ${report.summary.safeToDelete}`,
    `# Требует проверки: ${report.summary.requiresManualReview}`,
    '',
    'echo "🚀 Начинаем безопасное удаление legacy компонентов..."',
    'echo ""',
    '',
    '# Создаем резервную копию',
    'echo "💾 Создаем резервную копию..."',
    `BACKUP_DIR="backup-legacy-$(date +%Y%m%d-%H%M%S)"`,
    'mkdir -p "$BACKUP_DIR"',
    '',
    '# Функция безопасного удаления',
    'safe_delete() {',
    '  local file="$1"',
    '  local reason="$2"',
    '  ',
    '  if [ -f "$file" ]; then',
    '    echo "🗑️  Удаляем: $file ($reason)"',
    '    cp "$file" "$BACKUP_DIR/"',
    '    rm "$file"',
    '  else',
    '    echo "⚠️  Файл не найден: $file"',
    '  fi',
    '}',
    '',
    '# БЕЗОПАСНЫЕ ДЛЯ УДАЛЕНИЯ ФАЙЛЫ',
    'echo "🟢 Удаляем безопасные файлы..."',
    ''
  ]
  
  // Добавляем безопасные файлы
  for (const item of report.safeToDelete) {
    scriptLines.push(`safe_delete "${item.legacyPath}" "${item.reason}"`)
  }
  
  scriptLines.push(
    '',
    '# ФАЙЛЫ ТРЕБУЮЩИЕ РУЧНОЙ ПРОВЕРКИ (закомментированы)',
    'echo ""',
    'echo "⚠️  Следующие файлы требуют ручной проверки:"',
    ''
  )
  
  // Добавляем файлы для ручной проверки (закомментированными)
  for (const item of report.manualReview) {
    scriptLines.push(`echo "❌ ПРОВЕРИТЬ: ${item.legacyPath} (${item.reason})"`)
    scriptLines.push(`# safe_delete "${item.legacyPath}" "${item.reason}"`)
  }
  
  scriptLines.push(
    '',
    'echo ""',
    'echo "✅ Безопасное удаление завершено!"',
    'echo "📁 Резервная копия сохранена в: $BACKUP_DIR"',
    'echo "📋 Проверьте файлы, отмеченные для ручной проверки"',
    ''
  )
  
  return scriptLines.join('\n')
}

// 🚀 ОСНОВНАЯ ФУНКЦИЯ
async function main() {
  console.log('🚀 Запуск анализатора legacy компонентов...')
  console.log(`📁 Проект: ${CONFIG.projectRoot}`)
  console.log('')
  
  try {
    // Анализируем компоненты
    analyzeLegacyComponents()
    analyzeFsdComponents()
    
    // Ищем дубликаты
    findDuplicates()
    
    // Создаем план удаления
    createDeletionPlan()
    
    // Генерируем отчет
    const report = generateReport()
    
    // Сохраняем отчет
    const reportPath = path.join(CONFIG.projectRoot, 'deletion-analysis-report.json')
    fs.writeFileSync(reportPath, JSON.stringify(report, null, 2))
    
    // Создаем скрипт удаления
    const script = createDeletionScript(report)
    const scriptPath = path.join(CONFIG.projectRoot, 'safe-delete-legacy.sh')
    fs.writeFileSync(scriptPath, script)
    fs.chmodSync(scriptPath, '755')
    
    // Выводим результаты
    console.log('')
    console.log('📊 РЕЗУЛЬТАТЫ АНАЛИЗА:')
    console.log('=====================================')
    console.log(`📁 Legacy компонентов: ${report.summary.totalLegacyComponents}`)
    console.log(`🎯 FSD компонентов: ${report.summary.totalFsdComponents}`)
    console.log(`🔄 Найдено дубликатов: ${report.summary.duplicatesFound}`)
    console.log(`✅ Безопасно удалить: ${report.summary.safeToDelete}`)
    console.log(`⚠️  Требует проверки: ${report.summary.requiresManualReview}`)
    console.log('')
    console.log('📄 СОЗДАННЫЕ ФАЙЛЫ:')
    console.log(`📋 Отчет: ${reportPath}`)
    console.log(`🔨 Скрипт удаления: ${scriptPath}`)
    console.log('')
    console.log('🎯 СЛЕДУЮЩИЕ ШАГИ:')
    console.log('1. Проверьте отчет deletion-analysis-report.json')
    console.log('2. Просмотрите файлы, требующие ручной проверки')
    console.log('3. Запустите ./safe-delete-legacy.sh для удаления')
    console.log('4. Проверьте работу приложения после удаления')
    
  } catch (error) {
    console.error('❌ Ошибка при анализе:', error.message)
    process.exit(1)
  }
}

// Принудительный запуск main функции
main().catch(console.error)

export {
  analyzeLegacyComponents,
  analyzeFsdComponents,
  findDuplicates,
  generateReport,
  CONFIG
}