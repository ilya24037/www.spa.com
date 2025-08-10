<template>
  <div>
    <!-- Hero секция на всю ширину экрана -->
    <section class="bg-gradient-to-r from-blue-600 to-purple-600 text-white">
      <div class="container-adaptive py-16">
        <h1 class="text-4xl font-bold mb-4">Демонстрация архитектуры макета</h1>
        <p class="text-xl opacity-90">Как у Ozon и Avito: адаптивность, центрирование, минимальная ширина</p>
      </div>
    </section>

    <!-- Основной контент с демонстрацией -->
    <div class="container-adaptive py-8">
      <!-- Информационный блок -->
      <div class="bg-blue-50 border border-blue-200 rounded-lg p-6 mb-8">
        <h2 class="text-2xl font-semibold mb-4">🎯 Ключевые особенности макета:</h2>
        <ul class="space-y-2">
          <li class="flex items-start">
            <span class="text-green-500 mr-2">✓</span>
            <span><strong>Максимальная ширина:</strong> Контент ограничен 1600px (как у Ozon)</span>
          </li>
          <li class="flex items-start">
            <span class="text-green-500 mr-2">✓</span>
            <span><strong>Центрирование:</strong> Автоматическое выравнивание по центру на больших экранах</span>
          </li>
          <li class="flex items-start">
            <span class="text-green-500 mr-2">✓</span>
            <span><strong>Минимальная ширина:</strong> Карточки не сжимаются меньше 280px</span>
          </li>
          <li class="flex items-start">
            <span class="text-green-500 mr-2">✓</span>
            <span><strong>Адаптивность:</strong> Автоматическая перестройка сетки под размер экрана</span>
          </li>
        </ul>
      </div>

      <!-- Демонстрация различных сеток -->
      <section class="mb-12">
        <h2 class="text-2xl font-semibold mb-6">Стандартная сетка услуг (service-grid)</h2>
        <div class="service-grid">
          <div v-for="i in 8" :key="`service-${i}`" class="bg-white rounded-lg shadow-md p-6 hover:shadow-lg transition-shadow">
            <div class="w-full h-32 bg-gray-200 rounded mb-4"></div>
            <h3 class="font-semibold mb-2">Услуга {{ i }}</h3>
            <p class="text-gray-600 text-sm">Минимальная ширина: 280px</p>
            <p class="text-gray-500 text-xs mt-2">При сжатии окна карточки не станут уже</p>
          </div>
        </div>
      </section>

      <!-- Сетка мастеров -->
      <section class="mb-12">
        <h2 class="text-2xl font-semibold mb-6">Сетка мастеров (master-grid)</h2>
        <div class="master-grid">
          <div v-for="i in 4" :key="`master-${i}`" class="bg-white rounded-lg shadow-md p-6 hover:shadow-lg transition-shadow">
            <div class="flex items-center mb-4">
              <div class="w-16 h-16 bg-gray-300 rounded-full mr-4"></div>
              <div>
                <h3 class="font-semibold">Мастер {{ i }}</h3>
                <p class="text-sm text-gray-600">Специалист</p>
              </div>
            </div>
            <p class="text-gray-600">Карточки мастеров шире - минимум 350px</p>
          </div>
        </div>
      </section>

      <!-- Компактная сетка -->
      <section class="mb-12">
        <h2 class="text-2xl font-semibold mb-6">Компактная сетка (compact-grid)</h2>
        <div class="compact-grid">
          <div v-for="i in 12" :key="`compact-${i}`" class="bg-white rounded shadow p-4 hover:shadow-md transition-shadow">
            <div class="w-full h-20 bg-gray-200 rounded mb-2"></div>
            <p class="text-sm font-medium">Товар {{ i }}</p>
            <p class="text-xs text-gray-500">200px мин.</p>
          </div>
        </div>
      </section>

      <!-- Демонстрация контейнеров -->
      <section class="mb-12">
        <h2 class="text-2xl font-semibold mb-6">Различные типы контейнеров</h2>
        
        <!-- Узкий контейнер -->
        <div class="bg-gray-50 p-6 rounded-lg mb-4">
          <div class="container-narrow mx-auto">
            <h3 class="font-semibold mb-2">Узкий контейнер (container-narrow)</h3>
            <p class="text-gray-600">Максимальная ширина: 800px. Идеально для текстового контента, статей, документации. Улучшает читаемость на больших экранах.</p>
          </div>
        </div>

        <!-- Широкий контейнер -->
        <div class="bg-gray-100 p-6 rounded-lg">
          <div class="container-wide mx-auto">
            <h3 class="font-semibold mb-2">Широкий контейнер (container-wide)</h3>
            <p class="text-gray-600">Максимальная ширина: 1800px. Для каталогов с большим количеством товаров.</p>
          </div>
        </div>
      </section>

      <!-- Инструкция по тестированию -->
      <section class="bg-yellow-50 border border-yellow-200 rounded-lg p-6">
        <h2 class="text-xl font-semibold mb-4">🧪 Как протестировать:</h2>
        <ol class="space-y-2 list-decimal list-inside">
          <li>Измените размер окна браузера - посмотрите как перестраиваются карточки</li>
          <li>Сожмите окно до минимума - появится горизонтальный скролл когда карточки достигнут минимальной ширины</li>
          <li>Растяните окно на весь экран - контент останется по центру с максимальной шириной 1600px</li>
          <li>Прокрутите страницу - шапка останется вверху (sticky)</li>
        </ol>
      </section>
    </div>

    <!-- Полноширинная секция в конце -->
    <section class="bg-gray-900 text-white mt-12">
      <div class="container-adaptive py-12">
        <h2 class="text-2xl font-bold mb-4">Полноширинные секции</h2>
        <p>Фон секции растягивается на всю ширину, но контент остается в контейнере</p>
      </div>
    </section>
  </div>
</template>

<script setup>
// Этот компонент демонстрирует все принципы макета
// Никакой дополнительной логики не требуется - все работает через CSS
</script>

<style scoped>
/* Дополнительные стили для демонстрации */
.debug-info {
  position: fixed;
  bottom: 20px;
  right: 20px;
  background: rgba(0, 0, 0, 0.8);
  color: white;
  padding: 10px 15px;
  border-radius: 5px;
  font-family: monospace;
  font-size: 14px;
  z-index: 9999;
}
</style>