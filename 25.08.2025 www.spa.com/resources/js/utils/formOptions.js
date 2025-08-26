/**
 * Справочники опций для форм объявлений
 * Выносим из компонентов для переиспользования
 */

export const specialtyOptions = [
    { value: 'massage', label: 'Массаж' },
    { value: 'apparatus_massage', label: 'Аппаратный массаж' },
    { value: 'solarium', label: 'Солярий' },
    { value: 'spa', label: 'Спа-процедуры' }
]

export const clientOptions = [
    { value: 'women', label: 'Женщины' },
    { value: 'men', label: 'Мужчины' }
]

export const serviceLocationOptions = [
    { value: 'client_home', label: 'У заказчика дома' },
    { value: 'my_home', label: 'У себя дома' },
    { value: 'salon', label: 'В салоне' },
    { value: 'coworking', label: 'В коворкинге' },
    { value: 'clinic', label: 'В клинике' }
]

export const workFormatOptions = [
    { 
        value: 'individual', 
        label: 'Индивидуально',
        description: 'Работаю с одним клиентом'
    },
    { 
        value: 'group', 
        label: 'Групповые занятия',
        description: 'Работаю с группой клиентов'
    }
]

export const serviceProviderOptions = [
    { value: 'individual', label: 'Частное лицо' },
    { value: 'company', label: 'Компания' }
]

export const experienceOptions = [
    { value: 'no_experience', label: 'Нет опыта' },
    { value: 'less_than_year', label: 'Менее года' },
    { value: '1_3_years', label: '1-3 года' },
    { value: '3_5_years', label: '3-5 лет' },
    { value: 'more_than_5', label: 'Более 5 лет' }
]

export const priceUnitOptions = [
    { value: 'service', label: 'За услугу' },
    { value: 'hour', label: 'За час' },
    { value: 'session', label: 'За сеанс' }
]

export const contactMethodOptions = [
    { value: 'any', label: 'Любой способ' },
    { value: 'calls', label: 'Только звонки' },
    { value: 'messages', label: 'Только сообщения' }
]

/**
 * Получить опции по категории
 */
export const getOptionsByCategory = (category) => {
    const optionsMap = {
        specialty: specialtyOptions,
        clients: clientOptions,
        serviceLocation: serviceLocationOptions,
        workFormat: workFormatOptions,
        serviceProvider: serviceProviderOptions,
        experience: experienceOptions,
        priceUnit: priceUnitOptions,
        contactMethod: contactMethodOptions
    }
  
    return optionsMap[category] || []
}

/**
 * Получить label по value
 */
export const getOptionLabel = (category, value) => {
    const options = getOptionsByCategory(category)
    const option = options.find(opt => opt.value === value)
    return option ? option.label : value
} 