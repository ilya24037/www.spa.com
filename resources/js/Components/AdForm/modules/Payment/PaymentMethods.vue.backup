<template>
  <FormSection
    title="Способы оплаты"
    hint="Укажите удобные для вас способы оплаты услуг"
    :error="errors.payment_methods"
  >
    <div class="payment-container">
      <!-- Основные способы оплаты -->
      <FormField
        label="Способы оплаты"
        hint="Выберите все подходящие варианты"
        :error="errors.payment_methods"
      >
        <div class="payment-methods-grid">
          <label
            v-for="method in paymentMethods"
            :key="method.value"
            class="payment-method-wrapper"
          >
            <input
              type="checkbox"
              :value="method.value"
              :checked="isMethodSelected(method.value)"
              @change="togglePaymentMethod(method.value, $event.target.checked)"
              class="payment-checkbox"
            />
            <div class="payment-method-card">
              <div class="method-icon">{{ method.icon }}</div>
              <div class="method-content">
                <div class="method-title">{{ method.title }}</div>
                <div class="method-description">{{ method.description }}</div>
                <div v-if="method.fee" class="method-fee">{{ method.fee }}</div>
              </div>
              <div v-if="method.popular" class="popular-badge">Популярно</div>
            </div>
          </label>
        </div>
      </FormField>

      <!-- Настройки предоплаты -->
      <FormField
        label="Предоплата"
        hint="Требуете ли вы предоплату от клиентов"
      >
        <div class="prepayment-options">
          <label class="prepayment-option">
            <input
              type="radio"
              name="prepayment"
              value="none"
              :checked="prepaymentType === 'none'"
              @change="updatePrepaymentType('none')"
              class="prepayment-radio"
            />
            <div class="prepayment-content">
              <div class="prepayment-title">Без предоплаты</div>
              <div class="prepayment-description">Оплата после оказания услуги</div>
            </div>
          </label>
          
          <label class="prepayment-option">
            <input
              type="radio"
              name="prepayment"
              value="partial"
              :checked="prepaymentType === 'partial'"
              @change="updatePrepaymentType('partial')"
              class="prepayment-radio"
            />
            <div class="prepayment-content">
              <div class="prepayment-title">Частичная предоплата</div>
              <div class="prepayment-description">Часть суммы заранее, остальное после</div>
            </div>
          </label>
          
          <label class="prepayment-option">
            <input
              type="radio"
              name="prepayment"
              value="full"
              :checked="prepaymentType === 'full'"
              @change="updatePrepaymentType('full')"
              class="prepayment-radio"
            />
            <div class="prepayment-content">
              <div class="prepayment-title">Полная предоплата</div>
              <div class="prepayment-description">100% оплата до начала сеанса</div>
            </div>
          </label>
        </div>

        <!-- Детали предоплаты -->
        <div v-if="prepaymentType !== 'none'" class="prepayment-details">
          <div v-if="prepaymentType === 'partial'" class="prepayment-amount">
            <label class="amount-label">Размер предоплаты:</label>
            <div class="amount-input-group">
              <input
                v-model="prepaymentAmount"
                @input="updatePrepaymentAmount"
                type="number"
                min="10"
                max="90"
                step="5"
                placeholder="30"
                class="amount-input"
              />
              <span class="amount-suffix">%</span>
            </div>
          </div>
          
          <div class="prepayment-note">
            <label class="note-label">Комментарий к предоплате:</label>
            <textarea
              v-model="prepaymentNote"
              @input="updatePrepaymentNote"
              rows="2"
              placeholder="Например: предоплата через СБП, возврат при отмене за 24 часа..."
              class="note-textarea"
              maxlength="200"
            ></textarea>
            <div class="note-counter">{{ prepaymentNote.length }}/200</div>
          </div>
        </div>
      </FormField>

      <!-- Быстрые наборы -->
      <FormField
        label="Быстрые наборы"
        hint="Выберите готовый набор способов оплаты"
      >
        <div class="quick-sets">
          <button
            v-for="set in paymentSets"
            :key="set.name"
            @click="applyPaymentSet(set)"
            type="button"
            class="quick-set-btn"
          >
            <div class="set-name">{{ set.name }}</div>
            <div class="set-methods">{{ set.methods.join(', ') }}</div>
          </button>
        </div>
      </FormField>

      <!-- Предварительный просмотр -->
      <div v-if="hasSelectedMethods" class="payment-preview">
        <div class="preview-header">
          <span class="preview-icon">💳</span>
          <span class="preview-title">Способы оплаты в объявлении:</span>
        </div>
        
        <div class="preview-methods">
          <div
            v-for="methodValue in selectedMethods"
            :key="methodValue"
            class="preview-method"
          >
            <span class="preview-method-icon">{{ getMethodIcon(methodValue) }}</span>
            <span class="preview-method-name">{{ getMethodTitle(methodValue) }}</span>
          </div>
        </div>
        
        <div v-if="prepaymentType !== 'none'" class="preview-prepayment">
          <div class="preview-prepayment-title">Условия предоплаты:</div>
          <div class="preview-prepayment-text">
            {{ getPrepaymentText() }}
          </div>
          <div v-if="prepaymentNote" class="preview-prepayment-note">
            {{ prepaymentNote }}
          </div>
        </div>
      </div>

      <!-- Советы по оплате -->
      <div class="payment-tips">
        <div class="tip-icon">💡</div>
        <div class="tip-content">
          <p class="tip-title">Советы по способам оплаты</p>
          <ul class="tip-list">
            <li>Наличные остаются самым популярным способом (60% клиентов)</li>
            <li>СБП и карты удобны для молодой аудитории</li>
            <li>Предоплата снижает количество отмен на 40%</li>
            <li>Несколько способов оплаты увеличивают конверсию</li>
          </ul>
        </div>
      </div>
    </div>
  </FormSection>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import FormSection from '@/Components/UI/Forms/FormSection.vue'
