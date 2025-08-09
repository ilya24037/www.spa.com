// Простая навигация как на Avito
export function useNavigation() {
  
  // Простой переход назад
  const goBack = () => {
    if (window.history.length > 1) {
      window.history.back()
    } else {
      window.location.href = '/'
    }
  }
  
  // Простой переход по URL
  const goTo = (url) => {
    window.location.href = url
  }
  
  // Проверка можно ли идти назад
  const canGoBack = () => {
    return window.history.length > 1
  }
  
  return {
    goBack,
    goTo,
    canGoBack
  }
} 