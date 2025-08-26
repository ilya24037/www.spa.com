import { ref, readonly } from 'vue'
import type { Master } from '@/src/entities/master/model/types'

/**
 * Composable для управления Quick View модальным окном
 * Позволяет открывать быстрый просмотр мастера из любого компонента
 */
export function useQuickView() {
  // Состояние модалки
  const isOpen = ref(false)
  const currentMaster = ref<Master | null>(null)
  const isLoading = ref(false)

  /**
   * Открыть Quick View для мастера
   */
  const openQuickView = (master: Master) => {
    currentMaster.value = master
    isOpen.value = true
  }

  /**
   * Открыть Quick View по ID мастера (с загрузкой данных)
   */
  const openQuickViewById = async (masterId: number) => {
    isLoading.value = true
    
    try {
      // В реальном приложении здесь будет API запрос
      const response = await fetch(`/api/masters/${masterId}`)
      const master = await response.json()
      
      currentMaster.value = master
      isOpen.value = true
    } catch (error) {
      console.error('Failed to load master data:', error)
    } finally {
      isLoading.value = false
    }
  }

  /**
   * Закрыть Quick View
   */
  const closeQuickView = () => {
    isOpen.value = false
    // Очищаем данные после анимации закрытия
    setTimeout(() => {
      currentMaster.value = null
    }, 300)
  }

  /**
   * Переключить Quick View для мастера
   */
  const toggleQuickView = (master: Master) => {
    if (isOpen.value && currentMaster.value?.id === master.id) {
      closeQuickView()
    } else {
      openQuickView(master)
    }
  }

  return {
    // State
    isOpen: readonly(isOpen),
    currentMaster: readonly(currentMaster),
    isLoading: readonly(isLoading),
    
    // Methods
    openQuickView,
    openQuickViewById,
    closeQuickView,
    toggleQuickView
  }
}

// Глобальный инстанс для использования во всем приложении
let globalQuickView: ReturnType<typeof useQuickView> | null = null

/**
 * Получить глобальный инстанс Quick View
 * Позволяет использовать один инстанс модалки во всем приложении
 */
export function useGlobalQuickView() {
  if (!globalQuickView) {
    globalQuickView = useQuickView()
  }
  return globalQuickView
}