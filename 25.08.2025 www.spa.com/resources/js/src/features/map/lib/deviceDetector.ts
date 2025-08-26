/**
 * Определение типа устройства
 */

export function isMobileDevice(): boolean {
  return /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(
    navigator.userAgent
  )
}

export function isTouchDevice(): boolean {
  return 'ontouchstart' in window || navigator.maxTouchPoints > 0
}

export function getDeviceType(): 'mobile' | 'tablet' | 'desktop' {
  const userAgent = navigator.userAgent.toLowerCase()
  
  if (/ipad|tablet|(android(?!.*mobile))/i.test(userAgent)) {
    return 'tablet'
  }
  
  if (/mobile|android|iphone|ipod|blackberry|opera mini|iemobile/i.test(userAgent)) {
    return 'mobile'
  }
  
  return 'desktop'
}