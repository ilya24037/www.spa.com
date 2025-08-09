<template>
  <div 
    :class="containerClasses"
    :style="containerStyle"
    role="status"
    :aria-busy="true"
    :aria-label="ariaLabel"
  >
    <!-- Spinner -->
    <div 
      :class="spinnerClasses"
      :style="spinnerStyle"
    >
      <!-- Р’СЃС‚СЂРѕРµРЅРЅС‹Рµ РІР°СЂРёР°РЅС‚С‹ СЃРїРёРЅРЅРµСЂРѕРІ -->
      <template v-if="variant === 'ring'">
        <div class="spinner-ring">
          <div /><div /><div /><div />
        </div>
      </template>
      
      <template v-else-if="variant === 'dots'">
        <div class="spinner-dots">
          <div /><div /><div />
        </div>
      </template>
      
      <template v-else-if="variant === 'pulse'">
        <div class="spinner-pulse" />
      </template>
      
      <template v-else-if="variant === 'bars'">
        <div class="spinner-bars">
          <div /><div /><div /><div /><div />
        </div>
      </template>
      
      <template v-else-if="variant === 'circle'">
        <svg class="spinner-circle" viewBox="0 0 50 50">
          <circle
            class="path"
            cx="25"
            cy="25"
            r="20"
            fill="none"
            stroke="currentColor"
            stroke-width="2"
            stroke-linecap="round"
            stroke-dasharray="31.416"
            stroke-dashoffset="31.416"
          />
        </svg>
      </template>
      
      <!-- РљР°СЃС‚РѕРјРЅР°СЏ РёРєРѕРЅРєР° -->
      <component
        :is="customIcon"
        v-else-if="customIcon"
        class="spinner-custom"
      />
      
      <!-- РџРѕ СѓРјРѕР»С‡Р°РЅРёСЋ - ring -->
      <template v-else>
        <div class="spinner-ring">
          <div /><div /><div /><div />
        </div>
      </template>
    </div>
    
    <!-- РўРµРєСЃС‚ Р·Р°РіСЂСѓР·РєРё -->
    <div 
      v-if="text || $slots.default" 
      :class="textClasses"
    >
      <slot>{{ text }}</slot>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, useSlots } from 'vue'
import type { SpinnerProps } from './Spinner.types'

const props = withDefaults(defineProps<SpinnerProps>(), {
    variant: 'ring',
    size: 'medium',
    color: 'primary',
    centered: false,
    overlay: false,
    text: '',
    ariaLabel: 'Р—Р°РіСЂСѓР·РєР°...'
})

const slots = useSlots()

const containerClasses = computed(() => [
    'spinner-container',
    {
        'spinner-container--centered': props.centered,
        'spinner-container--overlay': props.overlay,
        'spinner-container--with-text': props.text || !!slots.default
    },
    props.customClass
])

const containerStyle = computed(() => {
    const styles: Record<string, string> = {}
  
    if (props.overlay && props.overlayColor) {
        styles.backgroundColor = props.overlayColor
    }
  
    return styles
})

const spinnerClasses = computed(() => [
    'spinner',
    `spinner--${props.variant}`,
    `spinner--${props.size}`,
    `spinner--${props.color}`
])

const spinnerStyle = computed(() => {
    const styles: Record<string, string> = {}
  
    if (props.customSize) {
        const size = typeof props.customSize === 'number' 
            ? `${props.customSize}px` 
            : props.customSize
        styles.width = size
        styles.height = size
    }
  
    if (props.customColor) {
        styles.color = props.customColor
    }
  
    return styles
})

const textClasses = computed(() => [
    'spinner-text',
    `spinner-text--${props.size}`
])

const ariaLabel = computed(() => {
    if (props.text) {
        return `${props.ariaLabel}: ${props.text}`
    }
    return props.ariaLabel
})
</script>

<style scoped>
.spinner-container {
  display: inline-flex;
  align-items: center;
  gap: 0.75rem;
}

.spinner-container--centered {
  display: flex;
  justify-content: center;
  align-items: center;
  width: 100%;
  min-height: 200px;
}

.spinner-container--overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  z-index: 9999;
  background: rgba(255, 255, 255, 0.8);
  backdrop-filter: blur(2px);
}

.spinner-container--with-text {
  flex-direction: column;
  gap: 1rem;
}

/* Р‘Р°Р·РѕРІС‹Рµ СЂР°Р·РјРµСЂС‹ СЃРїРёРЅРЅРµСЂРѕРІ */
.spinner {
  display: inline-block;
}

.spinner--small {
  width: 16px;
  height: 16px;
}

.spinner--medium {
  width: 24px;
  height: 24px;
}

.spinner--large {
  width: 32px;
  height: 32px;
}

.spinner--extra-large {
  width: 48px;
  height: 48px;
}

/* Р¦РІРµС‚Р° */
.spinner--primary {
  color: #3b82f6;
}

.spinner--secondary {
  color: #6b7280;
}

.spinner--success {
  color: #10b981;
}

.spinner--warning {
  color: #f59e0b;
}

.spinner--error {
  color: #ef4444;
}

/* Ring Spinner */
.spinner-ring {
  display: inline-block;
  position: relative;
  width: 100%;
  height: 100%;
}

