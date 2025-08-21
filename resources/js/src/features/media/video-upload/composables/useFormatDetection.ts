import { ref, computed } from 'vue'
import type { VideoFormat } from '../model/types'

export interface FormatSupport {
  name: string
  type: string
  level: string
  status: string
  icon: string
}

export function useFormatDetection() {
  const formatSupport = ref<FormatSupport[]>([])
  const detectedFormat = ref<string>('')
  const currentBrowser = ref<string>('')

  const isChromiumBased = computed(() => {
    const userAgent = navigator.userAgent
    return userAgent.includes('Chrome') && !userAgent.includes('Edg') && !userAgent.includes('OPR')
  })

  const detectBrowser = () => {
    const userAgent = navigator.userAgent
    
    if (userAgent.includes('Chrome') && !userAgent.includes('Edg') && !userAgent.includes('OPR')) {
      currentBrowser.value = 'chromium'
    } else if (userAgent.includes('Safari') && !userAgent.includes('Chrome')) {
      currentBrowser.value = 'safari'
    } else if (userAgent.includes('Firefox')) {
      currentBrowser.value = 'firefox'
    } else if (userAgent.includes('Edg')) {
      currentBrowser.value = 'edge'
    } else {
      currentBrowser.value = 'other'
    }
    
    return currentBrowser.value
  }

  const detectVideoFormat = async (file: File): Promise<string> => {
    const videoElement = document.createElement('video')
    const url = URL.createObjectURL(file)
    
    return new Promise((resolve) => {
      videoElement.addEventListener('loadedmetadata', () => {
        // Определяем кодек по метаданным (если возможно)
        // Это упрощенная версия, в реальности нужно использовать MediaSource API
        let format = 'unknown'
        
        if (file.type.includes('mp4')) {
          // Проверяем на HEVC (H.265)
          if (file.name.toLowerCase().includes('hevc') || 
              file.name.toLowerCase().includes('h265')) {
            format = 'hevc'
          } else {
            format = 'h264'
          }
        } else if (file.type.includes('webm')) {
          format = 'vp9'
        } else if (file.type.includes('ogg')) {
          format = 'theora'
        }
        
        detectedFormat.value = format
        URL.revokeObjectURL(url)
        resolve(format)
      })
      
      videoElement.addEventListener('error', () => {
        detectedFormat.value = 'unsupported'
        URL.revokeObjectURL(url)
        resolve('unsupported')
      })
      
      videoElement.src = url
    })
  }

  const detectVideoFormats = () => {
    const video = document.createElement('video')
    const formats = [
      { name: 'MP4 (H.264)', type: 'video/mp4; codecs="avc1.42E01E"' },
      { name: 'MP4 (H.265)', type: 'video/mp4; codecs="hev1.1.6.L93.B0"' },
      { name: 'WebM (VP8)', type: 'video/webm; codecs="vp8"' },
      { name: 'WebM (VP9)', type: 'video/webm; codecs="vp9"' },
      { name: 'WebM (AV1)', type: 'video/webm; codecs="av01.0.05M.08"' },
      { name: 'OGG (Theora)', type: 'video/ogg; codecs="theora"' }
    ]

    formatSupport.value = formats.map(format => {
      const support = video.canPlayType(format.type)
      return {
        ...format,
        level: support,
        status: support === 'probably' ? 'Отлично' : 
                support === 'maybe' ? 'Возможно' : 'Не поддерживается',
        icon: support === 'probably' ? '✅' : 
              support === 'maybe' ? '⚠️' : '❌'
      }
    })

    return formatSupport.value
  }

  const checkVideoCompatibility = (file: File) => {
    const videoElement = document.createElement('video')
    const support = videoElement.canPlayType(file.type)
    
    if (support === 'probably') {
      return {
        class: 'compatible',
        icon: '✅',
        text: 'Отличная совместимость'
      }
    } else if (support === 'maybe') {
      return {
        class: 'maybe-compatible',
        icon: '⚠️',
        text: 'Возможны проблемы с воспроизведением'
      }
    } else {
      return {
        class: 'incompatible',
        icon: '❌',
        text: 'Формат не поддерживается'
      }
    }
  }

  const getRecommendedFormats = () => {
    if (isChromiumBased.value) {
      return {
        primary: 'WebM',
        secondary: 'MP4 (H.264)',
        warning: 'Ваш браузер (Chromium) имеет ограниченную поддержку MP4. Рекомендуем использовать WebM формат или Google Chrome.'
      }
    }
    
    return {
      primary: 'MP4',
      secondary: 'WebM',
      warning: null
    }
  }

  // Инициализация
  detectBrowser()

  return {
    formatSupport,
    detectedFormat,
    currentBrowser,
    isChromiumBased,
    detectVideoFormat,
    detectVideoFormats,
    checkVideoCompatibility,
    getRecommendedFormats
  }
}