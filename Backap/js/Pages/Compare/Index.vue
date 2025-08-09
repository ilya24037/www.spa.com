<template>
    <Head title="Сравнение" />
    
    <div class="container mx-auto px-4 py-8">
        <h1 class="text-3xl font-bold text-gray-900 mb-8">Сравнение мастеров</h1>
        
        <div v-if="compareList.length > 0">
            <!-- Таблица сравнения -->
            <div class="bg-white rounded-lg shadow overflow-hidden">
                <table class="w-full">
                    <thead class="bg-gray-50">
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
                    <tbody>
                        <tr class="border-t">
                            <td class="px-6 py-4 font-medium">Цена за час</td>
                            <td v-for="master in compareList" :key="master.id" class="px-6 py-4">
                                {{ formatPrice(master.pricePerHour) }}
                            </td>
                        </tr>
                        <tr class="border-t">
                            <td class="px-6 py-4 font-medium">Рейтинг</td>
                            <td v-for="master in compareList" :key="master.id" class="px-6 py-4">
                                {{ master.rating }} 
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
        
        <div v-else class="text-center py-12">
            <p class="text-gray-500 text-lg">Добавьте мастеров для сравнения</p>
            <Link href="/" class="inline-block mt-4 px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                Найти мастеров
            </Link>
        </div>
    </div>
</template>

<script setup>
import { Head, Link } from '@inertiajs/vue3'

defineProps({
    compareList: {
        type: Array,
        default: () => []
    }
})

const formatPrice = (price) => {
    return new Intl.NumberFormat('ru-RU').format(price) + ' ₽'
}
</script>