<template>
    <div class="universal-ad-form">
        <!-- Универсальная форма для всех категорий -->
        <form @submit.prevent="handleSubmit" novalidate>
            
            <!-- 1. Обо мне (физические параметры) -->
            <div class="form-group-section">
                <ParametersSection 
                    v-model:age="form.age"
                    v-model:height="form.height" 
                    v-model:weight="form.weight" 
                    v-model:breast-size="form.breast_size"
                    v-model:hairColor="form.hair_color" 
                    v-model:eyeColor="form.eye_color" 
                    v-model:nationality="form.nationality" 
                    :show-age="true"
                    :show-breast-size="true"
                    :show-hair-color="true"
                    :show-eye-color="true"
                    :show-nationality="true"
                    :errors="errors"
                />
            </div>
            
            <!-- 2. Подробности (title + specialty) -->
            <div class="form-group-section">
                <TitleSection 
                    v-model:title="form.title" 
                    :errors="errors"
                />
                <SpecialtySection 
                    v-model:specialty="form.specialty" 
                    :errors="errors"
                />
            </div>

            <!-- 2. Ваши клиенты -->
            <div class="form-group-section">
                <ClientsSection 
                    v-model:clients="form.clients" 
                    :errors="errors"
                />
            </div>

            <!-- 3. Где вы оказываете услуги -->
            <div class="form-group-section">
                <LocationSection 
                    v-model:serviceLocation="form.service_location" 
                    :errors="errors"
                />
            </div>

            <!-- 4. Формат работы -->
            <div class="form-group-section">
                <WorkFormatSection 
                    v-model:workFormat="form.work_format" 
                    :errors="errors"
                />
            </div>

            <!-- 5. Кто оказывает услуги -->
            <div class="form-group-section">
                <ServiceProviderSection 
                    v-model:serviceProvider="form.service_provider" 
                    :errors="errors"
                />
            </div>

            <!-- 6. Опыт работы -->
            <div class="form-group-section">
                <ExperienceSection 
                    v-model:experience="form.experience" 
                    :errors="errors"
                />
            </div>

            <!-- 7. Описание -->
            <div class="form-group-section">
                <DescriptionSection 
                    v-model:description="form.description" 
                    :errors="errors"
                />
            </div>

            <!-- 7.1. Услуги -->
            <div class="form-group-section">
                <ServicesModule 
                    v-model:services="form.services" 
                    v-model:servicesAdditionalInfo="form.services_additional_info" 
                    :allowedCategories="[]"
                    :errors="errors"
                />
            </div>

            <!-- 7.2. Особенности мастера -->
            <div class="form-group-section">
                <FeaturesSection 
                    v-model:features="form.features" 
                    v-model:additionalFeatures="form.additional_features" 
                    :errors="errors"
                />
            </div>

            <!-- 7.3. График работы -->
            <div class="form-group-section">
                <ScheduleSection 
                    v-model:schedule="form.schedule" 
                    v-model:scheduleNotes="form.schedule_notes" 
                    :errors="errors"
                />
            </div>

            <!-- 8. Стоимость основной услуги -->
            <div class="form-group-section">
                <h2 class="form-group-title">Стоимость основной услуги</h2>
                <div class="field-hint" style="margin-bottom: 20px; color: #8c8c8c; font-size: 16px;">
                    Заказчик увидит эту цену рядом с названием объявления.
                </div>
                <PriceSection 
                    v-model:price="form.price" 
                    v-model:priceUnit="form.price_unit" 
                    v-model:isStartingPrice="form.is_starting_price" 
                    :errors="errors"
                />
            </div>

            <!-- 10. Акции -->
            <div class="form-group-section">
                <PromoSection 
                    v-model:newClientDiscount="form.new_client_discount" 
                    v-model:gift="form.gift" 
                    :errors="errors"
                />
            </div>

            <!-- 11. Медиа (Фото и Видео) -->
            <div class="form-group-section">
                <MediaSection 
                    v-model:photos="form.photos" 
                    v-model:video="form.video" 
                    v-model:media-settings="form.media_settings" 
                    :errors="errors"
                />
            </div>

            <!-- 13. География -->
            <div class="form-group-section geography-section">
                <GeoSection 
                    v-model:geo="form.geo" 
                    :errors="errors"
                />
            </div>

            <!-- 12. Контакты -->
            <div class="form-group-section">
                <ContactsSection 
                    v-model:phone="form.phone" 
                    v-model:contactMethod="form.contact_method" 
                    v-model:whatsapp="form.whatsapp" 
                    v-model:telegram="form.telegram" 
                    :errors="errors"
                />
            </div>

            <!-- Кнопки действий -->
            <div class="form-actions">
                <!-- Левая кнопка - Сохранить объявление -->
                <button 
                    type="button" 
                    @click="handleSaveDraft"
                    :disabled="saving"
                >
                    {{ saving ? 'Сохранение...' : 'Сохранить объявление' }}
                </button>
                
                <!-- Правая кнопка - Разместить/Обновить объявление -->
                <button 
                    type="button" 
                    @click="handlePublish"
                    :disabled="saving"
                >
                    {{ saving ? (isEditMode ? 'Обновление...' : 'Публикация...') : (isEditMode ? 'Обновить объявление' : 'Разместить объявление') }}
                </button>
            </div>
        </form>
    </div>
