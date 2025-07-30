<template>
    <div class="universal-ad-form">
        <!-- Универсальная форма для всех категорий -->
        <form @submit.prevent="handleSubmit" novalidate>
            
            <!-- 1. Формат работы (перенесено на первое место) -->
            <div class="form-group-section">
                <WorkFormatSection 
                    v-model:workFormat="form.work_format" 
                    v-model:hasGirlfriend="form.has_girlfriend"
                    :errors="errors"
                />
            </div>

            <!-- 2. Кто оказывает услуги (перенесено после формата работы) -->
            <div class="form-group-section">
                <ServiceProviderSection 
                    v-model:serviceProvider="form.service_provider" 
                    :errors="errors"
                />
            </div>

            <!-- 3. Ваши клиенты (перенесено после "Кто оказывает услуги") -->
            <div class="form-group-section">
                <ClientsSection 
                    v-model:clients="form.clients" 
                    :errors="errors"
                />
            </div>

            <!-- 4. Описание (перенесено после "Ваши клиенты") -->
            <div class="form-group-section">
                <DescriptionSection 
                    v-model:description="form.description" 
                    :errors="errors"
                />
            </div>

            <!-- 5. Физические параметры -->
            <div class="form-group-section">
                <ParametersSection 
                    v-model:age="form.age"
                    v-model:height="form.height" 
                    v-model:weight="form.weight" 
                    v-model:breastSize="form.breast_size"
                    v-model:hairColor="form.hair_color" 
                    v-model:eyeColor="form.eye_color" 
                    v-model:appearance="form.appearance"
                    v-model:nationality="form.nationality" 
                    :errors="errors"
                />
            </div>

            <!-- 6. Особенности мастера -->
            <div class="form-group-section">
                <FeaturesSection 
                    v-model:features="form.features" 
                    v-model:additionalFeatures="form.additional_features" 
                    v-model:experience="form.experience"
                    v-model:educationLevel="form.education_level"
                    :errors="errors"
                />
            </div>

            <!-- 7. Стоимость основной услуги -->
            <div class="form-group-section">
                <h2 class="form-group-title">Стоимость основной услуги</h2>
                <div class="field-hint" style="margin-bottom: 20px; color: #8c8c8c; font-size: 16px;">
                    Заказчик увидит эту цену рядом с названием объявления.
                </div>
                <PriceSection 
                    v-model:price="form.price" 
                    v-model:priceUnit="form.price_unit" 
                    v-model:isStartingPrice="form.is_starting_price" 
                    v-model:pricingData="form.pricing_data"
                    v-model:contactsPerHour="form.contacts_per_hour"
                    :errors="errors"
                />
            </div>

            <!-- 8. Акции -->
            <div class="form-group-section">
                <h2 class="form-group-title">Акции</h2>
                <PromoSection 
                    v-model:newClientDiscount="form.new_client_discount" 
                    v-model:gift="form.gift" 
                    :errors="errors"
                />
            </div>

            <!-- 9. Где вы оказываете услуги -->
            <div class="form-group-section">
                <LocationSection 
                    v-model:serviceLocation="form.service_location" 
                    v-model:outcallLocations="form.outcall_locations"
                    v-model:taxiOption="form.taxi_option"
                    :errors="errors"
                />
            </div>

            <!-- 10.1. Услуги (новый модульный компонент) -->
            <div class="form-group-section">
                <ServicesModule 
                    v-model:services="form.services" 
                    v-model:servicesAdditionalInfo="form.services_additional_info" 
                    :allowedCategories="[]"
                    :errors="errors"
                />
            </div>

            <!-- 10.2. График работы (новый модульный компонент) -->
            <div class="form-group-section">
                <ScheduleSection 
                    v-model:schedule="form.schedule" 
                    v-model:scheduleNotes="form.schedule_notes" 
                    :errors="errors"
                />
            </div>

            <!-- 11. Фотографии -->
            <div class="form-group-section">
                <PhotosSection 
                    v-model:photos="form.photos" 
                    :errors="errors"
                />
            </div>

            <!-- 12.2 Видео -->
            <div class="form-group-section">
                <VideosSection 
                    v-model:videos="form.videos" 
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

            <!-- 14. Контакты -->
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
                <!-- Левая кнопка - Сохранить черновик -->
                <button 
                    type="button" 
                    @click="handleSaveDraft"
                    :disabled="saving"
                >
                    {{ saving ? 'Сохранение...' : 'Сохранить черновик' }}
                </button>
                
                <!-- Правая кнопка - Разместить объявление -->
                <button 
                    type="button" 
                    @click="handlePublish"
                    :disabled="saving"
                >
                    {{ saving ? 'Публикация...' : 'Разместить объявление' }}
                </button>
            </div>
        </form>
    </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted, nextTick } from 'vue'
