<template>
  <div
    v-if="isSupported && isVisible"
    ref="geolocationControlRef"
    class="ymaps-geolocation-control"
    :class="controlClasses"
    :style="controlStyles"
    @click="handleClick"
    @keydown="onKeyDown"
    :title="buttonTitle"
    :aria-label="buttonTitle"
    tabindex="0"
    role="button"
    :disabled="isLocating"
  >
    <Transition name="geolocation-icon" mode="out-in">
      <span 
        v-if="!isLocating"
        key="icon"
        class="ymaps-geolocation-icon"
        :class="iconClasses"
        v-html="currentIcon"
      />
      <span 
        v-else
        key="spinner"
        class="ymaps-geolocation-spinner"
        :class="spinnerClasses"
        v-html="spinnerIcon"
      />
    </Transition>
  </div>
</template>

<script setup lang="ts">
/**
 * GeolocationControl Vue Component
 * 
 * Vue 3 компонент для управления геолокацией пользователя на Яндекс Картах
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
interface GeolocationResult {
  coords: [number, number]
  accuracy: number
  altitude: number | null
  altitudeAccuracy: number | null
  heading: number | null
  speed: number | null
  timestamp: number
}

interface GeolocationError {
  message: string
  code: 'PERMISSION_DENIED' | 'POSITION_UNAVAILABLE' | 'TIMEOUT' | 'UNKNOWN_ERROR'
  originalError: any
}

interface GeolocationControlProps {
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
  /** Не создавать метку на карте */
  noPlacemark?: boolean
  /** Использовать отступы карты при позиционировании */
  useMapMargin?: boolean
  /** Автоматически применять состояние карты */
  mapStateAutoApply?: boolean
  /** Опции Geolocation API */
  geolocationOptions?: {
    enableHighAccuracy?: boolean
    timeout?: number
    maximumAge?: number
  }
  /** Экземпляр карты */
  map?: any
}

interface GeolocationControlEmits {
  /** Успешное определение местоположения */
  (event: 'locationchange', data: { position: GeolocationResult; geoObjects: any }): void
  /** Ошибка определения местоположения */
  (event: 'locationerror', error: GeolocationError): void
  /** Нажатие на кнопку */
  (event: 'press'): void
  /** Изменение состояния */
  (event: 'statechange', state: { 
    state: string; 
    previousState: string; 
    isLocating: boolean 
  }): void
  /** Клик по кнопке */
  (event: 'click'): void
}

// Props с дефолтными значениями
const props = withDefaults(defineProps<GeolocationControlProps>(), {
  position: 'topLeft',
  visible: true,
  size: () => ({ width: 36, height: 36 }),
  zIndex: 1000,
  title: '',
  noPlacemark: false,
  useMapMargin: true,
  mapStateAutoApply: true,
  geolocationOptions: () => ({
    enableHighAccuracy: true,
    timeout: 10000,
    maximumAge: 300000
  })
})

// Emits
const emit = defineEmits<GeolocationControlEmits>()

// Реактивные переменные
const geolocationControlRef: Ref<HTMLElement | null> = ref(null)
const controlState: Ref<'ready' | 'pending' | 'error'> = ref('ready')
const isSupported: Ref<boolean> = ref(false)
const lastKnownPosition: Ref<GeolocationResult | null> = ref(null)
const geoObjects: Ref<any> = ref(null)
const currentRequest: Ref<any> = ref(null)
const watchId: Ref<number | null> = ref(null)

// Вычисляемые свойства
const isVisible = computed(() => props.visible && isSupported.value)
const isLocating = computed(() => controlState.value === 'pending')
const isError = computed(() => controlState.value === 'error')

const currentIcon = computed(() => {
  if (isError.value) return '⚠️'
  return '🎯' // GPS иконка
})

const spinnerIcon = computed(() => '↻')

const buttonTitle = computed(() => {
  if (props.title) return props.title
  
  switch (controlState.value) {
    case 'pending':
      return 'Определение местоположения...'
    case 'error':
      return 'Ошибка определения местоположения. Повторить?'
    default:
      return 'Определить местоположение'
  }
})

const controlClasses = computed(() => ({
  'ymaps-geolocation-control--ready': controlState.value === 'ready',
  'ymaps-geolocation-control--pending': controlState.value === 'pending',
  'ymaps-geolocation-control--error': controlState.value === 'error',
  'ymaps-geolocation-control--supported': isSupported.value,
  'ymaps-geolocation-control--visible': isVisible.value
}))

