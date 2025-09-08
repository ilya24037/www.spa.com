<template>
  <Teleport v-if="isMounted && mapContainer" :to="mapContainer">
    <!-- Кастомный контент метки (через slot) -->
    <div
      v-if="showCustomContent && slots.default"
      ref="customContentRef"
      :class="[
        'ymaps-placemark-custom',
        customClass,
        {
          'ymaps-placemark-dragging': isDragging,
          'ymaps-placemark-hovered': isHovered,
          'ymaps-placemark-selected': isSelected
        }
      ]"
      :style="customContentStyle"
      @click="handleCustomClick"
      @mouseenter="handleMouseEnter"
      @mouseleave="handleMouseLeave"
    >
      <slot />
    </div>
  </Teleport>
</template>

<script setup lang="ts">
/**
 * Vue 3 компонент для меток на Yandex Maps
 * Поддерживает все возможности класса Placemark
 * @module PlacemarkVue
 */
import {
  ref,
  computed,
  watch,
  onMounted,
  onUnmounted,
  nextTick,
  useSlots,
  type PropType
} from 'vue'
import Placemark from './Placemark.js'
import type {
  PlacemarkPosition,
  PlacemarkOptions,
  PlacemarkPreset,
  PlacemarkIconOptions,
  PlacemarkEvents
} from './Placemark'

// Props определение
const props = defineProps({
  /**
   * Экземпляр карты Yandex Maps
   */
  map: {
    type: Object as PropType<any>,
    required: true
  },
  
  /**
   * Позиция метки [latitude, longitude]
   */
  position: {
    type: [Array, Object] as PropType<PlacemarkPosition>,
    required: true
  },
  
  /**
   * Preset стиль метки
   */
  preset: {
    type: String as PropType<PlacemarkPreset>,
    default: 'islands#blueIcon'
  },
  
  /**
   * URL изображения для кастомной иконки
   */
  icon: {
    type: String,
    default: ''
  },
  
  /**
   * Размер иконки [width, height]
   */
  iconSize: {
    type: Array as PropType<[number, number]>,
    default: () => [30, 42]
  },
  
  /**
   * Смещение иконки [x, y]
   */
  iconOffset: {
    type: Array as PropType<[number, number]>,
    default: () => [-15, -42]
  },
  
  /**
   * Цвет иконки (для preset)
   */
  iconColor: {
    type: String,
    default: ''
  },
  
  /**
   * Текст внутри метки
   */
  iconContent: {
    type: String,
    default: ''
  },
  
  /**
   * Содержимое всплывающего окна
   */
  balloonContent: {
    type: [String, Object],
    default: ''
  },
  
  /**
   * Заголовок balloon
   */
  balloonHeader: {
    type: String,
    default: ''
  },
  
  /**
   * Основной текст balloon
   */
  balloonBody: {
    type: String,
    default: ''
  },
  
  /**
   * Футер balloon
   */
  balloonFooter: {
    type: String,
    default: ''
  },
  
  /**
   * Содержимое хинта
   */
  hintContent: {
    type: String,
    default: ''
  },
  
  /**
   * Можно ли перетаскивать метку
   */
  draggable: {
    type: Boolean,
    default: false
  },
  
  /**
   * Видимость метки
   */
  visible: {
    type: Boolean,
    default: true
  },
  
  /**
   * Анимация при появлении
   */
  animation: {
    type: String as PropType<'bounce' | 'drop' | 'pulse' | 'shake' | ''>,
    default: ''
  },
  
  /**
   * Длительность анимации
   */
  animationDuration: {
    type: Number,
    default: 500
  },
  
  /**
   * Прозрачность метки
   */
  opacity: {
    type: Number,
    default: 1,
    validator: (v: number) => v >= 0 && v <= 1
  },
  
  /**
   * Курсор при наведении
   */
  cursor: {
    type: String,
    default: 'pointer'
  },
  
  /**
   * Z-индекс метки
   */
  zIndex: {
    type: Number,
    default: 0
  },
  
  /**
   * Открывать balloon при клике
   */
  openBalloonOnClick: {
    type: Boolean,
    default: true
  },
  
  /**
   * Открывать хинт при наведении
   */
  openHintOnHover: {
    type: Boolean,
    default: true
  },
  
  /**
   * Скрывать иконку при открытии balloon
   */
  hideIconOnBalloonOpen: {
    type: Boolean,
    default: false
  },
  
  /**
   * Пользовательские данные
   */
  data: {
    type: Object,
    default: () => ({})
  },
  
  /**
   * Идентификатор метки
   */
  id: {
    type: [String, Number],
    default: null
  },
  
  /**
   * Имя метки
   */
  name: {
    type: String,
    default: ''
  },
  
  /**
   * Кастомный CSS класс
   */
  customClass: {
    type: String,
    default: ''
  },
  
  /**
   * Использовать кастомный контент через slot
   */
  useCustomContent: {
    type: Boolean,
    default: false
  },
  
  /**
   * Автопанорамирование при перетаскивании
   */
  autoPan: {
    type: Boolean,
    default: true
  },
  
  /**
   * Поднимать при наведении
   */
  raiseOnHover: {
    type: Boolean,
    default: true
  },
  
  /**
   * Выбрана ли метка (для управления извне)
   */
  selected: {
    type: Boolean,
    default: false
  }
})

