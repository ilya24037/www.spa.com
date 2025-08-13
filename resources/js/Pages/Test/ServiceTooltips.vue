<template>
  <AppLayout title="Тест подсказок для услуг">
    <div class="max-w-6xl mx-auto p-6">
      <h1 class="text-2xl font-bold mb-6">Демонстрация подсказок для услуг</h1>
      
      <!-- Пример списка услуг с подсказкой -->
      <div class="bg-white rounded-lg shadow p-6 mb-6">
        <h2 class="text-lg font-semibold mb-4">Пример услуги с подсказкой</h2>
        
        <div class="space-y-4">
          <!-- Услуга с подсказкой -->
          <div class="flex items-center justify-between p-4 border rounded-lg hover:bg-gray-50">
            <div class="flex items-center gap-3">
              <input 
                type="checkbox" 
                id="sex_in_car_demo"
                class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500"
              >
              <div class="flex items-center">
                <label for="sex_in_car_demo" class="cursor-pointer">
                  <span class="text-sm font-medium text-gray-700">Секс в машине</span>
                </label>
                
                <!-- Подсказка (вне label чтобы не активировать чекбокс) -->
                <Tooltip 
                  :placement="'right'"
                  :show-icon="false"
                  trigger="click"
                  class="ml-1"
                  @click.stop
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
                    <div class="service-tooltip-content">
                      <div class="tooltip-title">
                        Услуга в автомобиле
                      </div>
                      <div class="tooltip-description">
                        Возможность проведения интимной встречи в автомобиле клиента или исполнителя.
                      </div>
                      <ul class="tooltip-items">
                        <li>Требуется подходящий автомобиль</li>
                        <li>Выбор безопасного места</li>
                        <li>Соблюдение конфиденциальности</li>
                        <li>Дополнительная оплата за риски</li>
                      </ul>
                      <div class="tooltip-examples">
                        <div class="examples-label">Варианты:</div>
                        <div class="examples-list">
                          В машине клиента, В машине исполнителя, На парковке, За городом
                        </div>
                      </div>
                      <div class="tooltip-note">
                        <InfoIcon :size="14" color="#f90" />
                        <span>Обсуждается индивидуально с клиентом</span>
                      </div>
                    </div>
                  </template>
                </Tooltip>
              </div>
            </div>
            
            <div class="flex items-center gap-2">
              <input 
                type="number" 
                placeholder="Доплата"
                class="w-24 px-2 py-1 text-sm border border-gray-300 rounded"
              >
              <span class="text-sm text-gray-500">₽</span>
            </div>
          </div>

          <!-- Обычная услуга без подсказки -->
          <div class="flex items-center justify-between p-4 border rounded-lg hover:bg-gray-50">
            <div class="flex items-center gap-3">
              <input 
                type="checkbox" 
                id="regular_service"
                class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500"
              >
              <label for="regular_service" class="flex items-center cursor-pointer">
                <span class="text-sm font-medium text-gray-700">Классический секс</span>
              </label>
            </div>
            
            <div class="flex items-center gap-2">
              <input 
                type="number" 
                placeholder="Доплата"
                class="w-24 px-2 py-1 text-sm border border-gray-300 rounded"
              >
              <span class="text-sm text-gray-500">₽</span>
            </div>
          </div>
        </div>
      </div>

      <!-- Информация о реализации -->
      <div class="bg-blue-50 border border-blue-200 rounded-lg p-6">
        <h3 class="text-lg font-semibold text-blue-900 mb-3">Как это работает</h3>
        <ul class="space-y-2 text-sm text-blue-800">
          <li>✓ Иконка с вопросительным знаком появляется рядом с названием услуги</li>
          <li>✓ Клик по иконке открывает подсказку (не влияет на чекбокс)</li>
          <li>✓ Повторный клик или клик на крестик закрывает подсказку</li>
          <li>✓ Подсказка содержит: заголовок, описание, список пунктов, варианты и примечание</li>
          <li>✓ Подсказки настраиваются в файле characteristicsInfo.ts</li>
          <li>✓ Можно добавить подсказки для любых других услуг</li>
        </ul>
      </div>
    </div>
  </AppLayout>
</template>

<script setup lang="ts">
import AppLayout from '@/src/shared/layouts/MainLayout/MainLayout.vue'
import Tooltip from '@/src/shared/ui/molecules/Tooltip/Tooltip.vue'
import InfoIcon from '@/src/shared/ui/atoms/InfoIcon/InfoIcon.vue'
</script>

<style scoped>
.service-tooltip-content {
  max-width: 320px;
}

.tooltip-title {
  font-weight: 600;
  font-size: 14px;
  margin-bottom: 8px;
  color: white;
}

.tooltip-description {
  font-size: 13px;
  line-height: 1.5;
  margin-bottom: 12px;
  color: rgba(255, 255, 255, 0.9);
}

.tooltip-items {
  margin: 0;
  padding: 0 0 0 20px;
  list-style: disc;
}

.tooltip-items li {
  font-size: 13px;
  line-height: 1.5;
  margin-bottom: 4px;
  color: rgba(255, 255, 255, 0.9);
}

.tooltip-examples {
  margin-top: 12px;
  padding-top: 12px;
  border-top: 1px solid rgba(255, 255, 255, 0.2);
}

.examples-label {
  font-size: 12px;
  font-weight: 600;
  margin-bottom: 4px;
  color: rgba(255, 255, 255, 0.7);
}

.examples-list {
  font-size: 13px;
  color: rgba(255, 255, 255, 0.9);
}

.tooltip-note {
  display: flex;
  align-items: flex-start;
  gap: 6px;
  margin-top: 12px;
  padding: 8px;
  background: rgba(255, 152, 0, 0.1);
  border-radius: 4px;
  font-size: 12px;
  line-height: 1.4;
  color: #ff9800;
}
</style>