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

export interface VideoUploadProps {
  videos?: Video[]
  maxFiles?: number
  maxSize?: number // в байтах
  acceptedFormats?: string[]
  errors?: Record<string, string>
}

export interface VideoUploadEmits {
  'update:videos': [videos: Video[]]
  'upload': [video: Video]
  'remove': [id: string | number]
  'error': [error: string]
}