<template>
  <FormSection
    title="Акции и предложения"
    hint="Добавьте специальные предложения для привлечения клиентов"
    :error="errors.gift || errors.new_client_discount"
  >
    <div class="promo-container">
      <!-- Скидка новым клиентам -->
      <FormField
        label="Скидка новым клиентам"
        hint="Процент скидки для первого визита"
        :error="errors.new_client_discount"
      >
        <div class="discount-input-wrapper">
          <div class="discount-input-group">
            <input
              v-model="localNewClientDiscount"
              @input="updateNewClientDiscount"
              type="number"
              min="0"
              max="50"
              step="5"
              placeholder="10"
              class="discount-input"
            />
            <span class="discount-suffix">%</span>
          </div>
          
          <!-- Быстрые скидки -->
          <div class="quick-discounts">
            <button
              v-for="discount in quickDiscounts"
              :key="discount"
              type="button"
              @click="setQuickDiscount(discount)"
              :class="[
                'quick-discount-btn',
                { 'active': localNewClientDiscount == discount }
              ]"
            >
              {{ discount }}%
            </button>
          </div>
        </div>
      </FormField>

      <!-- Подарок -->
      <FormField
        label="Подарок или бонус"
        hint="Дополнительная услуга или подарок для клиентов"
        :error="errors.gift"
      >
        <div class="gift-input-wrapper">
          <textarea
            v-model="localGift"
            @input="updateGift"
            rows="3"
            placeholder="Например: бесплатный чай, дополнительные 15 минут массажа, ароматерапия..."
            class="gift-textarea"
            maxlength="200"
          ></textarea>
          
          <div class="gift-counter">
            {{ localGift.length }}/200
          </div>

          <!-- Готовые варианты подарков -->
          <div class="gift-suggestions">
            <p class="suggestions-label">Популярные подарки:</p>
            <div class="suggestion-buttons">
              <button
                v-for="gift in giftSuggestions"
                :key="gift"
                type="button"
                @click="addGiftSuggestion(gift)"
                class="suggestion-btn"
              >
                {{ gift }}
              </button>
            </div>
          </div>
        </div>
      </FormField>

      <!-- Предварительный просмотр -->
      <div v-if="hasPromoContent" class="promo-preview">
        <div class="preview-header">
          <span class="preview-icon">👁️</span>
          <span class="preview-title">Как это будет выглядеть:</span>
        </div>
        
        <div class="preview-card">
          <div v-if="localNewClientDiscount" class="discount-badge">
            -{{ localNewClientDiscount }}% новым клиентам
          </div>
          
          <div v-if="localGift" class="gift-badge">
            🎁 {{ localGift }}
          </div>
        </div>
      </div>
    </div>

    <!-- Советы по акциям -->
    <div class="promo-tips">
      <div class="tip-icon">💡</div>
      <div class="tip-content">
        <p class="tip-title">Советы по акциям</p>
        <ul class="tip-list">
          <li>Скидка 10-20% увеличивает количество обращений на 30%</li>
          <li>Подарки создают дополнительную ценность для клиентов</li>
          <li>Акции особенно эффективны для новых мастеров</li>
        </ul>
      </div>
    </div>
  </FormSection>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import FormSection from '@/Components/UI/Forms/FormSection.vue'
import FormField from '@/Components/UI/Forms/FormField.vue'

const props = defineProps({
  newClientDiscount: { type: [String, Number], default: '' },
  gift: { type: String, default: '' },
  errors: { type: Object, default: () => ({}) }
})

const emit = defineEmits([
  'update:newClientDiscount',
  'update:gift'
])

// Локальное состояние
const localNewClientDiscount = ref(String(props.newClientDiscount || ''))
const localGift = ref(props.gift || '')

// Отслеживание изменений пропсов
watch(() => props.newClientDiscount, (newValue) => { 
  localNewClientDiscount.value = String(newValue || '') 
})
watch(() => props.gift, (newValue) => { 
  localGift.value = newValue || '' 
})

// Методы обновления
const updateNewClientDiscount = () => {
  emit('update:newClientDiscount', localNewClientDiscount.value)
}

const updateGift = () => {
  emit('update:gift', localGift.value)
}

// Быстрые скидки
const quickDiscounts = [5, 10, 15, 20, 25]

const setQuickDiscount = (discount) => {
  localNewClientDiscount.value = String(discount)
  updateNewClientDiscount()
}

