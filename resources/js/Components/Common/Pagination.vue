<template>
    <nav class="flex items-center justify-between">
        <!-- Мобильная версия -->
        <div class="flex-1 flex justify-between sm:hidden">
            <button
                @click="goToPrevious"
                :disabled="currentPage === 1"
                :class="[
                    'relative inline-flex items-center px-4 py-2 text-sm font-medium rounded-md',
                    currentPage === 1
                        ? 'bg-gray-100 text-gray-400 cursor-not-allowed'
                        : 'bg-white text-gray-700 hover:bg-gray-50 border border-gray-300'
                ]"
            >
                Назад
            </button>
            <button
                @click="goToNext"
                :disabled="currentPage === totalPages"
                :class="[
                    'ml-3 relative inline-flex items-center px-4 py-2 text-sm font-medium rounded-md',
                    currentPage === totalPages
                        ? 'bg-gray-100 text-gray-400 cursor-not-allowed'
                        : 'bg-white text-gray-700 hover:bg-gray-50 border border-gray-300'
                ]"
            >
                Вперёд
            </button>
        </div>

        <!-- Десктопная версия -->
        <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
            <div>
                <p class="text-sm text-gray-700">
                    Страница <span class="font-medium">{{ currentPage }}</span> из
                    <span class="font-medium">{{ totalPages }}</span>
                </p>
            </div>
            <div>
                <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                    <!-- Кнопка "Предыдущая" -->
                    <button
                        @click="goToPrevious"
                        :disabled="currentPage === 1"
                        :class="[
                            'relative inline-flex items-center px-2 py-2 rounded-l-md border bg-white text-sm font-medium',
                            currentPage === 1
                                ? 'text-gray-300 cursor-not-allowed'
                                : 'text-gray-500 hover:bg-gray-50 border-gray-300'
                        ]"
                    >
                        <span class="sr-only">Предыдущая</span>
                        <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" />
                        </svg>
                    </button>

                    <!-- Номера страниц -->
                    <template v-for="page in displayedPages" :key="page">
                        <button
                            v-if="page !== '...'"
                            @click="goToPage(page)"
                            :class="[
                                'relative inline-flex items-center px-4 py-2 border text-sm font-medium',
                                page === currentPage
                                    ? 'z-10 bg-blue-50 border-blue-500 text-blue-600'
                                    : 'bg-white border-gray-300 text-gray-500 hover:bg-gray-50'
                            ]"
                        >
                            {{ page }}
                        </button>
                        <span
                            v-else
                            class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700"
                        >
                            ...
                        </span>
                    </template>

                    <!-- Кнопка "Следующая" -->
                    <button
                        @click="goToNext"
                        :disabled="currentPage === totalPages"
                        :class="[
                            'relative inline-flex items-center px-2 py-2 rounded-r-md border bg-white text-sm font-medium',
                            currentPage === totalPages
                                ? 'text-gray-300 cursor-not-allowed'
                                : 'text-gray-500 hover:bg-gray-50 border-gray-300'
                        ]"
                    >
                        <span class="sr-only">Следующая</span>
                        <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                        </svg>
                    </button>
                </nav>
            </div>
        </div>
    </nav>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
    currentPage: {
        type: Number,
        required: true
    },
    totalPages: {
        type: Number,
        required: true
    },
    onEachSide: {
        type: Number,
        default: 2
    }
})

const emit = defineEmits(['page-changed'])

// Вычисляемые свойства
const displayedPages = computed(() => {
    const pages = []
    const onEachSide = props.onEachSide
    const currentPage = props.currentPage
    const totalPages = props.totalPages

    // Всегда показываем первую страницу
    pages.push(1)

    // Добавляем многоточие если нужно
    if (currentPage > onEachSide + 2) {
        pages.push('...')
    }

    // Добавляем страницы вокруг текущей
    for (let i = Math.max(2, currentPage - onEachSide); i <= Math.min(totalPages - 1, currentPage + onEachSide); i++) {
        pages.push(i)
    }

    // Добавляем многоточие если нужно
    if (currentPage < totalPages - onEachSide - 1) {
        pages.push('...')
    }

    // Всегда показываем последнюю страницу
    if (totalPages > 1) {
        pages.push(totalPages)
    }

    return pages
})

// Методы
const goToPage = (page) => {
    if (page !== props.currentPage && page !== '...') {
        emit('page-changed', page)
    }
}

const goToPrevious = () => {
    if (props.currentPage > 1) {
        emit('page-changed', props.currentPage - 1)
    }
}

const goToNext = () => {
    if (props.currentPage < props.totalPages) {
        emit('page-changed', props.currentPage + 1)
    }
}
</script>