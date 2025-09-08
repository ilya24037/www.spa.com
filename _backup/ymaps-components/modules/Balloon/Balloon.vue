<template>
  <!-- Телепорт для рендеринга вне компонента -->
  <Teleport to="body" v-if="shouldRender">
    <Transition
      name="balloon"
      @enter="onEnter"
      @leave="onLeave"
    >
      <div
        v-if="isVisible"
        ref="balloonRef"
        class="ymaps-balloon-wrapper"
        :class="balloonClasses"
        :style="balloonStyles"
        @click.stop
      >
        <!-- Стрелка указатель на точку -->
        <div 
          v-if="!isPanelMode"
          class="ymaps-balloon__tail"
          :style="tailStyles"
        />
        
        <!-- Основной контейнер -->
        <div class="ymaps-balloon">
          <!-- Кнопка закрытия -->
          <button
            v-if="showCloseButton"
            type="button"
            class="ymaps-balloon__close"
            :aria-label="closeButtonLabel"
            @click="handleClose"
          >
            <svg 
              width="14" 
              height="14" 
              viewBox="0 0 14 14"
              fill="currentColor"
            >
              <path d="M8.414 7l5.293-5.293a1 1 0 10-1.414-1.414L7 5.586 1.707.293A1 1 0 10.293 1.707L5.586 7 .293 12.293a1 1 0 101.414 1.414L7 8.414l5.293 5.293a1 1 0 001.414-1.414L8.414 7z"/>
            </svg>
          </button>
          
          <!-- Заголовок если есть -->
          <div 
            v-if="computedHeader"
            class="ymaps-balloon__header"
          >
            {{ computedHeader }}
          </div>
          
          <!-- Контент -->
          <div class="ymaps-balloon__content">
            <!-- Слот для кастомного контента -->
            <slot>
              <!-- Если контент передан как HTML строка -->
              <div 
                v-if="isHtmlContent"
                v-html="sanitizedContent"
              />
              <!-- Если контент передан как текст -->
              <div v-else>
                {{ content }}
              </div>
            </slot>
          </div>
          
          <!-- Футер если есть -->
          <div 
            v-if="computedFooter"
            class="ymaps-balloon__footer"
          >
            <slot name="footer">
              {{ computedFooter }}
            </slot>
          </div>
        </div>
        
        <!-- Оверлей для панельного режима -->
        <div 
          v-if="isPanelMode"
          class="ymaps-balloon__overlay"
          @click="handleClose"
        />
      </div>
    </Transition>
  </Teleport>
</template>

<script setup lang="ts">
import { 
  ref, 
  computed, 
  watch, 
  onMounted, 
  onBeforeUnmount, 
  nextTick,
  type PropType 
} from 'vue'
import Balloon from './Balloon.js'
import type { 
  BalloonPosition, 
  BalloonContent, 
  BalloonOptions 
} from './Balloon'

