<template>
    <div class="universal-ad-form">
        <!-- Прогресс-бар -->
        <div class="form-progress">
            <div class="progress-bar">
                <div class="progress-fill" :style="{ width: formProgress + '%' }"></div>
            </div>
            <span class="progress-text">Заполнено {{ formProgress }}%</span>
        </div>

        <!-- Кнопки управления секциями -->
        <div class="form-controls">
            <button type="button" @click="expandAll" class="control-btn">
                📂 Развернуть всё
            </button>
            <button type="button" @click="collapseAll" class="control-btn">
                📁 Свернуть всё
            </button>
            <button type="button" @click="expandRequired" class="control-btn">
                ⭐ Только обязательные
            </button>
        </div>

        <!-- Универсальная форма для всех категорий -->
        <form @submit.prevent="handleSubmit" novalidate class="ad-form-sections">
            
            <!-- 1. ФИЗИЧЕСКИЕ ПАРАМЕТРЫ (обязательная, раскрыта) -->
            <CollapsibleSection
                title="Физические параметры"
                :is-open="sectionsState.parameters"
                :is-required="true"
                :is-filled="checkSectionFilled('parameters')"
                :filled-count="getFilledCount('parameters')"
                :total-count="7"
                @toggle="toggleSection('parameters')"
            >
                <ParametersSection 
                    v-model:age="form.age"
                    v-model:height="form.height" 
                    v-model:weight="form.weight" 
                    v-model:breastSize="form.breast_size"
                    v-model:hairColor="form.hair_color" 
                    v-model:eyeColor="form.eye_color" 
                    v-model:nationality="form.nationality" 
                    :showAge="true"
                    :showBreastSize="true"
                    :showHairColor="true"
                    :showEyeColor="true"
                    :showNationality="true"
                    :errors="errors"
                />
            </CollapsibleSection>

            <!-- 2. СТОИМОСТЬ УСЛУГ (обязательная, раскрыта) -->
            <CollapsibleSection
                title="Стоимость услуг"
                :is-open="sectionsState.price"
                :is-required="true"
                :is-filled="checkSectionFilled('price')"
                @toggle="toggleSection('price')"
            >
                <PricingSection 
                    v-model:prices="form.prices" 
                    :errors="errors"
                />
            </CollapsibleSection>

            <!-- 3. УСЛУГИ (обязательная, раскрыта) -->
            <CollapsibleSection
                title="Услуги"
                :is-open="sectionsState.services"
                :is-required="true"
                :is-filled="checkSectionFilled('services')"
                :filled-count="getFilledCount('services')"
                :total-count="3"
                @toggle="toggleSection('services')"
            >
                <ServicesModule 
                    v-model:services="form.services" 
                    v-model:servicesAdditionalInfo="form.services_additional_info" 
                    :allowedCategories="[]"
                    :errors="errors"
                />
            </CollapsibleSection>

            <!-- 4. ФОТО И ВИДЕО (обязательная, раскрыта) -->
            <CollapsibleSection
                title="Фото и видео"
                :is-open="sectionsState.media"
                :is-required="true"
                :is-filled="checkSectionFilled('media')"
                :filled-count="form.photos?.length || 0"
                :total-count="'мин. 3'"
                @toggle="toggleSection('media')"
            >
                <MediaSection 
                    v-model:photos="form.photos" 
                    v-model:video="form.video" 
                    v-model:media-settings="form.media_settings" 
                    :errors="errors"
                />
            </CollapsibleSection>

            <!-- 5. ГЕОГРАФИЯ (обязательная, раскрыта) -->
            <CollapsibleSection
                title="География"
                :is-open="sectionsState.geo"
                :is-required="true"
                :is-filled="checkSectionFilled('geo')"
                @toggle="toggleSection('geo')"
            >
                <GeoSection 
                    v-model:geo="form.geo" 
                    :errors="errors"
                />
            </CollapsibleSection>

            <!-- 6. КОНТАКТЫ (обязательная, раскрыта) -->
            <CollapsibleSection
                title="Контакты"
                :is-open="sectionsState.contacts"
                :is-required="true"
                :is-filled="checkSectionFilled('contacts')"
                @toggle="toggleSection('contacts')"
            >
                <ContactsSection 
                    v-model:phone="form.phone" 
                    v-model:contactMethod="form.contact_method" 
                    v-model:whatsapp="form.whatsapp" 
                    v-model:telegram="form.telegram" 
                    :errors="errors"
                />
            </CollapsibleSection>

            <!-- 7. ОСНОВНАЯ ИНФОРМАЦИЯ (свернута) -->
            <CollapsibleSection
                title="Основная информация"
                :is-open="sectionsState.basic"
                :is-filled="checkSectionFilled('basic')"
                @toggle="toggleSection('basic')"
            >
                <TitleSection 
                    v-model:title="form.title" 
                    :errors="errors"
                />
                <SpecialtySection 
                    v-model:specialty="form.specialty" 
                    :errors="errors"
                />
            </CollapsibleSection>

            <!-- 7. УСЛОВИЯ РАБОТЫ (свернута) -->
            <CollapsibleSection
                title="Условия работы"
                :is-open="sectionsState.conditions"
                :is-filled="checkSectionFilled('conditions')"
                @toggle="toggleSection('conditions')"
            >
                <ClientsSection 
                    v-model:clients="form.clients" 
                    :errors="errors"
                />
                <WorkFormatSection 
                    v-model:workFormat="form.work_format" 
                    :errors="errors"
                />
                <ServiceProviderSection 
                    v-model:serviceProvider="form.service_provider" 
                    :errors="errors"
                />
            </CollapsibleSection>

            <!-- 8. ГРАФИК РАБОТЫ (свернута) -->
            <CollapsibleSection
                title="График работы"
                :is-open="sectionsState.schedule"
                :is-filled="checkSectionFilled('schedule')"
                @toggle="toggleSection('schedule')"
            >
                <ScheduleSection 
                    v-model:schedule="form.schedule" 
                    v-model:scheduleNotes="form.schedule_notes" 
                    :errors="errors"
                />
            </CollapsibleSection>

            <!-- 9. ОПЫТ РАБОТЫ (свернута) -->
            <CollapsibleSection
                title="Опыт и квалификация"
                :is-open="sectionsState.experience"
                :is-filled="checkSectionFilled('experience')"
                @toggle="toggleSection('experience')"
            >
                <ExperienceSection 
                    v-model:experience="form.experience" 
                    :errors="errors"
                />
            </CollapsibleSection>

            <!-- 10. ОПИСАНИЕ (свернута) -->
            <CollapsibleSection
                title="Описание"
                :is-open="sectionsState.description"
                :is-filled="checkSectionFilled('description')"
                @toggle="toggleSection('description')"
            >
                <DescriptionSection 
                    v-model:description="form.description" 
                    :errors="errors"
                />
            </CollapsibleSection>

            <!-- 11. АКЦИИ (свернута) -->
            <CollapsibleSection
                title="Акции и скидки"
                :is-open="sectionsState.promo"
                :is-filled="checkSectionFilled('promo')"
                @toggle="toggleSection('promo')"
            >
                <PromoSection 
                    v-model:newClientDiscount="form.new_client_discount" 
                    v-model:gift="form.gift" 
                    :errors="errors"
                />
            </CollapsibleSection>

            <!-- 12. ДОПОЛНИТЕЛЬНЫЕ ОСОБЕННОСТИ (свернута) -->
            <CollapsibleSection
                title="Дополнительные особенности"
                :is-open="sectionsState.features"
                :is-filled="checkSectionFilled('features')"
                @toggle="toggleSection('features')"
            >
                <FeaturesSection 
                    v-model:features="form.features" 
                    v-model:additionalFeatures="form.additional_features" 
                    :errors="errors"
                />
            </CollapsibleSection>

            <!-- Кнопки действий -->
            <div class="form-actions">
                <!-- Левая кнопка - Сохранить объявление -->
                <SecondaryButton
                    @click="handleSaveDraft"
                    :disabled="saving"
                >
                    {{ saving ? 'Сохранение...' : 'Сохранить объявление' }}
                </SecondaryButton>
                
                <!-- Правая кнопка - Разместить/Обновить объявление -->
                <PrimaryButton
                    @click="handlePublish"
                    :disabled="saving"
                >
                    {{ saving ? (isEditMode ? 'Обновление...' : 'Публикация...') : (isEditMode ? 'Обновить объявление' : 'Разместить объявление') }}
                </PrimaryButton>
            </div>
        </form>
    </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted, watch } from 'vue'
