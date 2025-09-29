<template>
  <div class="characteristic-tooltip-wrapper">
    <div class="characteristic-label">
      <span class="characteristic-name">{{ name }}</span>
      <Tooltip 
        :placement="tooltipPlacement"
        :show-icon="false"
      >
        <template #trigger>
          <InfoIcon 
            :size="16" 
            color="rgba(0, 26, 52, 0.36)"
            hover-color="rgba(0, 26, 52, 0.6)"
          />
        </template>
        <template #content>
          <div class="characteristic-tooltip-content">
            <!-- Изображение сверху -->
            <div v-if="image" class="tooltip-image mb-3">
              <img :src="image" :alt="title || name" />
            </div>
            <!-- Заголовок с описанием -->
            <div v-if="title" class="tooltip-title">
              {{ title }}
            </div>
            
            <!-- Основное описание -->
            <div v-if="description" class="tooltip-description">
              {{ description }}
            </div>
            
            <!-- Список пунктов если есть -->
            <ul v-if="items && items.length > 0" class="tooltip-items">
              <li v-for="(item, index) in items" :key="index">
                {{ item }}
              </li>
            </ul>
            
            <!-- Примеры если есть -->
            <div v-if="examples && examples.length > 0" class="tooltip-examples">
              <div class="examples-label">Примеры:</div>
              <div class="examples-list">
                <span v-for="(example, index) in examples" :key="index" class="example-item">
                  {{ example }}<span v-if="index < examples.length - 1">, </span>
                </span>
              </div>
            </div>
            
            <!-- Дополнительная информация -->
            <div v-if="note" class="tooltip-note">
              <InfoIcon :size="14" color="#f90" />
              <span>{{ note }}</span>
            </div>
          </div>
        </template>
      </Tooltip>
    </div>
    
    <!-- Значение характеристики -->
    <div v-if="value" class="characteristic-value">
      {{ formattedValue }}
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import Tooltip from '@/src/shared/ui/molecules/Tooltip/Tooltip.vue'
import InfoIcon from '@/src/shared/ui/atoms/InfoIcon/InfoIcon.vue'

interface Props {
  // Основные свойства
  name: string           // Название характеристики
  value?: string | number | boolean  // Значение характеристики
  
  // Контент подсказки
  title?: string         // Заголовок подсказки
  description?: string   // Основное описание
  image?: string        // URL изображения
  items?: string[]      // Список пунктов
  examples?: string[]   // Примеры
  note?: string         // Важное примечание
  
  // Настройки отображения
  tooltipPlacement?: 'top' | 'bottom' | 'left' | 'right' | 'auto'
  valueFormat?: 'text' | 'boolean' | 'duration' | 'price'
}

const props = withDefaults(defineProps<Props>(), {
  tooltipPlacement: 'auto',
  valueFormat: 'text'
})

// Форматирование значения в зависимости от типа
const formattedValue = computed(() => {
  if (props.value === undefined || props.value === null) {
    return '—'
  }
  
  switch (props.valueFormat) {
    case 'boolean':
      return props.value ? 'Да' : 'Нет'
      
    case 'duration':
      if (typeof props.value === 'number') {
        if (props.value < 60) {
          return `${props.value} мин`
        } else {
          const hours = Math.floor(props.value / 60)
          const minutes = props.value % 60
          return minutes > 0 ? `${hours} ч ${minutes} мин` : `${hours} ч`
        }
      }
      return String(props.value)
      
    case 'price':
      if (typeof props.value === 'number') {
        return new Intl.NumberFormat('ru-RU', {
          style: 'currency',
          currency: 'RUB',
          minimumFractionDigits: 0
        }).format(props.value)
      }
      return String(props.value)
      
    default:
      return String(props.value)
  }
})
</script>

<style scoped>
.characteristic-tooltip-wrapper {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 12px 0;
  border-bottom: 1px solid #f0f2f5;
}

.characteristic-tooltip-wrapper:last-child {
  border-bottom: none;
}

.characteristic-label {
  display: flex;
  align-items: center;
  gap: 6px;
  flex: 1;
  min-width: 0;
}

.characteristic-name {
  font-size: 14px;
  color: #6c757d;
  line-height: 1.4;
}

.characteristic-value {
  font-size: 14px;
  color: #001a34;
  font-weight: 500;
  text-align: right;
  margin-left: 16px;
}

/* Стили для контента подсказки */
.characteristic-tooltip-content {
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

.tooltip-image {
  margin: 12px -12px;
  text-align: center;
  background: white;
  padding: 8px;
}

.tooltip-image img {
  max-width: 100%;
  height: auto;
  border-radius: 4px;
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

.example-item {
  display: inline;
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

.tooltip-note svg {
  flex-shrink: 0;
  margin-top: 1px;
}

/* Мобильная адаптация */
@media (max-width: 640px) {
  .characteristic-tooltip-wrapper {
    flex-direction: column;
    align-items: flex-start;
    gap: 4px;
  }
  
  .characteristic-value {
    margin-left: 0;
    text-align: left;
    font-size: 15px;
  }
  
  .characteristic-tooltip-content {
    max-width: 280px;
  }
}
</style>