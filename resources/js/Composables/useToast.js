import { ref } from 'vue'

const toasts = ref([])
let toastId = 0

export function useToast() {
  const showToast = (message, type = 'info', duration = 4000) => {
    const id = ++toastId
    
    const toast = {
      id,
      message,
      type, // success, error, warning, info
      duration,
      visible: true
    }
    
    toasts.value.push(toast)
    
    // Auto-remove after duration
    if (duration > 0) {
      setTimeout(() => {
        removeToast(id)
      }, duration)
    }
    
    return id
  }
  
  const removeToast = (id) => {
    const index = toasts.value.findIndex(toast => toast.id === id)
    if (index > -1) {
      toasts.value.splice(index, 1)
    }
  }
  
  const showSuccess = (message, duration = 4000) => {
    return showToast(message, 'success', duration)
  }
  
  const showError = (message, duration = 6000) => {
    return showToast(message, 'error', duration)
  }
  
  const showWarning = (message, duration = 5000) => {
    return showToast(message, 'warning', duration)
  }
  
  const showInfo = (message, duration = 4000) => {
    return showToast(message, 'info', duration)
  }
  
  const clearAll = () => {
    toasts.value = []
  }
  
  return {
    toasts,
    showToast,
    removeToast,
    showSuccess,
    showError,
    showWarning,
    showInfo,
    clearAll
  }
}