// Используем модель AdForm для всей бизнес-логики
import { useAdFormModel } from '../model/adFormModel'

// Импорт компонента раскрывающейся секции
import CollapsibleSection from '@/src/shared/ui/organisms/CollapsibleSection.vue'

// Импорты секций из features (новая FSD архитектура)
import TitleSection from '@/src/features/AdSections/TitleSection/ui/TitleSection.vue'
import SpecialtySection from '@/src/features/AdSections/SpecialtySection/ui/SpecialtySection.vue'
import ClientsSection from '@/src/features/AdSections/ClientsSection/ui/ClientsSection.vue'
import WorkFormatSection from '@/src/features/AdSections/WorkFormatSection/ui/WorkFormatSection.vue'
import ServiceProviderSection from '@/src/features/AdSections/ServiceProviderSection/ui/ServiceProviderSection.vue'
import ExperienceSection from '@/src/features/AdSections/ExperienceSection/ui/ExperienceSection.vue'
import DescriptionSection from '@/src/features/AdSections/DescriptionSection/ui/DescriptionSection.vue'
import PricingSection from '@/src/features/AdSections/PricingSection/ui/PricingSection.vue'
import ParametersSection from '@/src/features/AdSections/ParametersSection/ui/ParametersSection.vue'
import PromoSection from '@/src/features/AdSections/PromoSection/ui/PromoSection.vue'
import MediaSection from '@/src/features/AdSections/MediaSection/ui/MediaSection.vue'
import GeoSection from '@/src/features/AdSections/GeoSection/ui/GeoSection.vue'
import ContactsSection from '@/src/features/AdSections/ContactsSection/ui/ContactsSection.vue'

