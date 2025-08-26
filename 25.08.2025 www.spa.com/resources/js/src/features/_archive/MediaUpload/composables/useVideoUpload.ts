import { ref, computed } from 'vue'

export interface Video {
  id: string | number
  file?: File
  url?: string
  preview?: string
  name?: string
  type?: string
}

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

  const videoSources = computed((): VideoSource[] => {
    if (localVideos.value.length === 0) return []
    
    const video = localVideos.value[0]
    const sources: VideoSource[] = []
    
    if (video.url || video.preview) {
      const url = video.url || video.preview!
      
      // Определяем тип по расширению файла или MIME типу
      let type = video.type || ''
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
      
      if (file.size > 50 * 1024 * 1024) {
        error.value = 'Максимальный размер видео 50MB'
        isUploading.value = false
        reject(new Error('File too large'))
        return
      }
      
      const reader = new FileReader()
      
      reader.onload = (e) => {
        const video: Video = {
          id: Date.now(),
          file: file,
          preview: e.target?.result as string,
          name: file.name,
          type: file.type
        }
        
        isUploading.value = false
        resolve(video)
      }
      
      reader.onerror = () => {
        error.value = 'Ошибка при загрузке файла'
        isUploading.value = false
        reject(new Error('FileReader error'))
      }
      
      reader.readAsDataURL(file)
    })
  }

  const addVideo = async (file: File) => {
    try {
      const video = await processVideo(file)
      localVideos.value = [video] // Заменяем существующее видео
    } catch (err) {
      console.error('Error adding video:', err)
    }
  }

  const removeVideo = () => {
    localVideos.value = []
    videoMetadata.value = null
    error.value = ''
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
    const maxSize = 50 * 1024 * 1024 // 50MB
    
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
    if (localVideos.value.length === 0 && videos && videos.length > 0) {
      localVideos.value = videos.map((video, index) => {
        if (typeof video === 'string') {
          return {
            id: `video-${index}`,
            url: video,
            preview: video
          }
        }
        return video
      })
    }
  }

  return {
    localVideos,
    error,
    videoMetadata,
    isUploading,
    videoSources,
    processVideo,
    addVideo,
    removeVideo,
    extractMetadata,
    formatDuration,
    formatFileSize,
    getVideoErrorMessage,
    validateVideoFile,
    initializeFromProps
  }
}