// =====================
// Props
// =====================
const props = defineProps({
  /** Экземпляр карты Yandex Maps */
  map: {
    type: Object as PropType<any>,
    required: true
  },
  
  /** Позиция balloon на карте */
  position: {
    type: [Array, Object] as PropType<BalloonPosition>,
    default: null
  },
  
  /** Содержимое balloon */
  content: {
    type: [String, Object] as PropType<string | Record<string, any>>,
    default: ''
  },
  
  /** Заголовок balloon */
  header: {
    type: String,
    default: ''
  },
  
  /** Футер balloon */
  footer: {
    type: String,
    default: ''
  },
  
  /** v-model для управления видимостью */
  modelValue: {
    type: Boolean,
    default: false
  },
  
  /** Показывать кнопку закрытия */
  closeButton: {
    type: Boolean,
    default: true
  },
  
  /** Текст для aria-label кнопки закрытия */
  closeButtonLabel: {
    type: String,
    default: 'Закрыть'
  },
  
  /** Автопанорамирование при открытии */
  autoPan: {
    type: Boolean,
    default: true
  },
  
  /** Отступ от краев при автопанорамировании */
  autoPanMargin: {
    type: Number,
    default: 34
  },
  
  /** Максимальная ширина */
  maxWidth: {
    type: Number,
    default: 400
  },
  
  /** Максимальная высота */
  maxHeight: {
    type: Number,
    default: 400
  },
  
  /** Минимальная ширина */
  minWidth: {
    type: Number,
    default: 85
  },
  
  /** Минимальная высота */
  minHeight: {
    type: Number,
    default: 30
  },
  
  /** Смещение от точки привязки */
  offset: {
    type: Array as PropType<[number, number]>,
    default: () => [0, 0]
  },
  
  /** Z-index для balloon */
  zIndex: {
    type: Number,
    default: 1000
  },
  
  /** Задержка открытия */
  openDelay: {
    type: Number,
    default: 0
  },
  
  /** Задержка закрытия */
  closeDelay: {
    type: Number,
    default: 0
  },
  
  /** Площадь карты для режима панели */
  panelMaxMapArea: {
    type: Number,
    default: 160000
  },
  
  /** Дополнительные опции для Balloon */
  options: {
    type: Object as PropType<Partial<BalloonOptions>>,
    default: () => ({})
  }
})

// =====================
// Emits
// =====================
const emit = defineEmits<{
  /** Событие обновления v-model */
  'update:modelValue': [value: boolean]
  /** Событие открытия */
  'open': []
  /** Событие закрытия */
  'close': []
  /** Событие закрытия пользователем */
  'userClose': []
  /** Событие начала автопанорамирования */
  'autopanStart': []
  /** Событие завершения автопанорамирования */
  'autopanEnd': []
  /** Событие ошибки */
  'error': [error: Error]
}>()

// =====================
// Refs и состояние
// =====================
const balloonRef = ref<HTMLElement>()
const isVisible = ref(false)
const isPanelMode = ref(false)
const shouldRender = ref(false)
const balloonPosition = ref({ x: 0, y: 0 })
const mapSize = ref({ width: 0, height: 0 })

let balloonInstance: Balloon | null = null
let openTimer: number | null = null
let closeTimer: number | null = null
let positionUpdateTimer: number | null = null

// =====================
// Computed свойства
// =====================

/** Показывать ли кнопку закрытия */
const showCloseButton = computed(() => {
  return props.closeButton && !isPanelMode.value
})

/** Заголовок из props или content */
const computedHeader = computed(() => {
  if (props.header) return props.header
  if (typeof props.content === 'object' && props.content?.header) {
    return props.content.header
  }
  return ''
})

/** Футер из props или content */
const computedFooter = computed(() => {
  if (props.footer) return props.footer
  if (typeof props.content === 'object' && props.content?.footer) {
    return props.content.footer
  }
  return ''
})

/** Основной контент */
const computedContent = computed(() => {
  if (typeof props.content === 'string') {
    return props.content
  }
  if (typeof props.content === 'object') {
    return props.content.body || ''
  }
  return ''
})

/** Проверка, является ли контент HTML */
const isHtmlContent = computed(() => {
  const content = computedContent.value
  return typeof content === 'string' && /<[a-z][\s\S]*>/i.test(content)
})