import FormField from '@/Components/UI/Forms/FormField.vue'

const props = defineProps({
  paymentMethods: { type: [Array, String], default: () => [] },
  errors: { type: Object, default: () => ({}) }
})

const emit = defineEmits(['update:paymentMethods'])

// Локальное состояние
const selectedMethods = ref([])
const prepaymentType = ref('none')
const prepaymentAmount = ref('30')
const prepaymentNote = ref('')

// Инициализация данных
const initializeData = () => {
  let methods = props.paymentMethods
  
  if (typeof methods === 'string') {
    try {
      methods = JSON.parse(methods) || []
    } catch (e) {
      methods = []
    }
  }
  
  selectedMethods.value = Array.isArray(methods) ? [...methods] : []
}

// Отслеживание изменений пропсов
watch(() => props.paymentMethods, () => {
  initializeData()
}, { immediate: true })

// Способы оплаты
const paymentMethods = [
  {
    value: 'cash',
    title: 'Наличные',
    description: 'Оплата наличными при встрече',
    icon: '💵',
    popular: true
  },
  {
    value: 'card',
    title: 'Банковская карта',
    description: 'Оплата картой через терминал',
    icon: '💳',
    popular: true
  },
  {
    value: 'sbp',
    title: 'СБП (QR-код)',
    description: 'Быстрые платежи по QR-коду',
    icon: '📱',
    popular: true
  },
  {
    value: 'transfer',
    title: 'Банковский перевод',
    description: 'Перевод на карту или счет',
    icon: '🏦'
  },
  {
    value: 'yandex_money',
    title: 'ЮMoney',
    description: 'Оплата через ЮMoney',
    icon: '🟡'
  },
  {
    value: 'qiwi',
    title: 'QIWI',
    description: 'Оплата через QIWI кошелек',
    icon: '🟠'
  },
  {
    value: 'cryptocurrency',
    title: 'Криптовалюта',
    description: 'Оплата криптовалютой',
    icon: '₿'
  }
]

// Готовые наборы
const paymentSets = [
  {
    name: 'Базовый',
    methods: ['Наличные', 'СБП'],
    values: ['cash', 'sbp']
  },
  {
    name: 'Стандартный',
    methods: ['Наличные', 'Карта', 'СБП'],
    values: ['cash', 'card', 'sbp']
  },
  {
    name: 'Максимальный',
    methods: ['Наличные', 'Карта', 'СБП', 'Перевод'],
    values: ['cash', 'card', 'sbp', 'transfer']
  },
  {
    name: 'Только безнал',
    methods: ['Карта', 'СБП', 'Перевод'],
    values: ['card', 'sbp', 'transfer']
  }
]

