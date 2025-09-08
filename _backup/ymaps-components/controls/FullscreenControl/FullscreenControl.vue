<template>
  <div
    v-if="isSupported && isVisible"
    ref="fullscreenControlRef"
    class="ymaps-fullscreen-control"
    :class="controlClasses"
    :style="controlStyles"
    @click="toggleFullscreen"
    @keydown="onKeyDown"
    :title="buttonTitle"
    :aria-label="buttonTitle"
    tabindex="0"
    role="button"
  >
    <span 
      class="ymaps-fullscreen-icon"
      :class="iconClasses"
      v-html="currentIcon"
    />
  </div>
</template>

<script setup lang="ts">
/**
 * FullscreenControl Vue Component
 * 
 * Vue 3 компонент для управления полноэкранным режимом Яндекс Карт
 * Следует принципам CLAUDE.md: KISS, SOLID, TypeScript strict mode
 * 
 * @version 1.0.0
 * @author YMaps Components
 * @created 2025-09-04
 */

import { 
  ref, 
  computed, 
  onMounted, 
  onBeforeUnmount, 
  watch,
  nextTick,
  type Ref
} from 'vue'

// Типы для строгой типизации
interface FullscreenControlProps {
  /** Позиция контрола на карте */
  position?: 'topLeft' | 'topRight' | 'bottomLeft' | 'bottomRight'
  /** Видимость контрола */
  visible?: boolean
  /** Размеры кнопки */
  size?: { width: number; height: number }
  /** Z-index элемента */
  zIndex?: number
  /** Заголовок для accessibility */
  title?: string
  /** Иконки контрола */
  icons?: {
    enter?: string
    exit?: string
  }
  /** Экземпляр карты */
  map?: any
}

interface FullscreenControlEmits {
  /** Вход в полноэкранный режим */
  (event: 'fullscreenenter'): void
  /** Выход из полноэкранного режима */
  (event: 'fullscreenexit'): void
  /** Клик по кнопке */
  (event: 'click'): void
  /** Изменение состояния */
  (event: 'statechange', state: { isFullscreen: boolean }): void
}

// Props с дефолтными значениями
const props = withDefaults(defineProps<FullscreenControlProps>(), {
  position: 'topRight',
  visible: true,
  size: () => ({ width: 36, height: 36 }),
  zIndex: 1000,
  title: '',
  icons: () => ({
    enter: '⛶',
    exit: '⛷'
  })
})

// Emits
const emit = defineEmits<FullscreenControlEmits>()

// Реактивные переменные
const fullscreenControlRef: Ref<HTMLElement | null> = ref(null)
const isFullscreen: Ref<boolean> = ref(false)
const isSupported: Ref<boolean> = ref(false)

// Вычисляемые свойства
const isVisible = computed(() => props.visible && isSupported.value)

const currentIcon = computed(() => {
  return isFullscreen.value ? 
    (props.icons.exit || '⛷') : 
    (props.icons.enter || '⛶')
})

const buttonTitle = computed(() => {
  if (props.title) return props.title
  return isFullscreen.value ? 
    'Выйти из полноэкранного режима' : 
    'Войти в полноэкранный режим'
})

const controlClasses = computed(() => ({
  'ymaps-fullscreen-control--active': isFullscreen.value,
  'ymaps-fullscreen-control--supported': isSupported.value,
  'ymaps-fullscreen-control--visible': isVisible.value
}))

const controlStyles = computed(() => ({
  position: 'absolute' as const,
  zIndex: props.zIndex,
  width: `${props.size.width}px`,
  height: `${props.size.height}px`,
  ...getPositionStyles(props.position)
}))

const iconClasses = computed(() => ({
  'ymaps-fullscreen-icon--enter': !isFullscreen.value,
  'ymaps-fullscreen-icon--exit': isFullscreen.value
}))

/**
 * Получение стилей позиционирования
 * @param position - Позиция контрола
 */
