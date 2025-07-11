<template>
    <div class="bg-white rounded-lg shadow-sm p-4">
        <h3 class="font-semibold text-gray-900 mb-4">Контактная информация</h3>
        
        <div class="space-y-3">
            <!-- Телефон -->
            <div v-if="master.show_phone && master.phone" class="flex items-center gap-3">
                <div class="flex-shrink-0 w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center">
                    <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                              d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                    </svg>
                </div>
                <div>
                    <div class="text-sm text-gray-600">Телефон</div>
                    <a :href="`tel:${master.phone}`" class="font-medium text-gray-900 hover:text-purple-600">
                        {{ formatPhone(master.phone) }}
                    </a>
                </div>
            </div>
            
            <!-- WhatsApp -->
            <div v-if="master.whatsapp" class="flex items-center gap-3">
                <div class="flex-shrink-0 w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center">
                    <svg class="w-5 h-5 text-green-600" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z"/>
                    </svg>
                </div>
                <div>
                    <div class="text-sm text-gray-600">WhatsApp</div>
                    <a :href="`https://wa.me/${master.whatsapp.replace(/\D/g, '')}`" 
                       target="_blank"
                       class="font-medium text-gray-900 hover:text-green-600">
                        Написать в WhatsApp
                    </a>
                </div>
            </div>
            
            <!-- Адрес -->
            <div v-if="master.address || master.work_zones?.length" class="flex items-start gap-3">
                <div class="flex-shrink-0 w-10 h-10 bg-purple-100 rounded-lg flex items-center justify-center">
                    <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                              d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                              d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                </div>
                <div>
                    <div class="text-sm text-gray-600">Локация</div>
                    <div class="font-medium text-gray-900">
                        <div v-if="master.address">{{ master.address }}</div>
                        <div v-if="master.work_zones?.length" class="text-sm mt-1">
                            Районы: {{ master.work_zones.map(z => z.name).join(', ') }}
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Режим работы -->
            <div v-if="master.schedules?.length" class="flex items-start gap-3">
                <div class="flex-shrink-0 w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center">
                    <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                              d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div>
                    <div class="text-sm text-gray-600">Режим работы</div>
                    <div class="font-medium text-gray-900">
                        {{ getWorkingHours() }}
                    </div>
                </div>
            </div>
            
            <!-- Мессенджеры -->
            <div v-if="master.telegram || master.viber" class="flex items-center gap-2 pt-2">
                <span class="text-sm text-gray-600">Также доступен в:</span>
                
                <a v-if="master.telegram" 
                   :href="`https://t.me/${master.telegram}`"
                   target="_blank"
                   class="inline-flex items-center gap-1 text-blue-600 hover:text-blue-700">
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M11.944 0A12 12 0 0 0 0 12a12 12 0 0 0 12 12 12 12 0 0 0 12-12A12 12 0 0 0 12 0a12 12 0 0 0-.056 0zm4.962 7.224c.1-.002.321.023.465.14a.506.506 0 0 1 .171.325c.016.093.036.306.02.472-.18 1.898-.962 6.502-1.36 8.627-.168.9-.499 1.201-.82 1.23-.696.065-1.225-.46-1.9-.902-1.056-.693-1.653-1.124-2.678-1.8-1.185-.78-.417-1.21.258-1.91.177-.184 3.247-2.977 3.307-3.23.007-.032.014-.15-.056-.212s-.174-.041-.249-.024c-.106.024-1.793 1.14-5.061 3.345-.48.33-.913.49-1.302.48-.428-.008-1.252-.241-1.865-.44-.752-.245-1.349-.374-1.297-.789.027-.216.325-.437.893-.663 3.498-1.524 5.83-2.529 6.998-3.014 3.332-1.386 4.025-1.627 4.476-1.635z"/>
                    </svg>
                    Telegram
                </a>
                
                <a v-if="master.viber" 
                   :href="`viber://chat?number=${master.viber}`"
                   class="inline-flex items-center gap-1 text-purple-600 hover:text-purple-700">
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M11.398.002C9.473.028 5.331.344 3.014 2.467 1.294 4.177.693 6.698.623 9.82c-.06 3.11-.13 8.95 5.5 10.541v2.42s-.038.97.602 1.17c.79.25 1.24-.499 1.99-1.299l1.4-1.58c3.85.32 6.8-.419 7.14-.529.78-.25 5.181-.811 5.901-6.652.74-6.031-.36-9.831-2.34-11.551l-.01-.002c-.6-.55-3-2.3-8.37-2.32 0 0-.396-.025-1.037-.016zm.27 1.951c.508-.008.82.008.82.008 4.3.03 6.45 1.44 6.94 1.9 1.43 1.25 2.5 4.82 1.88 9.65-.58 4.45-3.74 4.72-4.37 4.89-.26.08-2.67.68-5.83.46 0 0-2.31 2.79-3.03 3.52-.12.12-.26.17-.35.15-.13-.03-.17-.19-.16-.41l.02-3.82c-4.69-1.3-4.42-6.07-4.37-8.6.06-2.6.52-4.72 1.82-6.01 1.87-1.71 5.21-1.96 6.64-1.73zM7.27 4.78a.48.48 0 0 0-.23.058c-.14.08-.19.26-.11.4.45.79 1.07 1.58 1.73 2.21.68.64 1.48 1.21 2.35 1.68.15.08.34 0 .42-.15.08-.15 0-.34-.16-.42h-.01c-.8-.43-1.54-.95-2.17-1.55-.61-.57-1.19-1.3-1.61-2.04a.31.31 0 0 0-.21-.08zm4.55.48a.411.411 0 0 0-.418.413v.001c0 .23.19.42.42.42 2.13 0 3.85 1.72 3.86 3.85a.42.42 0 0 0 .84 0c0-2.59-2.11-4.7-4.7-4.7v.018zm-4.79.74a.477.477 0 0 0-.348.146l-.01.002-.01.01-.638.67c-.3.3-.25.85.17 1.14 0 0 .52.419.99.89.55.56 1.78 1.88 2.7 2.78.448.448 1.009.928 1.549 1.398.26.23.52.46.78.68.59.5 1.19.86 1.19.86.45.21.86.25 1.14-.07l.66-.65c.28-.28.06-.76-.37-.98l-1.56-.81c-.42-.22-.82-.02-.98.17l-.4.399c-.18.19-.48.16-.79-.05a8.471 8.471 0 0 1-.51-.35 19.04 19.04 0 0 1-1.319-1.119c-.399-.37-.72-.7-1.01-1.05a8.473 8.473 0 0 1-.33-.468c-.19-.32-.21-.62-.03-.8l.4-.399c.2-.17.41-.57.18-.97l-.82-1.57c-.14-.27-.41-.42-.68-.42zm5.5-.92h.01c1.65 0 3 1.35 3 3a.42.42 0 0 0 .84 0 3.86 3.86 0 0 0-3.85-3.85.426.426 0 0 0-.42.43c0 .22.18.41.41.42z"/>
                    </svg>
                    Viber
                </a>
            </div>
        </div>
    </div>
</template>

<script setup>
const props = defineProps({
    master: {
        type: Object,
        required: true
    }
})

// Methods
const formatPhone = (phone) => {
    // Форматируем телефон в читаемый вид
    const cleaned = phone.replace(/\D/g, '')
    const match = cleaned.match(/^(\d{1})(\d{3})(\d{3})(\d{2})(\d{2})$/)
    
    if (match) {
        return `+${match[1]} (${match[2]}) ${match[3]}-${match[4]}-${match[5]}`
    }
    
    return phone
}

const getWorkingHours = () => {
    if (!props.master.schedules?.length) return 'Не указан'
    
    // Группируем дни с одинаковым временем
    const schedule = props.master.schedules[0]
    const days = ['Пн', 'Вт', 'Ср', 'Чт', 'Пт', 'Сб', 'Вс']
    
    if (schedule.is_24h) return 'Круглосуточно'
    
    return `${days[schedule.day_of_week]}: ${schedule.start_time} - ${schedule.end_time}`
}
</script>