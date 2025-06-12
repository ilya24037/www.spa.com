// resources/js/Components/Masters/MasterCard.vue
<template>
    <article 
        class="bg-white rounded-lg shadow-sm hover:shadow-md transition-all duration-200 overflow-hidden group cursor-pointer"
        @click="goToProfile"
    >
        <!-- Изображение -->
        <div class="relative aspect-[3/4] overflow-hidden">
            <img 
                :src="master.photo || '/images/default-avatar.jpg'"
                :alt="master.name"
                class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
                loading="lazy"
            >
            
            <!-- Бейдж "Работает сейчас" -->
            <div 
                v-if="master.isAvailableNow"
                class="absolute top-2 left-2 px-2 py-1 bg-green-500 text-white text-xs font-medium rounded-full flex items-center"
            >
                <span class="w-2 h-2 bg-white rounded-full mr-1 animate-pulse"></span>
                Онлайн
            </div>

            <!-- Кнопка избранного -->
            <button 
                @click.stop="toggleFavorite"
                class="absolute top-2 right-2 p-2 bg-white bg-opacity-90 rounded-full hover:bg-opacity-100 transition-all"
                :class="[isFavorite ? 'text-red-500' : 'text-gray-600 hover:text-red-500']"
            >
                <svg class="w-5 h-5" :fill="isFavorite ? 'currentColor' : 'none'" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                        d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
                </svg>
            </button>

            <!-- Количество фото -->
            <div 
                v-if="master.photosCount > 1"
                class="absolute bottom-2 left-2 px-2 py-1 bg-black bg-opacity-60 text-white text-xs rounded-full flex items-center"
            >
                <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M4 3a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V5a2 2 0 00-2-2H4zm12 12H4l4-8 3 6 2-4 3 6z" clip-rule="evenodd" />
                </svg>
                {{ master.photosCount }}
            </div>
        </div>

        <!-- Информация -->
        <div class="p-4">
            <!-- Цена -->
            <div class="text-xl font-bold text-gray-900 mb-2">
                {{ formatPrice(master.pricePerHour) }}
            </div>

            <!-- Имя и специализация -->
            <h3 class="font-medium text-gray-900 truncate">{{ master.name }}</h3>
            <p class="text-sm text-gray-600 truncate mb-2">{{ master.specialization }}</p>

            <!-- Характеристики -->
            <div class="text-xs text-gray-500 space-y-1">
                <div>{{ master.age }} лет • рост {{ master.height }} см</div>
                
                <!-- Рейтинг и отзывы -->
                <div class="flex items-center">
                    <svg class="w-4 h-4 text-yellow-400 mr-1" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                    </svg>
                    <span class="font-medium">{{ master.rating }}</span>
                    <span class="mx-1">•</span>
                    <span>{{ master.reviewsCount }} отзывов</span>
                </div>
            </div>

            <!-- Кнопка действия -->
            <button 
                @click.stop="callMaster"
                class="mt-3 w-full py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition-colors flex items-center justify-center"
            >
                <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                        d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                </svg>
                Позвонить
            </button>
        </div>
    </article>
</template>

<script setup>
import { ref, computed } from 'vue'
import { router } from '@inertiajs/vue3'
import { usePage } from '@inertiajs/vue3'

// Пропсы
const props = defineProps({
    master: {
        type: Object,
        required: true
    }
})

// Состояние
const page = usePage()
const isFavorite = ref(props.master.isFavorite || false)

// Методы
const formatPrice = (price) => {
    return new Intl.NumberFormat('ru-RU').format(price) + ' ₽'
}

const toggleFavorite = () => {
    isFavorite.value = !isFavorite.value
    
    // Отправка запроса на сервер
    router.post(route('favorites.toggle'), {
        master_id: props.master.id
    }, {
        preserveScroll: true,
        preserveState: true
    })
}

const callMaster = () => {
    // Логика для звонка мастеру
    window.location.href = `tel:${props.master.phone}`
}

const goToProfile = () => {
    router.visit(route('masters.show', props.master.id))
}
</script>