function getPositionStyles(position: string): Record<string, string> {
  const offset = '10px'
  
  switch (position) {
    case 'topLeft':
      return { top: offset, left: offset }
    case 'topRight':
      return { top: offset, right: offset }
    case 'bottomLeft':
      return { bottom: offset, left: offset }
    case 'bottomRight':
      return { bottom: offset, right: offset }
    default:
      return { top: offset, right: offset }
  }
}

/**
 * Проверка поддержки Fullscreen API
 */
function checkFullscreenSupport(): boolean {
  return !!(
    document.fullscreenEnabled ||
    (document as any).webkitFullscreenEnabled ||
    (document as any).mozFullScreenEnabled ||
    (document as any).msFullscreenEnabled
  )
}

/**
 * Получение текущего состояния fullscreen
 */
function getCurrentFullscreenState(): boolean {
  return !!(
    document.fullscreenElement ||
    (document as any).webkitFullscreenElement ||
    (document as any).mozFullScreenElement ||
    (document as any).msFullscreenElement
  )
}

/**
 * Вход в полноэкранный режим
 */
async function enterFullscreen(): Promise<boolean> {
  if (!isSupported.value || !props.map) {
    return false
  }

  try {
    const container = props.map.container?.getElement()
    if (!container) {
      console.error('FullscreenControl: Контейнер карты не найден')
      return false
    }

    if (container.requestFullscreen) {
      await container.requestFullscreen()
    } else if (container.webkitRequestFullscreen) {
      await container.webkitRequestFullscreen()
    } else if (container.mozRequestFullScreen) {
      await container.mozRequestFullScreen()
    } else if (container.msRequestFullscreen) {
      await container.msRequestFullscreen()
    } else {
      console.warn('FullscreenControl: Fullscreen API недоступен')
      return false
    }

    return true
  } catch (error) {
    console.error('FullscreenControl: Ошибка входа в полноэкранный режим:', error)
    return false
  }
}

/**
 * Выход из полноэкранного режима
 */
async function exitFullscreen(): Promise<boolean> {
  if (!isSupported.value) {
    return false
  }

  try {
    if (document.exitFullscreen) {
      await document.exitFullscreen()
    } else if ((document as any).webkitExitFullscreen) {
      await (document as any).webkitExitFullscreen()
    } else if ((document as any).mozCancelFullScreen) {
      await (document as any).mozCancelFullScreen()
    } else if ((document as any).msExitFullscreen) {
      await (document as any).msExitFullscreen()
    } else {
      console.warn('FullscreenControl: Exit fullscreen API недоступен')
      return false
    }

    return true
  } catch (error) {
    console.error('FullscreenControl: Ошибка выхода из полноэкранного режима:', error)
    return false
  }
}

/**
 * Переключение полноэкранного режима
 */
async function toggleFullscreen(): Promise<void> {
  try {
    if (isFullscreen.value) {
      await exitFullscreen()
    } else {
      await enterFullscreen()
    }
    
    emit('click')
  } catch (error) {
    console.error('FullscreenControl: Ошибка переключения режима:', error)
  }
}

/**
 * Обработчик нажатия клавиш
 * @param event - Событие клавиатуры
 */
function onKeyDown(event: KeyboardEvent): void {
  if (event.key === 'Enter' || event.key === ' ') {
    event.preventDefault()
    toggleFullscreen()
  }
}

/**
 * Обработчик изменения fullscreen состояния
 */
function onFullscreenChange(): void {
  const newState = getCurrentFullscreenState()
  
  if (newState !== isFullscreen.value) {
    isFullscreen.value = newState
    
    if (newState) {
      emit('fullscreenenter')
    } else {
      emit('fullscreenexit')
    }
    
    emit('statechange', { isFullscreen: newState })
  }
}

/**
 * Привязка событий документа
 */
function bindDocumentEvents(): void {
  document.addEventListener('fullscreenchange', onFullscreenChange)
  document.addEventListener('webkitfullscreenchange', onFullscreenChange)
  document.addEventListener('mozfullscreenchange', onFullscreenChange)
  document.addEventListener('msfullscreenchange', onFullscreenChange)
}

