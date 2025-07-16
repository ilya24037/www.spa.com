<template>
    <div class="ad-form">
        <!-- Основная форма -->
        <form @submit.prevent="handleSubmit" novalidate>
            <!-- Скрытое поле с категорией -->
            <input type="hidden" name="category" :value="category">
            
            <!-- Специфичные секции для категорий -->
            <EroticSection 
                v-if="category === 'erotic'"
                :form="form" 
                :errors="errors" 
            />
            
            <StripSection 
                v-if="category === 'strip'"
                :form="form" 
                :errors="errors" 
            />
            
            <EscortSection 
                v-if="category === 'escort'"
                :form="form" 
                :errors="errors" 
            />
            
            <MassageSection 
                v-if="category === 'massage'"
                :form="form" 
                :errors="errors" 
            />
            
            <!-- Общие секции для всех категорий -->
            <ContactsSection 
                :form="form" 
                :errors="errors" 
            />
            
            <PhotosSection 
                :form="form" 
                :errors="errors" 
            />
            
            <VideosSection 
                :form="form" 
                :errors="errors" 
            />
            
            <DescriptionSection 
                :form="form" 
                :errors="errors" 
            />
            
            <DetailsSection 
                :form="form" 
                :errors="errors" 
            />
            
            <GeoSection 
                :form="form" 
                :errors="errors" 
            />
            
            <PriceSection 
                :form="form" 
                :errors="errors" 
            />
            
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

// Секции формы
import EroticSection from '@/Components/Form/Sections/EroticSection.vue'
import StripSection from '@/Components/Form/Sections/StripSection.vue'
import EscortSection from '@/Components/Form/Sections/EscortSection.vue'
import MassageSection from '@/Components/Form/Sections/MassageSection.vue'
import ContactsSection from '@/Components/Form/Sections/ContactsSection.vue'
import PhotosSection from '@/Components/Form/Sections/PhotosSection.vue'
import VideosSection from '@/Components/Form/Sections/VideosSection.vue'
import DescriptionSection from '@/Components/Form/Sections/DescriptionSection.vue'
import DetailsSection from '@/Components/Form/Sections/DetailsSection.vue'
import GeoSection from '@/Components/Form/Sections/GeoSection.vue'
import PriceSection from '@/Components/Form/Sections/PriceSection.vue'
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
        const response = await fetch('/additem/draft', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({
                ...form,
                category: props.category
            })
        })
        
        if (response.ok) {
            const result = await response.json()
            console.log('Черновик сохранен:', result)
            // Можно показать уведомление об успешном сохранении
        } else {
            throw new Error('Ошибка при сохранении черновика')
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