<template>
    <Head title="Создать анкету мастера" />
    
    <div class="container mx-auto px-4 py-8">
        <div class="max-w-2xl mx-auto">
            <h1 class="text-3xl font-bold text-gray-900 mb-8">Создать анкету мастера</h1>
            
            <form @submit.prevent="submit" class="space-y-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Имя
                    </label>
                    <input 
                        v-model="form.name"
                        type="text" 
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                        required
                    >
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Специализация
                    </label>
                    <input 
                        v-model="form.specialization"
                        type="text" 
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                        required
                    >
                </div>
                
                <div class="flex gap-4">
                    <div class="flex-1">
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Возраст
                        </label>
                        <input 
                            v-model="form.age"
                            type="number" 
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                            required
                        >
                    </div>
                    <div class="flex-1">
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Рост (см)
                        </label>
                        <input 
                            v-model="form.height"
                            type="number" 
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                            required
                        >
                    </div>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Цена за час (₽)
                    </label>
                    <input 
                        v-model="form.pricePerHour"
                        type="number" 
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                        required
                    >
                </div>
                
                <button 
                    type="submit"
                    class="w-full py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors"
                >
                    Создать анкету
                </button>
            </form>
        </div>
    </div>
</template>

<script setup>
import { Head } from '@inertiajs/vue3'
import { router } from '@inertiajs/vue3'
import { ref } from 'vue'

const form = ref({
    name: '',
    specialization: '',
    age: '',
    height: '',
    pricePerHour: ''
})

const submit = () => {
    router.post('/masters', form.value)
}
</script>