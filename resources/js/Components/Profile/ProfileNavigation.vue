<!-- resources/js/Components/Profile/ProfileNavigation.vue -->
<template>
    <nav class="flex-1">
        <div class="py-2">
            <!-- Мои объявления (основная секция) -->
            <div class="px-4">
                <Link 
                    href="/profile/items/inactive/all"
                    :class="menuItemClass(isAdsRoute)"
                >
                    <span>Мои объявления</span>
                    <span v-if="totalAds > 0" class="text-xs bg-gray-100 text-gray-600 px-2 py-0.5 rounded-full">
                        {{ totalAds }}
                    </span>
                </Link>
            </div>
            
            <!-- Остальные пункты меню -->
            <div class="px-4 mt-2 space-y-1">
                <Link 
                    v-for="item in menuItems" 
                    :key="item.href"
                    :href="item.href"
                    :class="menuItemClass(isCurrentRoute(item.href))"
                >
                    <span>{{ item.label }}</span>
                    <span v-if="item.count > 0" class="text-xs bg-gray-100 text-gray-600 px-2 py-0.5 rounded-full">
                        {{ item.count }}
                    </span>
                </Link>
            </div>
        </div>
    </nav>
</template>

<script setup>
import { computed } from 'vue'
import { Link, usePage } from '@inertiajs/vue3'

// Props
const props = defineProps({
    counts: {
        type: Object,
        default: () => ({})
    }
})

// Текущая страница
const page = usePage()

// Пункты меню
const menuItems = [
    { href: '/bookings', label: 'Заказы', count: 0 },
    { href: '/profile/reviews', label: 'Мои отзывы', count: 0 },
    { href: '/favorites', label: 'Избранное', count: props.counts?.favorites || 0 },
    { href: '/messages', label: 'Сообщения', count: 0 },
    { href: '/notifications', label: 'Уведомления', count: 0 },
    { href: '/wallet', label: 'Кошелёк', count: 0 },
    { href: '/profile/addresses', label: 'Адреса', count: 0 },
    { href: '/profile/edit', label: 'Управление профилем', count: 0 },
    { href: '/profile/security', label: 'Защита профиля', count: 0 },
    { href: '/settings', label: 'Настройки', count: 0 },
    { href: '/services', label: 'Платные услуги', count: 0 }
]

// Проверка текущего роута для объявлений
const isAdsRoute = computed(() => {
    const currentRoute = page.url
    return currentRoute.includes('/profile/items/')
})

// Общее количество объявлений
const totalAds = computed(() => {
    const counts = props.counts || {}
    return (counts.active || 0) + (counts.draft || 0) + (counts.waiting_payment || 0) + (counts.old || 0) + (counts.archive || 0)
})

// Проверка активного роута
const isCurrentRoute = (href) => {
    // Убираем ведущий слэш для сравнения
    const routePath = href.replace(/^\//, '')
    const currentPath = page.url.replace(/^\//, '')
    
    // Точное совпадение или начало пути
    return currentPath === routePath || currentPath.startsWith(routePath + '/')
}

// Класс для пунктов меню (копируем из Dashboard)
const menuItemClass = (isActive) => [
    'flex items-center justify-between px-3 py-2 text-sm rounded-md transition-colors',
    isActive 
        ? 'bg-blue-50 text-blue-700 font-medium' 
        : 'text-gray-700 hover:bg-gray-50'
]
</script>