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
          this.searchQuery = newValue.title || ''
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
        // TODO: Интеграция с Yandex Geocoder API
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
          },
          {
            id: 3,
            title: `${query}, Новосибирск`,
            subtitle: 'Новосибирск, Россия',
            coords: { lat: 55.0084, lng: 82.9357 }
          }
        ]
        
        // Имитация задержки API
        await new Promise(resolve => setTimeout(resolve, 200))
        
        this.suggestions = mockSuggestions
        this.showSuggestions = true
        this.activeIndex = -1
      } catch (error) {
        console.error('Ошибка поиска адресов:', error)
        this.suggestions = []
      } finally {
        this.loading = false
      }
    },
    
    selectSuggestion(suggestion) {
      this.selectedAddress = suggestion
      this.searchQuery = suggestion.title
      this.suggestions = []
      this.showSuggestions = false
      this.activeIndex = -1
      
      this.$emit('update:modelValue', suggestion)
      this.$emit('select', suggestion)
    },
    
    handleFocus() {
      if (this.suggestions.length > 0) {
        this.showSuggestions = true
      }
    },
    
    handleBlur() {
      // Задержка для обработки клика по элементу
      setTimeout(() => {
        this.showSuggestions = false
      }, 200)
    },
    
    handleKeydown(event) {
      if (!this.showSuggestions || this.suggestions.length === 0) return
      
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
    }
  }
}
</script>

<style scoped>
.geo-suggest {
  position: relative;
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
  padding: 12px 45px 12px 12px;
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
  width: 20px;
  height: 20px;
  color: #666;
  pointer-events: none;
}

.loading-spinner {
  position: absolute;
  right: 40px;
  top: 50%;
  transform: translateY(-50%);
  width: 16px;
  height: 16px;
  color: #007bff;
  animation: spin 1s linear infinite;
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
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
  z-index: 1000;
  max-height: 300px;
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

.suggestion-item:last-child {
  border-radius: 0 0 8px 8px;
}

.suggestion-icon {
  flex-shrink: 0;
  width: 20px;
  height: 20px;
  color: #666;
  margin-right: 12px;
}

.suggestion-content {
  flex: 1;
  min-width: 0;
}

.suggestion-title {
  font-size: 14px;
  font-weight: 500;
  color: #333;
  margin-bottom: 2px;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.suggestion-subtitle {
  font-size: 12px;
  color: #666;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
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
</style>
