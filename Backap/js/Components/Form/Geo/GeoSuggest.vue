<template>
  <div class="geo-suggest">
    <label v-if="label" :for="inputId" class="geo-label">{{ label }}</label>
    
    <div class="suggest-container">
      <div class="input-wrapper">
        <input
          :id="inputId"
          :value="searchQuery"
          :placeholder="placeholder"
          :disabled="disabled"
          :class="['suggest-input', { 'error': error }]"
          @input="handleInput"
          @focus="handleFocus"
          @blur="handleBlur"
          @keydown="handleKeydown"
          ref="inputRef"
        />
        
        <div class="input-icon">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
            <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/>
            <circle cx="12" cy="10" r="3"/>
          </svg>
        </div>
        
        <div v-if="loading" class="loading-spinner">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
            <path d="M21 12a9 9 0 11-6.219-8.56"/>
          </svg>
        </div>
      </div>
      
      <!-- Выпадающий список -->
      <div 
        v-if="showSuggestions && suggestions.length > 0"
        class="suggestions-dropdown"
      >
        <div 
          v-for="(suggestion, index) in suggestions"
          :key="suggestion.id"
          :class="['suggestion-item', { 'active': index === activeIndex }]"
          @click="selectSuggestion(suggestion)"
          @mouseenter="activeIndex = index"
        >
          <div class="suggestion-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
              <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/>
              <circle cx="12" cy="10" r="3"/>
            </svg>
          </div>
          <div class="suggestion-content">
            <div class="suggestion-title">{{ suggestion.title }}</div>
            <div class="suggestion-subtitle">{{ suggestion.subtitle }}</div>
          </div>
        </div>
      </div>
    </div>
    
    <!-- Выбранный адрес -->
    <div v-if="selectedAddress" class="selected-address">
      <div class="address-info">
        <div class="address-icon">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
            <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/>
            <circle cx="12" cy="10" r="3"/>
          </svg>
        </div>
        <div class="address-text">
          <div class="address-title">{{ selectedAddress.title }}</div>
          <div class="address-coords">
            {{ selectedAddress.coords.lat.toFixed(6) }}, {{ selectedAddress.coords.lng.toFixed(6) }}
          </div>
        </div>
        <button 
          type="button"
          class="clear-btn"
          @click="clearSelection"
          title="Очистить"
        >
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
            <line x1="18" y1="6" x2="6" y2="18"/>
            <line x1="6" y1="6" x2="18" y2="18"/>
          </svg>
        </button>
      </div>
    </div>
    
    <!-- Ошибки -->
    <div v-if="error" class="error-message">{{ error }}</div>
    <div v-if="hint && !error" class="hint-text">{{ hint }}</div>
  </div>
</template>

<script>
export default {
  name: 'GeoSuggest',
  props: {
    modelValue: {
      type: Object,
      default: null
    },
    label: {
      type: String,
      default: 'Адрес'
    },
    placeholder: {
      type: String,
      default: 'Введите адрес'
    },
    disabled: {
      type: Boolean,
      default: false
    },
    error: {
      type: String,
      default: ''
    },
    hint: {
      type: String,
      default: 'Укажите точный адрес для клиентов'
    },
    apiKey: {
      type: String,
      default: ''
    }
  },
  emits: ['update:modelValue', 'select'],
  data() {
    return {
      searchQuery: '',
      suggestions: [],
      selectedAddress: null,
      showSuggestions: false,
      activeIndex: -1,
      loading: false,
      debounceTimer: null
    }
  },
  computed: {
    inputId() {
      return `geo-suggest-${Date.now()}`
    }
  },
  watch: {
    modelValue: {
      handler(newValue) {
        if (newValue) {
          this.selectedAddress = newValue
          this.searchQuery = newValue.title
        } else {
          this.selectedAddress = null
          this.searchQuery = ''
        }
      },
      immediate: true
    }
  },
  methods: {
    handleInput(event) {
      const query = event.target.value
      this.searchQuery = query
      this.selectedAddress = null
      
      if (query.length < 3) {
        this.suggestions = []
        this.showSuggestions = false
        return
      }
      
      // Дебаунс для API запросов
      clearTimeout(this.debounceTimer)
      this.debounceTimer = setTimeout(() => {
        this.searchAddresses(query)
      }, 300)
    },
    
    async searchAddresses(query) {
      this.loading = true
      this.showSuggestions = false
      
      try {
        // Здесь будет интеграция с Yandex Geocoder API
        // Пока используем моковые данные
        const mockSuggestions = [
          {
            id: 1,
            title: `${query}, Москва`,
            subtitle: 'Москва, Россия',
            coords: { lat: 55.7558, lng: 37.6176 }
          },
          {
            id: 2,
            title: `${query}, Санкт-Петербург`,
            subtitle: 'Санкт-Петербург, Россия',
            coords: { lat: 59.9311, lng: 30.3609 }
          }
        ]
        
        this.suggestions = mockSuggestions
        this.showSuggestions = true
        this.activeIndex = -1
      } catch (error) {
        console.error('Ошибка поиска адресов:', error)
      } finally {
        this.loading = false
      }
    },
    
    selectSuggestion(suggestion) {
      this.selectedAddress = suggestion
      this.searchQuery = suggestion.title
      this.showSuggestions = false
      this.suggestions = []
      
      this.$emit('update:modelValue', suggestion)
      this.$emit('select', suggestion)
    },
    
    clearSelection() {
      this.selectedAddress = null
      this.searchQuery = ''
      this.showSuggestions = false
      this.suggestions = []
      
      this.$emit('update:modelValue', null)
      this.$refs.inputRef?.focus()
    },
    
    handleFocus() {
      if (this.suggestions.length > 0) {
        this.showSuggestions = true
      }
    },
    
    handleBlur() {
      // Небольшая задержка для обработки клика по предложениям
      setTimeout(() => {
        this.showSuggestions = false
      }, 200)
    },
    
    handleKeydown(event) {
      if (!this.showSuggestions) return
      
      switch (event.key) {
        case 'ArrowDown':
          event.preventDefault()
          this.activeIndex = Math.min(this.activeIndex + 1, this.suggestions.length - 1)
          break
        case 'ArrowUp':
          event.preventDefault()
          this.activeIndex = Math.max(this.activeIndex - 1, -1)
          break
        case 'Enter':
          event.preventDefault()
          if (this.activeIndex >= 0) {
            this.selectSuggestion(this.suggestions[this.activeIndex])
          }
          break
        case 'Escape':
          this.showSuggestions = false
          this.activeIndex = -1
          break
      }
    },
    
    // Метод для интеграции с Yandex Geocoder API
    async searchWithYandex(query) {
      if (!this.apiKey) {
        console.warn('API ключ для Yandex Geocoder не указан')
        return []
      }
      
      // Здесь будет реальный API запрос
      // const response = await fetch(`https://geocode-maps.yandex.ru/1.x/?apikey=${this.apiKey}&format=json&geocode=${encodeURIComponent(query)}`)
      // const data = await response.json()
      // return this.parseYandexResponse(data)
    },
    
    parseYandexResponse(data) {
      // Парсинг ответа от Yandex API
      return []
    }
  }
}
</script>

