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
    try {
      const newPhotos = await processPhotos(files)
      localPhotos.value = [...localPhotos.value, ...newPhotos]
    } catch (err) {
      // Ошибка обрабатывается через error.value
    }
  }

  const removePhoto = (index: number) => {
    if (index >= 0 && index < localPhotos.value.length) {
      const newPhotos = [...localPhotos.value]
      newPhotos.splice(index, 1)
      localPhotos.value = newPhotos
    }
  }

  const rotatePhoto = (index: number) => {
    if (index < 0 || index >= localPhotos.value.length) return
    
    const newPhotos = [...localPhotos.value]
    const photo = newPhotos[index]
    
    if (!photo) {
      return
    }
    
    const currentRotation = photo.rotation || 0
    newPhotos[index] = {
      ...photo,
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
    if (localPhotos.value.length === 0 && photos.length > 0) {
      localPhotos.value = photos.map((photo, index) => {
        if (typeof photo === 'string') {
          return {
            id: `existing-${index}`,
            url: photo,
            preview: photo,
            rotation: 0
          }
        }
        const photoObj = photo as Partial<Photo>
        return {
          ...photoObj,
          id: photoObj.id || `photo-${index}`,
          rotation: photoObj.rotation || 0
        } as Photo
      })
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