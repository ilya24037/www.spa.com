<template>
  <tr class="service-item border-b border-gray-100 hover:bg-gray-50">
    <!-- Чекбокс -->
    <td class="py-3 px-2 w-10 align-top">
      <div class="flex items-center h-12">
        <input 
          :id="checkboxId"
          v-model="enabled"
          :name="`service_enabled_${service.id}`"
          type="checkbox"
          :aria-label="`Включить услугу ${service.name}`"
          class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 focus:ring-2"
        >
      </div>
    </td>

    <!-- Название услуги -->
    <td class="py-3 px-2 align-top">
      <div class="flex items-center h-12">
        <label 
          :for="checkboxId" 
          class="text-sm font-medium text-gray-700 cursor-pointer inline-flex items-center"
        >
          {{ service.name }}
        </label>
        <!-- Подсказка для услуги если есть (вне label чтобы не активировать чекбокс) -->
        <Tooltip 
          v-if="hasTooltip"
          :placement="'right'"
          :show-icon="false"
          :offset="8"
          trigger="click"
          class="ml-1 inline-flex"
          @click.stop
        >
          <template #trigger>
            <button 
              type="button"
              class="inline-flex items-center justify-center p-1 hover:bg-gray-100 rounded transition-colors focus:outline-none"
              aria-label="Информация об услуге"
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
              <!-- Изображение сверху -->
              <div v-if="serviceInfo" class="tooltip-image mb-3">
                <img :src="imageSrc" :alt="serviceInfo.title || service.name" @error="onImageError" />
              </div>
              <div v-if="serviceInfo?.title" class="tooltip-title">
                {{ serviceInfo.title }}
              </div>
              <div v-if="serviceInfo?.description" class="tooltip-description">
                {{ serviceInfo.description }}
              </div>
              <ul v-if="serviceInfo?.items && serviceInfo.items.length > 0" class="tooltip-items">
                <li v-for="(item, index) in serviceInfo.items" :key="index">
                  {{ item }}
                </li>
              </ul>
              <div v-if="serviceInfo?.examples && serviceInfo.examples.length > 0" class="tooltip-examples">
                <div class="examples-label">Варианты:</div>
                <div class="examples-list">
                  <span v-for="(example, index) in serviceInfo.examples" :key="index" class="example-item">
                    {{ example }}<span v-if="index < serviceInfo.examples.length - 1">, </span>
                  </span>
                </div>
              </div>
              <div v-if="serviceInfo?.note" class="tooltip-note">
                <InfoIcon :size="14" color="#f90" />
                <span>{{ serviceInfo.note }}</span>
              </div>
            </div>
          </template>
        </Tooltip>
      </div>
    </td>

    <!-- Доплата и Комментарий -->
    <td colspan="2" class="py-3 px-2 align-top">
      <div v-if="enabled" class="flex items-center gap-2 h-12">
        <!-- Кнопка + для цены -->
        <button 
          @click="increasePrice"
          type="button"
          class="w-10 h-10 border-2 border-gray-300 flex items-center justify-center text-gray-500 hover:text-blue-600 hover:bg-blue-50 hover:border-gray-400 rounded-lg transition-all flex-shrink-0 focus:outline-none"
          title="Добавить 1000₽"
        >
          <svg width="20" height="20" viewBox="0 0 16 16" fill="none">
            <path d="M8 3v10M3 8h10" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
          </svg>
        </button>
        
        <!-- Поле цены -->
        <BaseInput
          :id="priceInputId"
          v-model="serviceData.price"
          :name="`service_price_${service.id}`"
          type="number"
          :clearable="true"
          suffix="₽"
          placeholder="0"
          :min="0"
          :max="999999"
          :step="1000"
          :aria-label="`Доплата за ${service.name}`"
          @update:modelValue="emitUpdate"
          class="max-w-[160px] h-10"
        />
        
        <!-- Поле комментария или кнопка добавить -->
        <template v-if="!showComment">
          <button 
            @click="showComment = true"
            type="button"
            class="flex-1 h-10 px-4 text-sm text-blue-600 hover:text-blue-800 hover:bg-blue-50 border-2 border-dashed border-blue-300 rounded-lg transition-all focus:outline-none focus:ring-2 focus:ring-blue-500 flex items-center justify-center"
          >
            + Добавить комментарий
          </button>
        </template>
        <template v-else>
          <BaseInput
            :id="commentInputId"
            v-model="serviceData.comment"
            :name="`service_comment_${service.id}`"
            type="text"
            :clearable="true"
            placeholder="Максимум 100 символов"
            :maxlength="100"
            :show-counter="true"
            :aria-label="`Комментарий к ${service.name}`"
            @update:modelValue="emitUpdate"
            class="flex-1 h-10"
          />
          <button 
            @click="hideComment"
            type="button"
            class="w-10 h-10 border-2 border-gray-200 hover:bg-gray-50 rounded-lg flex items-center justify-center transition-colors focus:outline-none"
            title="Скрыть комментарий"
            aria-label="Скрыть комментарий"
          >
            <svg width="16" height="16" viewBox="0 0 16 16" fill="none">
              <path d="M12 4L4 12M4 4L12 12" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
          </button>
        </template>
      </div>
    </td>
  </tr>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import Tooltip from '@/src/shared/ui/molecules/Tooltip/Tooltip.vue'
