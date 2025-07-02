<template>
    <div class="border rounded-lg p-4 hover:shadow-md transition">
        <div class="flex gap-4">
            <!-- Изображение -->
            <div class="w-40 h-32 flex-shrink-0">
                <img 
                    v-if="profile.photos?.length"
                    :src="profile.photos[0].url"
                    :alt="profile.name"
                    class="w-full h-full object-cover rounded-lg"
                >
                <div v-else class="w-full h-full bg-gray-200 rounded-lg flex items-center justify-center">
                    <svg class="w-12 h-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                </div>
            </div>
            
            <!-- Информация -->
            <div class="flex-1">
                <div class="flex items-start justify-between">
                    <div>
                        <h3 class="font-semibold text-lg">
                            <Link 
                                :href="`/masters/${profile.slug}-${profile.id}`"
                                class="hover:text-blue-600"
                            >
                                {{ profile.name }}
                            </Link>
                        </h3>
                        
                        <!-- Цена -->
                        <div class="mt-1">
                            <span class="text-xl font-semibold">
                                {{ formatPrice(profile.price_from) }}
                            </span>
                            <span class="text-gray-500 text-sm ml-1">за час</span>
                        </div>
                        
                        <!-- Услуги -->
                        <div class="mt-2 text-sm text-gray-600">
                            {{ profile.services_list || 'Услуги не указаны' }}
                        </div>
                        
                        <!-- Адрес -->
                        <div class="mt-2 text-sm text-gray-500">
                            {{ profile.full_address }}
                        </div>
                    </div>
                    
                    <!-- Действия -->
                    <div class="relative">
                        <button 
                            @click="showMenu = !showMenu"
                            class="p-2 hover:bg-gray-100 rounded-lg"
                        >
                            <svg class="w-5 h-5 text-gray-500" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M10 6a2 2 0 110-4 2 2 0 010 4zM10 12a2 2 0 110-4 2 2 0 010 4zM10 18a2 2 0 110-4 2 2 0 010 4z" />
                            </svg>
                        </button>
                        
                        <!-- Выпадающее меню -->
                        <div 
                            v-if="showMenu"
                            v-click-outside="() => showMenu = false"
                            class="absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-lg border z-10"
                        >
                            <button
                                v-if="!isDraft && !isArchived"
                                @click="handleEdit"
                                class="w-full text-left px-4 py-2 hover:bg-gray-50 text-sm"
                            >
                                Редактировать
                            </button>
                            
                            <button
                                v-if="isDraft"
                                @click="handlePublish"
                                class="w-full text-left px-4 py-2 hover:bg-gray-50 text-sm text-green-600"
                            >
                                Опубликовать
                            </button>
                            
                            <button
                                v-if="isArchived"
                                @click="handleRestore"
                                class="w-full text-left px-4 py-2 hover:bg-gray-50 text-sm"
                            >
                                Восстановить
                            </button>
                            
                            <button
                                v-if="!isDraft && !isArchived"
                                @click="handleToggle"
                                class="w-full text-left px-4 py-2 hover:bg-gray-50 text-sm"
                            >
                                {{ profile.is_active ? 'Деактивировать' : 'Активировать' }}
                            </button>
                            
                            <div class="border-t"></div>
                            
                            <button
                                @click="handleDelete"
                                class="w-full text-left px-4 py-2 hover:bg-gray-50 text-sm text-red-600"
                            >
                                Удалить
                            </button>
                        </div>
                    </div>
                </div>
                
                <!-- Статистика и статус -->
                <div class="mt-4 flex items-center justify-between">
                    <div class="flex items-center gap-4 text-sm text-gray-500">
                        <span class="flex items-center gap-1">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                            </svg>
                            {{ profile.views_count }} просмотров
                        </span>
                        
                        <span class="flex items-center gap-1">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                            {{ profile.bookings_count || 0 }} бронирований
                        </span>
                    </div>
                    
                    <!-- Статус -->
                    <div>
                        <span 
                            v-if="profile.rejection_reason"
                            class="text-xs text-red-600 bg-red-50 px-2 py-1 rounded"
                        >
                            Отклонено: {{ profile.rejection_reason }}
                        </span>
                        
                        <span 
                            v-else-if="isDraft"
                            class="text-xs text-gray-600 bg-gray-100 px-2 py-1 rounded"
                        >
                            Черновик
                        </span>
                        
                        <span 
                            v-else-if="!profile.is_active"
                            class="text-xs text-gray-600 bg-gray-100 px-2 py-1 rounded"
                        >
                            Неактивна
                        </span>
                        
                        <span 
                            v-else
                            class="text-xs text-green-600 bg-green-50 px-2 py-1 rounded"
                        >
                            Активна
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref } from 'vue'
import { Link } from '@inertiajs/vue3'

const props = defineProps({
    profile: Object,
    isDraft: Boolean,
    isArchived: Boolean
})

const emit = defineEmits(['edit', 'delete', 'toggle', 'publish', 'restore'])

const showMenu = ref(false)

const formatPrice = (price) => {
    return new Intl.NumberFormat('ru-RU', {
        minimumFractionDigits: 0
    }).format(price)
}

const handleEdit = () => {
    showMenu.value = false
    emit('edit', props.profile)
}

const handleDelete = () => {
    showMenu.value = false
    emit('delete', props.profile)
}

const handleToggle = () => {
    showMenu.value = false
    emit('toggle', props.profile)
}

const handlePublish = () => {
    showMenu.value = false
    emit('publish', props.profile)
}

const handleRestore = () => {
    showMenu.value = false
    emit('restore', props.profile)
}
</script>