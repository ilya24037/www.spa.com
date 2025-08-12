<!-- Точная копия логики старого AppLayout.vue -->
<template>
  <!-- Динамический фон в зависимости от типа страницы -->
  <div :class="[
    'min-h-screen flex flex-col',
    isAuthPage ? 'bg-gray-50' : 'bg-gray-100'
  ]">
    
    <!-- Обертка для ограничения ширины всего контента -->
    <div class="max-w-[1400px] mx-auto w-full flex flex-col min-h-screen px-4">
      <!-- Header с отступами и закруглением как у Ozon -->
      <header v-if="!isAuthPage" class="sticky top-0 z-50 -mx-4 px-4 pb-4 header-sticky">
        <div class="bg-white rounded-b-[24px] shadow-lg border border-gray-100 overflow-hidden" style="box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);">
          <AppHeader :user="user" :is-authenticated="isAuthenticated" />
        </div>
      </header>
      
      <!-- Main контент -->
      <main class="flex-1">
        <slot />
      </main>
      
      <!-- Footer -->
      <footer v-if="!isAuthPage" class="mt-auto">
        <AppFooter :config="footerConfig" />
      </footer>
    </div>
         
    <!-- Глобальные уведомления (вне контейнера) -->
    <!-- <ToastNotifications /> -->
  </div>
</template>

<script setup lang="ts">
import { provide, computed } from 'vue'
import { usePage } from '@inertiajs/vue3'

// Компоненты шапки и футера
import AppHeader from '@/src/widgets/header/AppHeader.vue'
import AppFooter from '@/src/shared/ui/organisms/Footer/Footer.vue'

// Конфигурация футера
import { defaultFooterConfig } from '@/src/shared/ui/organisms/Footer/model/footer.config'

const page = usePage()

// Определяем, является ли это страницей авторизации
const isAuthPage = computed(() => {
  return window.isAuthPage || false
})

// Глобальные данные
provide('auth', page.props.auth)
provide('canLogin', page.props.canLogin)
provide('canRegister', page.props.canRegister)

// Provide для sticky top (высота шапки)
provide('stickyTop', 112) // высота двухуровневой шапки

// Computed
const user = computed(() => page.props.auth?.user || null)
const isAuthenticated = computed(() => !!user.value)

// Footer config
const footerConfig = defaultFooterConfig
</script>

<style scoped>
/* Улучшенный sticky header с тенью и фоном */
.header-sticky {
  padding-top: 0;
  transition: padding-top 0.2s ease-in-out;
}

/* Добавляем отступ сверху при скролле для визуального отделения */
.header-sticky.scrolled {
  padding-top: 8px;
}
</style>