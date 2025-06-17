<template>
    <Head title="Избранное" />
    
    <div class="container mx-auto px-4 py-8">
        <h1 class="text-3xl font-bold text-gray-900 mb-8">Избранное</h1>
        
        <div v-if="favorites.length > 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
            <MasterCard 
                v-for="master in favorites"
                :key="master.id"
                :master="master"
            />
        </div>
        
        <div v-else class="text-center py-12">
            <p class="text-gray-500 text-lg">У вас пока нет избранных мастеров</p>
            <Link href="/" class="inline-block mt-4 px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                Найти мастеров
            </Link>
        </div>
    </div>
</template>

<script setup>
import { Head, Link } from '@inertiajs/vue3'
import MasterCard from '@/Components/Cards/MasterCard.vue'

defineProps({
    favorites: {
        type: Array,
        default: () => []
    }
})
</script>