/**
 * Composable для автосохранения формы
 * Автоматически сохраняет черновик через определенные интервалы
 */

import { ref, watch, onUnmounted } from 'vue'
import { autosaveDraft } from '@/utils/adApi'

export function useAutosave(form, options = {}) {
  const {
    interval = 30000, // 30 секунд
    enabled = true,
    onSave = null,
    onError = null
  } = options

  const isSaving = ref(false)
  const lastSaved = ref(null)
  const hasUnsavedChanges = ref(false)
  
  let autosaveTimer = null
  let initialFormSnapshot = null

  /**
   * Создать снимок формы для сравнения
   */
  const createFormSnapshot = (formData) => {
    return JSON.stringify(formData)
  }

  /**
   * Проверить, изменилась ли форма
   */
  const hasFormChanged = (formData) => {
    const currentSnapshot = createFormSnapshot(formData)
    return currentSnapshot !== initialFormSnapshot
  }

  /**
   * Выполнить автосохранение
   */
  const performAutosave = async () => {
    if (!enabled || isSaving.value || !hasUnsavedChanges.value) {
      return
    }

    try {
      isSaving.value = true
      
      const success = await autosaveDraft(form.value)
      
      if (success) {
        lastSaved.value = new Date()
        hasUnsavedChanges.value = false
        initialFormSnapshot = createFormSnapshot(form.value)
        
        if (onSave) {
          onSave(lastSaved.value)
        }
      }
    } catch (error) {
      
      if (onError) {
        onError(error)
      }
    } finally {
      isSaving.value = false
    }
  }

  /**
   * Запустить автосохранение
   */
  const startAutosave = () => {
    if (!enabled) return

    stopAutosave() // Остановить предыдущий таймер
    
    autosaveTimer = setInterval(() => {
      performAutosave()
    }, interval)
  }

  /**
   * Остановить автосохранение
   */
  const stopAutosave = () => {
    if (autosaveTimer) {
      clearInterval(autosaveTimer)
      autosaveTimer = null
    }
  }

  /**
   * Принудительно сохранить
   */
  const forceSave = async () => {
    hasUnsavedChanges.value = true
    await performAutosave()
  }

  /**
   * Сбросить состояние
   */
  const reset = () => {
    hasUnsavedChanges.value = false
    lastSaved.value = null
    initialFormSnapshot = createFormSnapshot(form.value)
  }

  // Отслеживать изменения формы
  watch(form, (newForm) => {
    if (initialFormSnapshot === null) {
      initialFormSnapshot = createFormSnapshot(newForm)
      return
    }

    hasUnsavedChanges.value = hasFormChanged(newForm)
  }, { deep: true })

  // Запустить автосохранение при инициализации
  if (enabled) {
    startAutosave()
  }

  // Очистить таймер при размонтировании
  onUnmounted(() => {
    stopAutosave()
  })

  // Сохранить при закрытии страницы
  if (typeof window !== 'undefined') {
    const handleBeforeUnload = (event) => {
      if (hasUnsavedChanges.value) {
        event.preventDefault()
        event.returnValue = 'У вас есть несохраненные изменения. Вы уверены, что хотите покинуть страницу?'
        return event.returnValue
      }
    }

    window.addEventListener('beforeunload', handleBeforeUnload)
    
    onUnmounted(() => {
      window.removeEventListener('beforeunload', handleBeforeUnload)
    })
  }

  return {
    isSaving,
    lastSaved,
    hasUnsavedChanges,
    startAutosave,
    stopAutosave,
    forceSave,
    reset
  }
} 