// Варианты подарков
const giftSuggestions = [
  'Бесплатный чай или кофе',
  'Дополнительные 15 минут',
  'Ароматерапия',
  'Расслабляющая музыка',
  'Консультация по здоровью',
  'Скидка на следующий визит'
]

const addGiftSuggestion = (suggestion) => {
  if (localGift.value) {
    localGift.value += ', ' + suggestion
  } else {
    localGift.value = suggestion
  }
  updateGift()
}

// Computed
const hasPromoContent = computed(() => {
  return localNewClientDiscount.value || localGift.value
})
</script>

<style scoped>
.promo-container {
  display: grid;
  gap: 2rem;
}

.discount-input-wrapper {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.discount-input-group {
  position: relative;
  display: flex;
  align-items: center;
  max-width: 200px;
}

.discount-input {
  flex: 1;
  padding: 0.75rem 1rem;
  border: 1px solid #d1d5db;
  border-radius: 0.5rem;
  font-size: 1rem;
  transition: border-color 0.2s;
}

.discount-input:focus {
  outline: none;
  border-color: #3b82f6;
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.discount-suffix {
  position: absolute;
  right: 1rem;
  color: #6b7280;
  font-weight: 500;
  pointer-events: none;
}

.quick-discounts {
  display: flex;
  flex-wrap: wrap;
  gap: 0.5rem;
}

.quick-discount-btn {
  padding: 0.5rem 1rem;
  background: #f3f4f6;
  border: 1px solid #d1d5db;
  border-radius: 0.375rem;
  font-size: 0.875rem;
  color: #374151;
  cursor: pointer;
  transition: all 0.2s;
}

.quick-discount-btn:hover {
  background: #e5e7eb;
  border-color: #9ca3af;
}

.quick-discount-btn.active {
  background: #3b82f6;
  border-color: #3b82f6;
  color: white;
}

.gift-input-wrapper {
  position: relative;
}

.gift-textarea {
  width: 100%;
  padding: 0.75rem 1rem;
  border: 1px solid #d1d5db;
  border-radius: 0.5rem;
  font-size: 1rem;
  resize: vertical;
  min-height: 80px;
  transition: border-color 0.2s;
}

.gift-textarea:focus {
  outline: none;
  border-color: #3b82f6;
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.gift-counter {
  position: absolute;
  bottom: 0.5rem;
  right: 0.75rem;
  font-size: 0.75rem;
  color: #6b7280;
  background: white;
  padding: 0.25rem;
}

.gift-suggestions {
  margin-top: 1rem;
}

.suggestions-label {
  font-size: 0.875rem;
  font-weight: 500;
  color: #374151;
  margin-bottom: 0.5rem;
}

.suggestion-buttons {
  display: flex;
  flex-wrap: wrap;
  gap: 0.5rem;
}

.suggestion-btn {
  padding: 0.375rem 0.75rem;
  background: #f9fafb;
  border: 1px solid #e5e7eb;
  border-radius: 0.375rem;
  font-size: 0.75rem;
  color: #374151;
  cursor: pointer;
  transition: all 0.2s;
}

.suggestion-btn:hover {
  background: #f3f4f6;
  border-color: #d1d5db;
}

.promo-preview {
  margin-top: 1.5rem;
  padding: 1rem;
  background: #f8fafc;
  border: 1px solid #e2e8f0;
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
  color: #475569;
}

.preview-card {
  display: flex;
  flex-wrap: wrap;
  gap: 0.5rem;
}

.discount-badge {
  display: inline-flex;
  align-items: center;
  padding: 0.375rem 0.75rem;
  background: #ef4444;
  color: white;
  font-size: 0.75rem;
  font-weight: 600;
  border-radius: 0.375rem;
}

.gift-badge {
  display: inline-flex;
  align-items: center;
  padding: 0.375rem 0.75rem;
  background: #10b981;
  color: white;
  font-size: 0.75rem;
  font-weight: 500;
  border-radius: 0.375rem;
}

.promo-tips {
  display: flex;
  gap: 0.75rem;
  margin-top: 1.5rem;
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
  .promo-container {
    gap: 1.5rem;
  }
  
  .quick-discounts,
  .suggestion-buttons {
    justify-content: center;
  }
  
  .preview-card {
    justify-content: center;
  }
}
</style>