</template>

<script setup>
// Используем модель AdForm для всей бизнес-логики
import { useAdFormModel } from '../model/adFormModel'

// Импорты секций из features (новая FSD архитектура)
import TitleSection from '@/src/features/AdSections/TitleSection/ui/TitleSection.vue'
import SpecialtySection from '@/src/features/AdSections/SpecialtySection/ui/SpecialtySection.vue'
import ClientsSection from '@/src/features/AdSections/ClientsSection/ui/ClientsSection.vue'
import LocationSection from '@/src/features/AdSections/LocationSection/ui/LocationSection.vue'
import WorkFormatSection from '@/src/features/AdSections/WorkFormatSection/ui/WorkFormatSection.vue'
import ServiceProviderSection from '@/src/features/AdSections/ServiceProviderSection/ui/ServiceProviderSection.vue'
import ExperienceSection from '@/src/features/AdSections/ExperienceSection/ui/ExperienceSection.vue'
import DescriptionSection from '@/src/features/AdSections/DescriptionSection/ui/DescriptionSection.vue'
import PriceSection from '@/src/features/AdSections/PriceSection/ui/PriceSection.vue'
import ParametersSection from '@/src/features/AdSections/ParametersSection/ui/ParametersSection.vue'
import PromoSection from '@/src/features/AdSections/PromoSection/ui/PromoSection.vue'
import MediaSection from '@/src/features/AdSections/MediaSection/ui/MediaSection.vue'
import GeoSection from '@/src/features/AdSections/GeoSection/ui/GeoSection.vue'
import ContactsSection from '@/src/features/AdSections/ContactsSection/ui/ContactsSection.vue'

// Модульные компоненты
import ServicesModule from '@/src/features/Services/index.vue'
import FeaturesSection from '@/src/features/AdSections/FeaturesSection/ui/FeaturesSection.vue'
import ScheduleSection from '@/src/features/AdSections/ScheduleSection/ui/ScheduleSection.vue'

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
    },
    initialData: {
        type: Object,
        default: () => ({})
    }
})

// Events
const emit = defineEmits(['success'])

// Используем композабл модели для всей логики
const {
    form,
    errors,
    saving,
    isEditMode,
    handleSubmit,
    handleSaveDraft,
    handlePublish
} = useAdFormModel(props, emit)
</script>

<style scoped>
/* Только стили для кнопок действий - остальные стили в глобальном CSS */
.form-actions {
    margin-top: 32px;
    display: flex;
    gap: 16px;
    padding: 24px 0;
    border-top: 1px solid #f0f0f0;
}

.form-actions button {
    flex: 1;
    padding: 16px 24px;
    border-radius: 8px;
    font-size: 16px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s ease;
    border: none;
}

.form-actions button:first-child {
    background: #f5f5f5;
    color: #1a1a1a;
}

.form-actions button:first-child:hover {
    background: #e6e6e6;
}

.form-actions button:last-child {
    background: #1890ff;
    color: white;
}

.form-actions button:last-child:hover {
    background: #1677ff;
}

.form-actions button:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}
</style>