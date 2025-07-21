<template>
    <div class="form-section">
        <h3 class="form-section-title">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path>
            </svg>
            Контакты
        </h3>
        
        <div class="form-row">
            <div class="w-full">
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Адрес *
                </label>
                <input 
                    v-model="form.address"
                    type="text"
                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
                    :class="{ 'border-red-500 focus:ring-red-500 focus:border-red-500': errors.address }"
                    placeholder="Введите адрес"
                    required
                >
                <div v-if="errors.address" class="mt-2 text-sm text-red-600 flex items-center">
                    <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                    </svg>
                    {{ errors.address }}
                </div>
            </div>
        </div>
        
        <div class="form-row">
            <div class="w-full">
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Район выезда *
                </label>
                <input 
                    v-model="form.travel_area"
                    type="text"
                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
                    :class="{ 'border-red-500 focus:ring-red-500 focus:border-red-500': errors.travel_area }"
                    placeholder="Укажите район"
                    required
                >
                <div v-if="errors.travel_area" class="mt-2 text-sm text-red-600 flex items-center">
                    <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                    </svg>
                    {{ errors.travel_area }}
                </div>
            </div>
        </div>
        
        <div class="form-row">
            <div class="w-full">
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Телефон *
                </label>
                <input 
                    v-model="form.phone"
                    type="tel"
                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
                    :class="{ 'border-red-500 focus:ring-red-500 focus:border-red-500': errors.phone }"
                    placeholder="+7 (___) ___-__-__"
                    required
                >
                <div v-if="errors.phone" class="mt-2 text-sm text-red-600 flex items-center">
                    <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                    </svg>
                    {{ errors.phone }}
                </div>
            </div>
        </div>
        
        <div class="form-row">
            <div class="w-full">
                <label class="block text-sm font-medium text-gray-700 mb-3">
                    Способ связи *
                </label>
                <div class="space-y-3">
                    <label class="flex items-center p-4 border border-gray-200 rounded-lg cursor-pointer hover:bg-gray-50 transition-colors">
                        <input 
                            v-model="form.contact_method"
                            type="radio"
                            value="any"
                            class="w-5 h-5 text-blue-600 border-gray-300 focus:ring-blue-500 focus:ring-2"
                        >
                        <div class="ml-3">
                            <div class="text-sm font-medium text-gray-900">Любой способ</div>
                            <div class="text-xs text-gray-500">Звонки и сообщения</div>
                        </div>
                    </label>
                    <label class="flex items-center p-4 border border-gray-200 rounded-lg cursor-pointer hover:bg-gray-50 transition-colors">
                        <input 
                            v-model="form.contact_method"
                            type="radio"
                            value="calls"
                            class="w-5 h-5 text-blue-600 border-gray-300 focus:ring-blue-500 focus:ring-2"
                        >
                        <div class="ml-3">
                            <div class="text-sm font-medium text-gray-900">Только звонки</div>
                            <div class="text-xs text-gray-500">Предпочитаю общение по телефону</div>
                        </div>
                    </label>
                    <label class="flex items-center p-4 border border-gray-200 rounded-lg cursor-pointer hover:bg-gray-50 transition-colors">
                        <input 
                            v-model="form.contact_method"
                            type="radio"
                            value="messages"
                            class="w-5 h-5 text-blue-600 border-gray-300 focus:ring-blue-500 focus:ring-2"
                        >
                        <div class="ml-3">
                            <div class="text-sm font-medium text-gray-900">Только сообщения</div>
                            <div class="text-xs text-gray-500">Предпочитаю переписку</div>
                        </div>
                    </label>
                </div>
                
                <div v-if="errors.contact_method" class="mt-2 text-sm text-red-600 flex items-center">
                    <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                    </svg>
                    {{ errors.contact_method }}
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
const props = defineProps({
    form: {
        type: Object,
        required: true
    },
    errors: {
        type: Object,
        default: () => ({})
    }
})
</script> 