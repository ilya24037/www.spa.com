/**
 * Композабл для работы с мастерами
 * Удобный API для компонентов
 */

import { computed } from 'vue'
import { useMasterStore } from './masterStore'

export function useMaster(masterId = null) {
    const store = useMasterStore()

    // Вычисляемые свойства
    const master = computed(() => {
        if (masterId) {
            return store.masters.find(m => m.id === masterId) || store.currentMaster
        }
        return store.currentMaster
    })

    const isLoading = computed(() => store.loading)

    // Статусы мастера
    const isOnline = computed(() => master.value?.is_online || master.value?.is_available_now)
    const isPremium = computed(() => master.value?.is_premium)
    const isVerified = computed(() => master.value?.is_verified)
    const isFavorite = computed(() => master.value?.is_favorite)

    // Медиа
    const hasPhotos = computed(() => master.value?.photos && master.value.photos.length > 0)
    const mainPhoto = computed(() => {
        if (!hasPhotos.value) return null
        return master.value.photos[0]?.url || master.value.photos[0]?.path || master.value.photos[0]
    })
    const photoCount = computed(() => master.value?.photos?.length || 0)

    // Рейтинг и отзывы
    const rating = computed(() => master.value?.rating || 0)
    const reviewsCount = computed(() => master.value?.reviews_count || 0)
    const formattedRating = computed(() => Number(rating.value).toFixed(1))

    // Контактная информация
    const hasContacts = computed(() => 
        master.value?.phone || master.value?.whatsapp || master.value?.telegram
    )

    // Цена
    const formattedPrice = computed(() => {
        if (!master.value?.price_from) return 'Договорная'
    
        const price = new Intl.NumberFormat('ru-RU').format(master.value.price_from)
        return `от ${price} ₽/час`
    })

    // Локация
    const location = computed(() => {
        const parts = []
        if (master.value?.city) parts.push(master.value.city)
        if (master.value?.district) parts.push(master.value.district)
        return parts.join(', ')
    })

    // Действия
    const actions = {
    /**
     * Загрузить мастера
     */
        async fetch(id = masterId) {
            if (!id) throw new Error('ID мастера не указан')
            return await store.fetchMaster(id)
        },

        /**
     * Добавить/убрать из избранного
     */
        async toggleFavorite() {
            if (!master.value) throw new Error('Мастер не найден')
            return await store.toggleFavorite(master.value.id)
        },

        /**
     * Увеличить счетчик просмотров
     */
        async incrementViews() {
            if (!master.value) return
            await store.incrementViews(master.value.id)
        },

        /**
     * Загрузить отзывы
     */
        async fetchReviews(params = {}) {
            if (!master.value) throw new Error('Мастер не найден')
            return await store.fetchMasterReviews(master.value.id, params)
        },

        /**
     * Загрузить похожих мастеров
     */
        async fetchSimilar(params = {}) {
            if (!master.value) throw new Error('Мастер не найден')
            return await store.fetchSimilarMasters(master.value.id, params)
        }
    }

    return {
    // Состояние
        master,
        isLoading,

        // Статусы
        isOnline,
        isPremium,
        isVerified,
        isFavorite,

        // Медиа
        hasPhotos,
        mainPhoto,
        photoCount,

        // Рейтинг
        rating,
        reviewsCount,
        formattedRating,

        // Контакты
        hasContacts,

        // Цена и локация
        formattedPrice,
        location,

        // Действия
        ...actions
    }
}

/**
 * Композабл для списка мастеров
 */
export function useMasterList() {
    const store = useMasterStore()

    return {
    // Состояние
        masters: computed(() => store.filteredMasters),
        loading: computed(() => store.loading),
        pagination: computed(() => store.pagination),
    
        // Специальные списки
        popular: computed(() => store.popularMasters),
        premium: computed(() => store.premiumMasters),
        verified: computed(() => store.verifiedMasters),
        online: computed(() => store.onlineMasters),
    
        // Фильтры
        filters: computed(() => store.filters),
        searchQuery: computed(() => store.searchQuery),
    
        // Действия
        fetch: store.fetchMasters,
        search: store.searchMasters,
        setFilters: store.setFilters,
        clearFilters: store.clearFilters,
        setSearch: store.setSearchQuery,
        loadMore: store.loadMoreMasters,
    
        // Утилиты
        hasMore: computed(() => store.hasMorePages)
    }
}