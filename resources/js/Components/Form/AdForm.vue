<template>
    <div class="ad-form">
        <!-- Основная форма -->
        <form @submit.prevent="handleSubmit" novalidate>
            <!-- Скрытое поле с категорией -->
            <input type="hidden" name="category" :value="category">
            
            <!-- Секции согласно порядку Avito -->
            
            <!-- 1. Название объявления -->
            <TitleSection 
                :form="form" 
                :errors="errors" 
            />
            
            <!-- 2. Специальность или сфера -->
            <SpecialtySection 
                :form="form" 
                :errors="errors" 
            />
            
            <!-- 3. Ваши клиенты -->
            <ClientsSection 
                :form="form" 
                :errors="errors" 
            />
            
            <!-- 4. Местоположение (где оказываете услуги) -->
            <LocationSection 
                :form="form" 
                :errors="errors" 
            />
            
            <!-- 5. Формат работы -->
            <WorkFormatSection 
                :form="form" 
                :errors="errors" 
            />
            
            <!-- 6. Опыт работы -->
            <ExperienceSection 
                :form="form" 
                :errors="errors" 
            />
            
            <!-- 7. Стоимость основной услуги -->
            <PriceSection 
                :form="form" 
                :errors="errors" 
            />
            
            <!-- 8. Акции -->
            <PromoSection 
                :form="form" 
                :errors="errors" 
            />
            
            <!-- 9. Фотографии и видео -->
            <PhotosSection 
                :form="form" 
                :errors="errors" 
            />
            
            <VideosSection 
                :form="form" 
                :errors="errors" 
            />
            
            <!-- 10. География (куда выезжаете) -->
            <GeoSection 
                :form="form" 
                :errors="errors" 
            />
            
            <!-- 11. Контакты -->
            <ContactsSection 
                :form="form" 
                :errors="errors" 
            />
            
            <!-- Описание (дополнительная информация) -->
            <DescriptionSection 
                :form="form" 
                :errors="errors" 
            />
            
            <!-- Расписание работы -->
            <ScheduleSection 
                :form="form" 
                :errors="errors" 
            />
            
            <!-- Кнопки управления -->
            <div class="flex justify-end items-center mt-8 pt-6 border-t space-x-4">
                <button 
                    type="button" 
                    @click="saveDraft"
                    class="px-6 py-2 text-blue-600 bg-blue-50 rounded-lg hover:bg-blue-100 transition-colors"
                    :disabled="saving"
                >
                    {{ saving ? 'Сохранение...' : 'Сохранить черновик' }}
                </button>
                
                <button 
                    type="submit" 
                    class="px-8 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors disabled:opacity-50"
                    :disabled="saving"
                >
                    {{ saving ? 'Публикация...' : 'Опубликовать' }}
                </button>
            </div>
        </form>
    </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useForm } from '@inertiajs/vue3'
import { useAdForm } from '@/Composables/useAdForm'

// Новые секции согласно Avito
import TitleSection from '@/Components/Form/Sections/TitleSection.vue'
import SpecialtySection from '@/Components/Form/Sections/SpecialtySection.vue'
import ClientsSection from '@/Components/Form/Sections/ClientsSection.vue'
import LocationSection from '@/Components/Form/Sections/LocationSection.vue'
import WorkFormatSection from '@/Components/Form/Sections/WorkFormatSection.vue'
import ExperienceSection from '@/Components/Form/Sections/ExperienceSection.vue'
import PriceSection from '@/Components/Form/Sections/PriceSection.vue'
import PromoSection from '@/Components/Form/Sections/PromoSection.vue'

// Существующие секции
import PhotosSection from '@/Components/Form/Sections/PhotosSection.vue'
import VideosSection from '@/Components/Form/Sections/VideosSection.vue'
import GeoSection from '@/Components/Form/Sections/GeoSection.vue'
import ContactsSection from '@/Components/Form/Sections/ContactsSection.vue'
import DescriptionSection from '@/Components/Form/Sections/DescriptionSection.vue'
import ScheduleSection from '@/Components/Form/Sections/ScheduleSection.vue'

