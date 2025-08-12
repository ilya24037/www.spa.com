<template>
  <div class="collapsible-section" :class="{ 
    'is-open': isOpen,
    'is-required': isRequired,
    'is-filled': isFilled,
    'has-errors': hasErrors
  }">
    <div class="section-header" @click="$emit('toggle')">
      <div class="section-left">
        <span class="toggle-icon">{{ isOpen ? '▼' : '▶' }}</span>
        <span class="section-title">
          {{ title }}
          <span v-if="isRequired" class="required-badge">*</span>
        </span>
      </div>
      
      <div class="section-right">
        <span v-if="filledCount !== undefined" class="filled-counter">
          {{ filledCount }}/{{ totalCount }}
        </span>
        <span class="status-icon">
          <span v-if="isFilled" class="icon-check">✓</span>
          <span v-else-if="hasErrors" class="icon-error">⚠</span>
          <span v-else class="icon-empty">○</span>
        </span>
      </div>
    </div>
    
    <transition name="collapse">
      <div v-show="isOpen" class="section-content">
        <slot></slot>
      </div>
    </transition>
  </div>
</template>

<script setup lang="ts">
interface Props {
  title: string
  isOpen?: boolean
  isRequired?: boolean
  isFilled?: boolean
  hasErrors?: boolean
  filledCount?: number
  totalCount?: number | string
}

withDefaults(defineProps<Props>(), {
  isOpen: false,
  isRequired: false,
  isFilled: false,
  hasErrors: false
})

defineEmits<{
  toggle: []
}>()
</script>

<style scoped>
.collapsible-section {
  margin-bottom: 12px;
  border: 1px solid #e5e7eb;
  border-radius: 12px;
  background: white;
  overflow: visible !important;
}

.collapsible-section.is-required {
  border-color: #3b82f6;
}

.collapsible-section.is-filled {
  border-color: #10b981;
}

.section-header {
  padding: 16px 20px;
  background: rgba(249, 250, 251, 0.8);
  cursor: pointer;
  display: flex;
  justify-content: space-between;
  align-items: center;
  user-select: none;
  transition: background 0.2s;
  border-radius: 12px 12px 0 0;
}

.section-header:hover {
  background: rgba(243, 244, 246, 0.9);
}

.is-open .section-header {
  background: rgba(239, 246, 255, 0.9);
  border-bottom: 1px solid #e5e7eb;
}

.section-left {
  display: flex;
  align-items: center;
  gap: 12px;
}

.toggle-icon {
  font-size: 12px;
  color: #6b7280;
  transition: transform 0.3s;
  width: 16px;
  text-align: center;
}

.section-title {
  font-size: 16px;
  font-weight: 500;
  color: #111827;
}

.required-badge {
  color: #ef4444;
  margin-left: 4px;
  font-size: 14px;
}

.section-right {
  display: flex;
  align-items: center;
  gap: 12px;
}

.filled-counter {
  font-size: 14px;
  color: #6b7280;
  background: white;
  padding: 4px 8px;
  border-radius: 6px;
}

.status-icon {
  width: 24px;
  height: 24px;
  display: flex;
  align-items: center;
  justify-content: center;
}

.icon-check {
  color: #10b981;
  font-size: 18px;
  font-weight: bold;
}

.icon-error {
  color: #f59e0b;
  font-size: 18px;
}

.icon-empty {
  color: #d1d5db;
  font-size: 18px;
}

.section-content {
  padding: 20px;
  background: white;
  border-bottom-left-radius: 12px;
  border-bottom-right-radius: 12px;
  position: relative;
  overflow: visible !important; /* Для выпадающих списков */
  min-height: 100px; /* Минимальная высота для корректного отображения */
}

/* Простая анимация */
.collapse-enter-active,
.collapse-leave-active {
  transition: all 0.2s ease;
}

.collapse-enter-from,
.collapse-leave-to {
  opacity: 0;
}

/* Мобильная адаптация */
@media (max-width: 640px) {
  .section-header {
    padding: 12px 16px;
  }
  
  .section-title {
    font-size: 14px;
  }
  
  .section-content {
    padding: 16px;
  }
  
  .filled-counter {
    font-size: 12px;
  }
}
</style>