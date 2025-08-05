// create-missing-build-components.cjs - Создание недостающих компонентов для сборки
const fs = require('fs');
const path = require('path');

// Список недостающих компонентов и их базовое содержимое
const missingComponents = [
  {
    path: 'resources/js/src/entities/ad/ui/AdForm/components/AdFormMediaUpload.vue',
    content: `<template>
  <div>
    <label class="block text-sm font-medium text-gray-700 mb-2">
      Медиа-файлы
    </label>
    <div class="mt-2 border-2 border-dashed border-gray-300 rounded-lg p-6 text-center">
      <p class="text-gray-500">Нажмите для загрузки фото/видео</p>
    </div>
  </div>
</template>

<script setup lang="ts">
defineProps<{ errors?: any }>()
</script>`
  },
  {
    path: 'resources/js/src/entities/ad/ui/AdForm/components/AdFormContacts.vue',
    content: `<template>
  <div>
    <label class="block text-sm font-medium text-gray-700 mb-2">
      Контактная информация
    </label>
    <div class="space-y-3">
      <div>
        <label class="block text-sm text-gray-600 mb-1">Телефон</label>
        <input 
          type="tel" 
          placeholder="+7 (___) ___-__-__"
          class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
        />
      </div>
      <div>
        <label class="block text-sm text-gray-600 mb-1">WhatsApp (необязательно)</label>
        <input 
          type="tel" 
          placeholder="+7 (___) ___-__-__"
          class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
        />
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
defineProps<{ errors?: any }>()
</script>`
  },
  {
    path: 'resources/js/src/widgets/masters-catalog/index.ts',
    content: `export { default as MastersCatalog } from './MastersCatalog.vue'`
  },
  {
    path: 'resources/js/src/widgets/masters-catalog/MastersCatalog.vue',
    content: `<template>
  <div class="masters-catalog">
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
      <slot />
    </div>
  </div>
</template>

<script setup lang="ts">
defineProps<{
  masters?: any[]
  loading?: boolean
}>()
</script>`
  },
  {
    path: 'resources/js/src/shared/ui/atoms/LoadingSpinner/LoadingSpinner.vue',
    content: `<template>
  <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
</template>

<script setup lang="ts">
// Loading spinner component
</script>`
  }
];

// Создаем все недостающие компоненты
let createdCount = 0;

missingComponents.forEach(({ path: filePath, content }) => {
  const dir = path.dirname(filePath);

  // Создаем директорию если её нет
  if (!fs.existsSync(dir)) {
    fs.mkdirSync(dir, { recursive: true });
  }

  // Создаем файл если его нет
  if (!fs.existsSync(filePath)) {
    fs.writeFileSync(filePath, content);

    createdCount++;
  }
});