// Модульные компоненты
import ServicesModule from '@/src/features/Services/index.vue'
import FeaturesSection from '@/src/features/AdSections/FeaturesSection/ui/FeaturesSection.vue'
import ScheduleSection from '@/src/features/AdSections/ScheduleSection/ui/ScheduleSection.vue'

// Унифицированные UI компоненты
import { Button } from '@/src/shared/ui/atoms/Button'
import { SecondaryButton } from '@/src/shared/ui/atoms/SecondaryButton'
import { PrimaryButton } from '@/src/shared/ui/atoms/PrimaryButton'

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

// Состояние раскрытых/свернутых секций
const sectionsState = reactive({
    // Обязательные - раскрыты по умолчанию
    parameters: true,  // Физические параметры
    price: true,       // Стоимость основной услуги
    services: true,    // Услуги
    media: true,       // Фото и видео
    geo: true,         // География
    contacts: true,    // Контакты
    
    // Дополнительные - свернуты по умолчанию
    basic: false,      // Основная информация
    conditions: false, // Условия работы
    schedule: false,   // График
    experience: false, // Опыт
    description: false,// Описание
    promo: false,      // Акции
    features: false    // Особенности
})

// Методы управления секциями
const toggleSection = (section) => {
    sectionsState[section] = !sectionsState[section]
    saveStateToLocalStorage()
}

const expandAll = () => {
    Object.keys(sectionsState).forEach(key => {
        sectionsState[key] = true
    })
    saveStateToLocalStorage()
}

const collapseAll = () => {
    Object.keys(sectionsState).forEach(key => {
        sectionsState[key] = false
    })
    saveStateToLocalStorage()
}

const expandRequired = () => {
    // Раскрыть только обязательные
    sectionsState.parameters = true
    sectionsState.price = true
    sectionsState.services = true
    sectionsState.media = true
    sectionsState.geo = true
    sectionsState.contacts = true
    
    // Свернуть остальные
    sectionsState.basic = false
    sectionsState.conditions = false
    sectionsState.schedule = false
    sectionsState.experience = false
    sectionsState.description = false
    sectionsState.promo = false
    sectionsState.features = false
    
    saveStateToLocalStorage()
}

