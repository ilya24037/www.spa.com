<template>
    <Head title="Платные услуги" />
    
    <div class="py-6 lg:py-8">
        <div class="flex gap-6">
            <!-- Боковая панель -->
            <SidebarWrapper 
                v-model="showSidebar"
                content-class="p-0"
                :show-desktop-header="false"
                :always-visible-desktop="true"
            >
                <!-- Профиль пользователя -->
                <div class="p-6 border-b">
                    <div class="flex items-center space-x-3">
                        <div 
                            class="w-12 h-12 rounded-full flex items-center justify-center text-white font-medium text-lg"
                            :style="{ backgroundColor: avatarColor }"
                        >
                            {{ userInitial }}
                        </div>
                        <div>
                            <div class="font-medium text-gray-900">{{ userName }}</div>
                            <div class="text-sm text-gray-500">★ 4.2 • 5 отзывов</div>
                        </div>
                    </div>
                </div>
                
                <!-- Меню -->
                <nav class="flex-1">
                    <div class="py-2">
                        <div class="px-4">
                            <Link href="/profile/items/inactive/all" class="flex items-center justify-between px-3 py-2 text-sm rounded-md transition-colors text-gray-700 hover:bg-gray-50">
                                <span>Мои объявления</span>
                            </Link>
                        </div>
                        
                        <div class="px-4 mt-2 space-y-1">
                            <Link href="/bookings" class="flex items-center px-3 py-2 text-sm text-gray-700 hover:bg-gray-50 rounded-md transition-colors">Заказы</Link>
                            <Link href="/profile/reviews" class="flex items-center px-3 py-2 text-sm text-gray-700 hover:bg-gray-50 rounded-md transition-colors">Мои отзывы</Link>
                            <Link href="/favorites" class="flex items-center px-3 py-2 text-sm text-gray-700 hover:bg-gray-50 rounded-md transition-colors">Избранное</Link>
                            <Link href="/messages" class="flex items-center px-3 py-2 text-sm text-gray-700 hover:bg-gray-50 rounded-md transition-colors">Сообщения</Link>
                            <Link href="/notifications" class="flex items-center px-3 py-2 text-sm text-gray-700 hover:bg-gray-50 rounded-md transition-colors">Уведомления</Link>
                            <Link href="/wallet" class="flex items-center px-3 py-2 text-sm text-gray-700 hover:bg-gray-50 rounded-md transition-colors">Кошелёк</Link>
                            <Link href="/profile/addresses" class="flex items-center px-3 py-2 text-sm text-gray-700 hover:bg-gray-50 rounded-md transition-colors">Адреса</Link>
                            <Link href="/profile/edit" class="flex items-center px-3 py-2 text-sm text-gray-700 hover:bg-gray-50 rounded-md transition-colors">Управление профилем</Link>
                            <Link href="/profile/security" class="flex items-center px-3 py-2 text-sm text-gray-700 hover:bg-gray-50 rounded-md transition-colors">Защита профиля</Link>
                            <Link href="/settings" class="flex items-center px-3 py-2 text-sm text-gray-700 hover:bg-gray-50 rounded-md transition-colors">Настройки</Link>
                            <Link href="/services" class="flex items-center px-3 py-2 text-sm bg-blue-50 text-blue-700 font-medium rounded-md transition-colors">Платные услуги</Link>
                        </div>
                    </div>
                </nav>
            </SidebarWrapper>
            
            <!-- Контент -->
            <section class="flex-1">
                <ContentCard>
                    <div class="text-center py-16">
                        <div class="w-20 h-20 mx-auto mb-6 bg-gray-100 rounded-full flex items-center justify-center">
                            <svg class="w-10 h-10 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <h3 class="text-xl font-medium text-gray-900 mb-3">Платные услуги</h3>
                        <p class="text-gray-600 mb-8 leading-relaxed">
                            Здесь будут доступны дополнительные платные функции для продвижения ваших объявлений.<br>
                            Функционал находится в разработке.
                        </p>
                    </div>
                </ContentCard>
            </section>
        </div>
    </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { Head, Link, usePage } from '@inertiajs/vue3'
import SidebarWrapper from '@/Components/Layout/SidebarWrapper.vue'
import ContentCard from '@/Components/Layout/ContentCard.vue'

const showSidebar = ref(false)
const page = usePage()
const user = computed(() => page.props.auth?.user || {})

const userName = computed(() => user.value.name || 'Пользователь')
const userInitial = computed(() => userName.value.charAt(0).toUpperCase())
const avatarColor = computed(() => {
    const colors = ['#ef4444', '#f97316', '#eab308', '#22c55e', '#06b6d4', '#3b82f6', '#8b5cf6', '#ec4899']
    const index = userName.value.charCodeAt(0) % colors.length
    return colors[index]
})
</script> 