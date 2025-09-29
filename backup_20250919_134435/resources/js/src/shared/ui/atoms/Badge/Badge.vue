<template>
  <span 
    class="badge"
    :class="[
      `badge--${variant}`,
      `badge--${size}`,
      { 'badge--rounded': rounded }
    ]"
  >
    <slot>{{ text }}</slot>
  </span>
</template>

<script setup lang="ts">
interface Props {
  text?: string
  variant?: 'primary' | 'secondary' | 'success' | 'warning' | 'danger' | 'info'
  size?: 'sm' | 'md' | 'lg'
  rounded?: boolean
}

withDefaults(defineProps<Props>(), {
  text: '',
  variant: 'primary',
  size: 'md',
  rounded: true
})
</script>

<style scoped>
.badge {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  font-weight: 500;
  line-height: 1;
  white-space: nowrap;
}

/* Размеры */
.badge--sm {
  padding: 2px 6px;
  font-size: 11px;
}

.badge--md {
  padding: 4px 8px;
  font-size: 12px;
}

.badge--lg {
  padding: 6px 12px;
  font-size: 14px;
}

/* Скругление */
.badge--rounded {
  border-radius: 4px;
}

/* Варианты цветов */
.badge--primary {
  background-color: #ff5252;
  color: white;
}

.badge--secondary {
  background-color: #6b7280;
  color: white;
}

.badge--success {
  background-color: #10b981;
  color: white;
}

.badge--warning {
  background-color: #f59e0b;
  color: white;
}

.badge--danger {
  background-color: #ef4444;
  color: white;
}

.badge--info {
  background-color: #3b82f6;
  color: white;
}
</style>