/**
 * Store для галереи фотографий
 * Управляет просмотром фото в модальном окне
 * Поддерживает навигацию, зум, полноэкранный режим
 */

import { defineStore } from 'pinia'
import { ref, computed, nextTick } from 'vue'
import type { Photo, GalleryState, GalleryOptions } from './types'

export const useGalleryStore = defineStore('gallery', () => {
  
  // =================== СОСТОЯНИЕ ===================
  
  const isOpen = ref(false)
  const photos = ref<Photo[]>([])
  const currentIndex = ref(0)
  const isFullscreen = ref(false)
  const isZoomed = ref(false)
  const zoomLevel = ref(1)
  const isLoading = ref(false)
  const error = ref<string | null>(null)
  
  // Опции галереи
  const options = ref<GalleryOptions>({
    showThumbnails: true,
    showControls: true,
    showCounter: true,
    autoplay: false,
    autoplayInterval: 3000,
    enableZoom: true,
    maxZoom: 3,
    enableFullscreen: true,
    enableKeyboard: true,
    closeOnOutsideClick: true,
    loop: true
  })

  // =================== ВЫЧИСЛЯЕМЫЕ ===================
  
  // Текущее фото
  const currentPhoto = computed(() => {
    if (photos.value.length === 0) return null
    return photos.value[currentIndex.value] || null
  })

  // Есть ли предыдущее фото
  const hasPrevious = computed(() => {
    if (!options.value.loop) {
      return currentIndex.value > 0
    }
    return photos.value.length > 1
  })

  // Есть ли следующее фото
  const hasNext = computed(() => {
    if (!options.value.loop) {
      return currentIndex.value < photos.value.length - 1
    }
    return photos.value.length > 1
  })

  // Счетчик фото
  const photoCounter = computed(() => {
    if (photos.value.length === 0) return '0 / 0'
    return `${currentIndex.value + 1} / ${photos.value.length}`
  })

  // Индексы соседних фото для предзагрузки
  const preloadIndices = computed(() => {
    const indices: number[] = []
    const length = photos.value.length
    
    if (length <= 1) return indices
    
    // Предыдущее фото
    const prevIndex = currentIndex.value > 0 
      ? currentIndex.value - 1 
      : (options.value.loop ? length - 1 : -1)
    
    // Следующее фото
    const nextIndex = currentIndex.value < length - 1 
      ? currentIndex.value + 1 
      : (options.value.loop ? 0 : -1)
    
    if (prevIndex >= 0) indices.push(prevIndex)
    if (nextIndex >= 0) indices.push(nextIndex)
    
    return indices
  })

  // =================== ДЕЙСТВИЯ ===================

  // 🖼️ Открыть галерею
  function openGallery(photoList: Photo[], startIndex = 0) {
    photos.value = [...photoList]
    currentIndex.value = Math.max(0, Math.min(startIndex, photoList.length - 1))
    isOpen.value = true
    isZoomed.value = false
    zoomLevel.value = 1
    error.value = null
    
    // Предзагрузка соседних фото
    nextTick(() => {
      preloadPhotos()
    })
  }

  // ❌ Закрыть галерею
  function closeGallery() {
    isOpen.value = false
    isFullscreen.value = false
    isZoomed.value = false
    zoomLevel.value = 1
    photos.value = []
    currentIndex.value = 0
    error.value = null
  }

  // ⬅️ Предыдущее фото
  function goToPrevious() {
    if (!hasPrevious.value) return
    
    if (currentIndex.value > 0) {
      currentIndex.value--
    } else if (options.value.loop) {
      currentIndex.value = photos.value.length - 1
    }
    
    resetZoom()
    preloadPhotos()
  }

  // ➡️ Следующее фото
  function goToNext() {
    if (!hasNext.value) return
    
    if (currentIndex.value < photos.value.length - 1) {
      currentIndex.value++
    } else if (options.value.loop) {
      currentIndex.value = 0
    }
    
    resetZoom()
    preloadPhotos()
  }

  // 🎯 Перейти к конкретному фото
  function goToPhoto(index: number) {
    if (index < 0 || index >= photos.value.length) return
    
    currentIndex.value = index
    resetZoom()
    preloadPhotos()
  }

  // 🔍 Переключить зум
  function toggleZoom() {
    if (!options.value.enableZoom) return
    
    if (isZoomed.value) {
      resetZoom()
    } else {
      zoomLevel.value = 2
      isZoomed.value = true
    }
  }

  // 🔍 Установить уровень зума
  function setZoomLevel(level: number) {
    if (!options.value.enableZoom) return
    
    const clampedLevel = Math.max(1, Math.min(level, options.value.maxZoom))
    zoomLevel.value = clampedLevel
    isZoomed.value = clampedLevel > 1
  }

  // 🔄 Сбросить зум
  function resetZoom() {
    zoomLevel.value = 1
    isZoomed.value = false
  }

  // 🖥️ Переключить полноэкранный режим
  function toggleFullscreen() {
    if (!options.value.enableFullscreen) return
    
    if (isFullscreen.value) {
      exitFullscreen()
    } else {
      enterFullscreen()
    }
  }

  // 🖥️ Войти в полноэкранный режим
  function enterFullscreen() {
    if (!document.fullscreenElement) {
      document.documentElement.requestFullscreen?.()
    }
    isFullscreen.value = true
  }

  // 🚪 Выйти из полноэкранного режима
  function exitFullscreen() {
    if (document.fullscreenElement) {
      document.exitFullscreen?.()
    }
    isFullscreen.value = false
  }

  // ⚙️ Обновить опции галереи
  function updateOptions(newOptions: Partial<GalleryOptions>) {
    options.value = { ...options.value, ...newOptions }
  }

  // 📥 Предзагрузка фото
  function preloadPhotos() {
    preloadIndices.value.forEach(index => {
      const photo = photos.value[index]
      if (photo && photo.url) {
        const img = new Image()
        img.src = photo.url
      }
    })
  }

  // 📱 Обработка жестов (для мобильных)
  function handleSwipeLeft() {
    goToNext()
  }

  function handleSwipeRight() {
    goToPrevious()
  }

  function handlePinchZoom(scale: number) {
    if (!options.value.enableZoom) return
    
    const newLevel = Math.max(1, Math.min(scale, options.value.maxZoom))
    setZoomLevel(newLevel)
  }

  // ⌨️ Обработка клавиатуры
  function handleKeydown(event: KeyboardEvent) {
    if (!options.value.enableKeyboard || !isOpen.value) return
    
    switch (event.key) {
      case 'Escape':
        closeGallery()
        break
      case 'ArrowLeft':
        goToPrevious()
        break
      case 'ArrowRight':
        goToNext()
        break
      case 'ArrowUp':
        if (currentIndex.value >= 10) {
          goToPhoto(currentIndex.value - 10)
        }
        break
      case 'ArrowDown':
        if (currentIndex.value + 10 < photos.value.length) {
          goToPhoto(currentIndex.value + 10)
        }
        break
      case 'Home':
        goToPhoto(0)
        break
      case 'End':
        goToPhoto(photos.value.length - 1)
        break
      case ' ':
        event.preventDefault()
        toggleZoom()
        break
      case 'f':
      case 'F11':
        event.preventDefault()
        toggleFullscreen()
        break
      case '+':
      case '=':
        event.preventDefault()
        setZoomLevel(zoomLevel.value + 0.5)
        break
      case '-':
        event.preventDefault()
        setZoomLevel(zoomLevel.value - 0.5)
        break
      case '0':
        event.preventDefault()
        resetZoom()
        break
    }
  }

  // 🖱️ Обработка клика вне фото
  function handleOutsideClick(event: MouseEvent) {
    if (!options.value.closeOnOutsideClick) return
    
    const target = event.target as HTMLElement
    if (target.classList.contains('gallery-backdrop')) {
      closeGallery()
    }
  }

  // 📤 Поделиться фото
  async function sharePhoto(photo?: Photo) {
    const photoToShare = photo || currentPhoto.value
    if (!photoToShare) return
    
    if (navigator.share) {
      try {
        await navigator.share({
          title: photoToShare.title || 'Фотография',
          text: photoToShare.description || '',
          url: photoToShare.url
        })
      } catch (err) {
        console.warn('Ошибка поделиться:', err)
      }
    } else {
      // Fallback: копирование ссылки
      try {
        await navigator.clipboard.writeText(photoToShare.url)
        // Можно добавить toast уведомление
      } catch (err) {
        console.warn('Ошибка копирования:', err)
      }
    }
  }

  // 💾 Сохранить фото
  function downloadPhoto(photo?: Photo) {
    const photoToDownload = photo || currentPhoto.value
    if (!photoToDownload) return
    
    const link = document.createElement('a')
    link.href = photoToDownload.url
    link.download = photoToDownload.filename || `photo-${Date.now()}.jpg`
    document.body.appendChild(link)
    link.click()
    document.body.removeChild(link)
  }

  // 🔄 Повернуть фото (если поддерживается)
  function rotatePhoto(degrees: number = 90) {
    // Реализация поворота зависит от используемой библиотеки
    // Здесь заглушка для будущей реализации
    console.log(`Поворот фото на ${degrees} градусов`)
  }

  // =================== АВТОПЛЕЙ ===================

  let autoplayTimer: number | null = null

  function startAutoplay() {
    if (!options.value.autoplay || photos.value.length <= 1) return
    
    stopAutoplay()
    autoplayTimer = window.setInterval(() => {
      goToNext()
    }, options.value.autoplayInterval)
  }

  function stopAutoplay() {
    if (autoplayTimer) {
      clearInterval(autoplayTimer)
      autoplayTimer = null
    }
  }

  function toggleAutoplay() {
    if (autoplayTimer) {
      stopAutoplay()
    } else {
      startAutoplay()
    }
  }

  // =================== ВОЗВРАЩАЕМ ИНТЕРФЕЙС ===================

  return {
    // Состояние
    isOpen,
    photos,
    currentIndex,
    isFullscreen,
    isZoomed,
    zoomLevel,
    isLoading,
    error,
    options,

    // Вычисляемые
    currentPhoto,
    hasPrevious,
    hasNext,
    photoCounter,
    preloadIndices,

    // Действия
    openGallery,
    closeGallery,
    goToPrevious,
    goToNext,
    goToPhoto,
    toggleZoom,
    setZoomLevel,
    resetZoom,
    toggleFullscreen,
    enterFullscreen,
    exitFullscreen,
    updateOptions,
    preloadPhotos,

    // Жесты и управление
    handleSwipeLeft,
    handleSwipeRight,
    handlePinchZoom,
    handleKeydown,
    handleOutsideClick,

    // Дополнительные функции
    sharePhoto,
    downloadPhoto,
    rotatePhoto,

    // Автоплей
    startAutoplay,
    stopAutoplay,
    toggleAutoplay
  }
})