import { router } from '@inertiajs/vue3'
import { useAdForm } from '@/Composables/useAdForm'
import { publishAd } from '@/utils/adApi'

// Импорты секций формы
import ServiceProviderSection from './Sections/ServiceProviderSection.vue'
import ClientsSection from './Sections/ClientsSection.vue'
import LocationSection from './Sections/LocationSection.vue'
import WorkFormatSection from './Sections/WorkFormatSection.vue'
import PriceSection from './Sections/PriceSection.vue'
import DescriptionSection from './Sections/DescriptionSection.vue'
import ParametersSection from './Sections/ParametersSection.vue'
import PromoSection from './Sections/PromoSection.vue'
import PhotosSection from './Sections/PhotosSection.vue'
import VideosSection from './Sections/VideosSection.vue'
import GeoSection from './Sections/GeoSection.vue'
import ContactsSection from './Sections/ContactsSection.vue'

// Модульные компоненты (новая архитектура)
import ServicesModule from '@/Components/Features/Services/index.vue'
import FeaturesSection from './Sections/FeaturesSection.vue'
import ScheduleSection from './Sections/ScheduleSection.vue'


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

// Используем композабл для работы с формой
const { 
    form, 
    errors: formErrors, 
    handleSubmit: submitForm,
    loadDraft
} = useAdForm({
    category: props.category,
    ...props.initialData
}, { 
    isEditMode: !!props.adId,
    adId: props.adId,
    autosaveEnabled: false
})

// Собственное состояние для сохранения
const saving = ref(false)
const validationErrors = ref({})

// Определяем обязательные поля
const requiredFields = {
    title: 'Название объявления',
    specialty: 'Специальность или сфера',
    clients: 'Ваши клиенты',
    service_location: 'Где вы оказываете услуги', 
    work_format: 'Формат работы',
    experience: 'Опыт работы',
    description: 'Описание услуги',
    price: 'Стоимость услуги',
    phone: 'Телефон для связи'
}

// Функция валидации формы
const validateForm = () => {
    validationErrors.value = {}
    let isValid = true
    let firstErrorField = null

    // Проверяем каждое обязательное поле
    for (const [field, label] of Object.entries(requiredFields)) {
        if (field === 'clients' || field === 'service_location') {
            // Для массивов проверяем, что выбран хотя бы один элемент
            if (!form[field] || form[field].length === 0) {
                validationErrors.value[field] = `Поле "${label}" обязательно для заполнения`
                if (!firstErrorField) firstErrorField = field
                isValid = false
            }
        } else if (field === 'description') {
            // Для описания проверяем минимальную длину
            if (!form[field] || form[field].toString().trim() === '') {
                validationErrors.value[field] = `Поле "${label}" обязательно для заполнения`
                if (!firstErrorField) firstErrorField = field
                isValid = false
            } else if (form[field].toString().trim().length < 50) {
                validationErrors.value[field] = `${label} должно содержать не менее 50 символов`
                if (!firstErrorField) firstErrorField = field
                isValid = false
            }
        } else {
            // Для обычных полей проверяем на пустоту
            if (!form[field] || form[field].toString().trim() === '') {
                validationErrors.value[field] = `Поле "${label}" обязательно для заполнения`
                if (!firstErrorField) firstErrorField = field
                isValid = false
            }
        }
    }

    // Если есть ошибки, прокручиваем к первой
    if (!isValid && firstErrorField) {
        scrollToField(firstErrorField)
    }

    return isValid
}

