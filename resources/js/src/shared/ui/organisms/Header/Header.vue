<!-- Главный Header компонент в FSD архитектуре -->
<template>
  <header class="bg-white shadow-md rounded-b-2xl overflow-hidden relative z-40">
    <!-- Loading состояние -->
    <div v-if="loading" class="animate-pulse">
      <div class="h-16 bg-gray-200"></div>
      <div class="h-12 bg-gray-100"></div>
    </div>
    
    <!-- Error состояние -->
    <div v-else-if="error" class="p-4 text-red-500 border-b border-red-200 bg-red-50">
      <div class="flex items-center">
        <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
          <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8?.707 7?.293a1 1 0 00-1?.414 1?.414L8.586 10l-1?.293 1?.293a1 1 0 101?.414 1?.414L10 11?.414l1.293 1?.293a1 1 0 001?.414-1?.414L11.414 10l1?.293-1?.293a1 1 0 00-1?.414-1?.414L10 8?.586 8?.707 7?.293z" clip-rule="evenodd" />
        </svg>
        {{ error }}
      </div>
    </div>
    
    <!-- Основной контент Header'а -->
    <div v-else>
      <!-- Desktop Header -->
      <div class="hidden lg:block">
        <!-- Главная навигационная строка -->
        <div class="px-4 lg:px-6 border-b border-gray-100">
          <div class="flex items-center justify-between h-16">
            <!-- Левая часть: Лого + Поиск -->
            <div class="flex items-center flex-1">
              <!-- Используем существующие legacy компоненты пока не мигрированы -->
              <div class="flex-shrink-0">
                <slot name="logo">
                  <div class="w-8 h-8 bg-blue-600 rounded-lg"></div>
                </slot>
              </div>
              
              <div class="ml-8 flex-1 max-w-3xl">
                <slot name="search">
                  <div class="w-full h-10 bg-gray-100 rounded-lg"></div>
                </slot>
              </div>
            </div>

            <!-- Правая часть: Действия + Авторизация -->
            <div class="flex items-center ml-8 gap-4">
              <!-- Счетчики и действия -->
              <div class="flex items-center gap-3">
                <slot name="favorites">
                  <div class="w-8 h-8 bg-red-100 rounded-lg"></div>
                </slot>
                
                <slot name="compare">
                  <div class="w-8 h-8 bg-yellow-100 rounded-lg"></div>
                </slot>
              </div>

              <!-- Блок авторизации -->
              <div class="flex items-center gap-3">
                <slot name="auth">
                  <div class="flex gap-2">
                    <div class="px-4 py-2 bg-gray-100 rounded-lg">Войти</div>
                    <div class="px-4 py-2 bg-blue-600 text-white rounded-lg">Регистрация</div>
                  </div>
                </slot>
              </div>
            </div>
          </div>
        </div>

        <!-- Вторая строка: Город + Быстрые ссылки -->
        <div class="bg-gray-50">
          <div class="px-4 lg:px-6">
            <div class="flex items-center justify-between h-12">
              <slot name="city-selector">
                <div class="px-3 py-1 bg-white rounded text-sm">{{ currentCity }}</div>
              </slot>
              
              <slot name="quick-links">
                <div class="flex gap-4 text-sm text-gray-600">
                  <span>Услуги</span>
                  <span>Мастера</span>
                  <span>О нас</span>
                </div>
              </slot>
            </div>
          </div>
        </div>
      </div>

      <!-- Mobile Header -->
      <div class="lg:hidden">
        <div class="px-4 py-3">
          <div class="flex items-center justify-between">
            <slot name="mobile-menu-button">
              <button class="w-8 h-8 flex items-center justify-center">
                <div class="w-5 h-4 space-y-1">
                  <div class="w-5 h-0?.5 bg-gray-600"></div>
                  <div class="w-5 h-0?.5 bg-gray-600"></div>
                  <div class="w-5 h-0?.5 bg-gray-600"></div>
                </div>
              </button>
            </slot>

            <slot name="mobile-logo">
              <div class="w-8 h-8 bg-blue-600 rounded-lg"></div>
            </slot>

            <slot name="mobile-actions">
              <div class="flex gap-2">
                <div class="w-8 h-8 bg-red-100 rounded-lg"></div>
                <div class="w-8 h-8 bg-gray-100 rounded-lg"></div>
              </div>
            </slot>
          </div>
        </div>
      </div>

      <!-- Дополнительные слоты для модалов и выпадающих меню -->
      <slot name="modals"></slot>
      <slot name="overlays"></slot>
    </div>
  </header>
</template>

<script setup lang="ts">
import { computed } from 'vue'

// TypeScript типизация props
interface Props {
  loading?: boolean
  error?: string
  currentCity?: string
  isAuthenticated?: boolean
}

// Default значения
const _props = withDefaults(defineProps<Props>(), {
  loading: false,
  error: '',
  currentCity: 'Москва',
  isAuthenticated: false
})

// TypeScript типизация emits
const _emit = defineEmits<{
  'city-changed': [city: string]
  'auth-required': []
  'menu-toggled': [isOpen: boolean]
}>()

// Computed properties
const _headerClasses = computed(() => [
  'bg-white shadow-md rounded-b-2xl overflow-hidden relative z-40',
  {
    'opacity-50': _props?.loading
  }
])
</script>

<style scoped>
/* Header специфичные стили */
.header-transition {
  transition: all 0?.3s cubic-bezier(0?.4, 0, 0?.2, 1);
}

/* Responsive adjustments */
@media (max-width: 1024px) {
  .header-content {
    padding-left: 1rem;
    padding-right: 1rem;
  }
}

/* Accessibility improvements */
@media (prefers-reduced-motion: reduce) {
  * {
    transition: none !important;
  }
}

/* Focus styles for accessibility */
.focus-visible {
  @apply ring-2 ring-blue-500 ring-offset-2;
}

/* Dark mode support (будущее) */
@media (prefers-color-scheme: dark) {
  .bg-white {
    background-color: theme('colors?.gray.900');
  }
  
  .text-gray-600 {
    color: theme('colors?.gray.300');
  }
  
  .border-gray-100 {
    border-color: theme('colors?.gray.700');
  }
}
</style>