import { ref, reactive, computed, onMounted, nextTick } from 'vue'
import { router } from '@inertiajs/vue3'
import { useAdForm } from '@/Composables/useAdForm'
import { publishAd } from '@/utils/adApi'

/**
 * Композабл для управления бизнес-логикой формы объявления
 * Вынесена вся логика из старого монолитного AdForm.vue
 */
export function useAdFormModel(props, emit) {
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

    // Обработчик кнопки "Сохранить черновик"
    const handleSaveDraft = async () => {
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

    // Возвращаем API для использования в компоненте
    return {
        form,
        errors,
        saving,
        handleSubmit,
        handleSaveDraft,
        handlePublish
    }
}