// Функция прокрутки к полю с ошибкой
const scrollToField = async (fieldName) => {
    await nextTick()
    
    // Ищем элемент по имени поля или id
    const element = document.querySelector(
        `[name="${fieldName}"], #${fieldName}, [data-field="${fieldName}"]`
    )
    
    if (element) {
        // Прокручиваем с отступом от верха
        const elementPosition = element.getBoundingClientRect().top + window.pageYOffset
        const offsetPosition = elementPosition - 100
        
        window.scrollTo({
            top: offsetPosition,
            behavior: 'smooth'
        })
        
        // Фокусируемся на элементе если возможно
        if (element.focus) {
            setTimeout(() => element.focus(), 500)
        }
    }
}

// Объединяем ошибки из формы и валидации
const errors = computed(() => {
    return { ...formErrors.value, ...validationErrors.value }
})

// Категория устанавливается через initialData в useAdForm

// Если это режим редактирования, загружаем данные
onMounted(async () => {
    if (props.adId) {
        if (props.initialData && Object.keys(props.initialData).length > 0) {
            // Загружаем данные из props
            Object.keys(props.initialData).forEach(key => {
                if (props.initialData[key] !== null && props.initialData[key] !== undefined) {
                    form[key] = props.initialData[key]
                }
            })
        } else {
            // Загружаем данные через API
            await loadDraft(props.adId)
        }
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
            id: props.adId, // ВАЖНО: передаем ID для обновления существующего черновика
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
            return result
        } else {
            const errorText = await response.text()
            console.error('Ошибка сервера:', response.status, errorText)
            throw new Error(`Ошибка при сохранении черновика: ${response.status}`)
        }
    } catch (error) {
        console.error('Ошибка при сохранении черновика:', error)
        // Не выбрасываем ошибку дальше, чтобы не блокировать перенаправление
        return null
    } finally {
        saving.value = false
    }
}

// Обработчик кнопки "Сохранить черновик"
const handleSaveDraft = async () => {
    console.log('=== SAVE DRAFT DEBUG ===')
    console.log('Начинаем сохранение черновика...')
    console.log('props.adId:', props.adId)
    console.log('typeof props.adId:', typeof props.adId)
    console.log('props.adId exists:', !!props.adId)
    console.log('initialData.id:', props.initialData?.id)
    
    saving.value = true
    
    console.log('form.services before save:', form.services)
    
    const draftData = {
        id: props.adId, // ВАЖНО: передаем ID для обновления существующего черновика
        category: props.category,
        title: form.title,
        specialty: form.specialty,
        clients: form.clients,
        service_location: form.service_location,
        outcall_locations: form.outcall_locations,
        taxi_option: form.taxi_option,
        work_format: form.work_format,
        has_girlfriend: form.has_girlfriend,
        service_provider: form.service_provider,
        experience: form.experience,
        education_level: form.education_level,
        features: form.features,
        additional_features: form.additional_features,
        description: form.description,
        price: form.price,
        price_unit: form.price_unit,
        is_starting_price: form.is_starting_price,
        pricing_data: form.pricing_data,
        contacts_per_hour: form.contacts_per_hour,
        discount: form.discount,
        new_client_discount: form.new_client_discount,
        gift: form.gift,
        address: form.address,
        travel_area: form.travel_area,
        phone: form.phone,
        contact_method: form.contact_method,
        photos: form.photos,
        video: form.video,
        show_photos_in_gallery: form.show_photos_in_gallery,
        allow_download_photos: form.allow_download_photos,
        watermark_photos: form.watermark_photos,
        // ВАЖНО: Добавляем поля услуг
        services: form.services,
        services_additional_info: form.services_additional_info,
        // ВАЖНО: Добавляем поля графика работы
        schedule: form.schedule,
        schedule_notes: form.schedule_notes,
        // ВАЖНО: Добавляем физические параметры
        age: form.age,
        height: form.height,
        weight: form.weight,
        breast_size: form.breast_size,
        hair_color: form.hair_color,
        eye_color: form.eye_color,
        appearance: form.appearance,
        nationality: form.nationality
    }
    
    console.log('Отправляемые данные:', draftData)
    console.log('ID в данных:', draftData.id)
    
    // Используем Inertia router для отправки
    router.post('/ads/draft', draftData, {
        preserveScroll: true,
        onFinish: () => {
            saving.value = false
            console.log('Запрос завершен')
        }
    })
}