// Props
const props = defineProps({
    category: {
        type: String,
        required: true
    },
    categories: {
        type: Array,
        required: true
    },
    adId: {
        type: [String, Number],
        default: null
    }
})

// Events
const emit = defineEmits(['success'])

// Используем композабл для работы с формой
const { 
    form, 
    errors, 
    handleSubmit: submitForm,
    loadDraft
} = useAdForm({}, { 
    isEditMode: !!props.adId,
    adId: props.adId,
    autosaveEnabled: false
})

// Собственное состояние для сохранения
const saving = ref(false)

// Устанавливаем категорию в форму
form.category = props.category

// Если это режим редактирования, загружаем данные
onMounted(async () => {
    if (props.adId) {
        await loadDraft(props.adId)
    }
})

// Обработчики
const handleSubmit = async () => {
    try {
        const result = await submitForm()
        emit('success', result)
    } catch (error) {
        console.error('Ошибка при создании объявления:', error)
    }
}

// Простое сохранение черновика
const saveDraft = async () => {
    try {
        saving.value = true
        
        // Отправляем данные на сервер для сохранения черновика
        // Фильтруем только поддерживаемые поля
        const draftData = {
            category: props.category,
            title: form.title,
            specialty: form.specialty,
            clients: form.clients,
            service_location: form.service_location,
            work_format: form.work_format,
            service_provider: form.service_provider,
            experience: form.experience,
            description: form.description,
            price: form.price,
            price_unit: form.price_unit,
            is_starting_price: form.is_starting_price,
            discount: form.discount,
            gift: form.gift,
            address: form.address,
            travel_area: form.travel_area,
            phone: form.phone,
            contact_method: form.contact_method
        }
        
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
        if (!csrfToken) {
            throw new Error('CSRF токен не найден')
        }
        
        const response = await fetch('/ads/draft', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify(draftData)
        })
        
        if (response.ok) {
            const result = await response.json()
            console.log('Черновик сохранен:', result)
            // Можно показать уведомление об успешном сохранении
        } else {
            const errorText = await response.text()
            console.error('Ошибка сервера:', response.status, errorText)
            throw new Error(`Ошибка при сохранении черновика: ${response.status}`)
        }
    } catch (error) {
        console.error('Ошибка при сохранении черновика:', error)
    } finally {
        saving.value = false
    }
}

// Загружаем черновик при монтировании
onMounted(() => {
    if (props.adId) {
        loadDraft(props.adId)
    }
})
</script>

<style scoped>
.ad-form {
    max-width: 100%;
}

/* Стили для секций формы */
.ad-form :deep(.form-section) {
    margin-bottom: 2rem;
    padding: 1.5rem;
    background: #f9fafb;
    border-radius: 0.75rem;
    border: 1px solid #e5e7eb;
}

.ad-form :deep(.form-section-title) {
    font-size: 1.125rem;
    font-weight: 600;
    color: #1f2937;
    margin-bottom: 1rem;
    display: flex;
    align-items: center;
}

.ad-form :deep(.form-section-title svg) {
    margin-right: 0.5rem;
    width: 1.25rem;
    height: 1.25rem;
}

.ad-form :deep(.form-row) {
    display: grid;
    grid-template-columns: 1fr;
    gap: 1rem;
    margin-bottom: 1rem;
}

.ad-form :deep(.form-row.two-columns) {
    grid-template-columns: 1fr 1fr;
}

.ad-form :deep(.form-row.three-columns) {
    grid-template-columns: 1fr 1fr 1fr;
}

@media (max-width: 768px) {
    .ad-form :deep(.form-row.two-columns),
    .ad-form :deep(.form-row.three-columns) {
        grid-template-columns: 1fr;
    }
}
</style> 