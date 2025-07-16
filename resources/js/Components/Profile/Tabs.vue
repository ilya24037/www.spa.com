<template>
  <section class="tabs-section">
    <div class="tabs-container">
      <div class="tabs-content">
        <div 
          role="tablist" 
          class="tabs-list"
          :data-marker="marker"
        >
          <button 
            v-for="(tab, index) in tabs" 
            :key="tab.key"
            type="button" 
            role="tab" 
            :aria-selected="modelValue === tab.key"
            :class="[
              'tab-button',
              'tab-button-size-m',
              { 'tab-button-active': modelValue === tab.key }
            ]"
            :data-num="index"
            :data-marker="`${marker}/tab(${tab.key})`"
            @click="handleTabClick(tab.key)"
          >
            <span class="tab-text-wrapper">
              <span class="tab-button-title">{{ tab.title }}</span>
              <span v-if="tab.count !== undefined" class="tab-counter">{{ tab.count }}</span>
            </span>
          </button>
        </div>
        <div class="tabs-emphasis"></div>
      </div>
      <div class="tabs-underline"></div>
    </div>
  </section>
</template>

<script setup>
import { computed, nextTick, watch, onMounted, onUnmounted } from 'vue'

const props = defineProps({
  tabs: {
    type: Array,
    required: true
  },
  modelValue: {
    type: String,
    required: true
  },
  marker: {
    type: String,
    default: 'tabs'
  }
})

const emit = defineEmits(['update:modelValue'])

// Позиционирование подчеркивания
const updateEmphasis = () => {
  nextTick(() => {
    const activeTab = document.querySelector('.tab-button-active')
    const emphasis = document.querySelector('.tabs-emphasis')
    
    if (activeTab && emphasis) {
      const tabRect = activeTab.getBoundingClientRect()
      const tabsContainer = activeTab.closest('.tabs-list')
      const containerRect = tabsContainer.getBoundingClientRect()
      
      emphasis.style.left = `${tabRect.left - containerRect.left}px`
      emphasis.style.width = `${tabRect.width}px`
    }
  })
}

// Обработчик клика по вкладке
const handleTabClick = (tabKey) => {
  emit('update:modelValue', tabKey)
  updateEmphasis()
}

// Обработчик изменения размера окна
const handleResize = () => {
  updateEmphasis()
}

onMounted(() => {
  window.addEventListener('resize', handleResize)
})

onUnmounted(() => {
  window.removeEventListener('resize', handleResize)
})

// Обновляем позицию при изменении активной вкладки
watch(() => props.modelValue, updateEmphasis, { immediate: true })
</script>

<style scoped>
.tabs-section {
  @apply mb-6;
}

.tabs-container {
  @apply relative;
}

.tabs-content {
  @apply relative;
}

.tabs-list {
  @apply flex border-b border-gray-200;
}

.tab-button {
  @apply relative px-6 py-3 text-sm font-medium text-gray-600 hover:text-gray-900 transition-colors;
}

.tab-button-active {
  @apply text-blue-600;
}

.tab-text-wrapper {
  @apply flex items-center gap-2;
}

.tab-button-title {
  @apply whitespace-nowrap;
}

.tab-counter {
  @apply bg-gray-100 text-gray-600 text-xs font-medium px-2 py-0.5 rounded-full;
}

.tabs-emphasis {
  @apply absolute bottom-0 h-0.5 bg-blue-600 transition-all duration-200;
}

.tabs-underline {
  @apply border-b border-gray-200;
}
</style> 