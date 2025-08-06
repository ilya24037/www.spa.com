<template>
  <div class="app-layout">
    <!-- Основной контент -->
    <main class="min-h-screen">
      <h1>Пример использования нового Footer</h1>
      <p>Контент страницы...</p>
    </main>
    
    <!-- Новый Footer компонент -->
    <Footer
      :config="footerConfig"
      @accessibility-toggle="handleAccessibilityToggle"
    />
  </div>
</template>

<script setup lang="ts">
// import { computed } from 'vue'
import { Footer, useFooter, defaultFooterConfig } from '../index'
// import type { FooterConfig } from '../index'

// Используем composable для управления Footer
const { config: footerConfig, handleAccessibilityToggle } = useFooter({
  // Можем кастомизировать конфигурацию
  companyInfo: {
    ...defaultFooterConfig.companyInfo,
    currentYear: new Date().getFullYear()
  },
  accessibility: {
    ...defaultFooterConfig.accessibility,
    callback: () => {
      // Дополнительная логика для приложения
    }
  }
})

// Пример динамического изменения конфигурации
const handleCustomConfigUpdate = () => {
  // Можем обновить конфигурацию динамически
  footerConfig.value = {
    ...footerConfig.value,
    quickActions: [
      ...footerConfig.value.quickActions,
      {
        id: 'new-action',
        text: 'Новое действие',
        href: '/new-action',
        icon: 'user-plus'
      }
    ]
  }
}
</script>

<style scoped>
.app-layout {
  display: flex;
  flex-direction: column;
  min-height: 100vh;
}

main {
  flex: 1;
  padding: 2rem;
}
</style>
