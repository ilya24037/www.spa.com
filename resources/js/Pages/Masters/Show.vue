<template>
    <Head :title="master.name" />
    
    <div class="container mx-auto px-4 py-8">
        <div class="bg-white rounded-lg shadow-lg p-6">
            <div class="flex items-start gap-6">
                <img 
                    :src="master.photo" 
                    :alt="master.name"
                    class="w-32 h-32 rounded-lg object-cover"
                >
                <div class="flex-1">
                    <h1 class="text-3xl font-bold text-gray-900 mb-2">{{ master.name }}</h1>
                    <p class="text-xl text-gray-600 mb-4">{{ master.specialization }}</p>
                    <div class="flex items-center gap-4 text-sm text-gray-500 mb-4">
                        <span>{{ master.age }} лет</span>
                        <span>рост {{ master.height }} см</span>
                        <div class="flex items-center">
                            <svg class="w-4 h-4 text-yellow-400 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                            </svg>
                            <span>{{ master.rating }} ({{ master.reviewsCount }} отзывов)</span>
                        </div>
                    </div>
                    <div class="text-2xl font-bold text-blue-600 mb-4">
                        {{ formatPrice(master.pricePerHour) }}/час
                    </div>
                    <div class="flex gap-4">
                        <button class="px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                            Записаться
                        </button>
                        <button class="px-6 py-3 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors">
                            Позвонить
                        </button>
                    </div>
                </div>
            </div>
            
            <div class="mt-8">
                <h2 class="text-xl font-semibold mb-4">Описание</h2>
                <p class="text-gray-700">{{ master.description }}</p>
            </div>
        </div>
    </div>
</template>

<script setup>
import { Head } from '@inertiajs/vue3'

const props = defineProps({
    master: {
        type: Object,
        required: true
    }
})

const formatPrice = (price) => {
    return new Intl.NumberFormat('ru-RU').format(price) + ' ₽'
}
</script>