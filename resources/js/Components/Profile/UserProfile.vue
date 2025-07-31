<!-- resources/js/Components/Profile/UserProfile.vue -->
<template>
    <div class="p-6 border-b">
        <div class="flex items-center space-x-3">
            <!-- Аватар пользователя -->
            <div 
                class="w-12 h-12 rounded-full flex items-center justify-center text-white font-medium text-lg"
                :style="{ backgroundColor: avatarColor }"
            >
                {{ userInitial }}
            </div>
            
            <!-- Информация о пользователе -->
            <div>
                <div class="font-medium text-gray-900">{{ userName }}</div>
                <div class="text-sm text-gray-500">
                    ★ {{ userStats?.rating || 4.2 }} • {{ userStats?.reviews_count || userStats?.reviewsCount || 5 }} отзывов
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue'
import { usePage } from '@inertiajs/vue3'

// Props
const props = defineProps({
    userStats: {
        type: Object,
        default: () => ({})
    }
})

// Получение данных пользователя
const page = usePage()
const user = computed(() => page.props.auth?.user || {})

// Вычисляемые свойства для пользователя
const userName = computed(() => user.value.name || 'Пользователь')
const userInitial = computed(() => userName.value.charAt(0).toUpperCase())

// Цвет аватара (используем ту же логику что в Dashboard и UserMenu)
const colors = ['#ef4444', '#f97316', '#eab308', '#22c55e', '#06b6d4', '#3b82f6', '#8b5cf6', '#ec4899']
const avatarColor = computed(() => {
    const index = userName.value.charCodeAt(0) % colors.length
    return colors[index]
})
</script>