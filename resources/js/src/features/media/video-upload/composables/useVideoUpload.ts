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
      
      const reader = new FileReader()
      
      reader.onload = (e) => {
        const dataUrl = e.target?.result as string
        const video: Video = {
          id: Date.now(),
          file: file,
          url: dataUrl,           // Для воспроизведения
          thumbnail: dataUrl,     // Для превью
          format: file.type,
          size: file.size,
          isUploading: false
        }
        
        console.log('🎥 processVideo: Создано видео:', {
          id: video.id,
          hasUrl: !!video.url,
          hasThumbnail: !!video.thumbnail,
          format: video.format,
          size: video.size
        })
        
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

  const addVideos = async (files: File[]) => {
    console.log('🎥 addVideos: Начало обработки файлов:', {
      filesCount: files.length,
      currentVideosCount: localVideos.value.length
    })
    
    const newVideos: Video[] = []
    
    for (const file of files) {
      try {
        console.log('🎥 addVideos: Обрабатываем файл:', {
          name: file.name,
          type: file.type,
          size: file.size
        })
        
        const video = await processVideo(file)
        newVideos.push(video)
        
        console.log('🎥 addVideos: Видео успешно обработано:', video.id)
      } catch (err) {
        console.error('🎥 addVideos: Ошибка обработки видео:', err)
      }
    }
    
    const oldLength = localVideos.value.length
    localVideos.value = [...localVideos.value, ...newVideos]
    
    console.log('🎥 addVideos: Завершено:', {
      newVideosAdded: newVideos.length,
      totalVideosBefore: oldLength,
      totalVideosAfter: localVideos.value.length
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

  const removeVideo = (id: string | number) => {
    localVideos.value = localVideos.value.filter(v => v.id !== id)
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

  const initializeFromProps = (videos: Video[] | any[]) => {
    console.log('🎬 useVideoUpload: initializeFromProps вызван с:', {
      videos,
      videosType: typeof videos,
      isArray: Array.isArray(videos),
      length: videos?.length,
      localVideosLength: localVideos.value.length
    })
    
    if (localVideos.value.length === 0 && videos && videos.length > 0) {
      localVideos.value = videos.map((video, index) => {
        console.log(`🎬 useVideoUpload: Обрабатываем видео ${index}:`, {
          video,
          videoType: typeof video,
          isString: typeof video === 'string',
          hasUrl: video?.url,
          hasId: video?.id
        })
        
        // Если это строка (URL) - преобразуем в объект Video
        if (typeof video === 'string') {
          const convertedVideo = {
            id: `video-${index}-${Date.now()}`,
            url: video,
            file: null,
            thumbnail: null,
            format: 'video/mp4',
            size: 0,
            isUploading: false,
            uploadProgress: 0,
            error: null
          }
          console.log(`🎬 useVideoUpload: Преобразовали строку в объект:`, convertedVideo)
          return convertedVideo
        }
        
        // Если объект, но без ID - добавляем ID
        if (typeof video === 'object' && !video.id) {
          const videoWithId = {
            ...video,
            id: `video-${index}-${Date.now()}`
          }
          console.log(`🎬 useVideoUpload: Добавили ID к объекту:`, videoWithId)
          return videoWithId
        }
        
        console.log(`🎬 useVideoUpload: Используем видео как есть:`, video)
        return video
      })
      
      console.log('🎬 useVideoUpload: Финальный localVideos:', localVideos.value)
    }
  }

  return {
    localVideos,
    error,
    videoMetadata,
    isUploading,
    videoSources,
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
    initializeFromProps
  }
}