// Методы
const isMethodSelected = (method) => {
  return selectedMethods.value.includes(method)
}

const togglePaymentMethod = (method, checked) => {
  if (checked) {
    if (!selectedMethods.value.includes(method)) {
      selectedMethods.value.push(method)
    }
  } else {
    const index = selectedMethods.value.indexOf(method)
    if (index > -1) {
      selectedMethods.value.splice(index, 1)
    }
  }
  updatePaymentMethods()
}

const updatePrepaymentType = (type) => {
  prepaymentType.value = type
  updatePaymentMethods()
}

const updatePrepaymentAmount = () => {
  updatePaymentMethods()
}

const updatePrepaymentNote = () => {
  updatePaymentMethods()
}

const applyPaymentSet = (set) => {
  selectedMethods.value = [...set.values]
  updatePaymentMethods()
}

const updatePaymentMethods = () => {
  const data = {
    methods: [...selectedMethods.value],
    prepayment: {
      type: prepaymentType.value,
      amount: prepaymentAmount.value,
      note: prepaymentNote.value
    }
  }
  
  emit('update:paymentMethods', data)
}

const getMethodIcon = (value) => {
  const method = paymentMethods.find(m => m.value === value)
  return method ? method.icon : '💳'
}

const getMethodTitle = (value) => {
  const method = paymentMethods.find(m => m.value === value)
  return method ? method.title : value
}

const getPrepaymentText = () => {
  switch (prepaymentType.value) {
    case 'partial':
      return `Предоплата ${prepaymentAmount.value}%, остальное после оказания услуги`
    case 'full':
      return 'Полная предоплата до начала сеанса'
    default:
      return ''
  }
}

// Computed
const hasSelectedMethods = computed(() => {
  return selectedMethods.value.length > 0
})
</script>

<style scoped>
.payment-container {
  display: flex;
  flex-direction: column;
  gap: 2rem;
}

.payment-methods-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
  gap: 1rem;
}

.payment-method-wrapper {
  cursor: pointer;
  display: block;
}

.payment-checkbox {
  position: absolute;
  opacity: 0;
  pointer-events: none;
}

.payment-method-card {
  position: relative;
  display: flex;
  align-items: center;
  gap: 1rem;
  padding: 1rem;
  border: 2px solid #e5e7eb;
  border-radius: 0.75rem;
  transition: all 0.2s;
  background: white;
}

.payment-checkbox:checked + .payment-method-card {
  border-color: #10b981;
  background: #ecfdf5;
}

.payment-method-card:hover {
  border-color: #9ca3af;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
}

.method-icon {
  font-size: 1.5rem;
  flex-shrink: 0;
}

.method-content {
  flex: 1;
}

.method-title {
  font-weight: 600;
  color: #1f2937;
  margin-bottom: 0.25rem;
}

.method-description {
  font-size: 0.875rem;
  color: #6b7280;
  margin-bottom: 0.25rem;
}

.method-fee {
  font-size: 0.75rem;
  color: #ef4444;
  font-weight: 500;
}

.popular-badge {
  position: absolute;
  top: -0.5rem;
  right: -0.5rem;
  background: #f59e0b;
  color: white;
  font-size: 0.75rem;
  font-weight: 600;
  padding: 0.25rem 0.5rem;
  border-radius: 0.375rem;
}

.prepayment-options {
  display: grid;
  gap: 0.75rem;
}

.prepayment-option {
  display: flex;
  align-items: center;
  gap: 1rem;
  padding: 1rem;
  border: 1px solid #e5e7eb;
  border-radius: 0.5rem;
  cursor: pointer;
  transition: all 0.2s;
}

.prepayment-option:hover {
  border-color: #9ca3af;
  background: #f9fafb;
}

.prepayment-radio:checked + .prepayment-content {
  color: #3b82f6;
}

.prepayment-radio {
  width: 1.25rem;
  height: 1.25rem;
  flex-shrink: 0;
}

.prepayment-content {
  flex: 1;
}

.prepayment-title {
  font-weight: 500;
  color: #1f2937;
  margin-bottom: 0.25rem;
}

.prepayment-description {
  font-size: 0.875rem;
  color: #6b7280;
}

