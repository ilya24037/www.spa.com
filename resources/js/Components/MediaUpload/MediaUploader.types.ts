// MediaUploader.types.ts
export interface Photo {
  id: number
  thumb_url: string
  sort_order: number
  is_main: boolean
}

export interface Video {
  id: number
  video_url: string
  poster_url: string
  duration: string
  file_size: number
}

export interface MediaUploaderProps {
  masterId: number
  masterName: string
  initialPhotos?: Photo[]
  initialVideo?: Video | null
}

export interface UploadingState {
  avatar: boolean
  photos: boolean
  video: boolean
}

export interface UploadResponse {
  success: boolean
  message?: string
  error?: string
  avatar_url?: string
  photos?: Photo[]
  video?: Video
}

export interface MediaUploaderEmits {
  photosUpdated: [photos: Photo[]]
  videoUpdated: [video: Video | null]
  avatarUpdated: [avatarUrl: string]
}