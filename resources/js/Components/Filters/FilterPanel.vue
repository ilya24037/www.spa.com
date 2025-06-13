<template>
    <aside class="filters-panel" :class="{ 'transparent': transparent }">
        <div class="filters-content">
            <!-- Заголовок -->
            <div class="filters-header">
                <h2 class="filters-title">Фильтры</h2>
                <button @click="resetAllFilters" class="reset-button">
                    Сбросить все
                </button>
            </div>

            <!-- Секции фильтров -->
            <div class="filter-sections">
                <!-- Цена -->
                <div class="filter-section">
                    <h3 class="filter-section-title">Цена за час</h3>
                    <div class="space-y-2">
                        <label class="flex items-center">
                            <input type="checkbox" class="form-checkbox">
                            <span class="ml-2 text-sm">До 2000 ₽</span>
                        </label>
                        <label class="flex items-center">
                            <input type="checkbox" class="form-checkbox">
                            <span class="ml-2 text-sm">2000 - 3000 ₽</span>
                        </label>
                        <label class="flex items-center">
                            <input type="checkbox" class="form-checkbox">
                            <span class="ml-2 text-sm">3000 - 4000 ₽</span>
                        </label>
                        <label class="flex items-center">
                            <input type="checkbox" class="form-checkbox">
                            <span class="ml-2 text-sm">Свыше 4000 ₽</span>
                        </label>
                    </div>
                </div>

                <!-- Рейтинг -->
                <div class="filter-section">
                    <h3 class="filter-section-title">Рейтинг</h3>
                    <div class="space-y-2">
                        <label class="flex items-center">
                            <input type="checkbox" class="form-checkbox">
                            <span class="ml-2 text-sm"> 5.0</span>
                        </label>
                        <label class="flex items-center">
                            <input type="checkbox" class="form-checkbox">
                            <span class="ml-2 text-sm"> 4.0+</span>
                        </label>
                        <label class="flex items-center">
                            <input type="checkbox" class="form-checkbox">
                            <span class="ml-2 text-sm"> 3.0+</span>
                        </label>
                    </div>
                </div>

                <!-- Специализация -->
                <div class="filter-section">
                    <h3 class="filter-section-title">Специализация</h3>
                    <div class="space-y-2">
                        <label class="flex items-center">
                            <input type="checkbox" class="form-checkbox">
                            <span class="ml-2 text-sm">Классический массаж</span>
                        </label>
                        <label class="flex items-center">
                            <input type="checkbox" class="form-checkbox">
                            <span class="ml-2 text-sm">Тайский массаж</span>
                        </label>
                        <label class="flex items-center">
                            <input type="checkbox" class="form-checkbox">
                            <span class="ml-2 text-sm">Спортивный массаж</span>
                        </label>
                        <label class="flex items-center">
                            <input type="checkbox" class="form-checkbox">
                            <span class="ml-2 text-sm">Антицеллюлитный</span>
                        </label>
                        <label class="flex items-center">
                            <input type="checkbox" class="form-checkbox">
                            <span class="ml-2 text-sm">Релакс массаж</span>
                        </label>
                    </div>
                </div>

                <!-- Доступность -->
                <div class="filter-section">
                    <h3 class="filter-section-title">Доступность</h3>
                    <div class="space-y-2">
                        <label class="flex items-center">
                            <input type="checkbox" class="form-checkbox">
                            <span class="ml-2 text-sm">Онлайн сейчас</span>
                        </label>
                        <label class="flex items-center">
                            <input type="checkbox" class="form-checkbox">
                            <span class="ml-2 text-sm">С выездом</span>
                        </label>
                    </div>
                </div>
            </div>
        </div>
    </aside>
</template>

<script setup>
const props = defineProps({
    initialFilters: {
        type: Object,
        default: () => ({})
    },
    transparent: {
        type: Boolean,
        default: false
    }
})

const emit = defineEmits(['filters-updated'])

const resetAllFilters = () => {
    emit('filters-updated', {})
}
</script>

<style scoped>
.filters-panel {
    background: white;
    border-radius: 8px;
    padding: 16px;
    box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
    border: 1px solid rgba(0, 0, 0, 0.04);
}

.filters-panel.transparent {
    background: transparent;
    padding: 0;
    box-shadow: none;
    border: none;
}

.filters-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 16px;
    padding-bottom: 12px;
    border-bottom: 1px solid #e5e7eb;
}

.filters-title {
    font-size: 18px;
    font-weight: 600;
    color: #111827;
    margin: 0;
}

.reset-button {
    font-size: 12px;
    color: #6b7280;
    text-decoration: underline;
    background: none;
    border: none;
    cursor: pointer;
    padding: 0;
}

.reset-button:hover {
    color: #3b82f6;
}

.filter-sections {
    space-y: 20px;
}

.filter-section {
    padding-bottom: 16px;
    margin-bottom: 16px;
    border-bottom: 1px solid #e5e7eb;
}

.filter-section:last-child {
    border-bottom: none;
    margin-bottom: 0;
    padding-bottom: 0;
}

.filter-section-title {
    font-size: 14px;
    font-weight: 600;
    color: #374151;
    margin-bottom: 12px;
}

.form-checkbox {
    width: 16px;
    height: 16px;
    border-radius: 4px;
    border: 1px solid #d1d5db;
    color: #3b82f6;
}

.form-checkbox:checked {
    background-color: #3b82f6;
    border-color: #3b82f6;
}

.space-y-2 > * + * {
    margin-top: 8px;
}
</style>