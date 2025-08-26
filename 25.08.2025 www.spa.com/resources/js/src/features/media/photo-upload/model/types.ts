// Photo upload types

export interface Photo {
  id: string | number
  file?: File
  url?: string
  preview?: string
  name?: string
  rotation?: number
  isMain?: boolean
}

export interface PhotoUploadProps {
  photos: Photo[]
  maxFiles?: number
  errors?: Record<string, string>
  isLoading?: boolean
}

export interface PhotoUploadEmits {
  'update:photos': [photos: Photo[]]
}