/**
 * Отвязка событий документа
 */
function unbindDocumentEvents(): void {
  document.removeEventListener('fullscreenchange', onFullscreenChange)
  document.removeEventListener('webkitfullscreenchange', onFullscreenChange)
  document.removeEventListener('mozfullscreenchange', onFullscreenChange)
  document.removeEventListener('msfullscreenchange', onFullscreenChange)
}

// Watchers для отслеживания изменений props
watch(() => props.visible, (newVisible) => {
  if (newVisible && !isSupported.value) {
    console.warn('FullscreenControl: Fullscreen API не поддерживается')
  }
}, { immediate: true })

// Lifecycle hooks
onMounted(async () => {
  // Проверка поддержки API
  isSupported.value = checkFullscreenSupport()
  
  if (!isSupported.value) {
    console.warn('FullscreenControl: Fullscreen API не поддерживается в этом браузере')
    return
  }

  // Установка начального состояния
  isFullscreen.value = getCurrentFullscreenState()
  
  // Привязка событий
  bindDocumentEvents()
  
  // Фокус для accessibility
  await nextTick()
  if (fullscreenControlRef.value && props.visible) {
    fullscreenControlRef.value.setAttribute('tabindex', '0')
  }
})

onBeforeUnmount(() => {
  // Выход из fullscreen при размонтировании компонента
  if (isFullscreen.value) {
    exitFullscreen().catch(console.error)
  }
  
  // Отвязка событий
  unbindDocumentEvents()
})

// Экспорт для доступа из родительского компонента
defineExpose({
  enterFullscreen,
  exitFullscreen,
  toggleFullscreen,
  isFullscreen: () => isFullscreen.value,
  isSupported: () => isSupported.value
})
</script>

<style scoped>
.ymaps-fullscreen-control {
  @apply bg-white border border-gray-200 rounded-md shadow-md cursor-pointer;
  @apply flex items-center justify-center;
  @apply text-gray-700 font-medium;
  @apply transition-all duration-200 ease-in-out;
  @apply select-none;
  @apply hover:bg-gray-50 hover:shadow-lg;
  @apply focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-opacity-50;
  @apply active:scale-95;
  
  /* Размеры по умолчанию */
  min-width: 36px;
  min-height: 36px;
}

.ymaps-fullscreen-control--active {
  @apply bg-blue-600 text-white border-blue-600;
  @apply hover:bg-blue-700;
}

.ymaps-fullscreen-control--supported {
  @apply visible;
}

.ymaps-fullscreen-control:not(.ymaps-fullscreen-control--supported) {
  @apply hidden;
}

.ymaps-fullscreen-icon {
  @apply text-lg leading-none;
  @apply transition-transform duration-200;
}

.ymaps-fullscreen-icon--enter {
  @apply transform-gpu;
}

.ymaps-fullscreen-icon--exit {
  @apply transform-gpu rotate-45;
}

/* Мобильная адаптивность */
@media (max-width: 768px) {
  .ymaps-fullscreen-control {
    @apply w-12 h-12 text-xl;
    @apply shadow-lg border-2;
  }
}

/* Высокий контраст для accessibility */
@media (prefers-contrast: high) {
  .ymaps-fullscreen-control {
    @apply border-2 border-black;
  }
  
  .ymaps-fullscreen-control--active {
    @apply bg-black text-white border-black;
  }
}

/* Уменьшение анимации для пользователей с ограниченными возможностями */
@media (prefers-reduced-motion: reduce) {
  .ymaps-fullscreen-control,
  .ymaps-fullscreen-icon {
    @apply transition-none;
  }
}

/* Dark mode поддержка */
@media (prefers-color-scheme: dark) {
  .ymaps-fullscreen-control {
    @apply bg-gray-800 border-gray-600 text-gray-200;
    @apply hover:bg-gray-700;
  }
  
  .ymaps-fullscreen-control--active {
    @apply bg-blue-700 border-blue-700;
    @apply hover:bg-blue-800;
  }
}
</style>