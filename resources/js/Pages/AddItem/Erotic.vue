<!-- Форма создания анкеты эротического массажа (/additem/erotic) -->
<template>
    <Head title="Создать анкету эротического массажа" />
    
    <!-- Центрированный контент как на Avito -->
    <div class="avito-bg">
        <div class="avito-container">
            
            <!-- Хлебные крошки -->
            <nav class="flex mb-6 pt-6" aria-label="Breadcrumb">
                <ol class="inline-flex items-center space-x-1 md:space-x-3">
                    <li v-for="(breadcrumb, index) in breadcrumbs" :key="index" class="inline-flex items-center">
                        <Link 
                            v-if="breadcrumb.url" 
                            :href="breadcrumb.url"
                            class="text-gray-500 hover:text-gray-700 transition-colors"
                        >
                            {{ breadcrumb.name }}
                        </Link>
                        <span v-else class="text-gray-900 font-medium">{{ breadcrumb.name }}</span>
                        
                        <svg 
                            v-if="index < breadcrumbs.length - 1"
                            class="w-4 h-4 text-gray-400 mx-2" 
                            fill="currentColor" 
                            viewBox="0 0 20 20"
                        >
                            <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"></path>
                        </svg>
                    </li>
                </ol>
            </nav>

            <!-- Заголовок -->
            <div class="flex items-center justify-between mb-6">
                <div class="flex items-center space-x-4">
                    <Link 
                        href="/additem"
                        class="flex items-center text-gray-600 hover:text-gray-900 transition-colors"
                    >
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                        </svg>
                        Назад к категориям
                    </Link>
                    <div class="h-6 w-px bg-gray-300"></div>
                    <h1 class="text-2xl font-bold text-gray-900">
                        Эротический массаж
                    </h1>
                </div>
            </div>

            <!-- Форма объявления -->
            <AdForm 
                category="erotic"
                :categories="categories"
                @success="handleFormSuccess"
            />
        </div>
    </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { Head, Link, usePage } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import AdForm from '@/Components/Form/AdForm.vue'

defineOptions({
    layout: AppLayout
})

// Получаем данные от контроллера
const props = defineProps({
    categories: {
        type: Array,
        required: true
    },
    cities: {
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

const handleFormSuccess = () => {
    // Перенаправление после успешного создания объявления
}
</script> 