// Обработчик кнопки "Разместить объявление"
const handlePublish = async () => {
    // Сначала валидируем форму
    if (!validateForm()) {
        return // Если есть ошибки, не продолжаем
    }
    
    try {
        saving.value = true
        
        // Подготавливаем данные для отправки
        const publishData = {
            ...form,
            category: props.category, // Добавляем категорию из props
            // Убеждаемся, что массивы корректно переданы
            clients: Array.isArray(form.clients) ? form.clients : [],
            service_location: Array.isArray(form.service_location) ? form.service_location : [],
            service_provider: Array.isArray(form.service_provider) ? form.service_provider : [],
            is_starting_price: Array.isArray(form.is_starting_price) ? form.is_starting_price : [],
            // Переименовываем поле скидки для сервера
            discount: form.new_client_discount || null
        }
        
        console.log('Отправляем данные для публикации:', publishData)
        
        // Сохраняем объявление
        const result = await publishAd(publishData)
        
        if (result && result.success) {
            // Используем URL из ответа сервера
            if (result.redirect) {
                window.location.href = result.redirect
            } else if (result.id) {
                // Fallback на старый формат
                router.visit(`/ad/${result.id}/select-plan`)
            }
        }
    } catch (error) {
        console.error('Ошибка при публикации объявления:', error)
        
        // Показываем ошибки валидации
        if (error.response && error.response.data && error.response.data.errors) {
            const errors = error.response.data.errors
            for (let field in errors) {
                console.error(`${field}: ${errors[field].join(', ')}`)
            }
        }
    } finally {
        saving.value = false
    }
}



// Инициализация происходит через useAdForm.js
</script>

<style scoped>
/* Кнопки действий */
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

/* Чекбоксы */
.checkbox-group {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.checkbox-item {
    display: flex;
    align-items: center;
    cursor: pointer;
    gap: 12px;
    padding: 8px 0;
    user-select: none;
}

.custom-checkbox {
    width: 20px;
    height: 20px;
    border: 2px solid #d9d9d9;
    border-radius: 4px;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s ease;
    background: #fff;
    flex-shrink: 0;
    cursor: pointer;
}

.custom-checkbox:hover {
    border-color: #8c8c8c;
}

.custom-checkbox.checked {
    background: #007bff;
    border-color: #007bff;
}

.check-icon {
    width: 12px;
    height: 10px;
    color: #fff;
    opacity: 0;
    transition: opacity 0.2s ease;
}

.custom-checkbox.checked .check-icon {
    opacity: 1;
}

.checkbox-label {
    font-size: 16px;
    color: #1a1a1a;
    font-weight: 400;
    line-height: 1.4;
    cursor: pointer;
    user-select: none;
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

/* Радио кнопки как на Avito */
.radio-group {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.radio-item {
    display: flex;
    align-items: center;
    gap: 12px;
    cursor: pointer;
}

.custom-radio {
    width: 20px;
    height: 20px;
    border: 2px solid #d9d9d9;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s ease;
    background: #fff;
    flex-shrink: 0;
}

.custom-radio.checked {
    border-color: #1890ff;
}

.custom-radio.checked::after {
    content: '';
    width: 10px;
    height: 10px;
    border-radius: 50%;
    background: #1890ff;
}

.radio-content {
    flex: 1;
}

.radio-title {
    font-size: 16px;
    font-weight: 500;
    color: #1a1a1a;
    line-height: 1.4;
}

.radio-description {
    font-size: 14px;
    color: #8c8c8c;
    line-height: 1.4;
    margin-top: 2px;
}
</style> 