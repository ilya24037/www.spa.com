// fix-organisms-imports.cjs
const fs = require('fs');

const files = [
  'resources/js/Pages/Favorites/Index.vue',
  'resources/js/Pages/Notifications/Index.vue',
  'resources/js/Pages/Profile/Dashboard.vue',
  'resources/js/Pages/Reviews/Index.vue',
  'resources/js/Pages/Services/Index.vue',
  'resources/js/Pages/Settings/Index.vue',
  'resources/js/Pages/Wallet/Index.vue'
];

// Создание недостающих компонентов
const componentsToCreate = [
  'resources/js/src/shared/ui/organisms/SidebarWrapper/SidebarWrapper.vue',
];

componentsToCreate.forEach(compPath => {
  if (!fs.existsSync(compPath)) {
    const dir = require('path').dirname(compPath);
    if (!fs.existsSync(dir)) {
      fs.mkdirSync(dir, { recursive: true });
    }

    fs.writeFileSync(compPath, `<template>
  <div class="sidebar-wrapper">
    <slot />
  </div>
</template>

<script setup lang="ts">
// SidebarWrapper
</script>`);

  }
});

files.forEach(file => {
  if (!fs.existsSync(file)) return;

  let content = fs.readFileSync(file, 'utf-8');

  // Исправляем импорты
  content = content.replace(
    /import\s+\{([^}]+)\}\s+from\s+['"]@\/src\/shared\/ui\/organisms['"]/g,
    (match, imports) => {
      const importList = imports.split(',').map(i => i.trim());
      const newImports = importList.map(imp => {
        const name = imp.replace(/\s+as\s+.*/, '').trim();
        return `import ${imp} from '@/src/shared/ui/organisms/${name}/${name}.vue'`;
      });
      return newImports.join('\n');
    }
  );

  fs.writeFileSync(file, content);

});

