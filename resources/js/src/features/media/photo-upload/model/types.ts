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

export interface MediaSettings {
  showAdditionalInfo: boolean
  showServices: boolean
  showPrices: boolean
}

export interface PhotoUploadProps {
  photos: Photo[]
  showAdditionalInfo?: boolean
  showServices?: boolean
  showPrices?: boolean
  maxFiles?: number
  errors?: Record<string, string>
  isLoading?: boolean
}

export interface PhotoUploadEmits {
  'update:photos': [photos: Photo[]]
  'update:showAdditionalInfo': [value: boolean]
  'update:showServices': [value: boolean]
  'update:showPrices': [value: boolean]
}