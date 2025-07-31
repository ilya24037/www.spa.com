<template>
    <Head title="Сравнение" />
    
    <!-- Обертка с правильными отступами как на других страницах -->
    <div class="py-6 lg:py-8">
        
        <!-- Основной контент с гэпом между блоками -->
        <div class="flex gap-6">
            
            <!-- Боковая панель -->
            <ProfileSidebar 
                :counts="counts"
                :user-stats="userStats"
            />
            
            <!-- Основной контент -->
            <main class="flex-1">
                <ContentCard title="Сравнение мастеров">
                    <div v-if="compareList.length > 0">
                        <!-- Таблица сравнения -->
                        <div class="bg-gray-50 rounded-lg overflow-hidden">
                            <table class="w-full">
                                <thead class="bg-gray-100">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Параметр</th>
                                        <th v-for="master in compareList" :key="master.id" class="px-6 py-3 text-left">
                                            <div class="flex items-center">
                                                <img :src="master.photo" :alt="master.name" class="w-12 h-12 rounded-full mr-3">
                                                <div>
                                                    <div class="font-medium">{{ master.name }}</div>
                                                    <div class="text-sm text-gray-500">{{ master.specialization }}</div>
                                                </div>
                                            </div>
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white">
                                    <tr class="border-t">
                                        <td class="px-6 py-4 font-medium">Цена за час</td>
                                        <td v-for="master in compareList" :key="master.id" class="px-6 py-4">
                                            {{ formatPrice(master.pricePerHour) }}
                                        </td>
                                    </tr>
                                    <tr class="border-t">
                                        <td class="px-6 py-4 font-medium">Рейтинг</td>
                                        <td v-for="master in compareList" :key="master.id" class="px-6 py-4">
                                            {{ master.rating }} ⭐
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    
                    <div v-else class="text-center py-12">
                        <svg class="mx-auto h-12 w-12 text-gray-400 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                        </svg>
                        <p class="text-gray-500 text-lg mb-4">Добавьте мастеров для сравнения</p>
                        <Link 
                            href="/" 
                            class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors"
                        >
                            Найти мастеров
                        </Link>
                    </div>
                </ContentCard>
            </main>
        </div>
    </div>
</template>

<script setup>
import { Head, Link } from '@inertiajs/vue3'
import ProfileSidebar from '@/Components/Layout/ProfileSidebar.vue'
import ContentCard from '@/Components/Layout/ContentCard.vue'

defineProps({
    compareList: {
        type: Array,
        default: () => []
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

const formatPrice = (price) => {
    return new Intl.NumberFormat('ru-RU').format(price) + ' ₽'
}
</script>