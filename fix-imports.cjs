const fs = require('fs')
const path = require('path')

// Маппинг старых путей на новые FSD пути
const importMappings = {
  // Legacy -> FSD mappings
  '@/Components/Cards/ItemCard.vue': '@/src/entities/ad/ui/AdCard/ItemCard.vue',
  '@/Components/Booking/BookingModal.vue': '@/src/entities/booking/ui/BookingModal/BookingModal.vue',
  '@/Components/Masters/MasterCard.vue': '@/src/entities/master/ui/MasterCard/MasterCard.vue',
  '@/Components/UI/Toast.vue': '@/src/shared/ui/molecules/Toast/Toast.vue',
  '@/Components/UI/Modal.vue': '@/src/shared/ui/organisms/Modal/Modal.vue',
  '@/Components/Layout/MainLayout.vue': '@/src/shared/layouts/MainLayout/MainLayout.vue',
  '@/Components/Layout/ProfileLayout.vue': '@/src/shared/layouts/ProfileLayout/ProfileLayout.vue',
  '@/Components/Map/LeafletMap.vue': '@/src/features/map/ui/MapLegacy/LeafletMap.vue',
  '@/Components/Map/RealMap.vue': '@/Components/Map/RealMap.vue',

  // Добавляем недостающие импорты
  '@/widgets/master-profile': '@/src/widgets/master-profile',  
  '../Cards/ItemImage.vue': './ItemImage.vue',
  '../Cards/ItemContent.vue': './ItemContent.vue',
  '../Cards/ItemStats.vue': './ItemStats.vue',
  '../Cards/ItemActions.vue': './ItemActions.vue',
  '../UI/ConfirmModal.vue': '@/src/shared/ui/organisms/Modal/Modal.vue',
  './ui/PhotoGallery': './ui/PhotoGallery/PhotoGallery.vue',
  './ui/PhotoGalleryLegacy': './ui/PhotoGalleryLegacy/PhotoGalleryLegacy.vue'
}

// Исправляем неправильные типы импортов
const typeImportFixes = {
  'import { ErrorDetails }': '// import { ErrorDetails }',
  'readonly': 'Readonly',
  ': Ref<': ': import("vue").Ref<',
  'NodeJS.Timeout': 'number',
  'vue-router': '@vue/router'
}

const fixImportsInFile = (filePath) => {
  if (!fs.existsSync(filePath)) return;

  let content = fs.readFileSync(filePath, 'utf8')
  let modified = false

  // Исправляем пути импортов
  Object.entries(importMappings).forEach(([oldPath, newPath]) => {
    const regex = new RegExp(oldPath.replace(/[.*+?^${}()|[\]\\]/g, '\\$&'), 'g')
    if (content.includes(oldPath)) {
      content = content.replace(regex, newPath)
      modified = true
      }
  })

  // Исправляем типы
  Object.entries(typeImportFixes).forEach(([oldType, newType]) => {
    if (content.includes(oldType)) {
      content = content.replace(new RegExp(oldType.replace(/[.*+?^${}()|[\]\\]/g, '\\$&'), 'g'), newType)
      modified = true
    }
  })

  // Удаляем неиспользуемые импорты
  const unusedImports = ['reactive', 'watch', 'ref', 'onMounted', 'beforeEach']
  unusedImports.forEach(importName => {
    // Проверяем что импорт есть, но не используется
    const importRegex = new RegExp(`import\\s*\\{[^}]*\\b${importName}\\b[^}]*\\}`, 'g')
    const usageRegex = new RegExp(`\\b${importName}\\s*\\(`, 'g')

    if (importRegex.test(content) && !usageRegex.test(content)) {
      content = content.replace(new RegExp(`,?\\s*${importName}`, 'g'), '')
      modified = true
    }
  })

  if (modified) {
    fs.writeFileSync(filePath, content)
  }
}

// Обработать все файлы
const processDirectory = (dir) => {
  if (!fs.existsSync(dir)) return;

  const files = fs.readdirSync(dir)

  files.forEach(file => {
    const filePath = path.join(dir, file)
    const stat = fs.statSync(filePath)

    if (stat.isDirectory()) {
      processDirectory(filePath)
    } else if (file.endsWith('.vue') || file.endsWith('.js') || file.endsWith('.ts')) {
      fixImportsInFile(filePath)
    }
  })
}

// Создаем недостающие компоненты-заглушки
const createMissingComponents = () => {
  const missingComponents = [
    'resources/js/src/entities/ad/ui/AdCard/ItemImage.vue',
    'resources/js/src/entities/ad/ui/AdCard/ItemContent.vue', 
    'resources/js/src/entities/ad/ui/AdCard/ItemStats.vue',
    'resources/js/src/entities/ad/ui/AdCard/ItemActions.vue',
    'resources/js/src/features/gallery/ui/PhotoGalleryLegacy/PhotoGalleryLegacy.vue'
  ]

  missingComponents.forEach(componentPath => {
    if (!fs.existsSync(componentPath)) {
      const dir = path.dirname(componentPath)
      if (!fs.existsSync(dir)) {
        fs.mkdirSync(dir, { recursive: true })
      }

      const componentName = path.basename(componentPath, '.vue')
      const template = `<template>
  <div class="${componentName.toLowerCase()}">
    <!-- ${componentName} component stub -->
    <slot />
  </div>
</template>

<script setup lang="ts">
// ${componentName} stub component
defineProps<{
  [key: string]: any
}>()
</script>`

      fs.writeFileSync(componentPath, template)
      }
  })
}

// Создаем недостающие компоненты
createMissingComponents()

// Исправляем импорты
processDirectory('./resources/js')