import InfoIcon from '@/src/shared/ui/atoms/InfoIcon/InfoIcon.vue'
import BaseInput from '@/src/shared/ui/atoms/BaseInput/BaseInput.vue'
import { getCharacteristicInfo, hasServiceTooltip } from '@/src/shared/config/characteristicsInfo'
import { useId } from '@/src/shared/composables/useId'

const props = defineProps({
  service: {
    type: Object,
    required: true
  },
  modelValue: {
    type: Object,
    default: () => ({ enabled: false, price_comment: '' })
  }
})

const emit = defineEmits(['update:modelValue'])

// Генерируем уникальные ID для полей формы
const checkboxId = useId(`service-${props.service.id}`)
const priceInputId = useId(`service-price-${props.service.id}`)
const commentInputId = useId(`service-comment-${props.service.id}`)

// Получаем информацию о подсказке для услуги
const serviceInfo = computed(() => {
  // Проверяем есть ли информация для этой услуги
  return getCharacteristicInfo(props.service.id)
})

// Проверяем нужно ли показывать подсказку
const hasTooltip = computed(() => {
  // Показываем подсказку для всех услуг, у которых есть информация
  return hasServiceTooltip(props.service.id)
})

// Картинка с fallback, если пользовательский файл отсутствует
const defaultImage = '/images/services/car-service.svg'
const imageSrc = computed(() => serviceInfo.value?.image || defaultImage)
const onImageError = (event) => {
  const img = event?.target
  if (img && img.src.indexOf(defaultImage) === -1) {
    img.src = defaultImage
  }
}

// Локальное состояние - разделяем price и comment
const serviceData = ref({
  enabled: props.modelValue?.enabled || false,
  price: props.modelValue?.price || '',
  comment: props.modelValue?.comment || ''
})

// Состояние показа комментария - если есть комментарий, показываем сразу
const showComment = ref(!!serviceData.value.comment)

// Парсим price_comment если нужно (для обратной совместимости)
if (!serviceData.value.price && !serviceData.value.comment && props.modelValue?.price_comment) {
  // Пробуем извлечь цену из начала строки
  const match = props.modelValue.price_comment.match(/^(\d+)\s*(.*)$/)
  if (match) {
    serviceData.value.price = match[1]
    serviceData.value.comment = match[2] || ''
  } else {
    // Если не число в начале - всё в комментарий
    serviceData.value.comment = props.modelValue.price_comment
  }
  
  // Если есть комментарий после парсинга - показываем поле
  if (serviceData.value.comment) {
    showComment.value = true
  }
}

