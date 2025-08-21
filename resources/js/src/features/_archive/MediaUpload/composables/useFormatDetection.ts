import { ref, computed } from 'vue'

export interface FormatSupport {
  name: string
  type: string
  level: string
  status: string
  icon: string
}

export function useFormatDetection() {
  const formatSupport = ref<FormatSupport[]>([])

  const isChromiumBased = computed(() => {
    const userAgent = navigator.userAgent
    return userAgent.includes('Chrome') && !userAgent.includes('Edg') && !userAgent.includes('OPR')
  })

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

  return {
    formatSupport,
    isChromiumBased,
    detectVideoFormats,
    checkVideoCompatibility,
    getRecommendedFormats
  }
}