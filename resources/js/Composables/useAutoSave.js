import { ref, watch, onMounted, onUnmounted } from 'vue'

export function useAutoSave(formData, options = {}) {
  const {
    key = 'autosave_form',
    interval = 30000, // 30 секунд
    exclude = [], // поля которые не сохраняем
    enabled = true
  } = options

  const lastSaved = ref(null)
  const saving = ref(false)
  const hasDraft = ref(false)
  const saveInterval = ref(null)

  /**
   * Сохраняет данные в localStorage
   */
  const saveData = () => {
    if (!enabled) return

    saving.value = true
    
    try {
      // Фильтруем исключённые поля
      const dataToSave = Object.keys(formData).reduce((acc, key) => {
        if (!exclude.includes(key)) {
          acc[key] = formData[key]
        }
        return acc
      }, {})

      // Добавляем метаданные
      const saveData = {
        data: dataToSave,
        timestamp: Date.now(),
        version: '1.0'
      }

      localStorage.setItem(key, JSON.stringify(saveData))
      lastSaved.value = new Date()
      
      console.log('💾 Данные автоматически сохранены:', lastSaved.value.toLocaleTimeString())
    } catch (error) {
      console.warn('Ошибка автосохранения:', error)
    } finally {
      saving.value = false
    }
  }

  /**
   * Загружает данные из localStorage
   */
  const loadData = () => {
    try {
      const savedData = localStorage.getItem(key)
      
      if (!savedData) {
        hasDraft.value = false
        return null
      }

      const parsed = JSON.parse(savedData)
      
      // Проверяем структуру данных
      if (!parsed.data || !parsed.timestamp) {
        console.warn('Неверная структура сохранённых данных')
        clearData()
        return null
      }

      // Проверяем возраст данных (не старше 7 дней)
      const maxAge = 7 * 24 * 60 * 60 * 1000 // 7 дней
      if (Date.now() - parsed.timestamp > maxAge) {
        console.log('Сохранённые данные устарели, удаляем')
        clearData()
        return null
      }

      hasDraft.value = true
      lastSaved.value = new Date(parsed.timestamp)
      
      return parsed.data
    } catch (error) {
      console.warn('Ошибка загрузки данных:', error)
      clearData()
      return null
    }
  }

  /**
   * Очищает сохранённые данные
   */
  const clearData = () => {
    localStorage.removeItem(key)
    hasDraft.value = false
    lastSaved.value = null
  }

  /**
   * Восстанавливает данные в форму
   */
  const restoreData = () => {
    const savedData = loadData()
    
    if (savedData) {
      // Копируем данные в форму
      Object.keys(savedData).forEach(key => {
        if (formData.hasOwnProperty(key)) {
          formData[key] = savedData[key]
        }
      })
      
      return true
    }
    
    return false
  }

  /**
   * Проверяет, есть ли изменения в форме
   */
  const hasChanges = () => {
    const savedData = loadData()
    
    if (!savedData) return true
    
    // Сравниваем текущие данные с сохранёнными
    return JSON.stringify(formData) !== JSON.stringify(savedData)
  }

  /**
   * Получает информацию о сохранённых данных
   */
  const getSaveInfo = () => {
    const savedData = localStorage.getItem(key)
    
    if (!savedData) return null
    
    try {
      const parsed = JSON.parse(savedData)
      return {
        timestamp: parsed.timestamp,
        age: Date.now() - parsed.timestamp,
        size: new Blob([savedData]).size
      }
    } catch {
      return null
    }
  }

  /**
   * Запускает автосохранение
   */
  const startAutoSave = () => {
    if (!enabled || saveInterval.value) return

    saveInterval.value = setInterval(saveData, interval)
    
    // Также сохраняем при изменениях (с дебаунсом)
    let debounceTimer = null
    const debouncedSave = () => {
      clearTimeout(debounceTimer)
      debounceTimer = setTimeout(saveData, 2000) // 2 секунды задержки
    }

    // Отслеживаем изменения в форме
    const stopWatcher = watch(
      () => JSON.stringify(formData),
      debouncedSave,
      { deep: true }
    )

    // Сохраняем функцию остановки watcher'а
    saveInterval.value.stopWatcher = stopWatcher
  }

  /**
   * Останавливает автосохранение
   */
  const stopAutoSave = () => {
    if (saveInterval.value) {
      clearInterval(saveInterval.value)
      
      // Останавливаем watcher если есть
      if (saveInterval.value.stopWatcher) {
        saveInterval.value.stopWatcher()
      }
      
      saveInterval.value = null
    }
  }

  /**
   * Сохраняет данные при закрытии страницы
   */
  const handleBeforeUnload = (event) => {
    if (hasChanges()) {
      saveData()
      
      // Предупреждаем пользователя о несохранённых данных
      const message = 'У вас есть несохранённые изменения. Вы уверены, что хотите покинуть страницу?'
      event.returnValue = message
      return message
    }
  }

  // Инициализация
  onMounted(() => {
    // Проверяем наличие сохранённых данных
    loadData()
    
    // Запускаем автосохранение
    if (enabled) {
      startAutoSave()
      window.addEventListener('beforeunload', handleBeforeUnload)
    }
  })

  // Очистка
  onUnmounted(() => {
    stopAutoSave()
    window.removeEventListener('beforeunload', handleBeforeUnload)
  })

  return {
    // Состояние
    lastSaved,
    saving,
    hasDraft,
    
    // Методы
    saveData,
    loadData,
    clearData,
    restoreData,
    hasChanges,
    getSaveInfo,
    startAutoSave,
    stopAutoSave
  }
}