/** Очищенный HTML контент (базовая санитизация) */
const sanitizedContent = computed(() => {
  if (!isHtmlContent.value) return ''
  
  // Базовая санитизация - удаляем опасные теги и атрибуты
  let clean = computedContent.value
  
  // Удаляем script теги
  clean = clean.replace(/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/gi, '')
  
  // Удаляем on* атрибуты
  clean = clean.replace(/\s*on\w+\s*=\s*"[^"]*"/gi, '')
  clean = clean.replace(/\s*on\w+\s*=\s*'[^']*'/gi, '')
  
  // Удаляем javascript: в href
  clean = clean.replace(/href\s*=\s*["']?\s*javascript:[^"'>]*/gi, 'href="#"')
  
  return clean
})

/** Классы для balloon */
const balloonClasses = computed(() => {
  return {
    'ymaps-balloon--panel': isPanelMode.value,
    'ymaps-balloon--normal': !isPanelMode.value,
    'ymaps-balloon--has-header': !!computedHeader.value,
    'ymaps-balloon--has-footer': !!computedFooter.value
  }
})

/** Стили для balloon */
const balloonStyles = computed(() => {
  const styles: Record<string, any> = {
    zIndex: props.zIndex
  }
  
  if (isPanelMode.value) {
    // В режиме панели balloon прижат к низу
    styles.position = 'fixed'
    styles.bottom = '0'
    styles.left = '0'
    styles.right = '0'
    styles.maxHeight = `${mapSize.value.height * 0.5}px`
  } else {
    // В обычном режиме позиционируем относительно точки
    styles.position = 'absolute'
    styles.left = `${balloonPosition.value.x + props.offset[0]}px`
    styles.top = `${balloonPosition.value.y + props.offset[1]}px`
    styles.maxWidth = `${props.maxWidth}px`
    styles.maxHeight = `${props.maxHeight}px`
    styles.minWidth = `${props.minWidth}px`
    styles.minHeight = `${props.minHeight}px`
  }
  
  return styles
})

/** Стили для хвостика */
const tailStyles = computed(() => {
  return {
    left: '50%',
    transform: 'translateX(-50%)'
  }
})

// =====================
// Методы
// =====================

/**
 * Открывает balloon
 */
const open = async () => {
  if (!balloonInstance || !props.position) {
    console.warn('Невозможно открыть balloon: нет экземпляра или позиции')
    return
  }
  
  clearTimers()
  shouldRender.value = true
  
  try {
    // Определяем режим панели
    updateMapSize()
    checkPanelMode()
    
    // Открываем через экземпляр Balloon
    await balloonInstance.open(props.position, computedContent.value, {
      ...props.options,
      closeButton: props.closeButton,
      autoPan: props.autoPan && !isPanelMode.value,
      autoPanMargin: props.autoPanMargin,
      openTimeout: 0 // Управляем задержкой в Vue
    })
    
    // Обновляем позицию для отображения
    if (!isPanelMode.value) {
      await updateBalloonPosition()
    }
    
    // Показываем с анимацией
    await nextTick()
    isVisible.value = true
    
    emit('update:modelValue', true)
    emit('open')
    
  } catch (error) {
    console.error('Ошибка открытия balloon:', error)
    emit('error', error as Error)
  }
}

/**
 * Закрывает balloon
 */
const close = async () => {
  if (!balloonInstance) return
  
  clearTimers()
  
  try {
    isVisible.value = false
    
    // Ждем завершения анимации
    setTimeout(async () => {
      if (balloonInstance) {
        await balloonInstance.close(true)
      }
      
      shouldRender.value = false
      emit('update:modelValue', false)
      emit('close')
    }, 300) // Длительность анимации
    
  } catch (error) {
    console.error('Ошибка закрытия balloon:', error)
    emit('error', error as Error)
  }
}

/**
 * Обработчик закрытия пользователем
 */
const handleClose = () => {
  emit('userClose')
  close()
}

/**
 * Обновляет позицию balloon на экране
 */
const updateBalloonPosition = async () => {
  if (!props.map || !props.position || isPanelMode.value) return
  
  try {
    // Конвертируем географические координаты в пиксели
    const projection = props.map.options.get('projection')
    const zoom = props.map.getZoom()
    const center = props.map.getCenter()
    
    // Получаем позицию в пикселях относительно центра карты
    const globalPixels = projection.toGlobalPixels(props.position, zoom)
    const centerPixels = projection.toGlobalPixels(center, zoom)
    
    // Вычисляем смещение от центра
    const containerSize = props.map.container.getSize()
    const x = containerSize[0] / 2 + (globalPixels[0] - centerPixels[0])
    const y = containerSize[1] / 2 + (globalPixels[1] - centerPixels[1])
    
    balloonPosition.value = { x, y }
    
  } catch (error) {
    console.error('Ошибка обновления позиции balloon:', error)
  }
}

/**
 * Проверяет необходимость режима панели
 */
const checkPanelMode = () => {
  const area = mapSize.value.width * mapSize.value.height
  isPanelMode.value = area > 0 && area < props.panelMaxMapArea
}

/**
 * Обновляет размеры карты
 */
const updateMapSize = () => {
  if (!props.map) return
  
  const size = props.map.container.getSize()
  mapSize.value = {
    width: size[0],
    height: size[1]
  }
}

/**
 * Очищает все таймеры
 */
const clearTimers = () => {
  if (openTimer) {
    clearTimeout(openTimer)
    openTimer = null
  }
  if (closeTimer) {
    clearTimeout(closeTimer)
    closeTimer = null
  }
  if (positionUpdateTimer) {
    clearTimeout(positionUpdateTimer)
    positionUpdateTimer = null
  }
}

/**
 * Обработчик анимации входа
 */
const onEnter = (el: Element) => {
  const element = el as HTMLElement
  element.style.opacity = '0'
  element.style.transform = 'scale(0.9)'
  
  requestAnimationFrame(() => {
    element.style.transition = 'opacity 0.3s, transform 0.3s'
    element.style.opacity = '1'
    element.style.transform = 'scale(1)'
  })
}

/**
 * Обработчик анимации выхода
 */
const onLeave = (el: Element) => {
  const element = el as HTMLElement
  element.style.opacity = '0'
  element.style.transform = 'scale(0.9)'
}

// =====================
// Watchers
// =====================

// Следим за v-model
watch(() => props.modelValue, (newValue) => {
  if (newValue !== isVisible.value) {
    if (newValue) {
      if (props.openDelay > 0) {
        openTimer = setTimeout(open, props.openDelay) as unknown as number
      } else {
        open()
      }
    } else {
      if (props.closeDelay > 0) {
        closeTimer = setTimeout(close, props.closeDelay) as unknown as number
      } else {
        close()
      }
    }
  }
})

// Следим за позицией
watch(() => props.position, async (newPosition) => {
  if (!balloonInstance || !isVisible.value) return
  
  if (newPosition) {
    balloonInstance.setPosition(newPosition)
    
    // Обновляем позицию с небольшой задержкой для плавности
    if (positionUpdateTimer) clearTimeout(positionUpdateTimer)
    positionUpdateTimer = setTimeout(() => {
      updateBalloonPosition()
    }, 50) as unknown as number
  }
}, { deep: true })

// Следим за контентом
watch([
  () => props.content,
  () => props.header,
  () => props.footer
], () => {
  if (!balloonInstance || !isVisible.value) return
  
  const content = {
    header: computedHeader.value,
    body: computedContent.value,
    footer: computedFooter.value
  }
  
  balloonInstance.setContent(content)
}, { deep: true })

// Следим за размером карты
watch(() => props.map?.container?.getSize?.(), () => {
  updateMapSize()
  checkPanelMode()
  
  if (isVisible.value) {
    updateBalloonPosition()
  }
})

// =====================
// Lifecycle
// =====================

onMounted(() => {
  // Создаем экземпляр Balloon
  if (props.map) {
    const options: BalloonOptions = {
      ...props.options,
      closeButton: false, // Управляем кнопкой в Vue
      autoPan: props.autoPan,
      autoPanMargin: props.autoPanMargin,
      maxWidth: props.maxWidth,
      maxHeight: props.maxHeight,
      minWidth: props.minWidth,
      minHeight: props.minHeight,
      offset: props.offset,
      panelMaxMapArea: props.panelMaxMapArea,
      
      // Обработчики событий
      onautopanstart: () => emit('autopanStart'),
      onautopancomplete: () => emit('autopanEnd')
    }
    
    try {
      balloonInstance = new Balloon(props.map, options)
      
      // Если modelValue true при монтировании
      if (props.modelValue) {
        open()
      }
    } catch (error) {
      console.error('Ошибка создания Balloon:', error)
      emit('error', error as Error)
    }
  }
  
  // Обновляем размеры карты
  updateMapSize()
})

onBeforeUnmount(() => {
  clearTimers()
  
  // Уничтожаем экземпляр
  if (balloonInstance) {
    balloonInstance.destroy()
    balloonInstance = null
  }
})

// =====================
// Expose
// =====================
defineExpose({
  open,
  close,
  isOpen: () => isVisible.value,
  updatePosition: updateBalloonPosition
})
</script>

<style scoped>
/* Обертка balloon */
.ymaps-balloon-wrapper {
  pointer-events: auto;
  user-select: none;
}

/* Обычный режим */
.ymaps-balloon--normal {
  transform: translate(-50%, -100%);
  margin-top: -10px;
}

/* Панельный режим */
.ymaps-balloon--panel {
  transform: none !important;
  margin: 0 !important;
}

/* Основной контейнер */
.ymaps-balloon {
  position: relative;
  background: #fff;
  border-radius: 8px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
  padding: 12px;
  font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
  font-size: 14px;
  line-height: 1.5;
  color: #333;
}

/* Панельный режим стили */
.ymaps-balloon--panel .ymaps-balloon {
  border-radius: 0;
  box-shadow: 0 -2px 8px rgba(0, 0, 0, 0.15);
  width: 100%;
  max-width: none;
  overflow-y: auto;
}

/* Хвостик указатель */
.ymaps-balloon__tail {
  position: absolute;
  bottom: -8px;
  width: 16px;
  height: 16px;
  background: #fff;
  transform: rotate(45deg);
  box-shadow: 2px 2px 4px rgba(0, 0, 0, 0.1);
  z-index: -1;
}

/* Кнопка закрытия */
.ymaps-balloon__close {
  position: absolute;
  top: 8px;
  right: 8px;
  width: 24px;
  height: 24px;
  padding: 0;
  background: transparent;
  border: none;
  cursor: pointer;
  color: #999;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 4px;
  transition: all 0.2s;
}

.ymaps-balloon__close:hover {
  background: #f0f0f0;
  color: #333;
}

.ymaps-balloon__close:active {
  background: #e0e0e0;
}

/* Заголовок */
.ymaps-balloon__header {
  font-weight: 600;
  font-size: 16px;
  margin-bottom: 8px;
  padding-right: 20px;
  color: #000;
}

/* Контент */
.ymaps-balloon__content {
  word-wrap: break-word;
  overflow-wrap: break-word;
}

.ymaps-balloon--has-header .ymaps-balloon__content {
  margin-top: 4px;
}

.ymaps-balloon--has-footer .ymaps-balloon__content {
  margin-bottom: 8px;
}

/* Футер */
.ymaps-balloon__footer {
  margin-top: 8px;
  padding-top: 8px;
  border-top: 1px solid #e5e5e5;
  font-size: 12px;
  color: #666;
}

/* Оверлей для панели */
.ymaps-balloon__overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.3);
  z-index: -1;
}

/* Анимации */
.balloon-enter-active,
.balloon-leave-active {
  transition: opacity 0.3s, transform 0.3s;
}

.balloon-enter-from,
.balloon-leave-to {
  opacity: 0;
  transform: scale(0.9);
}

/* Адаптивность */
@media (max-width: 640px) {
  .ymaps-balloon {
    max-width: calc(100vw - 40px);
  }
  
  .ymaps-balloon--panel .ymaps-balloon {
    max-height: 50vh;
  }
}

/* Темная тема (опционально) */
@media (prefers-color-scheme: dark) {
  .ymaps-balloon {
    background: #2c2c2c;
    color: #e0e0e0;
  }
  
  .ymaps-balloon__tail {
    background: #2c2c2c;
  }
  
  .ymaps-balloon__header {
    color: #fff;
  }
  
  .ymaps-balloon__footer {
    border-top-color: #444;
    color: #999;
  }
  
  .ymaps-balloon__close {
    color: #999;
  }
  
  .ymaps-balloon__close:hover {
    background: #3a3a3a;
    color: #e0e0e0;
  }
}
</style>