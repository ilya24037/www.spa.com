// scripts/migrate-map-imports.js

const glob = require('glob')
const fs = require('fs')
const path = require('path')

console.log('🔄 Starting map imports migration...')

// Находим все файлы с импортами старой карты
const files = glob.sync('resources/js/**/*.{vue,ts,js}', {
  ignore: ['**/node_modules/**', '**/map/**']
})

const OLD_IMPORTS = [
  '@/src/features/map/ui/YandexMapBase/YandexMapBase.vue',
  '@/src/features/map/ui/UniversalMap/UniversalMap.vue',
  '@/src/features/map/composables/useMapController',
  '@/src/features/map/composables/useMapWithMasters'
]

const NEW_IMPORT = '@/src/shared/ui/molecules/YandexMapPicker/YandexMap.vue'

let updatedCount = 0

console.log(`📁 Scanning ${files.length} files...`)

files.forEach(file => {
  let content = fs.readFileSync(file, 'utf8')
  let hasChanges = false
  
  OLD_IMPORTS.forEach(oldImport => {
    if (content.includes(oldImport)) {
      content = content.replace(new RegExp(oldImport, 'g'), NEW_IMPORT)
      hasChanges = true
      console.log(`✅ Updated: ${file}`)
      console.log(`   ${oldImport} → ${NEW_IMPORT}`)
    }
  })
  
  if (hasChanges) {
    fs.writeFileSync(file, content)
    updatedCount++
  }
})

console.log(`\n📊 Migration complete: ${updatedCount} files updated`)

// Создаем отчет
const reportPath = 'map-migration-report.txt'
const report = `Map Imports Migration Report
Generated: ${new Date().toISOString()}

Files updated: ${updatedCount}
Old imports replaced:
${OLD_IMPORTS.map(imp => `- ${imp}`).join('\n')}

New import:
- ${NEW_IMPORT}

Files processed: ${files.length}
`

fs.writeFileSync(reportPath, report)
console.log(`📄 Report saved to: ${reportPath}`)