const controlStyles = computed(() => ({
  position: 'absolute' as const,
  zIndex: props.zIndex,
  width: `${props.size.width}px`,
  height: `${props.size.height}px`,
  ...getPositionStyles(props.position)
}))

const iconClasses = computed(() => ({
  'ymaps-geolocation-icon--ready': controlState.value === 'ready',
  'ymaps-geolocation-icon--error': controlState.value === 'error'
}))

const spinnerClasses = computed(() => ({
  'ymaps-geolocation-spinner--active': isLocating.value
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
      return { top: offset, left: offset }
  }
}

/**
 * Проверка поддержки Geolocation API
 */
function checkGeolocationSupport(): boolean {
  return 'geolocation' in navigator && 
         typeof navigator.geolocation.getCurrentPosition === 'function'
}

/**
 * Получение текущего местоположения
 */
async function getCurrentPosition(): Promise<GeolocationResult> {
  if (!isSupported.value) {
    throw new Error('Geolocation API не поддерживается')
  }

  return new Promise((resolve, reject) => {
    const request = {
      cancel: false,
      timestamp: Date.now()
    }
    
    currentRequest.value = request
    
    const onSuccess = (position: GeolocationPosition) => {
      if (request.cancel) return
      
      const result = processGeolocationResult(position)
      lastKnownPosition.value = result
      currentRequest.value = null
      
      resolve(result)
    }
    
    const onError = (error: GeolocationPositionError) => {
      if (request.cancel) return
      
      currentRequest.value = null
      const processedError = processGeolocationError(error)
      reject(processedError)
    }
    
    navigator.geolocation.getCurrentPosition(
      onSuccess,
      onError,
      props.geolocationOptions
    )
  })
}

/**
 * Обработка результата геолокации
 */
function processGeolocationResult(position: GeolocationPosition): GeolocationResult {
  const coords = position.coords
  
  return {
    coords: [coords.latitude, coords.longitude],
    accuracy: coords.accuracy,
    altitude: coords.altitude,
    altitudeAccuracy: coords.altitudeAccuracy,
    heading: coords.heading,
    speed: coords.speed,
    timestamp: position.timestamp
  }
}

/**
 * Обработка ошибки геолокации
 */
function processGeolocationError(error: GeolocationPositionError): GeolocationError {
  let message = 'Неизвестная ошибка геолокации'
  let code: GeolocationError['code'] = 'UNKNOWN_ERROR'
  
  switch (error.code) {
    case error.PERMISSION_DENIED:
      message = 'Доступ к геолокации запрещен'
      code = 'PERMISSION_DENIED'
      break
    case error.POSITION_UNAVAILABLE:
      message = 'Местоположение недоступно'
      code = 'POSITION_UNAVAILABLE'
      break
    case error.TIMEOUT:
      message = 'Превышено время ожидания'
      code = 'TIMEOUT'
      break
  }
  
  return {
    message,
    code,
    originalError: error
  }
}

/**
 * Создание геообъектов на карте
 */
async function createGeoObjects(position: GeolocationResult): Promise<any> {
  if (!props.map || props.noPlacemark) {
    return null
  }

  try {
    // Удаление предыдущих геообъектов
    if (geoObjects.value) {
      props.map.geoObjects.remove(geoObjects.value)
    }

    // Создание новой метки
    const placemark = new (window as any).ymaps.Placemark(position.coords, {
      balloonContentHeader: 'Ваше местоположение',
      balloonContentBody: createBalloonContent(position),
      balloonContentFooter: 'Точность: ±' + Math.round(position.accuracy) + ' м',
      hintContent: 'Ваше текущее местоположение'
    }, {
      preset: 'islands#redDotIcon',
      iconColor: '#1e40af',
      draggable: false
    })

    // Создание круга точности
    let accuracyCircle = null
    if (position.accuracy && position.accuracy < 10000) {
      accuracyCircle = new (window as any).ymaps.Circle([position.coords, position.accuracy], {
        balloonContent: 'Зона точности определения местоположения'
      }, {
        fillColor: '#1e40af',
        fillOpacity: 0.1,
        strokeColor: '#1e40af',
        strokeOpacity: 0.3,
        strokeWidth: 2
      })
    }

    // Создание коллекции геообъектов
    const collection = new (window as any).ymaps.GeoObjectCollection()
    collection.add(placemark)
    
    if (accuracyCircle) {
      collection.add(accuracyCircle)
    }

    // Добавление на карту
    props.map.geoObjects.add(collection)
    geoObjects.value = collection

    return collection
  } catch (error) {
    console.error('GeolocationControl: Ошибка создания геообъектов:', error)
    return null
  }
}

/**
 * Создание содержимого балуна
 */
function createBalloonContent(position: GeolocationResult): string {
  const time = new Date(position.timestamp).toLocaleTimeString()
  
  let content = `
    <div style="font-size: 12px; line-height: 1.4;">
      <p><strong>Координаты:</strong><br>
      ${position.coords[0].toFixed(6)}, ${position.coords[1].toFixed(6)}</p>
      <p><strong>Время определения:</strong> ${time}</p>
  `
  
  if (position.altitude !== null) {
    content += `<p><strong>Высота:</strong> ${Math.round(position.altitude)} м</p>`
  }
  
  if (position.speed !== null && position.speed > 0) {
    content += `<p><strong>Скорость:</strong> ${Math.round(position.speed * 3.6)} км/ч</p>`
  }
  
  content += '</div>'
  return content
}

/**
 * Применение состояния карты
 */
async function applyMapState(position: GeolocationResult): Promise<void> {
  if (!props.map) return

  try {
    // Определение подходящего зума
    let zoom = 16
    
    if (position.accuracy) {
      if (position.accuracy < 100) zoom = 17
      else if (position.accuracy < 500) zoom = 15
      else if (position.accuracy < 1000) zoom = 14
      else zoom = 13
    }

    // Анимированное перемещение к позиции
    await props.map.setCenter(position.coords, zoom, {
      checkZoomRange: true,
      duration: 500
    })
  } catch (error) {
    console.error('GeolocationControl: Ошибка применения состояния карты:', error)
  }
}

/**
 * Получение местоположения с добавлением на карту
 */
async function locate(): Promise<void> {
  if (controlState.value !== 'ready') {
    return
  }

  try {
    setState('pending')
    
    const position = await getCurrentPosition()
    
    // Создание геообъектов если нужно
    const createdGeoObjects = await createGeoObjects(position)
    
    // Применение состояния карты
    if (props.mapStateAutoApply) {
      await applyMapState(position)
    }
    
    setState('ready')
    emit('locationchange', { position, geoObjects: createdGeoObjects })
    
  } catch (error) {
    setState('error')
    emit('locationerror', error as GeolocationError)
  }
}

/**
 * Установка состояния контрола
 */
function setState(newState: 'ready' | 'pending' | 'error'): void {
  if (controlState.value === newState) return
  
  const previousState = controlState.value
  controlState.value = newState
  
  emit('statechange', { 
    state: newState, 
    previousState,
    isLocating: newState === 'pending'
  })

  // Автоматический сброс состояния error через 3 секунды
  if (newState === 'error') {
    setTimeout(() => {
      if (controlState.value === 'error') {
        setState('ready')
      }
    }, 3000)
  }
}

/**
 * Обработчик клика по кнопке
 */
async function handleClick(): Promise<void> {
  if (!isSupported.value || controlState.value !== 'ready') {
    return
  }

  try {
    await locate()
    emit('press')
    emit('click')
  } catch (error) {
    console.error('GeolocationControl: Ошибка при определении местоположения:', error)
  }
}

/**
 * Обработчик нажатия клавиш
 * @param event - Событие клавиатуры
 */
function onKeyDown(event: KeyboardEvent): void {
  if (event.key === 'Enter' || event.key === ' ') {
    event.preventDefault()
    handleClick()
  }
}

/**
 * Очистка ресурсов
 */
function cleanup(): void {
  // Отмена текущего запроса
  if (currentRequest.value) {
    currentRequest.value.cancel = true
    currentRequest.value = null
  }
  
  // Остановка отслеживания
  if (watchId.value !== null) {
    navigator.geolocation.clearWatch(watchId.value)
    watchId.value = null
  }
  
  // Удаление геообъектов с карты
  if (geoObjects.value && props.map) {
    try {
      props.map.geoObjects.remove(geoObjects.value)
    } catch (e) {
      // Игнорируем ошибки удаления
    }
    geoObjects.value = null
  }
}

// Watchers для отслеживания изменений props
watch(() => props.visible, (newVisible) => {
  if (newVisible && !isSupported.value) {
    console.warn('GeolocationControl: Geolocation API не поддерживается')
  }
}, { immediate: true })

// Lifecycle hooks
onMounted(async () => {
  // Проверка поддержки API
  isSupported.value = checkGeolocationSupport()
  
  if (!isSupported.value) {
    console.warn('GeolocationControl: Geolocation API не поддерживается в этом браузере')
    return
  }

  // Фокус для accessibility
  await nextTick()
  if (geolocationControlRef.value && props.visible) {
    geolocationControlRef.value.setAttribute('tabindex', '0')
  }
})

onBeforeUnmount(() => {
  cleanup()
})

// Экспорт для доступа из родительского компонента
defineExpose({
  getCurrentPosition,
  locate,
  getLastKnownPosition: () => lastKnownPosition.value,
  getControlState: () => controlState.value,
  isSupported: () => isSupported.value,
  cleanup
})
</script>

<style scoped>
.ymaps-geolocation-control {
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

.ymaps-geolocation-control--pending {
  @apply bg-gray-100 cursor-not-allowed border-gray-300;
  @apply hover:bg-gray-100 hover:shadow-md;
  @apply active:scale-100;
}

.ymaps-geolocation-control--error {
  @apply bg-red-50 border-red-200 text-red-600;
  @apply hover:bg-red-100;
}

.ymaps-geolocation-control--supported {
  @apply visible;
}

.ymaps-geolocation-control:not(.ymaps-geolocation-control--supported) {
  @apply hidden;
}

.ymaps-geolocation-icon,
.ymaps-geolocation-spinner {
  @apply text-lg leading-none;
  @apply flex items-center justify-center;
}

.ymaps-geolocation-spinner--active {
  animation: geolocation-spin 1s linear infinite;
}

/* Анимация спиннера */
@keyframes geolocation-spin {
  from { 
    transform: rotate(0deg); 
  }
  to { 
    transform: rotate(360deg); 
  }
}

/* Анимации переходов */
.geolocation-icon-enter-active,
.geolocation-icon-leave-active {
  @apply transition-all duration-200;
}

.geolocation-icon-enter-from {
  @apply opacity-0 scale-75;
}

.geolocation-icon-leave-to {
  @apply opacity-0 scale-125;
}

/* Мобильная адаптивность */
@media (max-width: 768px) {
  .ymaps-geolocation-control {
    @apply w-12 h-12 text-xl;
    @apply shadow-lg border-2;
  }
}

/* Высокий контраст для accessibility */
@media (prefers-contrast: high) {
  .ymaps-geolocation-control {
    @apply border-2 border-black;
  }
  
  .ymaps-geolocation-control--error {
    @apply bg-red-100 border-red-600;
  }
}

/* Уменьшение анимации для пользователей с ограниченными возможностями */
@media (prefers-reduced-motion: reduce) {
  .ymaps-geolocation-control,
  .ymaps-geolocation-icon,
  .ymaps-geolocation-spinner,
  .geolocation-icon-enter-active,
  .geolocation-icon-leave-active {
    @apply transition-none;
  }
  
  .ymaps-geolocation-spinner--active {
    animation: none;
  }
}

/* Dark mode поддержка */
@media (prefers-color-scheme: dark) {
  .ymaps-geolocation-control {
    @apply bg-gray-800 border-gray-600 text-gray-200;
    @apply hover:bg-gray-700;
  }
  
  .ymaps-geolocation-control--pending {
    @apply bg-gray-700 border-gray-500 text-gray-400;
    @apply hover:bg-gray-700;
  }
  
  .ymaps-geolocation-control--error {
    @apply bg-red-900 border-red-700 text-red-300;
    @apply hover:bg-red-800;
  }
}

/* Состояния кнопки */
.ymaps-geolocation-control[disabled] {
  @apply cursor-not-allowed opacity-75;
  @apply hover:transform-none;
}

/* Индикатор активности */
.ymaps-geolocation-control--pending::after {
  content: '';
  @apply absolute inset-0 border-2 border-blue-500 border-t-transparent rounded-md;
  animation: geolocation-border-spin 1s linear infinite;
}

@keyframes geolocation-border-spin {
  to {
    transform: rotate(360deg);
  }
}
</style>