// Emits
const emit = defineEmits<{
  'click': [event: any]
  'dblclick': [event: any]
  'contextmenu': [event: any]
  'mouseenter': [event: any]
  'mouseleave': [event: any]
  'dragstart': [event: any]
  'drag': [event: any]
  'dragend': [event: any]
  'positionChange': [position: [number, number]]
  'balloonOpen': []
  'balloonClose': []
  'visibilityChange': [visible: boolean]
  'ready': [placemark: Placemark]
  'error': [error: Error]
}>()

// Slots
const slots = useSlots()

// Refs
const customContentRef = ref<HTMLElement>()
const placemarkInstance = ref<Placemark | null>(null)
const mapContainer = ref<HTMLElement | null>(null)
const isMounted = ref(false)
const isDragging = ref(false)
const isHovered = ref(false)
const isSelected = ref(false)
const currentPosition = ref<[number, number] | null>(null)

// Computed
const normalizedPosition = computed<[number, number] | null>(() => {
  if (!props.position) return null
  
  // Массив координат
  if (Array.isArray(props.position)) {
    return props.position as [number, number]
  }
  
  // Объект с lat/lng
  if ('lat' in props.position && 'lng' in props.position) {
    return [props.position.lat, props.position.lng]
  }
  
  // Объект с latitude/longitude
  if ('latitude' in props.position && 'longitude' in props.position) {
    return [props.position.latitude, props.position.longitude]
  }
  
  return null
})

const placemarkOptions = computed<PlacemarkOptions>(() => {
  const options: PlacemarkOptions = {
    preset: props.preset,
    draggable: props.draggable,
    visible: props.visible,
    opacity: props.opacity,
    cursor: props.cursor,
    zIndex: props.zIndex,
    openBalloonOnClick: props.openBalloonOnClick,
    openHintOnHover: props.openHintOnHover,
    hideIconOnBalloonOpen: props.hideIconOnBalloonOpen,
    autoPan: props.autoPan,
    raiseOnHover: props.raiseOnHover
  }
  
  // Кастомная иконка
  if (props.icon) {
    options.iconImageHref = props.icon
    options.iconImageSize = props.iconSize
    options.iconImageOffset = props.iconOffset
  }
  
  // Цвет иконки
  if (props.iconColor) {
    options.iconColor = props.iconColor
  }
  
  // Текст в метке
  if (props.iconContent) {
    options.iconContent = props.iconContent
  }
  
  // Содержимое хинта
  if (props.hintContent) {
    options.hintContent = props.hintContent
  }
  
  return options
})

const balloonContentObject = computed(() => {
  // Если передан объект
  if (typeof props.balloonContent === 'object') {
    return props.balloonContent
  }
  
  // Если переданы отдельные части
  if (props.balloonHeader || props.balloonBody || props.balloonFooter || props.balloonContent) {
    return {
      header: props.balloonHeader,
      body: props.balloonBody || props.balloonContent,
      footer: props.balloonFooter
    }
  }
  
  return null
})

const showCustomContent = computed(() => {
  return props.useCustomContent && slots.default
})

