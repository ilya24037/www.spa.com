<!-- Страница создания объявления (/additem) -->
<template>
    <Head title="Разместить объявление" />
    
    <!-- Центрированный контент как на Avito -->
    <div class="avito-bg">
        <div class="avito-container">
            
            <!-- Хлебные крошки -->
            <nav class="flex mb-6" aria-label="Breadcrumb">
                <ol class="inline-flex items-center space-x-1 md:space-x-3">
                    <li class="inline-flex items-center">
                        <Link 
                            href="/"
                            class="text-gray-500 hover:text-gray-700 transition-colors"
                        >
                            Главная
                        </Link>
                        <svg 
                            class="w-4 h-4 text-gray-400 mx-2" 
                            fill="currentColor" 
                            viewBox="0 0 20 20"
                        >
                            <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"></path>
                        </svg>
                    </li>
                    <li>
                        <span class="text-gray-900 font-medium">
                            {{ selectedCategory ? getCategoryName(selectedCategory) : 'Эротический массаж' }}
                        </span>
                    </li>
                </ol>
            </nav>

            <!-- Основной контент -->
            <div v-if="!selectedCategory">
                <!-- Выбор категории -->
                <div class="form-group-section">
                    <h1 class="form-group-title">Выберите категорию</h1>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        <button 
                            v-for="category in categories"
                            :key="category.id"
                            @click="selectCategory(category.slug)"
                            class="p-6 border border-gray-200 rounded-lg hover:border-blue-500 hover:shadow-md transition-all text-left"
                        >
                            <div class="text-lg font-medium text-gray-900 mb-2">
                                {{ category.name }}
                            </div>
                            <div class="text-sm text-gray-500">
                                {{ category.description }}
                            </div>
                        </button>
                    </div>
                </div>
            </div>

            <div v-else>
                <!-- Заголовок с кнопкой "Назад" -->
                <div class="flex items-center justify-between mb-6">
                    <div class="flex items-center space-x-4">
                        <button 
                            @click="goBackToCategories"
                            type="button"
                            class="flex items-center text-gray-600 hover:text-gray-900 transition-colors"
                        >
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                            </svg>
                            Назад к категориям
                        </button>
                        <div class="h-6 w-px bg-gray-300"></div>
                        <h1 class="text-2xl font-bold text-gray-900">
                            {{ getCategoryName(selectedCategory) }}
                        </h1>
                    </div>
                </div>

                <!-- Форма объявления -->
                <AdForm 
                    :category="selectedCategory"
                    :categories="props.categories"
                    @success="handleFormSuccess"
                />
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { Head, Link, usePage } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import AdForm from '@/Components/Form/AdForm.vue'

// Получаем данные от контроллера
const props = defineProps({
  categories: {
    type: Array,
    required: true
  },
  breadcrumbs: {
    type: Array,
    required: true
  },
  counts: {
    type: Object,
    default: () => ({})
  },
  userStats: {
    type: Object,
    default: () => ({})
  }
})

// Внутреннее состояние для выбранной категории
const selectedCategory = ref('erotic') // По умолчанию эротический массаж

// Методы для работы с категориями
const selectCategory = (categoryId) => {
    selectedCategory.value = categoryId
}

const goBackToCategories = () => {
    selectedCategory.value = null
}

const getCategoryName = (categorySlug) => {
    const category = props.categories.find(cat => cat.slug === categorySlug)
    return category ? category.name : 'Неизвестная категория'
}

const handleFormSuccess = () => {
    // Перенаправление после успешного создания объявления
}
</script>

<script>
export default {
    layout: AppLayout
}
</script>