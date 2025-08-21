import { ref } from 'vue'
import type { Photo } from '../model/types'

export function usePhotoUpload() {
  const localPhotos = ref<Photo[]>([])
  const error = ref('')
  const isUploading = ref(false)
  
  // Drag and drop state
  const isDragOver = ref(false)
  const draggedIndex = ref<number | null>(null)
  const dragOverIndex = ref<number | null>(null)

  const processPhotos = (files: File[]): Promise<Photo[]> => {
    return new Promise((resolve) => {
      error.value = ''
      isUploading.value = true
      
      const imageFiles = files.filter(file => file.type.startsWith('image/'))
      
      if (imageFiles.length === 0) {
        error.value = 'Выберите изображения'
        isUploading.value = false
        resolve([])
        return
      }
      
      if (localPhotos.value.length + imageFiles.length > 10) {
        error.value = 'Максимум 10 фотографий'
        isUploading.value = false
        resolve([])
        return
      }
      
      const newPhotos: Photo[] = []
      let processedCount = 0
      
      imageFiles.forEach(file => {
        const reader = new FileReader()
        reader.onload = (e) => {
          const photo: Photo = {
            id: Date.now() + Math.random(),
            file: file,
            preview: e.target?.result as string,
            name: file.name,
            rotation: 0
          }
          newPhotos.push(photo)
          processedCount++
          
          if (processedCount === imageFiles.length) {
            isUploading.value = false
            resolve(newPhotos)
          }
        }
        reader.readAsDataURL(file)
      })
    })
  }

  const addPhotos = async (files: File[]) => {
    console.log('⚡ usePhotoUpload: addPhotos НАЧАЛО', {
      filesCount: files.length,
      currentPhotosCount: localPhotos.value.length
    })
    
    try {
      const newPhotos = await processPhotos(files)
      console.log('⚡ usePhotoUpload: processPhotos завершен, получено:', newPhotos.length, 'новых фото')
      
      console.log('⚡ usePhotoUpload: ДО объединения localPhotos.length:', localPhotos.value.length)
      localPhotos.value = [...localPhotos.value, ...newPhotos]
      console.log('✅ usePhotoUpload: ПОСЛЕ объединения localPhotos.length:', localPhotos.value.length)
    } catch (err) {
      console.error('❌ usePhotoUpload: Error adding photos:', err)
    }
  }

  const removePhoto = (index: number) => {
    console.log('🗑️ usePhotoUpload: removePhoto НАЧАЛО', {
      index,
      currentLength: localPhotos.value.length,
      validIndex: index >= 0 && index < localPhotos.value.length
    })
    
    if (index >= 0 && index < localPhotos.value.length) {
      const newPhotos = [...localPhotos.value]
      newPhotos.splice(index, 1)
      localPhotos.value = newPhotos
      console.log('✅ usePhotoUpload: Фото удалено, новая длина:', localPhotos.value.length)
    } else {
      console.error('❌ usePhotoUpload: Некорректный индекс для удаления:', index)
    }
  }

  const rotatePhoto = (index: number) => {
    if (index < 0 || index >= localPhotos.value.length) return
    
    const newPhotos = [...localPhotos.value]
    const currentRotation = newPhotos[index].rotation || 0
    newPhotos[index] = {
      ...newPhotos[index],
      rotation: (currentRotation + 90) % 360
    }
    localPhotos.value = newPhotos
  }

  const reorderPhotos = (fromIndex: number, toIndex: number) => {
    if (fromIndex === toIndex || fromIndex < 0 || toIndex < 0 || 
        fromIndex >= localPhotos.value.length || toIndex >= localPhotos.value.length) {
      return
    }
    
    const newPhotos = [...localPhotos.value]
    const [movedPhoto] = newPhotos.splice(fromIndex, 1)
    newPhotos.splice(toIndex, 0, movedPhoto)
    localPhotos.value = newPhotos
  }

  const getPhotoUrl = (photo: Photo): string => {
    if (!photo) return ''
    if (typeof photo === 'string') return photo
    if (photo.preview) return photo.preview
    if (photo.url) return photo.url
    if (photo.file && photo.file instanceof File) {
      return URL.createObjectURL(photo.file)
    }
    return ''
  }

  const validateImageFile = (file: File): boolean => {
    const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp']
    const maxSize = 10 * 1024 * 1024 // 10MB
    
    if (!allowedTypes.includes(file.type)) {
      error.value = `Поддерживаемые форматы: ${allowedTypes.join(', ')}`
      return false
    }
    
    if (file.size > maxSize) {
      error.value = `Максимальный размер файла: ${(maxSize / 1024 / 1024).toFixed(2)}MB`
      return false
    }
    
    return true
  }

  const initializeFromProps = (photos: Array<string | Photo>) => {
    console.log('🔄 usePhotoUpload: initializeFromProps НАЧАЛО', {
      incomingPhotos: photos,
      incomingLength: photos.length,
      currentLocalLength: localPhotos.value.length,
      condition: localPhotos.value.length === 0 && photos.length > 0
    })
    
    if (localPhotos.value.length === 0 && photos.length > 0) {
      console.log('✅ usePhotoUpload: Условие выполнено, начинаем маппинг фото')
      
      localPhotos.value = photos.map((photo, index) => {
        if (typeof photo === 'string') {
          console.log(`🔄 usePhotoUpload: Маппинг строки ${index}:`, photo)
          return {
            id: `existing-${index}`,
            url: photo,
            preview: photo,
            rotation: 0
          }
        }
        console.log(`🔄 usePhotoUpload: Маппинг объекта ${index}:`, photo)
        return {
          ...photo,
          id: photo.id || `photo-${index}`,
          rotation: photo.rotation || 0
        }
      })
      
      console.log('✅ usePhotoUpload: initializeFromProps ЗАВЕРШЕНО, новая длина:', localPhotos.value.length)
    } else {
      console.log('❌ usePhotoUpload: Условие инициализации НЕ выполнено')
    }
  }

  // Drag and drop handlers
  const handleDragStart = (index: number) => {
    draggedIndex.value = index
  }

  const handleDragOver = (index: number) => {
    if (draggedIndex.value !== null && draggedIndex.value !== index) {
      dragOverIndex.value = index
    }
  }

  const handleDragDrop = (targetIndex: number) => {
    const sourceIndex = draggedIndex.value
    
    if (sourceIndex !== null && sourceIndex !== targetIndex) {
      reorderPhotos(sourceIndex, targetIndex)
    }
    
    // Reset drag state
    draggedIndex.value = null
    dragOverIndex.value = null
  }

  const handleDragEnd = () => {
    draggedIndex.value = null
    dragOverIndex.value = null
  }

  const handleFileDrop = (event: DragEvent) => {
    event.preventDefault()
    event.stopPropagation()
    
    // Only process files if we're not dragging a photo
    if (draggedIndex.value === null) {
      isDragOver.value = false
      const files = Array.from(event.dataTransfer?.files || [])
      if (files.length > 0) {
        addPhotos(files)
      }
    }
  }

  const handleFileDragOver = (event: DragEvent) => {
    event.preventDefault()
    // Only show drag over if we're not dragging existing photos
    if (draggedIndex.value === null && event.dataTransfer?.types.includes('Files')) {
      isDragOver.value = true
    }
  }

  const handleFileDragLeave = (event: DragEvent) => {
    event.preventDefault()
    // Only reset if leaving the entire zone
    if (event.currentTarget === event.target) {
      isDragOver.value = false
    }
  }

  return {
    localPhotos,
    error,
    isUploading,
    isDragOver,
    draggedIndex,
    dragOverIndex,
    processPhotos,
    addPhotos,
    removePhoto,
    rotatePhoto,
    reorderPhotos,
    getPhotoUrl,
    validateImageFile,
    initializeFromProps,
    handleDragStart,
    handleDragOver,
    handleDragDrop,
    handleDragEnd,
    handleFileDrop,
    handleFileDragOver,
    handleFileDragLeave
  }
}