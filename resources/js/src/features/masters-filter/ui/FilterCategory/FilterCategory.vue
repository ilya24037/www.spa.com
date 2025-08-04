<!--
  Категория фильтра с коллапсом
  Обертка для группировки связанных фильтров
  Поддерживает состояние active и счетчик выбранных элементов
-->
<template>
  <div class="border-b border-gray-200 last:border-b-0">
    <!-- Заголовок категории -->
    <button
      @click="toggleExpanded"
      class="w-full flex items-center justify-between p-4 text-left hover:bg-gray-50 transition-colors"
      :class="[
        active ? 'bg-blue-50' : ''
      ]"
    >
      <!-- Иконка и название -->
      <div class="flex items-center gap-3">
        <span class="text-lg">{{ icon }}</span>
        <span class="font-medium text-gray-900">{{ title }}</span>
        <!-- Счетчик активных фильтров -->
        <span 
          v-if="count && count > 0"
          class="px-2 py-1 text-xs font-medium text-white bg-blue-600 rounded-full"
        >
          {{ count }}
        </span>
      </div>

      <!-- Кнопка сворачивания -->
      <div class="flex items-center gap-2">
        <!-- Индикатор активности -->
        <div
          v-if="active"
          class="w-2 h-2 bg-blue-600 rounded-full"
        />
        
        <!-- Стрелка -->
        <svg
          class="w-5 h-5 text-gray-400 transition-transform"
          :class="[
            isExpanded ? 'rotate-180' : ''
          ]"
          fill="none"
          viewBox="0 0 24 24"
          stroke="currentColor"
        >
          <path 
            stroke-linecap="round" 
            stroke-linejoin="round" 
            stroke-width="2" 
            d="M19 9l-7 7-7-7" 
          />
        </svg>
      </div>
    </button>

    <!-- Контент категории -->
    <Transition name="collapse">
      <div 
        v-if="isExpanded"
        class="px-4 pb-4"
      >
        <slot />
      </div>
    </Transition>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import type { FilterCategoryProps } from '../../model/types'

// =================== PROPS ===================

interface Props {
  title: string
  icon: string
  active?: boolean
  count?: number
  defaultExpanded?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  active: false,
  count: 0,
  defaultExpanded: true
})

// =================== СОСТОЯНИЕ ===================

const isExpanded = ref(props.defaultExpanded)

// =================== МЕТОДЫ ===================

const toggleExpanded = () => {
  isExpanded.value = !isExpanded.value
}
</script>

<style scoped>
/* Анимация сворачивания/разворачивания */
.collapse-enter-active,
.collapse-leave-active {
  transition: all 0.3s ease-out;
  overflow: hidden;
}

.collapse-enter-from,
.collapse-leave-to {
  max-height: 0;
  opacity: 0;
  padding-top: 0;
  padding-bottom: 0;
}

.collapse-enter-to,
.collapse-leave-from {
  max-height: 1000px;
  opacity: 1;
}
</style>