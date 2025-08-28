import { ref } from 'vue'
import { router } from '@inertiajs/vue3'
import type { AdForm, SubmissionResult } from '../types'
import { buildFormData } from '../utils/formDataBuilder'

/**
 * Composable для отправки данных формы объявления
 * KISS: Простая отправка без сложной логики
 */
export function useAdFormSubmission() {
  const abortController = ref<AbortController | null>(null)
  
  /**
   * Проверяет наличие файлов в форме (фото или видео)
   * Основано на оригинальной логике из backup
   */
  const hasFiles = (form: AdForm): boolean => {
    // Проверка фото файлов
    const hasPhotoFiles = form.photos?.some((p: any) => {
      if (p instanceof File) return true
      if (typeof p === 'object' && p !== null && p.file instanceof File) return true
      return false
    }) || false
    
    // Проверка видео файлов  
    const hasVideoFiles = form.video?.some((v: any) => {
      if (v instanceof File) return true
      if (typeof v === 'object' && v !== null && v.file instanceof File) return true
      // Строка base64
      if (typeof v === 'string' && v.startsWith('data:video/')) return true
      return false
    }) || false
    
    return hasPhotoFiles || hasVideoFiles
  }
  
  // ✅ СОХРАНЕНИЕ ЧЕРНОВИКА (ВОССТАНОВЛЕНО из backup: используем Inertia router как в оригинале)
  const saveDraft = async (form: AdForm): Promise<SubmissionResult> => {
    return new Promise((resolve) => {
      console.log('🔍 saveDraft НАЧАЛО с form:', form)
      
      // Флаг для предотвращения дублирования resolve
      let resolved = false
      
      // Подготовка данных
      const formData = buildFormData(form, false) // false = черновик
      console.log('🔍 saveDraft buildFormData завершен, formData создан')
      
      // Определение URL и метода
      const isUpdate = !!form.id
      
      if (isUpdate) {
        const adId = form.id
        const filesPresent = hasFiles(form)
        
        console.log('🔍 saveDraft ОБНОВЛЕНИЕ черновика:', { 
          adId, 
          hasFiles: filesPresent,
          method: filesPresent ? 'POST с _method=PUT' : 'PUT'
        })
        
        if (filesPresent) {
          // Если есть файлы - используем FormData с POST и _method=PUT (как в оригинале)
          formData.append('_method', 'PUT')
          
          router.post(`/draft/${adId}`, formData as any, {
            preserveScroll: true,
            preserveState: true,
            forceFormData: true,
            only: ['ad'],
            onSuccess: (page: any) => {
              console.log('✅ saveDraft: Черновик успешно обновлен (с файлами)', page)
              if (!resolved) {
                resolved = true
                resolve({
                  success: true,
                  // ИСПРАВЛЕНО: для Inertia запросов данные приходят через flash session в page.props
                  data: page.props?.ad || page.props?.flash?.ad || page.props,
                  message: page.props?.flash?.success || page.props?.success || 'Черновик сохранен'
                })
              }
            },
            onError: (errors: any) => {
              console.error('❌ saveDraft: Ошибка обновления черновика', errors)
              if (!resolved) {
                resolved = true
                resolve({
                  success: false,
                  errors: errors,
                  message: 'Ошибка сохранения черновика'
                })
              }
            },
            onFinish: () => {
              console.log('🏁 saveDraft: Запрос с файлами завершен')
              // Если не был резолвлен в onSuccess или onError
              if (!resolved) {
                resolved = true
                resolve({
                  success: true,
                  message: 'Черновик сохранен'
                })
              }
            }
          })
        } else {
          // Если файлов нет - используем обычный PUT с объектом (как в оригинале)
          const plainData = convertFormDataToPlainObject(formData)
          
          router.put(`/draft/${adId}`, plainData, {
            preserveScroll: true,
            preserveState: true,
            onSuccess: (page: any) => {
              console.log('✅ saveDraft: Черновик успешно обновлен (без файлов)', page)
              if (!resolved) {
                resolved = true
                resolve({
                  success: true,
                  // ИСПРАВЛЕНО: для Inertia запросов данные приходят через flash session в page.props
                  data: page.props?.ad || page.props?.flash?.ad || page.props,
                  message: page.props?.flash?.success || page.props?.success || 'Черновик сохранен'
                })
              }
            },
            onError: (errors: any) => {
              console.error('❌ saveDraft: Ошибка обновления черновика', errors)
              if (!resolved) {
                resolved = true
                resolve({
                  success: false,
                  errors: errors,
                  message: 'Ошибка сохранения черновика'
                })
              }
            },
            onFinish: () => {
              console.log('🏁 saveDraft: Запрос завершен')
              // Если не был резолвлен в onSuccess или onError
              if (!resolved) {
                resolved = true
                resolve({
                  success: true,
                  message: 'Черновик сохранен'
                })
              }
            }
          })
        }
      } else {
        // Создание нового черновика - всегда POST с FormData (как в оригинале)
        console.log('🔍 saveDraft СОЗДАНИЕ нового черновика')
        
        router.post('/draft', formData as any, {
          preserveScroll: true,
          forceFormData: true,
          onSuccess: (response: any) => {
            console.log('✅ saveDraft: Новый черновик создан')
            resolve({
              success: true,
              data: response.props?.ad || response,
              message: 'Черновик создан'
            })
          },
          onError: (errors: any) => {
            console.error('❌ saveDraft: Ошибка создания черновика', errors)
            resolve({
              success: false,
              errors: errors,
              message: 'Ошибка создания черновика'
            })
          }
        })
      }
    })
  }
  
  // ✅ ПУБЛИКАЦИЯ ОБЪЯВЛЕНИЯ (используем Inertia router)
  const publishAd = async (form: AdForm): Promise<SubmissionResult> => {
    return new Promise((resolve) => {
      console.log('🔍 publishAd НАЧАЛО с form:', form)
      
      // Подготовка данных
      const formData = buildFormData(form, true) // true = публикация
      
      // Определение URL и метода
      const isUpdate = !!form.id
      
      if (isUpdate) {
        const adId = form.id
        const filesPresent = hasFiles(form)
        
        console.log('🔍 publishAd ОБНОВЛЕНИЕ объявления:', { 
          adId,
          hasFiles: filesPresent
        })
        
        if (filesPresent) {
          // Если есть файлы - используем FormData с POST и _method=PUT
          formData.append('_method', 'PUT')
          
          router.post(`/ads/${adId}`, formData as any, {
            preserveScroll: true,
            forceFormData: true,
            onSuccess: (response: any) => {
              console.log('✅ publishAd: Объявление успешно обновлено (с файлами)')
              resolve({
                success: true,
                data: response.props?.ad || response,
                message: 'Объявление опубликовано'
              })
            },
            onError: (errors: any) => {
              console.error('❌ publishAd: Ошибка обновления объявления', errors)
              resolve({
                success: false,
                errors: errors,
                message: 'Ошибка публикации'
              })
            }
          })
        } else {
          // Если файлов нет - используем обычный PUT с объектом
          const plainData = convertFormDataToPlainObject(formData)
          
          router.put(`/ads/${adId}`, plainData, {
            preserveScroll: true,
            onSuccess: (response: any) => {
              console.log('✅ publishAd: Объявление успешно обновлено (без файлов)')
              resolve({
                success: true,
                data: response.props?.ad || response,
                message: 'Объявление опубликовано'
              })
            },
            onError: (errors: any) => {
              console.error('❌ publishAd: Ошибка обновления объявления', errors)
              resolve({
                success: false,
                errors: errors,
                message: 'Ошибка публикации'
              })
            }
          })
        }
      } else {
        // Создание нового объявления - всегда POST с FormData
        console.log('🔍 publishAd СОЗДАНИЕ нового объявления')
        
        router.post('/ads', formData as any, {
          preserveScroll: true,
          forceFormData: true,
          onSuccess: (response: any) => {
            console.log('✅ publishAd: Новое объявление создано')
            resolve({
              success: true,
              data: response.props?.ad || response,
              message: 'Объявление опубликовано'
            })
          },
          onError: (errors: any) => {
            console.error('❌ publishAd: Ошибка создания объявления', errors)
            resolve({
              success: false,
              errors: errors,
              message: 'Ошибка публикации'
            })
          }
        })
      }
    })
  }
  
  // ✅ ЗАГРУЗКА ДАННЫХ ОБЪЯВЛЕНИЯ
  const loadAd = async (id: number): Promise<SubmissionResult> => {
    try {
      const response = await axios.get(`/ads/${id}/edit`, {
        headers: {
          'X-Requested-With': 'XMLHttpRequest',
          'Accept': 'application/json'
        }
      })
      
      if (response.data) {
        // Обработка структуры ответа от Inertia
        
        // Проверяем разные варианты структуры ответа от Inertia
        let adData = null
        
        if (response.data.props?.ad?.data) {
          // Inertia structure with AdResource: { props: { ad: { data: {...} } } }
          adData = response.data.props.ad.data
          // Found Inertia props structure with Resource
        } else if (response.data.props?.ad) {
          // Inertia structure: { props: { ad: {...} } }
          adData = response.data.props.ad
          // Found Inertia props structure
        } else if (response.data.ad?.data) {
          // Direct structure with AdResource: { ad: { data: {...} } }
          adData = response.data.ad.data
          // Found direct Resource structure
        } else if (response.data.ad) {
          // Direct structure: { ad: {...} }
          adData = response.data.ad
          // Found direct structure
        } else {
          // Fallback to full response.data
          adData = response.data
          // Using fallback structure
        }
        
        // Получены данные объявления
        
        return {
          success: true,
          data: adData
        }
      }
      
      throw new Error('Объявление не найдено')
      
    } catch (error: any) {
      // Ошибка загрузки объявления
      
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
        // Автосохранение выполнено
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
      // После сохранения черновика перенаправляем в личный кабинет на вкладку черновиков
      router.visit('/profile/items/draft/all')
    } else {
      router.visit(`/ads/${adId}`)
    }
  }
  
  // ✅ ОБРАБОТКА ОШИБОК
  const handleSubmissionError = (error: any): Record<string, string[]> => {
    if (error.response?.status === 422 && error.response.data?.errors) {
      return error.response.data.errors
    }
    
    if (error.response?.status === 401) {
      router.visit('/login')
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

/**
 * Конвертирует FormData в обычный объект для исправления проблемы PUT + FormData
 * Основано на решении из DRAFT_FIELDS_SAVING_FIX_REPORT.md
 */
function convertFormDataToPlainObject(formData: FormData): Record<string, any> {
  const plainData: Record<string, any> = {}
  
  formData.forEach((value, key) => {
    // Обработка массивов (photos[0], photos[1])
    if (key.includes('[')) {
      const match = key.match(/^(.+?)\[(\d+)\]$/)
      if (match) {
        const fieldName = match[1]
        const index = parseInt(match[2], 10)
        if (!plainData[fieldName]) {
          plainData[fieldName] = []
        }
        plainData[fieldName][index] = value
      }
    } else {
      // Парсинг JSON строк
      if (typeof value === 'string' && (value.startsWith('{') || value.startsWith('['))) {
        try {
          plainData[key] = JSON.parse(value)
        } catch (e) {
          plainData[key] = value
        }
      } else {
        plainData[key] = value
      }
    }
  })
  
  return plainData
}