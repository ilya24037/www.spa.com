import { ref, computed } from 'vue'
import type { Video } from '../model/types'

export interface VideoMetadata {
  dimensions: string
  duration: string
  fileSize: string
}

export interface VideoSource {
  url: string
  type: string
}

export function useVideoUpload() {
  const localVideos = ref<Video[]>([])
  const error = ref('')
  const videoMetadata = ref<VideoMetadata | null>(null)
  const isUploading = ref(false)
  
  // Drag and drop состояния (как у фото)
  const draggedIndex = ref<number | null>(null)
  const dragOverIndex = ref<number | null>(null)

  const videoSources = computed((): VideoSource[] => {
    if (localVideos.value.length === 0) return []
    
    const video = localVideos.value[0]
    const sources: VideoSource[] = []
    
    if (video.url || video.thumbnail) {
      const url = video.url || video.thumbnail!
      
      // Определяем тип по расширению файла или MIME типу
      let type = video.format || ''
      if (!type) {
        if (url.includes('.webm')) type = 'video/webm'
        else if (url.includes('.mp4')) type = 'video/mp4'
        else if (url.includes('.ogv')) type = 'video/ogg'
      }
      
      sources.push({ url, type })
    }
    
    return sources
  })

  const processVideo = (file: File): Promise<Video> => {
    return new Promise((resolve, reject) => {
      error.value = ''
      isUploading.value = true
      
      if (!file.type.startsWith('video/')) {
        error.value = 'Выберите видео файл'
        isUploading.value = false
        reject(new Error('Invalid file type'))
        return
      }
      
      if (file.size > 100 * 1024 * 1024) {
        error.value = 'Максимальный размер видео 100MB'
        isUploading.value = false
        reject(new Error('File too large'))
        return
      }
      
      // ВАЖНО: Не используем FileReader для видео!
      // Вместо этого сохраняем сам файл, VideoItem создаст blob URL
      const video: Video = {
        id: Date.now(),
        file: file,              // Сохраняем файл для создания blob URL в VideoItem
        url: null,               // URL будет создан на сервере после загрузки
        thumbnail: null,         // Thumbnail будет создан позже если нужно
        format: file.type,
        size: file.size,
        isUploading: false
      }
      
      // Видео успешно обработано
      
      isUploading.value = false
      resolve(video)
    })
  }

  const addVideos = async (files: File[]) => {
    // ИСПРАВЛЕНО: Добавляем новые видео к существующим
    if (files.length === 0) return
    
    // Обрабатываем все выбранные файлы
    for (const file of files) {
      try {
        const video = await processVideo(file)
        // Добавляем к существующим видео, а не заменяем
        localVideos.value.push(video)
      } catch (err) {
        // Ошибка обработки видео
      }
    }
  }

  const addVideo = async (file: File) => {
    try {
      const video = await processVideo(file)
      // ИСПРАВЛЕНО: Добавляем к существующим видео
      localVideos.value.push(video)
    } catch (err) {
      // Ошибка добавления видео
    }
  }

  const removeVideo = (id: string | number) => {
    // ИСПРАВЛЕНО: Удаляем только конкретное видео по ID
    const index = localVideos.value.findIndex(v => v.id === id)
    if (index !== -1) {
      // Очищаем blob URL если есть
      const video = localVideos.value[index]
      if (video.url && video.url.startsWith('blob:')) {
        URL.revokeObjectURL(video.url)
      }
      // Удаляем видео из массива
      localVideos.value.splice(index, 1)
    }
    
    // Очищаем метаданные если удалили последнее видео
    if (localVideos.value.length === 0) {
      videoMetadata.value = null
      error.value = ''
    }
  }

  const uploadVideo = async (video: Video): Promise<void> => {
    if (!video.file) return
    
    const updatedVideo = localVideos.value.find(v => v.id === video.id)
    if (!updatedVideo) return
    
    updatedVideo.isUploading = true
    updatedVideo.uploadProgress = 0
    
    try {
      // Имитация загрузки (в реальном коде здесь будет API вызов)
      for (let i = 0; i <= 100; i += 10) {
        updatedVideo.uploadProgress = i
        await new Promise(resolve => setTimeout(resolve, 100))
      }
      
      updatedVideo.isUploading = false
      updatedVideo.url = updatedVideo.thumbnail // В реальном коде будет URL от сервера
    } catch (err) {
      updatedVideo.isUploading = false
      updatedVideo.error = 'Ошибка загрузки'
      throw err
    }
  }

  const extractMetadata = (videoElement: HTMLVideoElement, file?: File) => {
    videoMetadata.value = {
      dimensions: `${videoElement.videoWidth} × ${videoElement.videoHeight}`,
      duration: formatDuration(videoElement.duration),
      fileSize: file ? formatFileSize(file.size) : 'Неизвестно'
    }
  }

  const formatDuration = (seconds: number): string => {
    if (isNaN(seconds)) return 'Неизвестно'
    const minutes = Math.floor(seconds / 60)
    const remainingSeconds = Math.floor(seconds % 60)
    return `${minutes}:${remainingSeconds.toString().padStart(2, '0')}`
  }

  const formatFileSize = (bytes: number): string => {
    const sizes = ['Bytes', 'KB', 'MB', 'GB']
    if (bytes === 0) return '0 Bytes'
    const i = Math.floor(Math.log(bytes) / Math.log(1024))
    return `${(bytes / Math.pow(1024, i)).toFixed(2)} ${sizes[i]}`
  }

  const getVideoErrorMessage = (errorCode?: number): string => {
    const messages: Record<number, string> = {
      1: 'Загрузка прервана',
      2: 'Ошибка сети',
      3: 'Ошибка декодирования (формат не поддерживается)',
      4: 'Источник видео не поддерживается'
    }
    return messages[errorCode || 0] || 'Неизвестная ошибка'
  }

  const validateVideoFile = (file: File): boolean => {
    const allowedTypes = ['video/mp4', 'video/webm', 'video/ogg']
    const maxSize = 100 * 1024 * 1024 // 100MB
    
    if (!allowedTypes.includes(file.type)) {
      error.value = `Поддерживаемые форматы: ${allowedTypes.join(', ')}`
      return false
    }
    
    if (file.size > maxSize) {
      error.value = `Максимальный размер файла: ${formatFileSize(maxSize)}`
      return false
    }
    
    return true
  }

  const initializeFromProps = (videos: Array<string | Video>) => {
    // ТОЧНАЯ копия логики из фото (без фильтрации!)
    if (localVideos.value.length === 0 && videos.length > 0) {
      localVideos.value = videos.map((video, index) => {
        if (typeof video === 'string') {
          return {
            id: `existing-${index}`,
            url: video,
            thumbnail: video, // аналог preview в фото
            file: null,
            isUploading: false
          }
        }
        const videoObj = video as Partial<Video>
        return {
          ...videoObj,
          id: videoObj.id || `video-${index}`,
          isUploading: videoObj.isUploading || false
        } as Video
      })
    }
  }

  // Метод для изменения порядка видео (копия из фото)
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

  // Drag and drop handlers (точная копия из фото)
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
    
    // Reset drag state
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
    videoMetadata,
    isUploading,
    videoSources,
    // Drag and drop состояния
    draggedIndex,
    dragOverIndex,
    // Методы
    processVideo,
    addVideos,
    addVideo,
    removeVideo,
    uploadVideo,
    extractMetadata,
    formatDuration,
    formatFileSize,
    getVideoErrorMessage,
    validateVideoFile,
    initializeFromProps,
    // Drag and drop методы
    reorderVideos,
    handleDragStart,
    handleDragOver,
    handleDragDrop,
    handleDragEnd
  }
}