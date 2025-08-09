/**
 * Типы и константы для entities/ad
 * Общие интерфейсы и enum'ы для объявлений
 */

// 📊 Статусы объявлений (синхронизировано с Laravel Enum)
export const AD_STATUSES = {
    DRAFT: 'draft',
    WAITING_PAYMENT: 'waiting_payment', 
    ACTIVE: 'active',
    ARCHIVED: 'archived',
    EXPIRED: 'expired',
    REJECTED: 'rejected',
    BLOCKED: 'blocked'
}

// 🏷️ Лейблы статусов
export const AD_STATUS_LABELS = {
    [AD_STATUSES.DRAFT]: 'Черновик',
    [AD_STATUSES.WAITING_PAYMENT]: 'Ждет оплаты',
    [AD_STATUSES.ACTIVE]: 'Активное',
    [AD_STATUSES.ARCHIVED]: 'В архиве', 
    [AD_STATUSES.EXPIRED]: 'Истекло',
    [AD_STATUSES.REJECTED]: 'Отклонено',
    [AD_STATUSES.BLOCKED]: 'Заблокировано'
}

// 🎨 Цвета статусов
export const AD_STATUS_COLORS = {
    [AD_STATUSES.DRAFT]: 'gray',
    [AD_STATUSES.WAITING_PAYMENT]: 'amber',
    [AD_STATUSES.ACTIVE]: 'green',
    [AD_STATUSES.ARCHIVED]: 'gray',
    [AD_STATUSES.EXPIRED]: 'red',
    [AD_STATUSES.REJECTED]: 'red',
    [AD_STATUSES.BLOCKED]: 'red'
}

// 💰 Единицы цены
export const PRICE_UNITS = {
    SERVICE: 'service',
    HOUR: 'hour',
    NIGHT: 'night',
    WEEK: 'week',
    MONTH: 'month'
}

export const PRICE_UNIT_LABELS = {
    [PRICE_UNITS.SERVICE]: 'за услугу',
    [PRICE_UNITS.HOUR]: 'за час',
    [PRICE_UNITS.NIGHT]: 'за ночь',
    [PRICE_UNITS.WEEK]: 'за неделю',
    [PRICE_UNITS.MONTH]: 'за месяц'
}

// 📍 Форматы работы
export const WORK_FORMATS = {
    INCALL: 'incall',
    OUTCALL: 'outcall',
    BOTH: 'both'
}

export const WORK_FORMAT_LABELS = {
    [WORK_FORMATS.INCALL]: 'У меня',
    [WORK_FORMATS.OUTCALL]: 'К клиенту',
    [WORK_FORMATS.BOTH]: 'У меня и к клиенту'
}

// 👥 Типы клиентов
export const CLIENT_TYPES = {
    MEN: 'men',
    WOMEN: 'women',
    COUPLES: 'couples',
    ALL: 'all'
}

export const CLIENT_TYPE_LABELS = {
    [CLIENT_TYPES.MEN]: 'Мужчины',
    [CLIENT_TYPES.WOMEN]: 'Женщины', 
    [CLIENT_TYPES.COUPLES]: 'Пары',
    [CLIENT_TYPES.ALL]: 'Все'
}

// 📞 Способы связи
export const CONTACT_METHODS = {
    MESSAGES: 'messages',
    CALLS: 'calls',
    BOTH: 'both'
}

export const CONTACT_METHOD_LABELS = {
    [CONTACT_METHODS.MESSAGES]: 'Только сообщения',
    [CONTACT_METHODS.CALLS]: 'Только звонки',
    [CONTACT_METHODS.BOTH]: 'Звонки и сообщения'
}

// 💳 Способы оплаты
export const PAYMENT_METHODS = {
    CASH: 'cash',
    CARD: 'card',
    TRANSFER: 'transfer',
    CRYPTO: 'crypto'
}

export const PAYMENT_METHOD_LABELS = {
    [PAYMENT_METHODS.CASH]: 'Наличные',
    [PAYMENT_METHODS.CARD]: 'Картой',
    [PAYMENT_METHODS.TRANSFER]: 'Переводом',
    [PAYMENT_METHODS.CRYPTO]: 'Криптовалюта'
}

