<!-- resources/js/Pages/Home.vue -->
<template>
    <AppLayout>
        <Head title="СПА-услуги, массаж в Москве | Услуги на SPA.COM" />
        
        <!-- Контент начинается сразу под шапкой без наезда -->
        <div class="flex h-[calc(100vh-80px)]"> <!-- 80px = примерная высота шапки -->
            <!-- Левая панель с фильтрами -->
            <FilterPanel 
                :initial-filters="filters"
                @filters-updated="handleFiltersUpdate"
                class="border-t border-gray-200"
            />

            <!-- Основной контент -->
            <div class="flex-1 flex">
                <!-- Список мастеров -->
                <div class="w-1/2 border-r border-gray-200 bg-white">
                    <!-- Контент -->
                </div>

                <!-- Карта -->
                <div class="w-1/2 relative">
                    <!-- Карта -->
                </div>
            </div>
        </div>
    </AppLayout>
</template>