// Проверка заполненности секций - упрощенная версия
const checkSectionFilled = (section) => {
    // Просто проверяем наличие ключевых полей
    const checks = {
        parameters: form.age || form.height || form.weight,
        price: form.prices?.apartments_1h || form.prices?.outcall_1h,
        services: form.services?.length > 0,
        media: form.photos?.length >= 3,
        geo: form.geo?.address,
        contacts: form.phone,
        basic: form.title,
        conditions: form.work_format || form.service_provider,
        schedule: form.schedule?.length > 0,
        experience: form.experience,
        description: form.description,
        promo: form.new_client_discount,
        features: form.features?.length > 0
    }
    return !!checks[section]
}

// Подсчет заполненных полей - упрощенный
const getFilledCount = (section) => {
    if (section === 'parameters') {
        // Просто считаем сколько полей заполнено
        return [form.age, form.height, form.weight, form.breast_size, 
                form.hair_color, form.eye_color, form.nationality]
                .filter(Boolean).length
    }
    if (section === 'services') {
        return form.services?.length || 0
    }
    return 0
}

// Вычисление прогресса формы - упрощенный
const formProgress = computed(() => {
    // Считаем только обязательные секции
    const requiredSections = ['parameters', 'price', 'services', 'media', 'geo', 'contacts']
    const filled = requiredSections.filter(s => checkSectionFilled(s)).length
    return Math.round((filled / requiredSections.length) * 100)
})

// Сохранение состояния в localStorage
const saveStateToLocalStorage = () => {
    localStorage.setItem('adFormSectionsState', JSON.stringify(sectionsState))
}

const restoreStateFromLocalStorage = () => {
    const saved = localStorage.getItem('adFormSectionsState')
    if (saved) {
        Object.assign(sectionsState, JSON.parse(saved))
    }
    // Всегда раскрываем секцию "Услуги" при загрузке
    sectionsState.services = true
}

// При монтировании восстанавливаем состояние
onMounted(() => {
    restoreStateFromLocalStorage()
})

// Автосохранение формы при изменениях
watch(form, () => {
    localStorage.setItem('adFormData', JSON.stringify(form))
}, { deep: true })
</script>

<style scoped>
/* Управление z-index для секций */
.ad-form-sections {
    position: relative;
}

/* Каскад z-index - первые секции выше */
.ad-form-sections > :nth-child(1) { position: relative; z-index: 10; }
.ad-form-sections > :nth-child(2) { position: relative; z-index: 9; }
.ad-form-sections > :nth-child(3) { position: relative; z-index: 8; }
.ad-form-sections > :nth-child(4) { position: relative; z-index: 7; }
.ad-form-sections > :nth-child(5) { position: relative; z-index: 6; }
.ad-form-sections > :nth-child(6) { position: relative; z-index: 5; }

/* Прогресс-бар */
.form-progress {
    margin-bottom: 24px;
    padding: 16px;
    background: white;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
}

.progress-bar {
    height: 8px;
    background: #f3f4f6;
    border-radius: 4px;
    overflow: hidden;
    margin-bottom: 8px;
}

.progress-fill {
    height: 100%;
    background: linear-gradient(90deg, #3b82f6 0%, #2563eb 100%);
    border-radius: 4px;
    transition: width 0.3s ease;
}

.progress-text {
    font-size: 14px;
    color: #6b7280;
    font-weight: 500;
}

/* Кнопки управления секциями */
.form-controls {
    display: flex;
    gap: 12px;
    margin-bottom: 20px;
    flex-wrap: wrap;
}

.control-btn {
    padding: 10px 16px;
    background: white;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    font-size: 14px;
    color: #374151;
    cursor: pointer;
    transition: all 0.2s;
    display: flex;
    align-items: center;
    gap: 6px;
}

.control-btn:hover {
    background: #f9fafb;
    border-color: #d1d5db;
}

.control-btn:active {
    transform: scale(0.98);
}

/* Стили для кнопок действий */
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

/* Отступ для секции с ценой внутри услуг */
.mt-6 {
    margin-top: 24px;
}

/* Мобильная адаптация */
@media (max-width: 640px) {
    .form-controls {
        flex-direction: column;
    }
    
    .control-btn {
        width: 100%;
        justify-content: center;
    }
    
    .form-actions {
        flex-direction: column;
    }
    
    .form-actions button {
        width: 100%;
    }
}
</style>