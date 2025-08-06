/**
 * Композабл для изолированного виджета MasterProfile
 * Инкапсулирует всю бизнес-логику виджета
 */

import { computed, onMounted, onUnmounted, watch } from 'vue'
import type { 
  MasterProfileWidgetProps,
  MasterProfileFilters,
  MasterService,
  MasterPhoto
} from '../types/masterProfile.types'
import { useMasterProfileWidgetStore } from '../store/masterProfileStore'

export function useMasterProfileWidget(props: MasterProfileWidgetProps) {
  const store = useMasterProfileWidgetStore()

  // === ВЫЧИСЛЯЕМЫЕ СВОЙСТВА ===
  
  const filters = computed<MasterProfileFilters>(() => ({
    includeServices: props.showBooking ?? true,
    includePhotos: true,
    includeReviews: props.showReviews ?? false
  }))

  const displayConfig = computed(() => ({
    isCompact: props.compact ?? false,
    showBookingButton: props.showBooking ?? true,
    showReviewsSection: props.showReviews ?? false,
    showGallery: !props.compact
  }))

  const masterData = computed(() => store.state.master)
  const isLoading = computed(() => store.state.isLoading)
  const error = computed(() => store.state.error)

  // === МЕТОДЫ ===

  /**
   * Инициализация виджета
   */
  async function initialize() {
    try {
      await store.loadMasterProfile(props.masterId, filters.value)
    } catch (error) {
      console.error('[MasterProfileWidget] Initialization failed:', error)
    }
  }

  /**
   * Обработка выбора услуги
   */
  function handleServiceSelect(service: MasterService) {
    store.selectService(service)
    store.trackWidgetEvent('service_clicked', {
      serviceId: service.id,
      serviceName: service.name,
      price: service.price
    })
  }

  /**
   * Обработка клика по фотографии
   */
  function handlePhotoClick(photo: MasterPhoto) {
    store.trackWidgetEvent('photo_clicked', {
      photoId: photo.id,
      isMain: photo.isMain
    })
  }

  /**
   * Обработка клика по контакту
   */
  function handleContactClick(type: string, value: string) {
    store.trackWidgetEvent('contact_clicked', { type, value })
    
    // Открываем соответствующее приложение
    switch (type) {
      case 'phone':
        window.open(`tel:${value}`)
        break
      case 'whatsapp':
        window.open(`https://wa.me/${value.replace(/\D/g, '')}`)
        break
      case 'telegram':
        window.open(`https://t.me/${value}`)
        break
      case 'instagram':
        window.open(`https://instagram.com/${value}`)
        break
    }
  }

  /**
   * Запрос бронирования
   */
  function handleBookingRequest() {
    if (!masterData.value) return

    store.trackWidgetEvent('booking_requested', {
      masterId: masterData.value.id,
      selectedService: store.state.selectedService?.id
    })
  }

  /**
   * Повтор загрузки при ошибке
   */
  async function retryLoad() {
    store.clearError()
    await initialize()
  }

  // === РЕАКТИВНОСТЬ ===

  // Перезагружаем при изменении masterId
  watch(
    () => props.masterId,
    (newMasterId) => {
      if (newMasterId) {
        store.reset()
        initialize()
      }
    }
  )

  // === ЖИЗНЕННЫЙ ЦИКЛ ===

  onMounted(() => {
    initialize()
  })

  onUnmounted(() => {
    // Очищаем состояние при размонтировании виджета
    store.reset()
  })

  // === ВСПОМОГАТЕЛЬНЫЕ ФУНКЦИИ ===

  /**
   * Получить CSS классы для виджета
   */
  function getWidgetClasses() {
    return [
      'master-profile-widget',
      {
        'master-profile-widget--compact': displayConfig.value.isCompact,
        'master-profile-widget--loading': isLoading.value,
        'master-profile-widget--error': error.value
      }
    ]
  }

  /**
   * Проверить доступность услуги для бронирования
   */
  function isServiceBookable(service: MasterService): boolean {
    return store.canBookService(service) && displayConfig.value.showBookingButton
  }

  /**
   * Форматировать цену услуги
   */
  function formatPrice(service: MasterService): string {
    return store.formatServicePrice(service)
  }

  /**
   * Получить статус мастера
   */
  function getMasterStatus(): 'online' | 'offline' | 'unknown' {
    if (!masterData.value) return 'unknown'
    return masterData.value.isOnline ? 'online' : 'offline'
  }

  // === API ВИДЖЕТА ===
  
  return {
    // Данные
    masterData,
    isLoading,
    error,
    displayConfig,
    
    // Computed
    isLoaded: store.isLoaded,
    hasServices: store.hasServices,
    hasPhotos: store.hasPhotos,
    mainPhoto: store.mainPhoto,
    galleryPhotos: store.galleryPhotos,
    
    // Методы
    handleServiceSelect,
    handlePhotoClick,
    handleContactClick,
    handleBookingRequest,
    retryLoad,
    
    // Утилиты
    getWidgetClasses,
    isServiceBookable,
    formatPrice,
    getMasterStatus,
    
    // Store методы
    reload: store.reload,
    clearError: store.clearError
  }
}

// === ТИПЫ ===
export type MasterProfileWidgetComposable = ReturnType<typeof useMasterProfileWidget>