// 🔍 Параметры сортировки
export const SORT_OPTIONS = {
    CREATED_DESC: 'created_at_desc',
    CREATED_ASC: 'created_at_asc',
    PRICE_DESC: 'price_desc',
    PRICE_ASC: 'price_asc',
    RATING_DESC: 'rating_desc',
    VIEWS_DESC: 'views_desc',
    POPULAR: 'popular'
}

export const SORT_LABELS = {
    [SORT_OPTIONS.CREATED_DESC]: 'Сначала новые',
    [SORT_OPTIONS.CREATED_ASC]: 'Сначала старые',
    [SORT_OPTIONS.PRICE_DESC]: 'Сначала дорогие',
    [SORT_OPTIONS.PRICE_ASC]: 'Сначала дешевые',
    [SORT_OPTIONS.RATING_DESC]: 'По рейтингу',
    [SORT_OPTIONS.VIEWS_DESC]: 'По популярности',
    [SORT_OPTIONS.POPULAR]: 'Популярные'
}

// 📱 Размеры изображений
export const IMAGE_SIZES = {
    THUMBNAIL: 'thumbnail',
    SMALL: 'small',
    MEDIUM: 'medium',
    LARGE: 'large',
    ORIGINAL: 'original'
}

// 🎯 Валидация
export const VALIDATION_RULES = {
    title: {
        min: 3,
        max: 100
    },
    description: {
        min: 10,
        max: 2000
    },
    price: {
        min: 100,
        max: 50000
    },
    photos: {
        max_count: 20,
        max_size: 5 * 1024 * 1024, // 5MB
        allowed_types: ['image/jpeg', 'image/png', 'image/webp']
    },
    video: {
        max_size: 100 * 1024 * 1024, // 100MB
        allowed_types: ['video/mp4', 'video/webm', 'video/avi']
    }
}

// 🎨 CSS классы для статусов
export const getStatusClasses = (status) => {
    const colorMap = {
        gray: 'bg-gray-100 text-gray-800',
        amber: 'bg-amber-100 text-amber-800',
        green: 'bg-green-100 text-green-800',
        red: 'bg-red-100 text-red-800'
    }
  
    const color = AD_STATUS_COLORS[status] || 'gray'
    return colorMap[color] || colorMap.gray
}

// 🔧 Утилиты
export const isPublicStatus = (status) => {
    return status === AD_STATUSES.ACTIVE
}

export const isEditableStatus = (status) => {
    return [
        AD_STATUSES.DRAFT,
        AD_STATUSES.WAITING_PAYMENT,
        AD_STATUSES.ARCHIVED
    ].includes(status)
}

export const isDeletableStatus = (status) => {
    return [
        AD_STATUSES.DRAFT,
        AD_STATUSES.ARCHIVED
    ].includes(status)
}

// 📈 Дефолтные значения для формы
export const DEFAULT_AD_FORM = {
    // Базовая информация
    work_format: '',
    has_girlfriend: false,
    service_provider: [],
    clients: [],
    description: '',
  
    // Персональная информация
    age: '',
    height: '',
    weight: '',
    breast_size: '',
    hair_color: '',
    eye_color: '',
    appearance: '',
    nationality: '',
    features: {},
    additional_features: '',
    experience: '',
  
    // Коммерческая информация
    price: '',
    price_unit: PRICE_UNITS.HOUR,
    is_starting_price: false,
    pricing_data: {},
    contacts_per_hour: '',
    new_client_discount: '',
    gift: '',
    services: {},
    services_additional_info: '',
    schedule: {},
    schedule_notes: '',
  
    // Локация и контакты
    service_location: [],
    outcall_locations: [],
    address: '',
    taxi_option: '',
    geo: {},
    travel_area: '',
    phone: '',
    contact_method: CONTACT_METHODS.BOTH,
    whatsapp: '',
    telegram: '',
  
    // Способы оплаты
    payment_methods: [PAYMENT_METHODS.CASH],
  
    // Медиа
    photos: [],
    video: null,
  
    // Метаданные
    category: 'massage',
    status: AD_STATUSES.DRAFT
}