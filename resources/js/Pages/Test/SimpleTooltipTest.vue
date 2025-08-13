<template>
  <div class="p-8">
    <h1 class="text-2xl font-bold mb-6">Тест подсказки для услуги</h1>
    
    <!-- Простой тест Tooltip компонента -->
    <div class="mb-8 p-4 bg-gray-100 rounded">
      <h2 class="text-lg font-semibold mb-4">1. Базовый тест Tooltip</h2>
      <Tooltip 
        content="Это тестовая подсказка"
        trigger="click"
        placement="right"
      />
    </div>

    <!-- Тест с кастомным триггером -->
    <div class="mb-8 p-4 bg-gray-100 rounded">
      <h2 class="text-lg font-semibold mb-4">2. Тест с кастомной кнопкой</h2>
      <Tooltip 
        trigger="click"
        placement="right"
        :show-icon="false"
      >
        <template #trigger>
          <button 
            type="button"
            class="px-3 py-1 bg-blue-500 text-white rounded hover:bg-blue-600"
          >
            Нажми меня
          </button>
        </template>
        <template #content>
          <div>
            <h3 class="font-bold mb-2">Заголовок подсказки</h3>
            <p>Это кастомное содержимое подсказки</p>
          </div>
        </template>
      </Tooltip>
    </div>

    <!-- Тест как в ServiceItem -->
    <div class="mb-8 p-4 bg-white border rounded">
      <h2 class="text-lg font-semibold mb-4">3. Тест как в форме услуг</h2>
      
      <div class="flex items-center gap-2">
        <input type="checkbox" id="test-service" class="w-4 h-4">
        <label for="test-service" class="cursor-pointer">
          <span class="text-sm font-medium text-gray-700">Секс в машине</span>
        </label>
        
        <Tooltip 
          trigger="click"
          placement="right"
          :show-icon="false"
        >
          <template #trigger>
            <button 
              type="button"
              class="inline-flex items-center justify-center p-0.5 hover:bg-gray-100 rounded transition-colors focus:outline-none"
              @click.stop
            >
              <InfoIcon 
                :size="16" 
                color="rgba(0, 26, 52, 0.36)"
                hover-color="rgba(0, 26, 52, 0.6)"
              />
            </button>
          </template>
          <template #content>
            <div class="max-w-xs">
              <div class="font-semibold mb-2">Услуга в автомобиле</div>
              <div class="text-sm mb-3">
                Возможность проведения интимной встречи в автомобиле клиента или исполнителя.
              </div>
              <ul class="list-disc list-inside text-sm space-y-1">
                <li>Требуется подходящий автомобиль</li>
                <li>Выбор безопасного места</li>
                <li>Соблюдение конфиденциальности</li>
                <li>Дополнительная оплата за риски</li>
              </ul>
            </div>
          </template>
        </Tooltip>
      </div>
    </div>

    <!-- Отладочная информация -->
    <div class="mt-8 p-4 bg-yellow-50 border border-yellow-200 rounded">
      <h3 class="font-semibold text-yellow-800 mb-2">Отладочная информация:</h3>
      <ul class="text-sm text-yellow-700 space-y-1">
        <li>• Если подсказка не открывается - проверьте консоль браузера</li>
        <li>• Убедитесь что компонент Tooltip правильно импортирован</li>
        <li>• Проверьте что trigger="click" установлен</li>
        <li>• @click.stop должен быть только на кнопке, не на Tooltip</li>
      </ul>
    </div>
  </div>
</template>

<script setup lang="ts">
import Tooltip from '@/src/shared/ui/molecules/Tooltip/Tooltip.vue'
import InfoIcon from '@/src/shared/ui/atoms/InfoIcon/InfoIcon.vue'
</script>