import { ref, type Ref } from 'vue'

export interface UseLockScrollReturn {
  isLocked: Ref<boolean>
  lockScroll: () => void
  unlockScroll: () => void
}

export function useLockScroll(): UseLockScrollReturn {
  const scrollPosition: Ref<number> = ref(0)
  const isLocked: Ref<boolean> = ref(false)

  const lockScroll = (): void => {
    if (isLocked.value) return
    
    // Сохраняем текущую позицию скролла
    scrollPosition.value = (window as any).pageYOffset || document.documentElement.scrollTop
    
    // Блокируем скролл
    document.body.style.overflow = 'hidden'
    document.body.style.position = 'fixed'
    document.body.style.top = `-${scrollPosition.value}px`
    document.body.style.width = '100%'
    
    isLocked.value = true
  }

  const unlockScroll = (): void => {
    if (!isLocked.value) return
    
    // Восстанавливаем скролл
    document.body.style.overflow = ''
    document.body.style.position = ''
    document.body.style.top = ''
    document.body.style.width = ''
    
    // Возвращаем позицию скролла
    (window as any).scrollTo(0, scrollPosition.value)
    
    isLocked.value = false
  }

  return {
    isLocked,
    lockScroll,
    unlockScroll,
  }
}