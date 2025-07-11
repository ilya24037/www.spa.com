import { ref } from 'vue'

// Глобальное состояние для уведомлений
const toasts = ref([])
let toastId = 0

export function useToast() {
    /**
     * Добавить уведомление
     */
    const addToast = (options) => {
        const toast = {
            id: ++toastId,
            title: options.title || '',
            message: options.message || '',
            type: options.type || 'info', // success, error, warning, info
            duration: options.duration || 5000,
            closable: options.closable !== false,
            action: options.action || null,
            timestamp: Date.now()
        }
        
        toasts.value.push(toast)
        
        // Автоматическое удаление
        if (toast.duration > 0) {
            setTimeout(() => {
                removeToast(toast.id)
            }, toast.duration)
        }
        
        return toast.id
    }
    
    /**
     * Удалить уведомление
     */
    const removeToast = (id) => {
        const index = toasts.value.findIndex(t => t.id === id)
        if (index > -1) {
            toasts.value.splice(index, 1)
        }
    }
    
    /**
     * Показать успешное уведомление
     */
    const showSuccess = (message, title = 'Успешно', options = {}) => {
        return addToast({
            title,
            message,
            type: 'success',
            ...options
        })
    }
    
    /**
     * Показать уведомление об ошибке
     */
    const showError = (message, title = 'Ошибка', options = {}) => {
        return addToast({
            title,
            message,
            type: 'error',
            duration: 7000, // Ошибки показываем дольше
            ...options
        })
    }
    
    /**
     * Показать предупреждение
     */
    const showWarning = (message, title = 'Внимание', options = {}) => {
        return addToast({
            title,
            message,
            type: 'warning',
            ...options
        })
    }
    
    /**
     * Показать информационное уведомление
     */
    const showInfo = (message, title = 'Информация', options = {}) => {
        return addToast({
            title,
            message,
            type: 'info',
            ...options
        })
    }
    
    /**
     * Показать уведомление с действием
     */
    const showAction = (message, action, title = 'Действие', options = {}) => {
        return addToast({
            title,
            message,
            type: 'info',
            action: {
                label: action.label || 'Действие',
                handler: action.handler || (() => {}),
                class: action.class || 'text-purple-600 hover:text-purple-700'
            },
            ...options
        })
    }
    
    /**
     * Очистить все уведомления
     */
    const clearToasts = () => {
        toasts.value = []
    }
    
    /**
     * Показать уведомление загрузки
     */
    const showLoading = (message = 'Загрузка...', title = '') => {
        return addToast({
            title,
            message,
            type: 'loading',
            duration: 0, // Не исчезает автоматически
            closable: false
        })
    }
    
    /**
     * Обновить уведомление
     */
    const updateToast = (id, options) => {
        const toast = toasts.value.find(t => t.id === id)
        if (toast) {
            Object.assign(toast, options)
            
            // Если изменилась продолжительность, перезапускаем таймер
            if (options.duration !== undefined && options.duration > 0) {
                setTimeout(() => {
                    removeToast(id)
                }, options.duration)
            }
        }
    }
    
    return {
        toasts,
        addToast,
        removeToast,
        showSuccess,
        showError,
        showWarning,
        showInfo,
        showAction,
        showLoading,
        updateToast,
        clearToasts
    }
}

// Пример использования с промисами
export function useToastPromise() {
    const { showLoading, updateToast, removeToast } = useToast()
    
    const promise = async (
        promiseFn,
        {
            loading = 'Загрузка...',
            success = 'Успешно!',
            error = 'Произошла ошибка'
        } = {}
    ) => {
        const toastId = showLoading(loading)
        
        try {
            const result = await promiseFn()
            updateToast(toastId, {
                type: 'success',
                message: typeof success === 'function' ? success(result) : success,
                duration: 3000
            })
            return result
        } catch (err) {
            updateToast(toastId, {
                type: 'error',
                message: typeof error === 'function' ? error(err) : error,
                duration: 5000
            })
            throw err
        }
    }
    
    return { promise }
}