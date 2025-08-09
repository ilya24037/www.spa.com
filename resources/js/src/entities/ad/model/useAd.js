/**
 * Композабл для работы с объявлениями
 * Удобный API для компонентов
 */

import { computed, ref } from 'vue'
import { useAdStore } from './adStore'
import { adApi } from '../api/adApi'
import { AD_STATUSES, isEditableStatus, isPublicStatus } from './adTypes'

export function useAd(adId = null) {
    const store = useAdStore()
    const error = ref(null)

    // Вычисляемые свойства
    const ad = computed(() => {
        if (adId) {
            return store.ads.find(a => a.id === adId) || store.currentAd
        }
        return store.currentAd
    })

    const isLoading = computed(() => store.loading)
    const isSaving = computed(() => store.saving)

    // Статусы объявления
    const isActive = computed(() => ad.value?.status === AD_STATUSES.ACTIVE)
    const isDraft = computed(() => ad.value?.status === AD_STATUSES.DRAFT)
    const isArchived = computed(() => ad.value?.status === AD_STATUSES.ARCHIVED)
    const isExpired = computed(() => ad.value?.status === AD_STATUSES.EXPIRED)
    const isBlocked = computed(() => ad.value?.status === AD_STATUSES.BLOCKED)

    // Разрешения
    const canEdit = computed(() => ad.value && isEditableStatus(ad.value.status))
    const canDelete = computed(() => ad.value && (isDraft.value || isArchived.value))
    const canPublish = computed(() => ad.value && (isDraft.value || isArchived.value))
    const canArchive = computed(() => ad.value && (isActive.value || isDraft.value))
    const isPublic = computed(() => ad.value && isPublicStatus(ad.value.status))

    // Медиа
    const hasPhotos = computed(() => ad.value?.photos && ad.value.photos.length > 0)
    const mainPhoto = computed(() => {
        if (!hasPhotos.value) return null
        return ad.value.photos[0]?.url || ad.value.photos[0]?.path || ad.value.photos[0]
    })
    const photoCount = computed(() => ad.value?.photos?.length || 0)

    // Контактная информация
    const hasContacts = computed(() => ad.value?.phone || ad.value?.whatsapp || ad.value?.telegram)
    const displayPhone = computed(() => {
        if (!ad.value?.phone) return null
    
        // Показываем телефон только если объявление активно или это владелец
        if (isPublic.value || ad.value.is_owner) {
            return ad.value.phone
        }
    
        return null
    })

    // Цена
    const formattedPrice = computed(() => {
        if (!ad.value?.price) return 'Договорная'
    
        const price = new Intl.NumberFormat('ru-RU').format(ad.value.price)
        const unit = ad.value.price_unit === 'hour' ? 'час' : 'услугу'
        const prefix = ad.value.is_starting_price ? 'от ' : ''
    
        return `${prefix}${price} ₽/${unit}`
    })

    // Действия
    const actions = {
    /**
     * Загрузить объявление
     */
        async fetch(id = adId) {
            if (!id) {
                error.value = 'ID объявления не указан'
                return null
            }

            try {
                error.value = null
                const result = await store.fetchAd(id)
                return result
            } catch (err) {
                error.value = err.message || 'Ошибка загрузки объявления'
                throw err
            }
        },

        /**
     * Сохранить изменения
     */
        async save(data) {
            if (!ad.value) {
                error.value = 'Объявление не найдено'
                return null
            }

            try {
                error.value = null
                const result = await store.updateAd(ad.value.id, data)
                return result
            } catch (err) {
                error.value = err.message || 'Ошибка сохранения'
                throw err
            }
        },

        /**
     * Удалить объявление
     */
        async remove() {
            if (!ad.value || !canDelete.value) {
                error.value = 'Удаление недоступно'
                return false
            }

            try {
                error.value = null
                await store.deleteAd(ad.value.id)
                return true
            } catch (err) {
                error.value = err.message || 'Ошибка удаления'
                throw err
            }
        },

        /**
     * Опубликовать объявление
     */
        async publish() {
            if (!ad.value || !canPublish.value) {
                error.value = 'Публикация недоступна'
                return false
            }

            try {
                error.value = null
                await store.changeAdStatus(ad.value.id, AD_STATUSES.ACTIVE)
                return true
            } catch (err) {
                error.value = err.message || 'Ошибка публикации'
                throw err
            }
        },

        /**
     * Архивировать объявление
     */
        async archive() {
            if (!ad.value || !canArchive.value) {
                error.value = 'Архивирование недоступно'
                return false
            }

            try {
                error.value = null
                await store.changeAdStatus(ad.value.id, AD_STATUSES.ARCHIVED)
                return true
            } catch (err) {
                error.value = err.message || 'Ошибка архивирования'
                throw err
            }
        },

        /**
     * Добавить/убрать из избранного
     */
        async toggleFavorite() {
            if (!ad.value) {
                error.value = 'Объявление не найдено'
                return false
            }

            try {
                error.value = null
                const result = await store.toggleFavorite(ad.value.id)
                return result
            } catch (err) {
                error.value = err.message || 'Ошибка изменения избранного'
                throw err
            }
        },

        /**
     * Увеличить счетчик просмотров
     */
        async incrementViews() {
            if (!ad.value || !isPublic.value) return

            try {
                await adApi.incrementViews(ad.value.id)
        
                // Обновляем локально
                if (ad.value.views) {
                    ad.value.views++
                }
            } catch (_err) {
                // Не показываем ошибку пользователю для просмотров
            }
        },

        /**
     * Загрузить фотографии
     */
        async uploadPhotos(files) {
            if (!ad.value) {
                error.value = 'Объявление не найдено'
                return null
            }

            try {
                error.value = null
                const response = await adApi.uploadPhotos(files, ad.value.id)
        
                // Обновляем фотографии в объявлении
                if (response.photos) {
                    ad.value.photos = response.photos
                }
        
                return response
            } catch (err) {
                error.value = err.message || 'Ошибка загрузки фотографий'
                throw err
            }
        },

        /**
     * Удалить фотографию
     */
        async deletePhoto(photoId) {
            if (!ad.value) {
                error.value = 'Объявление не найдено'
                return false
            }

            try {
                error.value = null
                await adApi.deletePhoto(photoId)
        
                // Удаляем из локального списка
                if (ad.value.photos) {
                    ad.value.photos = ad.value.photos.filter(photo => 
                        photo.id !== photoId
                    )
                }
        
                return true
            } catch (err) {
                error.value = err.message || 'Ошибка удаления фотографии'
                throw err
            }
        }
    }

    return {
    // Состояние
        ad,
        error,
        isLoading,
        isSaving,

        // Статусы
        isActive,
        isDraft,
        isArchived,
        isExpired,
        isBlocked,

        // Разрешения
        canEdit,
        canDelete,
        canPublish,
        canArchive,
        isPublic,

        // Медиа
        hasPhotos,
        mainPhoto,
        photoCount,

        // Контакты
        hasContacts,
        displayPhone,

        // Цена
        formattedPrice,

        // Действия
        ...actions
    }
}

/**
 * Композабл для списка объявлений
 */
export function useAdList() {
    const store = useAdStore()

    return {
    // Состояние
        ads: computed(() => store.filteredAds),
        loading: computed(() => store.loading),
        pagination: computed(() => store.pagination),
    
        // Фильтры
        filters: computed(() => store.filters),
        searchQuery: computed(() => store.searchQuery),
    
        // Статистика
        userStats: computed(() => store.userStats),
    
        // Действия
        fetch: store.fetchAds,
        setFilters: store.setFilters,
        clearFilters: store.clearFilters,
        setSearch: store.setSearchQuery,
        loadMore: store.loadMoreAds,
    
        // Утилиты
        hasMore: computed(() => store.hasMorePages)
    }
}