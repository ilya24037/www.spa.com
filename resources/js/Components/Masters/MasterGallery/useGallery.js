// resources/js/Components/Masters/MasterGallery/useGallery.js
import { ref, computed } from 'vue'

export function useGallery(master) {
  // Состояние
  const currentIndex = ref(0)
  
  // Обработка фотографий мастера
  const photos = computed(() => {
    // Собираем все возможные источники фото
    const photoSources = []
    
    // Основное фото/аватар
    if (master.avatar) {
      photoSources.push(master.avatar)
    }
    
    // Массив дополнительных фото
    if (master.photos && Array.isArray(master.photos)) {
      photoSources.push(...master.photos)
    }
    
    // Альтернативный формат - all_photos
    if (master.all_photos && Array.isArray(master.all_photos)) {
      photoSources.push(...master.all_photos)
    }
    
    // Фото из галереи услуг
    if (master.services) {
      master.services.forEach(service => {
        if (service.photos && Array.isArray(service.photos)) {
          photoSources.push(...service.photos)
        }
      })
    }
    
    // Удаляем дубликаты и пустые значения
    const uniquePhotos = [...new Set(photoSources)].filter(Boolean)
    
    // Если нет фото, добавляем заглушку
    if (uniquePhotos.length === 0) {
      return ['/images/placeholder-master.jpg']
    }
    
    return uniquePhotos
  })
  
  // Текущее фото
  const currentPhoto = computed(() => {
    return photos.value[currentIndex.value] || photos.value[0]
  })
  
  // Есть ли следующее/предыдущее фото
  const hasNext = computed(() => currentIndex.value < photos.value.length - 1)
  const hasPrevious = computed(() => currentIndex.value > 0)
  
  // Методы навигации
  const nextPhoto = () => {
    if (hasNext.value) {
      currentIndex.value++
    } else {
      // Циклический переход к первому
      currentIndex.value = 0
    }
  }
  
  const previousPhoto = () => {
    if (hasPrevious.value) {
      currentIndex.value--
    } else {
      // Циклический переход к последнему
      currentIndex.value = photos.value.length - 1
    }
  }
  
  const setPhoto = (index) => {
    if (index >= 0 && index < photos.value.length) {
      currentIndex.value = index
    }
  }
  
  // Предзагрузка изображений
  const preloadImages = () => {
    photos.value.forEach((photo, index) => {
      // Пропускаем текущее и уже загруженные
      if (index === currentIndex.value) return
      
      const img = new Image()
      img.src = photo
    })
  }
  
  return {
    photos,
    currentIndex,
    currentPhoto,
    hasNext,
    hasPrevious,
    nextPhoto,
    previousPhoto,
    setPhoto,
    preloadImages
  }
}