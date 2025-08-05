// create-final-components.cjs
const fs = require('fs');
const path = require('path');

const componentsToCreate = [
  {
    path: 'resources/js/src/entities/ad/ui/AdCardList/AdCardList.vue',
    name: 'AdCardList'
  },
  {
    path: 'resources/js/src/entities/ad/ui/AdStatusFilter/AdStatusFilter.vue',
    name: 'AdStatusFilter'
  },
  {
    path: 'resources/js/src/entities/master/ui/MasterCardList/MasterCardList.vue',
    name: 'MasterCardList'
  }
];

componentsToCreate.forEach(({ path: compPath, name }) => {
  const dir = path.dirname(compPath);

  if (!fs.existsSync(dir)) {
    fs.mkdirSync(dir, { recursive: true });
  }

  if (!fs.existsSync(compPath)) {
    const content = `<template>
  <div class="${name.toLowerCase()}">
    <!-- ${name} component -->
    <div v-if="items && items.length > 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
      <div v-for="item in items" :key="item.id" class="bg-white rounded-lg shadow p-4">
        <slot name="item" :item="item">
          <p>{{ item.name || item.title || 'Item' }}</p>
        </slot>
      </div>
    </div>
    <div v-else class="text-center py-8 text-gray-500">
      Нет данных для отображения
    </div>
  </div>
</template>

<script setup lang="ts">
defineProps<{
  items?: any[]
  loading?: boolean
}>()
</script>`;

    fs.writeFileSync(compPath, content);

  }
});

