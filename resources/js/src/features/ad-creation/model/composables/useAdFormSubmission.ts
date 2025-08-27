import { ref } from 'vue'
import { useRouter } from 'vue-router'
import axios from 'axios'
import type { AdForm, SubmissionResult } from '../types'
import { buildFormData } from '../utils/formDataBuilder'

/**
 * Composable для отправки данных формы объявления
 * KISS: Простая отправка без сложной логики
 */
export function useAdFormSubmission() {
  const router = useRouter()
  const abortController = ref<AbortController | null>(null)
  
  // ✅ СОХРАНЕНИЕ ЧЕРНОВИКА
  const saveDraft = async (form: AdForm): Promise<SubmissionResult> => {
    try {
      // Подготовка данных
      const formData = buildFormData(form, false) // false = черновик
      
      // Определение URL и метода
      const isUpdate = !!form.id
      const url = isUpdate ? `/draft/${form.id}` : '/draft'
      const method = isUpdate ? 'put' : 'post'
      
      // Отправка запроса
      const response = await axios({
        method,
        url,
        data: formData,
        headers: {
          'Content-Type': 'multipart/form-data'
        }
      })
      
      // Обработка успешного ответа
      if (response.data.success) {
        return {
          success: true,
          data: response.data.ad,
          message: response.data.message || 'Черновик сохранен'
        }
      }
      
      // Обработка ошибок валидации
      if (response.data.errors) {
        return {
          success: false,
          errors: response.data.errors,
          message: response.data.message || 'Ошибка валидации'
        }
      }
      
      // Неожиданный ответ
      throw new Error('Неожиданный формат ответа')
      
    } catch (error: any) {
      console.error('Ошибка сохранения черновика:', error)
      
      // Обработка ошибок axios
      if (error.response?.data?.errors) {
        return {
          success: false,
          errors: error.response.data.errors,
          message: error.response.data.message || 'Ошибка сохранения'
        }
      }
      
      // Общая ошибка
      return {
        success: false,
        message: error.message || 'Произошла ошибка при сохранении'
      }
    }
  }
  
  // ✅ ПУБЛИКАЦИЯ ОБЪЯВЛЕНИЯ
  const publishAd = async (form: AdForm): Promise<SubmissionResult> => {
    try {
      // Подготовка данных
      const formData = buildFormData(form, true) // true = публикация
      
      // Определение URL и метода
      const isUpdate = !!form.id
      const url = isUpdate ? `/ads/${form.id}` : '/ads'
      const method = isUpdate ? 'put' : 'post'
      
      // Отправка запроса
      const response = await axios({
        method,
        url,
        data: formData,
        headers: {
          'Content-Type': 'multipart/form-data'
        }
      })
      
      // Обработка успешного ответа
      if (response.data.success) {
        return {
          success: true,
          data: response.data.ad,
          message: response.data.message || 'Объявление опубликовано'
        }
      }
      
      // Обработка ошибок валидации
      if (response.data.errors) {
        return {
          success: false,
          errors: response.data.errors,
          message: response.data.message || 'Заполните обязательные поля'
        }
      }
      
      // Неожиданный ответ
      throw new Error('Неожиданный формат ответа')
      
    } catch (error: any) {
      console.error('Ошибка публикации:', error)
      
      // Обработка ошибок axios
      if (error.response?.data?.errors) {
        return {
          success: false,
          errors: error.response.data.errors,
          message: error.response.data.message || 'Ошибка публикации'
        }
      }
      
      // Общая ошибка
      return {
        success: false,
        message: error.message || 'Произошла ошибка при публикации'
      }
    }
  }
  
  // ✅ ЗАГРУЗКА ДАННЫХ ОБЪЯВЛЕНИЯ
  const loadAd = async (id: number): Promise<SubmissionResult> => {
    try {
      const response = await axios.get(`/ads/${id}/edit`)
      
      if (response.data) {
        return {
          success: true,
          data: response.data.ad || response.data
        }
      }
      
      throw new Error('Объявление не найдено')
      
    } catch (error: any) {
      console.error('Ошибка загрузки объявления:', error)
      
      return {
        success: false,
        message: error.response?.data?.message || error.message || 'Ошибка загрузки'
      }
    }
  }
  
  // ✅ АВТОСОХРАНЕНИЕ (DEBOUNCED)
  let autosaveTimer: ReturnType<typeof setTimeout> | null = null
  
  const autosaveDraft = (form: AdForm, delay = 3000): void => {
    // Отменяем предыдущий таймер
    if (autosaveTimer) {
      clearTimeout(autosaveTimer)
    }
    
    // Устанавливаем новый таймер
    autosaveTimer = setTimeout(async () => {
      if (form.title && form.title.trim()) {
        const result = await saveDraft(form)
        if (result.success) {
          console.log('Автосохранение выполнено')
        }
      }
    }, delay)
  }
  
  // ✅ ОТМЕНА ЗАПРОСА
  const cancelRequest = (): void => {
    if (abortController.value) {
      abortController.value.abort()
      abortController.value = null
    }
  }
  
  // ✅ НАВИГАЦИЯ ПОСЛЕ СОХРАНЕНИЯ
  const navigateAfterSave = (adId: number, isDraft = false): void => {
    if (isDraft) {
      router.push(`/ads/${adId}/edit`)
    } else {
      router.push(`/ads/${adId}`)
    }
  }
  
  // ✅ ОБРАБОТКА ОШИБОК
  const handleSubmissionError = (error: any): Record<string, string[]> => {
    if (error.response?.status === 422 && error.response.data?.errors) {
      return error.response.data.errors
    }
    
    if (error.response?.status === 401) {
      router.push('/login')
      return { auth: ['Необходима авторизация'] }
    }
    
    if (error.response?.status === 403) {
      return { permission: ['У вас нет прав для этого действия'] }
    }
    
    return { general: ['Произошла ошибка. Попробуйте позже'] }
  }
  
  return {
    saveDraft,
    publishAd,
    loadAd,
    autosaveDraft,
    cancelRequest,
    navigateAfterSave,
    handleSubmissionError
  }
}