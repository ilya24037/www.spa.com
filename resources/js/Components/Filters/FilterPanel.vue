<!-- resources/js/Components/Filters/FilterPanel.vue -->
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
                <!-- Каждая секция с разделителем -->
                <div class="filter-section">
                    <!-- Содержимое фильтров -->
                </div>
            </div>
        </div>
    </aside>
</template>

<style scoped>
.filters-panel {
    background: white;
    border-radius: 8px;
    padding: 16px;
}

.filters-panel.transparent {
    background: transparent;
    padding: 0;
}

.filter-section {
    padding-bottom: 16px;
    margin-bottom: 16px;
    border-bottom: 1px solid #e5e7eb;
}

.filter-section:last-child {
    border-bottom: none;
}
</style>