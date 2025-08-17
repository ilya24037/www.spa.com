<!-- 
  GridControls - Управление отображением сетки
  Позволяет переключаться между видами и настраивать отображение
-->
<template>
  <div class="grid-controls">
    <!-- Левая часть: информация и сортировка -->
    <div class="grid-controls__info">
      <div class="grid-controls__count">
        <span class="text-sm text-gray-500">
          Показано {{ displayedCount }} из {{ totalCount }} {{ itemsLabel }}
        </span>
      </div>
      
      <div v-if="showSort" class="grid-controls__sort">
        <select 
          :value="currentSort"
          class="text-sm border border-gray-500 rounded px-3 py-1 bg-white"
          @change="handleSortChange"
        >
          <option value="popular">
            По популярности
          </option>
          <option value="price-asc">
            Цена: по возрастанию
          </option>
          <option value="price-desc">
            Цена: по убыванию
          </option>
          <option value="rating">
            По рейтингу
          </option>
          <option value="date">
            По дате
          </option>
        </select>
      </div>
    </div>
    
    <!-- Правая часть: переключатели вида -->
    <div class="grid-controls__actions">
      <!-- Переключатель вида: карта/сетка/список -->
      <div v-if="showViewToggle" class="view-toggle">
        <button
          :class="['view-toggle__button', { 'view-toggle__button--active': currentView === 'map' }]"
          :aria-label="'Вид карты'"
          title="Вид карты"
          @click="handleViewChange('map')"
        >
          <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M12 1.586l-4 4v12.828l4-4V1.586zM3.707 3.293A1 1 0 002 4v10a1 1 0 00.293.707L6 18.414V5.586L3.707 3.293zm14 2L14 1.586v12.828l2.293 2.293A1 1 0 0018 16V6a1 1 0 00-.293-.707z" clip-rule="evenodd" />
          </svg>
        </button>
        
        <button
          :class="['view-toggle__button', { 'view-toggle__button--active': currentView === 'grid' }]"
          :aria-label="'Сеточный вид'"
          title="Сеточный вид"
          @click="handleViewChange('grid')"
        >
          <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
            <path d="M5 3a2 2 0 00-2 2v2a2 2 0 002 2h2a2 2 0 002-2V5a2 2 0 00-2-2H5zM5 11a2 2 0 00-2 2v2a2 2 0 002 2h2a2 2 0 002-2v-2a2 2 0 00-2-2H5zM11 5a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V5zM11 13a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
          </svg>
        </button>
        
        <button
          :class="['view-toggle__button', { 'view-toggle__button--active': currentView === 'list' }]"
          :aria-label="'Списочный вид'"
          title="Списочный вид"
          @click="handleViewChange('list')"
        >
          <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M3 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1z" clip-rule="evenodd" />
          </svg>
        </button>
      </div>
      
      <!-- Переключатель плотности -->
      <div v-if="showDensityToggle" class="density-toggle">
        <button
          :class="['density-toggle__button', { 'density-toggle__button--active': currentDensity === 'comfortable' }]"
          title="Комфортный вид"
          @click="handleDensityChange('comfortable')"
        >
          <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
            <path d="M4 3a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V5a2 2 0 00-2-2H4z" />
          </svg>
        </button>
        
        <button
          :class="['density-toggle__button', { 'density-toggle__button--active': currentDensity === 'compact' }]"
          title="Компактный вид"
          @click="handleDensityChange('compact')"
        >
          <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
            <path d="M2 3a1 1 0 011-1h14a1 1 0 110 2H3a1 1 0 01-1-1zM2 7a1 1 0 011-1h14a1 1 0 110 2H3a1 1 0 01-1-1zM2 11a1 1 0 011-1h14a1 1 0 110 2H3a1 1 0 01-1-1zM2 15a1 1 0 011-1h14a1 1 0 110 2H3a1 1 0 01-1-1z" />
          </svg>
        </button>
      </div>
      
      <!-- Настройки колонок (только для десктопа) -->
      <div v-if="showColumnControl && isDesktop" class="column-control">
        <label class="column-control__label">
          Колонок:
          <select 
            :value="currentColumns"
            class="text-sm border border-gray-500 rounded px-2 py-1 ml-2 bg-white"
            @change="handleColumnsChange"
          >
            <option value="auto">Авто</option>
            <option value="2">2</option>
            <option value="3">3</option>
            <option value="4">4</option>
            <option value="5">5</option>
            <option value="6">6</option>
          </select>
        </label>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
/**
 * GridControls - Компонент управления сеткой
 * Предоставляет интерфейс для переключения видов отображения
 */

import { computed, ref, onMounted, onUnmounted } from 'vue'

// === ТИПЫ ===

export type GridView = 'map' | 'grid' | 'list'
export type GridDensity = 'comfortable' | 'compact'
export type GridSort = 'popular' | 'price-asc' | 'price-desc' | 'rating' | 'date'

interface Props {
  // Данные
  displayedCount: number
  totalCount: number
  itemsLabel?: string
  
  // Текущие значения
  currentView?: GridView
  currentDensity?: GridDensity
  currentSort?: GridSort
  currentColumns?: string | number
  
  // Функциональность
  showViewToggle?: boolean
  showDensityToggle?: boolean
  showColumnControl?: boolean
  showSort?: boolean
}

