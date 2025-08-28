import { ref } from 'vue'
import type { Video } from '../model/types'

// ✅ УПРОЩЕНИЕ: Упрощенная версия по принципу KISS
export function useVideoUpload() {
  const localVideos = ref<Video[]>([])
  const error = ref('')
  
  // Drag and drop состояния (необходимы для VideoList)
  const draggedIndex = ref<number | null>(null)
  const dragOverIndex = ref<number | null>(null)
  
  // ✅ УПРОЩЕНИЕ: Простой метод добавления видео
  const addVideos = async (files: File[]) => {
    if (!files?.length) return
    
    for (const file of files) {
      // Простая валидация
      if (!file.type.startsWith('video/')) {
        error.value = 'Выберите видео файл'
        continue
      }
      
      if (file.size > 100 * 1024 * 1024) {
        error.value = 'Максимальный размер видео 100MB'
        continue
      }
      
      // Создаем объект видео
      const video: Video = {
        id: Date.now() + Math.random(),
        file: file,
        url: URL.createObjectURL(file),
        format: file.type,
        size: file.size,
        isUploading: false
      }
      
      localVideos.value.push(video)
    }
    
    // Очищаем ошибку после успешного добавления
    if (error.value && localVideos.value.length > 0) {
      error.value = ''
    }
  }
  
  // ✅ УПРОЩЕНИЕ: Простое удаление видео
  const removeVideo = (id: string | number) => {
    const index = localVideos.value.findIndex(v => v.id === id)
    if (index !== -1) {
      // Очищаем blob URL
      const video = localVideos.value[index]
      if (video.url && video.url.startsWith('blob:')) {
        URL.revokeObjectURL(video.url)
      }
      localVideos.value.splice(index, 1)
    }
  }
  
  // ✅ УПРОЩЕНИЕ: Простая инициализация из props
  const initializeFromProps = (videos: Array<string | Video> | string | null | undefined) => {
    // Функция безопасного получения массива видео
    const safeVideosArray = (value: any): Array<string | Video> => {
      if (Array.isArray(value)) {
        return value
      }
      if (typeof value === 'string') {
        // Если пришла JSON строка, пробуем декодировать
        try {
          const parsed = JSON.parse(value)
          return Array.isArray(parsed) ? parsed : []
        } catch {
          return []
        }
      }
      return []
    }
    
    const safeVideos = safeVideosArray(videos)
    if (localVideos.value.length === 0 && safeVideos.length > 0) {
      localVideos.value = safeVideos.map((video, index) => {
        if (typeof video === 'string') {
          return {
            id: `existing-${index}`,
            url: video,
            file: null,
            isUploading: false
          } as Video
        }
        return {
          ...video,
          id: video.id || `video-${index}`,
          isUploading: false
        } as Video
      })
    }
  }
  
  // ✅ УПРОЩЕНИЕ: Простое изменение порядка
  const reorderVideos = (fromIndex: number, toIndex: number) => {
    if (fromIndex === toIndex || fromIndex < 0 || toIndex < 0 ||
        fromIndex >= localVideos.value.length || toIndex >= localVideos.value.length) {
      return
    }
    
    const newVideos = [...localVideos.value]
    const [movedVideo] = newVideos.splice(fromIndex, 1)
    newVideos.splice(toIndex, 0, movedVideo)
    localVideos.value = newVideos
  }
  
  // ✅ УПРОЩЕНИЕ: Простые drag&drop обработчики
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
      reorderVideos(sourceIndex, targetIndex)
    }
    
    // Сброс состояния
    draggedIndex.value = null
    dragOverIndex.value = null
  }
  
  const handleDragEnd = () => {
    draggedIndex.value = null
    dragOverIndex.value = null
  }
  
  return {
    localVideos,
    error,
    // Drag&drop состояния
    draggedIndex,
    dragOverIndex,
    // Методы
    addVideos,
    removeVideo,
    initializeFromProps,
    reorderVideos,
    // Drag&drop методы
    handleDragStart,
    handleDragOver,
    handleDragDrop,
    handleDragEnd
  }
}