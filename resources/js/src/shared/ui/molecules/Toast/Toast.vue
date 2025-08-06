<template>
  <Teleport to="body">
    <Transition
      enter-active-class="transition ease-out duration-300"
      enter-from-class="transform opacity-0 translate-y-2"
      enter-to-class="transform opacity-100 translate-y-0"
      leave-active-class="transition ease-in duration-200"
      leave-from-class="transform opacity-100 translate-y-0"
      leave-to-class="transform opacity-0 translate-y-2"
    >
      <div
        v-if="visible"
        class="toast-container"
        :class="[
          `toast-${type}`,
          positionClass
        ]"
        role="alert"
        aria-live="polite"
        :aria-label="`${type} СѓРІРµРґРѕРјР»РµРЅРёРµ: ${message}`"
      >
        <div class="toast-content">
          <!-- РРєРѕРЅРєР° -->
          <div class="toast-icon">
            <svg v-if="type === 'success'" width="20" height="20" viewBox="0 0 20 20" fill="none">
              <path d="M16.25 5.75L7.5 14.5L3.75 10.75" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            <svg v-else-if="type === 'error'" width="20" height="20" viewBox="0 0 20 20" fill="none">
              <path d="M15 5L5 15M5 5L15 15" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            <svg v-else-if="type === 'warning'" width="20" height="20" viewBox="0 0 20 20" fill="none">
              <path d="M10 6V10M10 14H10.01M18 10C18 14.4183 14.4183 18 10 18C5.58172 18 2 14.4183 2 10C2 5.58172 5.58172 2 10 2C14.4183 2 18 5.58172 18 10Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            <svg v-else width="20" height="20" viewBox="0 0 20 20" fill="none">
              <path d="M10 6V10M10 14H10.01M18 10C18 14.4183 14.4183 18 10 18C5.58172 18 2 14.4183 2 10C2 5.58172 5.58172 2 10 2C14.4183 2 18 5.58172 18 10Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
          </div>

          <!-- РљРѕРЅС‚РµРЅС‚ -->
          <div class="toast-text">
            <div v-if="title" class="toast-title">{{ title }}</div>
            <div class="toast-message">{{ message }}</div>
          </div>

          <!-- РљРЅРѕРїРєР° Р·Р°РєСЂС‹С‚РёСЏ -->
          <button
            v-if="closable"
            @click="close"
            class="toast-close"
            type="button"
            :aria-label="`Р—Р°РєСЂС‹С‚СЊ ${type} СѓРІРµРґРѕРјР»РµРЅРёРµ`"
          >
            <svg width="16" height="16" viewBox="0 0 16 16" fill="none">
              <path d="M12 4L4 12M4 4L12 12" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
          </button>
        </div>

        <!-- РџСЂРѕРіСЂРµСЃСЃ-Р±Р°СЂ -->
        <div v-if="duration > 0" class="toast-progress">
          <div 
            class="toast-progress-bar"
            :style="{ animationDuration: `${duration}ms` }"
          ></div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from 'vue'
import type { ToastProps, ToastEmits } from './Toast.types'

const props = withDefaults(defineProps<ToastProps>(), {
  type: 'info',
  title: '',
  duration: 4000,
  position: 'top-right',
  closable: true
})

const emit = defineEmits<ToastEmits>()

const visible = ref(false)
let timeoutId: number | null = null

const positionClass = computed(() => {
  const classes = {
    'top-left': 'toast-top-left',
    'top-center': 'toast-top-center', 
    'top-right': 'toast-top-right',
    'bottom-left': 'toast-bottom-left',
    'bottom-center': 'toast-bottom-center',
    'bottom-right': 'toast-bottom-right'
  }
  return classes[props.position]
})

const show = () => {
  visible.value = true
  
  if (props.duration > 0) {
    timeoutId = setTimeout(close, props.duration)
  }
}

const close = () => {
  visible.value = false
  if (timeoutId) {
    clearTimeout(timeoutId)
    timeoutId = null
  }
  emit('close')
}

onMounted(() => {
  show()
})

onUnmounted(() => {
  if (timeoutId) {
    clearTimeout(timeoutId)
    timeoutId = null
  }
})

defineExpose({
  show,
  close
})
</script>

<style scoped>
.toast-container {
  position: fixed;
  z-index: 9999;
  min-width: 300px;
  max-width: 500px;
  background: white;
  border-radius: 8px;
  box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
  border: 1px solid #e5e5e5;
  overflow: hidden;
}

/* РџРѕР·РёС†РёРѕРЅРёСЂРѕРІР°РЅРёРµ */
.toast-top-left {
  top: 20px;
  left: 20px;
}

.toast-top-center {
  top: 20px;
  left: 50%;
  transform: translateX(-50%);
}

.toast-top-right {
  top: 20px;
  right: 20px;
}

.toast-bottom-left {
  bottom: 20px;
  left: 20px;
}

.toast-bottom-center {
  bottom: 20px;
  left: 50%;
  transform: translateX(-50%);
}

.toast-bottom-right {
  bottom: 20px;
  right: 20px;
}

.toast-content {
  display: flex;
  align-items: flex-start;
  gap: 12px;
  padding: 16px;
}

.toast-icon {
  flex-shrink: 0;
  width: 20px;
  height: 20px;
}

.toast-success .toast-icon {
  color: #52c41a;
}

.toast-error .toast-icon {
  color: #ff4d4f;
}

.toast-warning .toast-icon {
  color: #faad14;
}

.toast-info .toast-icon {
  color: #1890ff;
}

.toast-text {
  flex: 1;
  min-width: 0;
}

.toast-title {
  font-size: 16px;
  font-weight: 600;
  color: #1a1a1a;
  margin-bottom: 4px;
  line-height: 1.4;
}

.toast-message {
  font-size: 14px;
  color: #666;
  line-height: 1.4;
}

.toast-close {
  flex-shrink: 0;
  background: none;
  border: none;
  padding: 4px;
  cursor: pointer;
  color: #999;
  border-radius: 4px;
  transition: all 0.2s ease;
}

.toast-close:hover {
  color: #666;
  background: #f5f5f5;
}

.toast-progress {
  position: absolute;
  bottom: 0;
  left: 0;
  right: 0;
  height: 3px;
  background: #f0f0f0;
}

.toast-progress-bar {
  height: 100%;
  background: currentColor;
  width: 100%;
  transform-origin: left;
  animation: toast-progress linear forwards;
}

.toast-success .toast-progress-bar {
  background: #52c41a;
}

.toast-error .toast-progress-bar {
  background: #ff4d4f;
}

.toast-warning .toast-progress-bar {
  background: #faad14;
}

.toast-info .toast-progress-bar {
  background: #1890ff;
}

@keyframes toast-progress {
  from {
    transform: scaleX(1);
  }
  to {
    transform: scaleX(0);
  }
}

/* РђРґР°РїС‚РёРІРЅРѕСЃС‚СЊ */
@media (max-width: 768px) {
  .toast-container {
    left: 16px !important;
    right: 16px !important;
    max-width: none;
    min-width: auto;
    transform: none !important;
  }
  
  .toast-top-center {
    top: 20px;
  }
  
  .toast-bottom-center {
    bottom: 20px;
  }
}
</style>