const customContentStyle = computed(() => {
  if (!currentPosition.value || !mapContainer.value) {
    return { display: 'none' }
  }
  
  // Здесь должна быть логика преобразования географических координат
  // в пиксельные координаты на карте
  // Это упрощенная версия
  return {
    position: 'absolute',
    zIndex: props.zIndex + (isHovered.value ? 1000 : 0),
    opacity: props.opacity,
    cursor: props.cursor,
    transform: `translate(-50%, -100%)`,
    transition: isDragging.value ? 'none' : 'all 0.3s ease'
  }
})

// Methods
const createPlacemark = async () => {
  try {
    if (!props.map || !normalizedPosition.value) {
      throw new Error('Map или position не определены')
    }
    
    // Создаем свойства
    const properties: Record<string, any> = {
      ...props.data,
      id: props.id,
      name: props.name
    }
    
    // Добавляем balloon контент
    if (balloonContentObject.value) {
      properties.balloonContent = balloonContentObject.value
    }
    
    // Создаем метку
    placemarkInstance.value = new Placemark(
      normalizedPosition.value,
      properties,
      placemarkOptions.value
    )
    
    // Добавляем обработчики событий
    setupEventListeners()
    
    // Добавляем на карту
    await placemarkInstance.value.addToMap(props.map)
    
    // Анимация при появлении
    if (props.animation) {
      await placemarkInstance.value.animate(props.animation, {
        duration: props.animationDuration
      })
    }
    
    currentPosition.value = normalizedPosition.value
    emit('ready', placemarkInstance.value)
    
  } catch (error) {
    console.error('Ошибка создания метки:', error)
    emit('error', error as Error)
  }
}

const setupEventListeners = () => {
  if (!placemarkInstance.value) return
  
  // Клик
  placemarkInstance.value.on('click', (event) => {
    emit('click', event)
  })
  
  // Двойной клик
  placemarkInstance.value.on('dblclick', (event) => {
    emit('dblclick', event)
  })
  
  // Правый клик
  placemarkInstance.value.on('contextmenu', (event) => {
    emit('contextmenu', event)
  })
  
  // Наведение
  placemarkInstance.value.on('mouseenter', (event) => {
    isHovered.value = true
    emit('mouseenter', event)
  })
  
  // Уход мыши
  placemarkInstance.value.on('mouseleave', (event) => {
    isHovered.value = false
    emit('mouseleave', event)
  })
  
  // Перетаскивание
  if (props.draggable) {
    placemarkInstance.value.on('dragstart', (event) => {
      isDragging.value = true
      emit('dragstart', event)
    })
    
    placemarkInstance.value.on('drag', (event) => {
      if (event.coords) {
        currentPosition.value = event.coords
      }
      emit('drag', event)
    })
    
    placemarkInstance.value.on('dragend', (event) => {
      isDragging.value = false
      if (event.coords) {
        currentPosition.value = event.coords
        emit('positionChange', event.coords)
      }
      emit('dragend', event)
    })
  }
}

const updatePosition = async () => {
  if (!placemarkInstance.value || !normalizedPosition.value) return
  
  try {
    await placemarkInstance.value.setPosition(normalizedPosition.value, true)
    currentPosition.value = normalizedPosition.value
    emit('positionChange', normalizedPosition.value)
  } catch (error) {
    console.error('Ошибка обновления позиции:', error)
    emit('error', error as Error)
  }
}

const updateIcon = () => {
  if (!placemarkInstance.value) return
  
  if (props.icon) {
    placemarkInstance.value.setIcon({
      iconImageHref: props.icon,
      iconImageSize: props.iconSize,
      iconImageOffset: props.iconOffset
    })
  } else if (props.preset) {
    placemarkInstance.value.setIcon(props.preset)
  }
}

const updateBalloonContent = () => {
  if (!placemarkInstance.value || !balloonContentObject.value) return
  placemarkInstance.value.setBalloonContent(balloonContentObject.value)
}

const updateVisibility = () => {
  if (!placemarkInstance.value) return
  
  if (props.visible) {
    placemarkInstance.value.show()
  } else {
    placemarkInstance.value.hide()
  }
  
  emit('visibilityChange', props.visible)
}

const updateDraggable = () => {
  if (!placemarkInstance.value) return
  
  if (props.draggable) {
    placemarkInstance.value.enableDragging()
  } else {
    placemarkInstance.value.disableDragging()
  }
}

