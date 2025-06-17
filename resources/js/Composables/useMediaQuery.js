import { ref, onMounted, onBeforeUnmount } from 'vue'

export function useMediaQuery(query) {
  const matches = ref(false)
  let mediaQuery = null

  function updateMatches() {
    matches.value = mediaQuery?.matches ?? false
  }

  onMounted(() => {
    if (typeof window !== 'undefined' && 'matchMedia' in window) {
      mediaQuery = window.matchMedia(query)
      updateMatches()
      
      // Используем addEventListener для поддержки старых браузеров
      if (mediaQuery.addEventListener) {
        mediaQuery.addEventListener('change', updateMatches)
      } else {
        mediaQuery.addListener(updateMatches)
      }
    }
  })

  onBeforeUnmount(() => {
    if (mediaQuery) {
      if (mediaQuery.removeEventListener) {
        mediaQuery.removeEventListener('change', updateMatches)
      } else {
        mediaQuery.removeListener(updateMatches)
      }
    }
  })

  return matches
}