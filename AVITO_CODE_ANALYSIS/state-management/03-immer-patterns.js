/**
 * 🔄 IMMER PATTERNS
 * Паттерны работы с иммутабельными данными из Avito
 * Файл извлечен из: avito-favorite-collections-integration.js
 */

import { produce, createDraft, finishDraft } from 'immer'

/**
 * Базовый паттерн использования Immer (из кода Avito)
 * Позволяет писать "мутирующий" код для иммутабельных обновлений
 */
export function updateStateWithImmer(currentState, updater) {
  return produce(currentState, draft => {
    // В draft можно "мутировать" напрямую
    updater(draft)
  })
}

/**
 * Пример сложного обновления вложенных структур
 * Паттерн из Avito для работы с коллекциями
 */
export function updateNestedCollection(state, collectionId, itemId, updates) {
  return produce(state, draft => {
    const collection = draft.collections.find(c => c.id === collectionId)
    if (collection) {
      const item = collection.items.find(i => i.id === itemId)
      if (item) {
        // Прямое обновление вложенного объекта
        Object.assign(item, updates)
        item.updatedAt = Date.now()
      }
    }
  })
}

/**
 * Паттерн для батчевых обновлений (из Avito)
 */
export function batchUpdateItems(state, updates) {
  return produce(state, draft => {
    updates.forEach(update => {
      const index = draft.items.findIndex(item => item.id === update.id)
      if (index !== -1) {
        draft.items[index] = {
          ...draft.items[index],
          ...update.changes,
          lastModified: Date.now()
        }
      }
    })
  })
}

/**
 * Паттерн для работы с draft напрямую (продвинутый)
 * Используется в Avito для оптимизации
 */
export class ImmerStateManager {
  constructor(initialState) {
    this.baseState = initialState
    this.draft = null
  }
  
  startUpdate() {
    this.draft = createDraft(this.baseState)
    return this.draft
  }
  
  applyUpdate(updater) {
    if (!this.draft) {
      this.startUpdate()
    }
    updater(this.draft)
  }
  
  finishUpdate() {
    if (this.draft) {
      this.baseState = finishDraft(this.draft)
      this.draft = null
      return this.baseState
    }
    return this.baseState
  }
  
  cancelUpdate() {
    this.draft = null
  }
}

/**
 * Утилиты для проверки состояния (из Avito)
 */
export const stateUtils = {
  // Проверка, является ли объект draft
  isDraft: (obj) => {
    return obj && obj['__$immer_state']
  },
  
  // Безопасное получение оригинального объекта
  getOriginal: (draft) => {
    if (stateUtils.isDraft(draft)) {
      return draft['__$immer_state'].base
    }
    return draft
  },
  
  // Проверка на изменения
  hasChanges: (draft) => {
    if (stateUtils.isDraft(draft)) {
      return draft['__$immer_state'].modified
    }
    return false
  }
}

/**
 * Пример использования в Redux reducer
 */
export const exampleReducer = (state = initialState, action) => {
  return produce(state, draft => {
    switch (action.type) {
      case 'ADD_ITEM':
        draft.items.push(action.payload)
        break
        
      case 'UPDATE_ITEM':
        const item = draft.items.find(i => i.id === action.payload.id)
        if (item) {
          Object.assign(item, action.payload.updates)
        }
        break
        
      case 'DELETE_ITEM':
        const index = draft.items.findIndex(i => i.id === action.payload)
        if (index !== -1) {
          draft.items.splice(index, 1)
        }
        break
        
      default:
        // Ничего не делаем, Immer вернет оригинальный state
    }
  })
}