const handleCustomClick = (event: MouseEvent) => {
  if (!placemarkInstance.value) return
  emit('click', { originalEvent: event, target: placemarkInstance.value })
}

const handleMouseEnter = () => {
  isHovered.value = true
  emit('mouseenter', { target: placemarkInstance.value })
}

const handleMouseLeave = () => {
  isHovered.value = false
  emit('mouseleave', { target: placemarkInstance.value })
}

// Public methods (через defineExpose)
const openBalloon = async () => {
  if (!placemarkInstance.value) return
  await placemarkInstance.value.openBalloon()
  emit('balloonOpen')
}

const closeBalloon = async () => {
  if (!placemarkInstance.value) return
  await placemarkInstance.value.closeBalloon()
  emit('balloonClose')
}

const animatePlacemark = async (
  type: 'bounce' | 'drop' | 'pulse' | 'shake',
  duration?: number
) => {
  if (!placemarkInstance.value) return
  await placemarkInstance.value.animate(type, { duration })
}

const setData = (key: string | object, value?: any) => {
  if (!placemarkInstance.value) return
  placemarkInstance.value.setData(key, value)
}

const getData = (key?: string) => {
  if (!placemarkInstance.value) return null
  return placemarkInstance.value.getData(key)
}

const getPlacemarkInstance = () => placemarkInstance.value

// Lifecycle
onMounted(async () => {
  // Ищем контейнер карты
  if (props.map && props.map.container) {
    mapContainer.value = props.map.container.getElement()
  }
  
  isMounted.value = true
  await nextTick()
  await createPlacemark()
})

onUnmounted(() => {
  if (placemarkInstance.value) {
    placemarkInstance.value.destroy()
    placemarkInstance.value = null
  }
})

// Watchers
watch(() => props.position, updatePosition)
watch(() => props.icon, updateIcon)
watch(() => props.preset, updateIcon)
watch(() => props.iconColor, () => {
  if (placemarkInstance.value && props.iconColor) {
    placemarkInstance.value.setOptions({ iconColor: props.iconColor })
  }
})
watch(() => props.iconContent, () => {
  if (placemarkInstance.value) {
    placemarkInstance.value.setOptions({ iconContent: props.iconContent })
  }
})
watch(() => [props.balloonContent, props.balloonHeader, props.balloonBody, props.balloonFooter],
  updateBalloonContent
)
watch(() => props.hintContent, () => {
  if (placemarkInstance.value) {
    placemarkInstance.value.setHintContent(props.hintContent)
  }
})
watch(() => props.visible, updateVisibility)
watch(() => props.draggable, updateDraggable)
watch(() => props.opacity, () => {
  if (placemarkInstance.value) {
    placemarkInstance.value.setOptions({ opacity: props.opacity })
  }
})
watch(() => props.zIndex, () => {
  if (placemarkInstance.value) {
    placemarkInstance.value.setOptions({ zIndex: props.zIndex })
  }
})
watch(() => props.selected, () => {
  isSelected.value = props.selected
})

// Expose public methods
defineExpose({
  openBalloon,
  closeBalloon,
  animatePlacemark,
  setData,
  getData,
  getPlacemarkInstance
})
</script>

<style scoped>
.ymaps-placemark-custom {
  pointer-events: auto;
  user-select: none;
  -webkit-user-select: none;
}

.ymaps-placemark-custom.ymaps-placemark-hovered {
  z-index: 1000 !important;
}

.ymaps-placemark-custom.ymaps-placemark-selected {
  z-index: 1001 !important;
}

.ymaps-placemark-custom.ymaps-placemark-dragging {
  opacity: 0.7;
  cursor: move !important;
}

/* Анимации */
@keyframes bounce {
  0%, 100% { transform: translateY(0); }
  50% { transform: translateY(-20px); }
}

@keyframes drop {
  0% { transform: translateY(-100px); opacity: 0; }
  100% { transform: translateY(0); opacity: 1; }
}

@keyframes pulse {
  0%, 100% { transform: scale(1); }
  50% { transform: scale(1.1); }
}

@keyframes shake {
  0%, 100% { transform: translateX(0); }
  25% { transform: translateX(-5px); }
  75% { transform: translateX(5px); }
}

/* Темная тема */
@media (prefers-color-scheme: dark) {
  .ymaps-placemark-custom {
    filter: brightness(0.9);
  }
}
</style>