interface Emits {
  'view-change': [view: GridView]
  'density-change': [density: GridDensity]
  'sort-change': [sort: GridSort]
  'columns-change': [columns: string | number]
}

// === PROPS И EVENTS ===

const props = withDefaults(defineProps<Props>(), {
    itemsLabel: 'элементов',
    currentView: 'grid',
    currentDensity: 'comfortable',
    currentSort: 'popular',
    currentColumns: 'auto',
    showViewToggle: true,
    showDensityToggle: true,
    showColumnControl: true,
    showSort: true
})

const emit = defineEmits<Emits>()

// === РЕАКТИВНОЕ СОСТОЯНИЕ ===

const screenWidth = ref(window.innerWidth)

// === COMPUTED ===

const isDesktop = computed(() => screenWidth.value >= 1024)

// === МЕТОДЫ ===

function handleViewChange(view: GridView) {
    emit('view-change', view)
}

function handleDensityChange(density: GridDensity) {
    emit('density-change', density)
}

function handleSortChange(event: Event) {
    const target = event.target as HTMLSelectElement
    emit('sort-change', target.value as GridSort)
}

function handleColumnsChange(event: Event) {
    const target = event.target as HTMLSelectElement
    const value = target.value === 'auto' ? 'auto' : parseInt(target.value)
    emit('columns-change', value)
}

function handleResize() {
    screenWidth.value = window.innerWidth
}

// === ЖИЗНЕННЫЙ ЦИКЛ ===

onMounted(() => {
    window.addEventListener('resize', handleResize)
})

onUnmounted(() => {
    window.removeEventListener('resize', handleResize)
})
</script>

<style scoped>
/* === ОСНОВНЫЕ СТИЛИ === */

.grid-controls {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 1rem 0;
  border-bottom: 1px solid var(--border-muted, #f3f4f6);
  margin-bottom: 1.5rem;
  
  /* На мобильных стэкаем вертикально */
  @media (max-width: 639px) {
    flex-direction: column;
    gap: 1rem;
    align-items: stretch;
  }
}

.grid-controls__info {
  display: flex;
  align-items: center;
  gap: 1.5rem;
  
  @media (max-width: 639px) {
    justify-content: space-between;
  }
}

.grid-controls__actions {
  display: flex;
  align-items: center;
  gap: 1rem;
  
  @media (max-width: 639px) {
    justify-content: center;
  }
}

/* === ПЕРЕКЛЮЧАТЕЛЬ ВИДОВ === */

.view-toggle {
  display: flex;
  border-radius: 0.5rem;
  overflow: hidden;
  border: 1px solid var(--border-default, #e5e7eb);
  background: white;
}

.view-toggle__button {
  padding: 0.5rem 0.75rem;
  border: none;
  background: transparent;
  cursor: pointer;
  color: var(--text-secondary, #6b7280);
  transition: all 0.2s ease;
  
  display: flex;
  align-items: center;
  justify-content: center;
}

.view-toggle__button:hover {
  background: var(--color-gray-50, #f9fafb);
  color: var(--text-primary, #111827);
}

.view-toggle__button--active {
  background: var(--color-primary, #3b82f6);
  color: white;
}

.view-toggle__button--active:hover {
  background: var(--color-primary-hover, #2563eb);
}

/* === ПЕРЕКЛЮЧАТЕЛЬ ПЛОТНОСТИ === */

.density-toggle {
  display: flex;
  border-radius: 0.5rem;
  overflow: hidden;
  border: 1px solid var(--border-default, #e5e7eb);
  background: white;
}

.density-toggle__button {
  padding: 0.5rem;
  border: none;
  background: transparent;
  cursor: pointer;
  color: var(--text-secondary, #6b7280);
  transition: all 0.2s ease;
  
  display: flex;
  align-items: center;
  justify-content: center;
}

.density-toggle__button:hover {
  background: var(--color-gray-50, #f9fafb);
  color: var(--text-primary, #111827);
}

.density-toggle__button--active {
  background: var(--color-primary, #3b82f6);
  color: white;
}

/* === УПРАВЛЕНИЕ КОЛОНКАМИ === */

.column-control {
  display: flex;
  align-items: center;
}

.column-control__label {
  display: flex;
  align-items: center;
  font-size: 0.875rem;
  color: var(--text-secondary, #6b7280);
  white-space: nowrap;
}

/* === АДАПТИВНОСТЬ === */

@media (max-width: 639px) {
  .grid-controls__sort {
    flex: 1;
  }
  
  .grid-controls__sort select {
    width: 100%;
  }
  
  .column-control {
    display: none;
  }
}

@media (max-width: 480px) {
  .density-toggle {
    display: none;
  }
  
  .grid-controls__info {
    flex-direction: column;
    align-items: stretch;
    gap: 0.75rem;
  }
}

/* === ACCESSIBILITY === */

.view-toggle__button:focus,
.density-toggle__button:focus {
  outline: 2px solid var(--color-primary);
  outline-offset: 2px;
}

/* Высокий контраст */
@media (prefers-contrast: high) {
  .view-toggle,
  .density-toggle {
    border-width: 2px;
  }
  
  .view-toggle__button--active,
  .density-toggle__button--active {
    border: 2px solid var(--color-primary);
  }
}
</style>