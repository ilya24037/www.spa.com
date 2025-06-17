import { ref } from 'vue'

export function useLockScroll() {
  const scrollPosition = ref(0)
  const isLocked = ref(false)

  function lockScroll() {
    if (isLocked.value) return
    
    // Сохраняем текущую позицию скролла
    scrollPosition.value = window.pageYOffset || document.documentElement.scrollTop
    
    // Блокируем скролл
    document.body.style.overflow = 'hidden'
    document.body.style.position = 'fixed'
    document.body.style.top = `-${scrollPosition.value}px`
    document.body.style.width = '100%'
    
    isLocked.value = true
  }

  function unlockScroll() {
    if (!isLocked.value) return
    
    // Восстанавливаем скролл
    document.body.style.overflow = ''
    document.body.style.position = ''
    document.body.style.top = ''
    document.body.style.width = ''
    
    // Возвращаем позицию скролла
    window.scrollTo(0, scrollPosition.value)
    
    isLocked.value = false
  }

  return {
    isLocked,
    lockScroll,
    unlockScroll,
  }
}