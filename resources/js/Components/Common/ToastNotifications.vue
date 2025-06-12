<!-- resources/js/Components/Common/ToastNotifications.vue -->
<template>
    <Teleport to="body">
        <div class="fixed top-4 right-4 z-[100] space-y-2">
            <TransitionGroup
                name="toast"
                tag="div"
                enter-active-class="transition duration-300 ease-out"
                enter-from-class="translate-x-full opacity-0"
                enter-to-class="translate-x-0 opacity-100"
                leave-active-class="transition duration-200 ease-in"
                leave-from-class="translate-x-0 opacity-100"
                leave-to-class="translate-x-full opacity-0"
            >
                <div
                    v-for="toast in toasts"
                    :key="toast.id"
                    :class="[
                        'min-w-[300px] max-w-[400px] p-4 rounded-lg shadow-lg flex items-start gap-3',
                        getToastClasses(toast.type)
                    ]"
                >
                    <!-- Иконка -->
                    <div class="flex-shrink-0">
                        <component :is="getToastIcon(toast.type)" class="w-5 h-5" />
                    </div>
                    
                    <!-- Текст -->
                    <div class="flex-1">
                        <p class="text-sm font-medium">{{ toast.message }}</p>
                    </div>
                    
                    <!-- Кнопка закрытия -->
                    <button
                        @click="removeToast(toast.id)"
                        class="flex-shrink-0 ml-2 text-current opacity-70 hover:opacity-100"
                    >
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </TransitionGroup>
        </div>
    </Teleport>
</template>

<script setup>
import { ref, defineExpose } from 'vue'

const toasts = ref([])
let toastId = 0

// Типы уведомлений
const toastTypes = {
    success: {
        classes: 'bg-green-100 text-green-800 border border-green-200',
        icon: `<svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
        </svg>`
    },
    error: {
        classes: 'bg-red-100 text-red-800 border border-red-200',
        icon: `<svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
        </svg>`
    },
    warning: {
        classes: 'bg-yellow-100 text-yellow-800 border border-yellow-200',
        icon: `<svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
        </svg>`
    },
    info: {
        classes: 'bg-blue-100 text-blue-800 border border-blue-200',
        icon: `<svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
        </svg>`
    }
}

// Методы
const addToast = (type, message, duration = 5000) => {
    const id = toastId++
    toasts.value.push({ id, type, message })
    
    // Автоматическое удаление
    setTimeout(() => {
        removeToast(id)
    }, duration)
}

const removeToast = (id) => {
    const index = toasts.value.findIndex(t => t.id === id)
    if (index > -1) {
        toasts.value.splice(index, 1)
    }
}

const getToastClasses = (type) => {
    return toastTypes[type]?.classes || toastTypes.info.classes
}

const getToastIcon = (type) => {
    return {
        template: toastTypes[type]?.icon || toastTypes.info.icon
    }
}

// Экспортируем метод для родительского компонента
defineExpose({ addToast })
</script>