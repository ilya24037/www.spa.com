<template>
    <div class="master-details">
        <div class="p-6">
            <!-- Основная информация -->
            <div class="mb-6">
                <h1 class="text-2xl font-bold text-gray-900 mb-2">
                    {{ master.name }}
                </h1>
                
                <div class="flex flex-wrap items-center gap-4 text-sm">
                    <!-- Специализация -->
                    <div class="flex items-center gap-2">
                        <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                  d="M21 13.255A8.002 8.002 0 0016.745 9H15a3 3 0 110-6h1.745A8.002 8.002 0 0021 7.255v6z" />
                        </svg>
                        <span class="text-gray-600">{{ master.specialization || 'Мастер массажа' }}</span>
                    </div>
                    
                    <!-- Рейтинг -->
                    <div class="flex items-center gap-2">
                        <StarRating :rating="master.rating || 5" :size="5" />
                        <span class="font-semibold">{{ formatRating(master.rating || 5) }}</span>
                        <span class="text-gray-500">({{ master.reviews_count || 0 }} {{ pluralize(master.reviews_count || 0, ['отзыв', 'отзыва', 'отзывов']) }})</span>
                    </div>
                    
                    <!-- Верификация -->
                    <div v-if="master.is_verified" class="flex items-center gap-1 text-green-600">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                        </svg>
                        <span class="text-sm font-medium">Подтвержден</span>
                    </div>
                </div>
            </div>

            <!-- Описание -->
            <div v-if="master.description" class="mb-8">
                <h3 class="font-semibold text-gray-900 mb-3 flex items-center gap-2">
                    <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                              d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    О мастере
                </h3>
                <div class="text-gray-700 leading-relaxed whitespace-pre-line"
                     :class="{ 'line-clamp-4': !showFullDescription }">
                    {{ master.description }}
                </div>
                <button v-if="master.description && master.description.length > 200"
                        @click="showFullDescription = !showFullDescription"
                        class="mt-2 text-purple-600 hover:text-purple-700 text-sm font-medium">
                    {{ showFullDescription ? 'Скрыть' : 'Показать полностью' }}
                </button>
            </div>

            <!-- Информационные блоки -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
                <InfoBlock 
                    v-for="block in infoBlocks" 
                    :key="block.label"
                    :icon="block.icon"
                    :value="block.value"
                    :label="block.label"
                    :color="block.color"
                />
            </div>

            <!-- Преимущества -->
            <div v-if="advantages.length" class="mb-8">
                <h3 class="font-semibold text-gray-900 mb-4">Преимущества</h3>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div v-for="advantage in advantages" 
                         :key="advantage.id"
                         class="flex items-start gap-3">
                        <div class="flex-shrink-0 w-10 h-10 bg-purple-100 rounded-lg flex items-center justify-center">
                            <svg class="w-5 h-5 text-purple-600" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div>
                            <div class="font-medium text-gray-900">{{ advantage.title }}</div>
                            <div v-if="advantage.description" class="text-sm text-gray-600 mt-0.5">
                                {{ advantage.description }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Образование и сертификаты -->
            <div v-if="master.education || master.certificates?.length" class="border-t pt-6">
                <h3 class="font-semibold text-gray-900 mb-4 flex items-center gap-2">
                    <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                              d="M12 14l9-5-9-5-9 5 9 5z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                              d="M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z" />
                    </svg>
                    Образование и квалификация
                </h3>
                
                <!-- Образование -->
                <div v-if="master.education" class="mb-4">
                    <div class="text-gray-700">{{ master.education }}</div>
                </div>
                
                <!-- Сертификаты -->
                <div v-if="master.certificates?.length" class="flex flex-wrap gap-2">
                    <button v-for="cert in master.certificates"
                            :key="cert.id"
                            @click="showCertificate(cert)"
                            class="inline-flex items-center gap-2 px-3 py-2 bg-gray-100 hover:bg-gray-200 rounded-lg text-sm transition-colors group">
                        <svg class="w-4 h-4 text-gray-500 group-hover:text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                  d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        {{ cert.name }}
                        <span v-if="cert.year" class="text-xs text-gray-500">({{ cert.year }})</span>
                    </button>
                </div>
            </div>
            
            <!-- Языки -->
            <div v-if="master.languages?.length" class="border-t pt-6">
                <h3 class="font-semibold text-gray-900 mb-3 flex items-center gap-2">
                    <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                              d="M3 5h12M9 3v2m1.048 9.5A18.022 18.022 0 016.412 9m6.088 9h7M11 21l5-10 5 10M12.751 5C11.783 10.77 8.07 15.61 3 18.129" />
                    </svg>
                    Языки общения
                </h3>
                <div class="flex flex-wrap gap-2">
                    <span v-for="lang in master.languages"
                          :key="lang"
                          class="px-3 py-1 bg-gray-100 text-gray-700 rounded-full text-sm">
                        {{ lang }}
                    </span>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { pluralize, formatRating } from '@/utils/helpers'
import StarRating from '@/Components/Common/StarRating.vue'
import InfoBlock from './InfoBlock.vue'

const props = defineProps({
    master: {
        type: Object,
        required: true
    },
    stats: {
        type: Object,
        default: () => ({})
    }
})

const emit = defineEmits(['show-certificate'])

// State
const showFullDescription = ref(false)

// Computed
const infoBlocks = computed(() => [
    {
        icon: 'experience',
        value: props.stats.experience || props.master.experience_years || 0,
        label: pluralize(props.stats.experience || props.master.experience_years || 0, ['год', 'года', 'лет']) + ' опыта',
        color: 'purple'
    },
    {
        icon: 'services',
        value: props.stats.services || props.master.services_count || 0,
        label: pluralize(props.stats.services || props.master.services_count || 0, ['услуга', 'услуги', 'услуг']),
        color: 'blue'
    },
    {
        icon: 'clients',
        value: props.stats.clients || props.master.clients_count || '100+',
        label: 'довольных клиентов',
        color: 'green'
    },
    {
        icon: 'certificates',
        value: props.stats.certificates || props.master.certificates_count || 0,
        label: pluralize(props.stats.certificates || props.master.certificates_count || 0, ['сертификат', 'сертификата', 'сертификатов']),
        color: 'orange'
    }
])

const advantages = computed(() => {
    const defaultAdvantages = []
    
    // Премиум статус
    if (props.master.is_premium) {
        defaultAdvantages.push({
            id: 'premium',
            title: 'Премиум мастер',
            description: 'Проверенный специалист с высоким рейтингом'
        })
    }
    
    // Быстрый ответ
    if (props.master.response_time && props.master.response_time < 30) {
        defaultAdvantages.push({
            id: 'response',
            title: 'Быстрый ответ',
            description: `Обычно отвечает в течение ${props.master.response_time} минут`
        })
    }
    
    // Выезд на дом
    if (props.master.home_service) {
        defaultAdvantages.push({
            id: 'home',
            title: 'Выезд на дом',
            description: 'Проведение сеансов у вас дома'
        })
    }
    
    // Гибкий график
    if (props.master.flexible_schedule) {
        defaultAdvantages.push({
            id: 'schedule',
            title: 'Гибкий график',
            description: 'Работает в удобное для вас время'
        })
    }
    
    // Собственный массажный стол
    if (props.master.has_massage_table) {
        defaultAdvantages.push({
            id: 'table',
            title: 'Свой массажный стол',
            description: 'Профессиональное оборудование для выезда'
        })
    }
    
    // Работа в выходные
    if (props.master.works_weekends) {
        defaultAdvantages.push({
            id: 'weekends',
            title: 'Работает в выходные',
            description: 'Доступен в субботу и воскресенье'
        })
    }
    
    // Скидки постоянным клиентам
    if (props.master.loyalty_discount) {
        defaultAdvantages.push({
            id: 'loyalty',
            title: 'Программа лояльности',
            description: `Скидка ${props.master.loyalty_discount}% постоянным клиентам`
        })
    }
    
    return props.master.advantages || defaultAdvantages
})

// Methods
const showCertificate = (cert) => {
    emit('show-certificate', cert)
}
</script>

<style scoped>
.line-clamp-4 {
    display: -webkit-box;
    -webkit-line-clamp: 4;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
</style>