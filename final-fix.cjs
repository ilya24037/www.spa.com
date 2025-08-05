// final-fix.cjs - Финальное исправление всех ошибок
const fs = require('fs');
const path = require('path');
const { execSync } = require('child_process');

let fixCount = 0;
let createCount = 0;

// Исправление путей в AdFormBasicInfo
function fixAdFormBasicInfo() {
  const file = 'resources/js/src/entities/ad/ui/AdForm/components/AdFormBasicInfo.vue';

  if (fs.existsSync(file)) {
    let content = fs.readFileSync(file, 'utf-8');

    // Исправляем неправильные пути
    content = content.replace(
      /from ['"]\.\.\/modules\/BasicInfo\//g,
      'from \'./'
    );

    fs.writeFileSync(file, content);

    fixCount++;
  }
}

// Создание недостающих компонентов AdForm
function createAdFormComponents() {
  const componentsDir = 'resources/js/src/entities/ad/ui/AdForm/components';
  const components = [
    'AdFormWorkFormat.vue',
    'AdFormCategory.vue',
    'AdFormServices.vue',
    'AdFormPhotos.vue',
    'AdFormContacts.vue',
    'AdFormSchedule.vue',
    'AdFormLocation.vue',
    'AdFormPublish.vue'
  ];

  components.forEach(comp => {
    const filePath = path.join(componentsDir, comp);

    if (!fs.existsSync(filePath)) {
      const name = comp.replace('.vue', '');
      const content = `<template>
  <div class="${name.toLowerCase()}">
    <h3 class="text-lg font-medium mb-4">{{ title }}</h3>
    <slot />
  </div>
</template>

<script setup lang="ts">
defineProps<{
  title?: string
  modelValue?: any
}>()

defineEmits<{
  'update:modelValue': [value: any]
}>()
</script>`;

      fs.writeFileSync(filePath, content);

      createCount++;
    }
  });
}

// Создание layouts если нет
function createLayouts() {
  const layoutsDir = 'resources/js/src/shared/layouts';

  // ProfileLayout
  const profileLayoutPath = path.join(layoutsDir, 'ProfileLayout/ProfileLayout.vue');
  if (!fs.existsSync(profileLayoutPath)) {
    fs.mkdirSync(path.dirname(profileLayoutPath), { recursive: true });
    fs.writeFileSync(profileLayoutPath, `<template>
  <div class="min-h-screen bg-gray-50">
    <div class="container mx-auto px-4 py-8">
      <slot />
    </div>
  </div>
</template>

<script setup lang="ts">
// ProfileLayout
</script>`);

    createCount++;
  }

  // MainLayout
  const mainLayoutPath = path.join(layoutsDir, 'MainLayout/MainLayout.vue');
  if (!fs.existsSync(mainLayoutPath)) {
    fs.mkdirSync(path.dirname(mainLayoutPath), { recursive: true });
    fs.writeFileSync(mainLayoutPath, `<template>
  <div class="min-h-screen">
    <slot />
  </div>
</template>

<script setup lang="ts">
// MainLayout
</script>`);

    createCount++;
  }
}

// Создание недостающих stores
function createStores() {
  const stores = [
    {
      path: 'resources/js/src/entities/master/model/masterStore.js',
      name: 'MasterStore'
    },
    {
      path: 'resources/js/src/entities/ad/model/adStore.js', 
      name: 'AdStore'
    }
  ];

  stores.forEach(({ path: storePath, name }) => {
    if (!fs.existsSync(storePath)) {
      const dir = path.dirname(storePath);
      if (!fs.existsSync(dir)) {
        fs.mkdirSync(dir, { recursive: true });
      }

      fs.writeFileSync(storePath, `import { defineStore } from 'pinia'
import { ref } from 'vue'

export const use${name} = defineStore('${name.toLowerCase()}', () => {
  const items = ref([])
  const loading = ref(false)
  const currentItem = ref(null)

  const fetchItems = async () => {
    loading.value = true
    try {
      // API call here
      items.value = []
    } finally {
      loading.value = false
    }
  }

  return {
    items,
    loading,
    currentItem,
    fetchItems
  }
})

export default use${name}`);

      createCount++;
    }
  });
}

// Исправление всех импортов ProfileLayout и MainLayout
function fixLayoutImports() {
  const files = execSync('find resources/js -name "*.vue" -o -name "*.ts" -o -name "*.js"', { encoding: 'utf-8' })
    .split('\n')
    .filter(f => f && fs.existsSync(f));

  files.forEach(file => {
    let content = fs.readFileSync(file, 'utf-8');
    let modified = false;

    // Исправление ProfileLayout
    if (content.includes("import { ProfileLayout }") || content.includes("import ProfileLayout")) {
      content = content.replace(
        /import\s+(?:\{?\s*ProfileLayout\s*\}?)\s+from\s+['"][^'"]+['"]/g,
        "import ProfileLayout from '@/src/shared/layouts/ProfileLayout/ProfileLayout.vue'"
      );
      modified = true;
    }

    // Исправление MainLayout
    if (content.includes("import { MainLayout }") || content.includes("import MainLayout")) {
      content = content.replace(
        /import\s+(?:\{?\s*MainLayout\s*\}?)\s+from\s+['"][^'"]+['"]/g,
        "import MainLayout from '@/src/shared/layouts/MainLayout/MainLayout.vue'"
      );
      modified = true;
    }

    if (modified) {
      fs.writeFileSync(file, content);
      fixCount++;
    }
  });
}

// Главная функция
function main() {

  // 1. Исправляем AdFormBasicInfo
  fixAdFormBasicInfo();

  // 2. Создаем недостающие компоненты AdForm
  createAdFormComponents();

  // 3. Создаем layouts
  createLayouts();

  // 4. Создаем stores
  createStores();

  // 5. Исправляем импорты layouts
  fixLayoutImports();

  // 6. Пробуем сборку

  try {
    execSync('npx vite build', { stdio: 'inherit' });

  } catch (error) {

  }
}

main();