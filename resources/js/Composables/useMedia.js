import { ref, computed } from 'vue'
import { router } from '@inertiajs/vue3'

export function useMedia() {
  const uploading = ref(false)
  const uploadProgress = ref(0)
  const uploadError = ref(null)

  // Загрузка фотографий
  const uploadPhotos = async (masterId, files) => {
    uploading.value = true
    uploadError.value = null
    uploadProgress.value = 0

    try {
      const formData = new FormData()
      
      // Добавляем файлы
      for (let i = 0; i < files.length; i++) {
        formData.append('photos[]', files[i])
      }

      const response = await fetch(`/masters/${masterId}/media/photos`, {
        method: 'POST',
        headers: {
          'X-CSRF-TOKEN': await getCSRFToken()
        },
        body: formData
      })

      const data = await response.json()

      if (data.success) {
        // Обновляем страницу для отображения новых фото
        router.reload()
        return data.photos
      } else {
        throw new Error(data.error || 'Ошибка загрузки')
      }
    } catch (error) {
      uploadError.value = error.message
      throw error
    } finally {
      uploading.value = false
      uploadProgress.value = 0
    }
  }

  // Загрузка видео
  const uploadVideo = async (masterId, file) => {
    uploading.value = true
    uploadError.value = null

    try {
      const formData = new FormData()
      formData.append('video', file)

      const response = await fetch(`/masters/${masterId}/media/video`, {
        method: 'POST',
        headers: {
          'X-CSRF-TOKEN': await getCSRFToken()
        },
        body: formData
      })

      const data = await response.json()

      if (data.success) {
        router.reload()
        return data.video
      } else {
        throw new Error(data.error || 'Ошибка загрузки видео')
      }
    } catch (error) {
      uploadError.value = error.message
      throw error
    } finally {
      uploading.value = false
    }
  }

  // Удаление медиа
  const deleteMedia = async (type, id) => {
    try {
      const response = await fetch(`/masters/media/${type}/${id}`, {
        method: 'DELETE',
        headers: {
          'X-CSRF-TOKEN': await getCSRFToken()
        }
      })

      const data = await response.json()

      if (data.success) {
        router.reload()
        return true
      } else {
        throw new Error(data.error || 'Ошибка удаления')
      }
    } catch (error) {
      uploadError.value = error.message
      throw error
    }
  }

  // Получение CSRF токена
  const getCSRFToken = async () => {
    const response = await fetch('/csrf-token')
    const data = await response.json()
    return data.token
  }

  return {
    uploading: Readonly(uploading),
    uploadProgress: Readonly(uploadProgress),
    uploadError: Readonly(uploadError),
    uploadPhotos,
    uploadVideo,
    deleteMedia
  }
} 