.spinner-ring div {
  box-sizing: border-box;
  display: block;
  position: absolute;
  width: 100%;
  height: 100%;
  border: 2px solid currentColor;
  border-radius: 50%;
  animation: spinner-ring 1.2s cubic-bezier(0.5, 0, 0.5, 1) infinite;
  border-color: currentColor transparent transparent transparent;
}

.spinner-ring div:nth-child(1) { animation-delay: -0.45s; }
.spinner-ring div:nth-child(2) { animation-delay: -0.3s; }
.spinner-ring div:nth-child(3) { animation-delay: -0.15s; }

@keyframes spinner-ring {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}

/* Dots Spinner */
.spinner-dots {
  display: inline-block;
  position: relative;
  width: 100%;
  height: 100%;
}

.spinner-dots div {
  position: absolute;
  top: 50%;
  width: 25%;
  height: 25%;
  border-radius: 50%;
  background: currentColor;
  animation-timing-function: cubic-bezier(0, 1, 1, 0);
}

.spinner-dots div:nth-child(1) {
  left: 8%;
  animation: spinner-dots1 0.6s infinite;
}

.spinner-dots div:nth-child(2) {
  left: 8%;
  animation: spinner-dots2 0.6s infinite;
}

.spinner-dots div:nth-child(3) {
  left: 37.5%;
  animation: spinner-dots2 0.6s infinite;
}

@keyframes spinner-dots1 {
  0% { transform: scale(0) translateY(-50%); }
  100% { transform: scale(1) translateY(-50%); }
}

@keyframes spinner-dots2 {
  0% { transform: translate(0, -50%); }
  100% { transform: translate(150%, -50%); }
}

/* Pulse Spinner */
.spinner-pulse {
  width: 100%;
  height: 100%;
  border-radius: 50%;
  background: currentColor;
  animation: spinner-pulse 1s ease-in-out infinite;
}

@keyframes spinner-pulse {
  0%, 100% {
    opacity: 1;
    transform: scale(0);
  }
  50% {
    opacity: 0.5;
    transform: scale(1);
  }
}

/* Bars Spinner */
.spinner-bars {
  display: inline-block;
  position: relative;
  width: 100%;
  height: 100%;
}

.spinner-bars div {
  display: inline-block;
  position: absolute;
  left: 8%;
  width: 16%;
  background: currentColor;
  animation: spinner-bars 1.2s cubic-bezier(0, 0.5, 0.5, 1) infinite;
}

.spinner-bars div:nth-child(1) {
  left: 8%;
  animation-delay: -0.24s;
}

.spinner-bars div:nth-child(2) {
  left: 32%;
  animation-delay: -0.12s;
}

.spinner-bars div:nth-child(3) {
  left: 56%;
  animation-delay: 0;
}

@keyframes spinner-bars {
  0%, 40%, 100% {
    opacity: 1;
    transform: scaleY(0.4);
  }
  20% {
    opacity: 1;
    transform: scaleY(1);
  }
}

/* Circle Spinner */
.spinner-circle {
  width: 100%;
  height: 100%;
  animation: spinner-rotate 2s linear infinite;
}

.spinner-circle .path {
  animation: spinner-dash 1.5s ease-in-out infinite;
}

@keyframes spinner-rotate {
  100% {
    transform: rotate(360deg);
  }
}

@keyframes spinner-dash {
  0% {
    stroke-dasharray: 1, 150;
    stroke-dashoffset: 0;
  }
  50% {
    stroke-dasharray: 90, 150;
    stroke-dashoffset: -35;
  }
  100% {
    stroke-dasharray: 90, 150;
    stroke-dashoffset: -124;
  }
}

/* РљР°СЃС‚РѕРјРЅС‹Р№ СЃРїРёРЅРЅРµСЂ */
.spinner-custom {
  width: 100%;
  height: 100%;
  animation: spinner-rotate 1s linear infinite;
}

/* РўРµРєСЃС‚ */
.spinner-text {
  color: #6b7280;
  font-weight: 500;
  text-align: center;
}

.spinner-text--small {
  font-size: 0.75rem;
}

.spinner-text--medium {
  font-size: 0.875rem;
}

.spinner-text--large {
  font-size: 1rem;
}

.spinner-text--extra-large {
  font-size: 1.125rem;
}

/* РўРµРјРЅР°СЏ С‚РµРјР° */
@media (prefers-color-scheme: dark) {
  .spinner-container--overlay {
    background: rgba(0, 0, 0, 0.8);
  }
  
  .spinner-text {
    color: #d1d5db;
  }
}

/* Accessibility - СѓРјРµРЅСЊС€РµРЅРЅР°СЏ Р°РЅРёРјР°С†РёСЏ */
@media (prefers-reduced-motion: reduce) {
  .spinner-ring div,
  .spinner-dots div,
  .spinner-pulse,
  .spinner-bars div,
  .spinner-circle,
  .spinner-custom {
    animation-duration: 3s;
    animation-iteration-count: 1;
  }
}

/* РђРґР°РїС‚РёРІРЅРѕСЃС‚СЊ */
@media (max-width: 768px) {
  .spinner-container--centered {
    min-height: 150px;
  }
  
  .spinner-container--with-text {
    gap: 0.75rem;
  }
  
  .spinner-text {
    font-size: 0.75rem;
  }
}
</style>
