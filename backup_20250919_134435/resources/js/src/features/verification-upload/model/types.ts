/**
 * Типы для системы верификации объявлений
 */

export type VerificationStatus = 'none' | 'pending' | 'verified' | 'rejected'
export type VerificationType = 'photo' | 'video' | 'both' | null

export interface VerificationData {
  status: VerificationStatus
  type: VerificationType
  comment: string | null
  verified_at: string | null
  expires_at: string | null
  has_photo: boolean
  has_video: boolean
  is_expired: boolean
  needs_update: boolean
  badge: VerificationBadge | null
}

export interface VerificationBadge {
  status: string
  text: string
  expires_at: string | null
  days_left: number | null
  needs_update: boolean
}

export interface VerificationInstructions {
  photo: InstructionSet
  video: InstructionSet
}

export interface InstructionSet {
  title: string
  steps: string[]
  requirements: string[]
}

export interface VerificationUploadResponse {
  success: boolean
  message: string
  path?: string
}

export interface VerificationFile {
  url: string | null
  file: File | null
  preview: string | null
  uploading: boolean
  error: string | null
}