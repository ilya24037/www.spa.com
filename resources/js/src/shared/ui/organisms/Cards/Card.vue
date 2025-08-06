<template>
  <div 
    :class="cardClasses"
    :aria-busy="loading"
    :aria-disabled="disabled"
    role="article"
    @click="handleClick"
  >
    <!-- Loading overlay -->
    <div v-if="loading" class="card-loading">
      <div class="card-spinner" aria-label="Р—Р°РіСЂСѓР·РєР°..." />
    </div>

    <!-- Р—Р°РіРѕР»РѕРІРѕРє РєР°СЂС‚РѕС‡РєРё -->
    <div v-if="$slots.header || title" class="card-header">
      <slot name="header">
        <h3 v-if="title" class="card-title">{{ title }}</h3>
      </slot>
    </div>

    <!-- РЎРѕРґРµСЂР¶РёРјРѕРµ РєР°СЂС‚РѕС‡РєРё -->
    <div class="card-body">
      <slot />
    </div>

    <!-- РџРѕРґРІР°Р» РєР°СЂС‚РѕС‡РєРё -->
    <div v-if="$slots.footer" class="card-footer">
      <slot name="footer" />
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import type { CardProps } from './Card.types'

const props = withDefaults(defineProps<CardProps>(), {
  variant: 'default',
  size: 'medium',
  hoverable: false,
  loading: false,
  disabled: false
})

const emit = defineEmits<{
  click: [event: MouseEvent]
}>()

const cardClasses = computed(() => [
  'card',
  `card--${props.variant}`,
  `card--${props.size}`,
  props.customClass,
  {
    'card--hoverable': props.hoverable && !props.disabled && !props.loading,
    'card--loading': props.loading,
    'card--disabled': props.disabled
  }
])

const handleClick = (event: MouseEvent) => {
  if (!props.disabled && !props.loading) {
    emit('click', event)
  }
}
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

/* Р’Р°СЂРёР°РЅС‚С‹ РєР°СЂС‚РѕС‡РµРє */
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

/* Р Р°Р·РјРµСЂС‹ РєР°СЂС‚РѕС‡РµРє */
.card--small {
  /* РњРµРЅСЊС€РёРµ РѕС‚СЃС‚СѓРїС‹ РґР»СЏ РјР°Р»РµРЅСЊРєРѕР№ РєР°СЂС‚РѕС‡РєРё */
}

.card--medium {
  /* РЎС‚Р°РЅРґР°СЂС‚РЅС‹Рµ РѕС‚СЃС‚СѓРїС‹ */
}

.card--large {
  /* РЈРІРµР»РёС‡РµРЅРЅС‹Рµ РѕС‚СЃС‚СѓРїС‹ РґР»СЏ Р±РѕР»СЊС€РѕР№ РєР°СЂС‚РѕС‡РєРё */
}

/* Р—Р°РіРѕР»РѕРІРѕРє РєР°СЂС‚РѕС‡РєРё */
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

/* РЎРѕРґРµСЂР¶РёРјРѕРµ РєР°СЂС‚РѕС‡РєРё */
.card-body {
  padding: 24px;
}

.card--small .card-body {
  padding: 16px;
}

.card--large .card-body {
  padding: 32px;
}

/* РџРѕРґРІР°Р» РєР°СЂС‚РѕС‡РєРё */
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

/* РЎРѕСЃС‚РѕСЏРЅРёСЏ РєР°СЂС‚РѕС‡РєРё */
.card--loading {
  position: relative;
  pointer-events: none;
}

.card--disabled {
  opacity: 0.6;
  cursor: not-allowed;
  pointer-events: none;
}

.card--disabled * {
  color: #999 !important;
}

/* Loading overlay */
.card-loading {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(255, 255, 255, 0.8);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 10;
  border-radius: inherit;
}

.card-spinner {
  width: 32px;
  height: 32px;
  border: 3px solid #f3f3f3;
  border-top: 3px solid #1890ff;
  border-radius: 50%;
  animation: card-spin 1s linear infinite;
}

@keyframes card-spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}

/* РђРґР°РїС‚РёРІРЅРѕСЃС‚СЊ */
@media (max-width: 768px) {
  .card-header,
  .card-body,
  .card-footer {
    padding-left: 16px;
    padding-right: 16px;
  }
  
  .card--large .card-header,
  .card--large .card-body,
  .card--large .card-footer {
    padding-left: 20px;
    padding-right: 20px;
  }
  
  .card-title {
    font-size: 16px;
  }
}
</style>

