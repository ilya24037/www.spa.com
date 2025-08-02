<template>
  <div :class="cardClasses">
    <!-- Заголовок карточки -->
    <div v-if="$slots.header || title" class="card-header">
      <slot name="header">
        <h3 v-if="title" class="card-title">{{ title }}</h3>
      </slot>
    </div>

    <!-- Содержимое карточки -->
    <div class="card-body">
      <slot />
    </div>

    <!-- Подвал карточки -->
    <div v-if="$slots.footer" class="card-footer">
      <slot name="footer" />
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  title: {
    type: String,
    default: ''
  },
  variant: {
    type: String,
    default: 'default',
    validator: (value) => ['default', 'bordered', 'elevated', 'outlined'].includes(value)
  },
  size: {
    type: String,
    default: 'medium',
    validator: (value) => ['small', 'medium', 'large'].includes(value)
  },
  hoverable: {
    type: Boolean,
    default: false
  }
})

const cardClasses = computed(() => [
  'card',
  `card--${props.variant}`,
  `card--${props.size}`,
  {
    'card--hoverable': props.hoverable
  }
])
</script>

<style scoped>
.card {
  background: #fff;
  border-radius: 8px;
  overflow: hidden;
  transition: all 0.2s ease;
}

.card--hoverable:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

/* Варианты карточек */
.card--default {
  border: 1px solid #f0f0f0;
}

.card--bordered {
  border: 1px solid #d9d9d9;
}

.card--elevated {
  border: none;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.card--outlined {
  border: 2px solid #1890ff;
}

/* Размеры карточек */
.card--small {
  /* Меньшие отступы для маленькой карточки */
}

.card--medium {
  /* Стандартные отступы */
}

.card--large {
  /* Увеличенные отступы для большой карточки */
}

/* Заголовок карточки */
.card-header {
  padding: 16px 24px;
  border-bottom: 1px solid #f0f0f0;
  background: #fafafa;
}

.card--small .card-header {
  padding: 12px 16px;
}

.card--large .card-header {
  padding: 20px 32px;
}

.card-title {
  margin: 0;
  font-size: 18px;
  font-weight: 600;
  color: #1a1a1a;
  line-height: 1.4;
}

.card--small .card-title {
  font-size: 16px;
}

.card--large .card-title {
  font-size: 20px;
}

/* Содержимое карточки */
.card-body {
  padding: 24px;
}

.card--small .card-body {
  padding: 16px;
}

.card--large .card-body {
  padding: 32px;
}

/* Подвал карточки */
.card-footer {
  padding: 16px 24px;
  border-top: 1px solid #f0f0f0;
  background: #fafafa;
}

.card--small .card-footer {
  padding: 12px 16px;
}

.card--large .card-footer {
  padding: 20px 32px;
}
</style>