<style scoped>
.geo-suggest {
  margin-bottom: 16px;
}

.geo-label {
  display: block;
  font-size: 14px;
  font-weight: 500;
  color: #333;
  margin-bottom: 8px;
}

.suggest-container {
  position: relative;
}

.input-wrapper {
  position: relative;
}

.suggest-input {
  width: 100%;
  padding: 12px 40px 12px 12px;
  border: 1px solid #ddd;
  border-radius: 8px;
  font-size: 16px;
  transition: border-color 0.2s, box-shadow 0.2s;
}

.suggest-input:focus {
  outline: none;
  border-color: #007bff;
  box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.1);
}

.suggest-input.error {
  border-color: #dc3545;
}

.suggest-input:disabled {
  background-color: #f8f9fa;
  cursor: not-allowed;
}

.input-icon {
  position: absolute;
  right: 12px;
  top: 50%;
  transform: translateY(-50%);
  color: #666;
  width: 20px;
  height: 20px;
}

.input-icon svg {
  width: 100%;
  height: 100%;
}

.loading-spinner {
  position: absolute;
  right: 12px;
  top: 50%;
  transform: translateY(-50%);
  width: 20px;
  height: 20px;
  color: #007bff;
  animation: spin 1s linear infinite;
}

.loading-spinner svg {
  width: 100%;
  height: 100%;
}

@keyframes spin {
  from { transform: translateY(-50%) rotate(0deg); }
  to { transform: translateY(-50%) rotate(360deg); }
}

.suggestions-dropdown {
  position: absolute;
  top: 100%;
  left: 0;
  right: 0;
  background: white;
  border: 1px solid #ddd;
  border-top: none;
  border-radius: 0 0 8px 8px;
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
  z-index: 1000;
  max-height: 200px;
  overflow-y: auto;
}

.suggestion-item {
  display: flex;
  align-items: center;
  padding: 12px;
  cursor: pointer;
  transition: background-color 0.2s;
}

.suggestion-item:hover,
.suggestion-item.active {
  background-color: #f8f9fa;
}

.suggestion-icon {
  width: 20px;
  height: 20px;
  color: #666;
  margin-right: 12px;
  flex-shrink: 0;
}

.suggestion-icon svg {
  width: 100%;
  height: 100%;
}

.suggestion-content {
  flex: 1;
  min-width: 0;
}

.suggestion-title {
  font-size: 14px;
  color: #333;
  margin-bottom: 2px;
}

.suggestion-subtitle {
  font-size: 12px;
  color: #666;
}

.selected-address {
  margin-top: 8px;
  padding: 12px;
  background: #f8f9fa;
  border-radius: 8px;
  border: 1px solid #e9ecef;
}

.address-info {
  display: flex;
  align-items: center;
  gap: 12px;
}

.address-icon {
  width: 20px;
  height: 20px;
  color: #007bff;
  flex-shrink: 0;
}

.address-icon svg {
  width: 100%;
  height: 100%;
}

.address-text {
  flex: 1;
  min-width: 0;
}

.address-title {
  font-size: 14px;
  color: #333;
  margin-bottom: 2px;
}

.address-coords {
  font-size: 12px;
  color: #666;
}

.clear-btn {
  width: 24px;
  height: 24px;
  background: none;
  border: none;
  color: #666;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 4px;
  transition: all 0.2s;
}

.clear-btn:hover {
  background: #e9ecef;
  color: #dc3545;
}

.clear-btn svg {
  width: 14px;
  height: 14px;
}

.error-message {
  color: #dc3545;
  font-size: 12px;
  margin-top: 4px;
}

.hint-text {
  color: #666;
  font-size: 12px;
  margin-top: 4px;
}

/* Адаптивность */
@media (max-width: 768px) {
  .suggestions-dropdown {
    max-height: 150px;
  }
  
  .suggestion-item {
    padding: 10px;
  }
}
</style> 