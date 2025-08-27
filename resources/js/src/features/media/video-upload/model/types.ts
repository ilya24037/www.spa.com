export interface Video {
  id: string | number
  file?: File
  url?: string
  duration?: number
  thumbnail?: string
  format?: string
  size?: number
  uploadProgress?: number
  isUploading?: boolean
  error?: string
}

export interface VideoFormat {
  extension: string
  mimeType: string
  codec?: string
  supported: boolean
  browserCompatibility?: {
    chrome: boolean
    firefox: boolean
    safari: boolean
    edge: boolean
  }
}

// ✅ УПРОЩЕНИЕ: Простые props по паттерну DescriptionSection
export interface VideoUploadProps {
  videos?: Video[] | string[]
  maxFiles?: number
  maxSize?: number // в байтах
}

// ✅ УПРОЩЕНИЕ: Один emit как в DescriptionSection
export interface VideoUploadEmits {
  'update:videos': [videos: Video[]]
}