// Computed для enabled
const enabled = computed({
  get: () => serviceData.value.enabled,
  set: (value) => {
    serviceData.value.enabled = value
    if (!value) {
      // Очищаем данные при отключении
      serviceData.value.price = ''
      serviceData.value.comment = ''
    }
    emitUpdate()
  }
})

// Функция увеличения цены на 1000
const increasePrice = () => {
  const currentPrice = parseInt(serviceData.value.price) || 0
  const newPrice = currentPrice + 1000
  // Ограничиваем максимальным значением 9999999 (7 цифр)
  serviceData.value.price = Math.min(newPrice, 9999999)
  emitUpdate()
}

// Функция сброса цены
const resetPrice = () => {
  serviceData.value.price = ''
  emitUpdate()
}

// Функция скрытия комментария (только если комментарий пустой)
const hideComment = () => {
  if (!serviceData.value.comment) {
    showComment.value = false
  }
}

// Функция сброса комментария
const resetComment = () => {
  serviceData.value.comment = ''
  emitUpdate()
}

// Функция для emit при изменениях пользователем
const emitUpdate = () => {
  // Формируем price_comment для обратной совместимости
  let price_comment = ''

  if (serviceData.value.price && serviceData.value.comment) {
    // Есть и цена и комментарий
    price_comment = `${serviceData.value.price} ${serviceData.value.comment}`
  } else if (serviceData.value.price) {
    // Только цена
    price_comment = serviceData.value.price.toString()
  } else if (serviceData.value.comment) {
    // Только комментарий
    price_comment = serviceData.value.comment
  }

  emit('update:modelValue', { 
    enabled: serviceData.value.enabled,
    price: serviceData.value.price || '',
    comment: serviceData.value.comment || '',
    price_comment // для обратной совместимости
  })
}

// Отслеживаем изменения из родителя
watch(() => props.modelValue, (newValue) => {
  if (newValue) {
    serviceData.value.enabled = newValue.enabled || false
    serviceData.value.price = newValue.price || ''
    serviceData.value.comment = newValue.comment || ''

    // Если нет price и comment, но есть price_comment - парсим
    if (!newValue.price && !newValue.comment && newValue.price_comment) {
      const match = newValue.price_comment.match(/^(\d+)\s*(.*)$/)
      if (match) {
        serviceData.value.price = match[1]
        serviceData.value.comment = match[2] || ''
      } else {
        serviceData.value.comment = newValue.price_comment
      }
    }

    // Если появился комментарий - показываем поле
    if (serviceData.value.comment) {
      showComment.value = true
    }
  }
}, { deep: true })

// Сбрасываем состояние показа комментария при отключении услуги
watch(() => serviceData.value.enabled, (newValue) => {
  if (!newValue) {
    showComment.value = false
  }
})
</script>

<style scoped>
/* Скрытие элементов визуально, но оставляя доступными для скринридеров */
.sr-only {
  position: absolute;
  width: 1px;
  height: 1px;
  padding: 0;
  margin: -1px;
  overflow: hidden;
  clip: rect(0, 0, 0, 0);
  white-space: nowrap;
  border-width: 0;
}
.service-item {
  transition: background-color 0.2s;
}

.service-item:last-child {
  border-bottom: none;
}

/* Анимация появления полей */
.service-item input[type="text"],
.service-item input[type="number"] {
  animation: fadeIn 0.3s ease-out;
}

@keyframes fadeIn {
  from {
    opacity: 0;
  }
  to {
    opacity: 1;
  }
}

/* Убираем стрелки у input[type="number"] */
input[type="number"]::-webkit-inner-spin-button,
input[type="number"]::-webkit-outer-spin-button {
  -webkit-appearance: none;
  margin: 0;
}

input[type="number"] {
  -moz-appearance: textfield;
}

/* Стили для подсказки услуги */
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

.tooltip-image {
  margin: 12px -12px;
  text-align: center;
  padding: 8px;
}

.tooltip-image img {
  max-width: 100%;
  height: auto;
  max-height: 150px;
  border-radius: 4px;
  object-fit: cover;
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