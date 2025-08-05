import { ref, onMounted, onBeforeUnmount, type Ref } from 'vue'

export function useMediaQuery(query: string): Ref<boolean> {
  const matches: Ref<boolean> = ref(false)
  let mediaQuery: MediaQueryList | null = null

  const updateMatches = (): void => {
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
        // @ts-ignore - поддержка старых браузеров
        mediaQuery.addListener(updateMatches)
      }
    }
  })

  onBeforeUnmount(() => {
    if (mediaQuery) {
      if (mediaQuery.removeEventListener) {
        mediaQuery.removeEventListener('change', updateMatches)
      } else {
        // @ts-ignore - поддержка старых браузеров
        mediaQuery.removeListener(updateMatches)
      }
    }
  })

  return matches
}