.prepayment-details {
  margin-top: 1rem;
  padding: 1rem;
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 0.5rem;
  display: grid;
  gap: 1rem;
}

.prepayment-amount {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.amount-label {
  font-size: 0.875rem;
  font-weight: 500;
  color: #374151;
  min-width: 150px;
}

.amount-input-group {
  position: relative;
  display: flex;
  align-items: center;
}

.amount-input {
  width: 80px;
  padding: 0.5rem 0.75rem;
  border: 1px solid #d1d5db;
  border-radius: 0.375rem;
  font-size: 0.875rem;
}

.amount-suffix {
  position: absolute;
  right: 0.75rem;
  color: #6b7280;
  font-weight: 500;
  pointer-events: none;
}

.prepayment-note {
  position: relative;
}

.note-label {
  display: block;
  font-size: 0.875rem;
  font-weight: 500;
  color: #374151;
  margin-bottom: 0.5rem;
}

.note-textarea {
  width: 100%;
  padding: 0.75rem 1rem;
  border: 1px solid #d1d5db;
  border-radius: 0.375rem;
  font-size: 0.875rem;
  resize: vertical;
}

.note-counter {
  position: absolute;
  bottom: 0.5rem;
  right: 0.75rem;
  font-size: 0.75rem;
  color: #6b7280;
  background: white;
  padding: 0.25rem;
}

.quick-sets {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 0.75rem;
}

.quick-set-btn {
  display: flex;
  flex-direction: column;
  align-items: center;
  padding: 1rem;
  background: white;
  border: 1px solid #e5e7eb;
  border-radius: 0.5rem;
  cursor: pointer;
  transition: all 0.2s;
}

.quick-set-btn:hover {
  border-color: #3b82f6;
  background: #eff6ff;
}

.set-name {
  font-weight: 500;
  color: #1f2937;
  margin-bottom: 0.5rem;
}

.set-methods {
  font-size: 0.875rem;
  color: #6b7280;
  text-align: center;
}

.payment-preview {
  padding: 1rem;
  background: #f0fdf4;
  border: 1px solid #22c55e;
  border-radius: 0.5rem;
}

.preview-header {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  margin-bottom: 0.75rem;
}

.preview-icon {
  font-size: 1rem;
}

.preview-title {
  font-size: 0.875rem;
  font-weight: 500;
  color: #166534;
}

.preview-methods {
  display: flex;
  flex-wrap: wrap;
  gap: 0.5rem;
  margin-bottom: 0.75rem;
}

.preview-method {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.375rem 0.75rem;
  background: #dcfce7;
  border-radius: 0.375rem;
}

.preview-method-icon {
  font-size: 0.875rem;
}

.preview-method-name {
  font-size: 0.875rem;
  color: #166534;
}

.preview-prepayment {
  padding-top: 0.75rem;
  border-top: 1px solid #bbf7d0;
}

.preview-prepayment-title {
  font-size: 0.875rem;
  font-weight: 500;
  color: #166534;
  margin-bottom: 0.5rem;
}

.preview-prepayment-text {
  font-size: 0.875rem;
  color: #166534;
  margin-bottom: 0.5rem;
}

.preview-prepayment-note {
  font-size: 0.875rem;
  color: #166534;
  font-style: italic;
}

.payment-tips {
  display: flex;
  gap: 0.75rem;
  padding: 1rem;
  background: #ecfdf5;
  border: 1px solid #10b981;
  border-radius: 0.5rem;
}

.tip-icon {
  font-size: 1.25rem;
  flex-shrink: 0;
}

.tip-content {
  flex: 1;
}

.tip-title {
  font-weight: 600;
  color: #065f46;
  margin-bottom: 0.5rem;
}

.tip-list {
  font-size: 0.875rem;
  color: #065f46;
  margin: 0;
  padding-left: 1.25rem;
}

.tip-list li {
  margin-bottom: 0.25rem;
}

@media (max-width: 768px) {
  .payment-methods-grid,
  .quick-sets {
    grid-template-columns: 1fr;
  }
  
  .prepayment-amount {
    flex-direction: column;
    align-items: flex-start;
    gap: 0.5rem;
  }
  
  .amount-label {
    min-width: auto;
  }
}
</style>