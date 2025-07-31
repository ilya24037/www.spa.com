<!-- Страница избранного (/favorites) -->
<template>
    <Head title="Избранное" />
    
    <!-- Обертка с правильными отступами как в Dashboard -->
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
                <ContentCard title="Избранные мастера">
                    <div v-if="favorites.length > 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        <MasterCard 
                            v-for="master in favorites"
                            :key="master.id"
                            :master="master"
                        />
                    </div>
                    
                    <div v-else class="text-center py-12">
                        <svg class="mx-auto h-12 w-12 text-gray-400 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
                        </svg>
                        <p class="text-gray-500 text-lg mb-4">У вас пока нет избранных мастеров</p>
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
import { Head } from '@inertiajs/vue3'
import ProfileSidebar from '@/Components/Layout/ProfileSidebar.vue'
import ContentCard from '@/Components/Layout/ContentCard.vue'
import MasterCard from '@/Components/Cards/MasterCard.vue'

// Props
defineProps({
    favorites: {
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

// Больше нет локального состояния - все в ProfileSidebar
</script>