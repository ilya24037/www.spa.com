<!-- 
  Пример кнопки с использованием дизайн-токенов
  Демонстрирует правильное применение системы токенов
-->
<template>
  <button
    :class="[
      'btn',
      `btn--${variant}`,
      `btn--${size}`,
      {
        'btn--loading': loading,
        'btn--icon-only': iconOnly
      }
    ]"
    :disabled="disabled || loading"
    @click="handleClick"
  >
    <!-- Загрузчик -->
    <div v-if="loading" class="spinner spinner--sm mr-2"></div>
    
    <!-- Иконка слева -->
    <component 
      v-if="iconLeft && !loading" 
      :is="iconLeft" 
      class="w-4 h-4"
      :class="{ 'mr-2': !iconOnly }"
    />
    
    <!-- Текст кнопки -->
    <span v-if="!iconOnly">
      <slot>{{ text }}</slot>
    </span>
    
    <!-- Иконка справа -->
    <component 
      v-if="iconRight" 
      :is="iconRight" 
      class="w-4 h-4 ml-2"
    />
  </button>
</template>

<script setup lang="ts">
interface Props {
  variant?: 'primary' | 'secondary' | 'outline'
  size?: 'sm' | 'md' | 'lg'
  text?: string
  loading?: boolean
  disabled?: boolean
  iconLeft?: any
  iconRight?: any
  iconOnly?: boolean
}

interface Emits {
  (e: 'click', event: MouseEvent): void
}

const props = withDefaults(defineProps<Props>(), {
  variant: 'primary',
  size: 'md',
  text: '',
  loading: false,
  disabled: false,
  iconOnly: false
})

const emit = defineEmits<Emits>()

const handleClick = (event: MouseEvent) => {
  if (!props.loading && !props.disabled) {
    emit('click', event)
  }
}
</script>

<style scoped>
/* Дополнительные стили для состояний */
.btn--loading {
  cursor: wait;
}

.btn--icon-only {
  padding: var(--spacing-3);
  width: auto;
  aspect-ratio: 1;
}

.btn--icon-only.btn--sm {
  padding: var(--spacing-2);
}

.btn--icon-only.btn--lg {
  padding: var(--spacing-4);
}

/* Состояния фокуса */
.btn:focus-visible {
  outline: 2px solid var(--color-primary);
  outline-offset: 2px;
}

/* Анимации для интерактивности */
.btn {
  transform: translateY(0);
}

.btn:active:not(:disabled) {
  transform: translateY(1px);
}

/* Особые эффекты для primary кнопок */
.btn--primary {
  background: linear-gradient(135deg, var(--color-primary) 0%, var(--color-primary-dark) 100%);
}

.btn--primary:hover:not(:disabled) {
  background: linear-gradient(135deg, var(--color-primary-dark) 0%, var(--color-blue-800) 100%);
}
</style>