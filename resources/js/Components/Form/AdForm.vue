<template>
    <div class="universal-ad-form">
        <!-- Универсальная форма для всех категорий -->
        <form @submit.prevent="handleSubmit" novalidate>
            
            <!-- 1. Подробности (title + specialty) -->
            <div class="form-group-section">
                <TitleSection :form="form" :errors="errors" />
                <SpecialtySection :form="form" :errors="errors" />
            </div>

            <!-- 2. Ваши клиенты -->
            <div class="form-group-section">
                <h2 class="form-group-title">Ваши клиенты</h2>
                <ClientsSection :form="form" :errors="errors" />
            </div>

            <!-- 3. Где вы оказываете услуги -->
            <div class="form-group-section">
                <h2 class="form-group-title">Где вы оказываете услуги</h2>
                <LocationSection :form="form" :errors="errors" />
            </div>

            <!-- 4. Формат работы -->
            <div class="form-group-section">
                <h2 class="form-group-title">Формат работы</h2>
                <WorkFormatSection :form="form" :errors="errors" />
            </div>

            <!-- 5. Кто оказывает услуги -->
            <div class="form-group-section">
                <h2 class="form-group-title">Кто оказывает услуги</h2>
                <div class="checkbox-group">
                    <div class="checkbox-item" @click="toggleServiceProvider('women')">
                        <div 
                            class="custom-checkbox"
                            :class="{ 'checked': form.service_provider.includes('women') }"
                        >
                            <svg class="check-icon" width="100%" height="100%" viewBox="0 0 10 8" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M1 4.35714L3.4 6.5L9 1.5" stroke="currentColor" stroke-width="2" stroke-linecap="round"></path>
                            </svg>
                        </div>
                        <span class="checkbox-label">Женщина</span>
                    </div>
                    
                    <div class="checkbox-item" @click="toggleServiceProvider('men')">
                        <div 
                            class="custom-checkbox"
                            :class="{ 'checked': form.service_provider.includes('men') }"
                        >
                            <svg class="check-icon" width="100%" height="100%" viewBox="0 0 10 8" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M1 4.35714L3.4 6.5L9 1.5" stroke="currentColor" stroke-width="2" stroke-linecap="round"></path>
                            </svg>
                        </div>
                        <span class="checkbox-label">Мужчина</span>
                    </div>
                </div>
            </div>

            <!-- 6. Опыт работы -->
            <div class="form-group-section">
                <h2 class="form-group-title">Опыт работы</h2>
                <ExperienceSection :form="form" :errors="errors" />
            </div>

            <!-- 7. Описание -->
            <div class="form-group-section">
                <h2 class="form-group-title">Описание</h2>
                <DescriptionSection :form="form" :errors="errors" />
            </div>

            <!-- 8. Стоимость основной услуги -->
            <div class="form-group-section">
                <h2 class="form-group-title">Стоимость основной услуги</h2>
                <div class="field-hint" style="margin-bottom: 20px; color: #8c8c8c; font-size: 16px;">
                    Заказчик увидит эту цену рядом с названием объявления.
                </div>
                <PriceSection :form="form" :errors="errors" />
            </div>

            <!-- 9. Акции -->
            <div class="form-group-section">
                <h2 class="form-group-title">Акции</h2>
                <div class="field-hint" style="margin-bottom: 20px; color: #8c8c8c; font-size: 16px;">
                    Клиенты увидят информацию о скидках и подарках в объявлении.
                </div>
                <PromoSection :form="form" :errors="errors" />
            </div>

            <!-- 10. Фотографии и видео -->
            <div class="form-group-section">
                <PhotosSection :form="form" :errors="errors" />
                <VideosSection :form="form" :errors="errors" />
            </div>

            <!-- 11. География с бейджем "Новое" -->
            <div class="form-group-section geography-section">
                <h2 class="form-group-title">
                    География
                    <span class="form-group-badge">Новое</span>
                </h2>
                <GeoSection :form="form" :errors="errors" />
            </div>

            <!-- 12. Контакты -->
            <div class="form-group-section">
                <h2 class="form-group-title">Контакты</h2>
                <ContactsSection :form="form" :errors="errors" />
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
import TitleSection from './Sections/TitleSection.vue'
import SpecialtySection from './Sections/SpecialtySection.vue'
import ClientsSection from './Sections/ClientsSection.vue'
import LocationSection from './Sections/LocationSection.vue'
import WorkFormatSection from './Sections/WorkFormatSection.vue'
import ExperienceSection from './Sections/ExperienceSection.vue'
import PriceSection from './Sections/PriceSection.vue'
import DescriptionSection from './Sections/DescriptionSection.vue'
import PromoSection from './Sections/PromoSection.vue'
import PhotosSection from './Sections/PhotosSection.vue'
import VideosSection from './Sections/VideosSection.vue'
import GeoSection from './Sections/GeoSection.vue'
import ContactsSection from './Sections/ContactsSection.vue'

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
} = useAdForm({}, { 
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

// Устанавливаем категорию в форму
form.category = props.category

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
    console.log('Начинаем сохранение черновика...')
    saving.value = true
    
    // Используем Inertia router для отправки
    router.post('/ads/draft', {
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
        contact_method: form.contact_method,
        photos: form.photos,
        video: form.video,
        show_photos_in_gallery: form.show_photos_in_gallery,
        allow_download_photos: form.allow_download_photos,
        watermark_photos: form.watermark_photos
    }, {
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
        
        // Сохраняем объявление
        const result = await publishAd(form)
        
        if (result && result.id) {
            // Перенаправляем на страницу оплаты
            router.visit(`/payment/ad/${result.id}/select-plan`)
        }
    } catch (error) {
        console.error('Ошибка при публикации объявления:', error)
        // Можно показать уведомление об ошибке
    } finally {
        saving.value = false
    }
}

// Функция переключения поставщиков услуг
const toggleServiceProvider = (provider) => {
    if (!form.service_provider) {
        form.service_provider = []
    }
    
    if (!form.service_provider.includes(provider)) {
        form.service_provider.push(provider)
    } else {
        const index = form.service_provider.indexOf(provider)
        form.service_provider.splice(index, 1)
    }
}

// Инициализация массивов
onMounted(() => {
    if (!form.service_provider) {
        form.service_provider = []
    }
})
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