<!-- Точная копия логики старого AppLayout.vue -->
<template>
  <!-- Динамический фон в зависимости от типа страницы -->
  <div :class="[
    'min-h-screen flex flex-col',
    isAuthPage ? 'bg-gray-50' : 'bg-gray-100'
  ]">
    
    <!-- Контейнер с фиксированной шириной для всего сайта -->
    <div class="max-w-[1400px] mx-auto min-h-screen flex flex-col w-full">
      
      <!-- НОВОЕ: Единая обертка с отступами для ВСЕГО контента -->
      <div class="site-padding flex-1 flex flex-col">
        
        <!-- Шапка с компенсацией отступов (скрыта для авторизации) -->
        <header v-if="!isAuthPage" class="sticky top-0 z-50 negative-margin">
          <div class="site-padding">
            <AppHeader :user="user" :is-authenticated="isAuthenticated" />
          </div>
        </header>
        
        <!-- Основной контент уже имеет отступы от обертки -->
        <main class="flex-1">
          <slot />
        </main>
        
        <!-- Footer (скрыт для авторизации) -->
        <AppFooter v-if="!isAuthPage" :config="footerConfig" />
      </div>
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
/* Стили из старого AppLayout.vue */
.site-padding {
  padding-left: 1rem;
  padding-right: 1rem;
}

@media (min-width: 1024px) {
  .site-padding {
    padding-left: 1.5rem;
    padding-right: 1.5rem;
  }
}

.negative-margin {
  margin-left: -1rem;
  margin-right: -1rem;
}

@media (min-width: 1024px) {
  .negative-margin {
    margin-left: -1.5rem;
    margin-right: -1.5rem;
  }
}
</style>