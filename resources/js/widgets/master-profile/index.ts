/**
 * Изолированный виджет MasterProfile с ленивой загрузкой
 * Принципы Ozon: самодостаточность + производительность
 */

import { defineAsyncComponent } from 'vue'
import { BaseWidget } from '../../shared/classes/BaseWidget'
import type { MasterProfileWidgetProps } from './types/masterProfile.types'
import { logger } from '@/src/shared/utils/logger'

// Создаем класс виджета по принципу Ozon
class MasterProfileWidget extends BaseWidget {
  constructor() {
    super({
      name: 'master-profile',
      delay: 100,
      timeout: 5000,
      retryCount: 3
    })
  }

  /**
   * Создает ленивый компонент виджета
   */
  public createComponent() {
    return defineAsyncComponent(
      () => import('./MasterProfile.vue')
    )
  }

  /**
   * Проверка совместимости props
   */
  public validateProps(props: MasterProfileWidgetProps): boolean {
    return !!(props.masterId && props.masterId > 0)
  }
}

// Создаем синглтон экземпляр виджета (может быть использован для глобального управления)
// const masterProfileWidget = new MasterProfileWidget()

// Экспортируем ленивый компонент
export default defineAsyncComponent({
  loader: () => import('./MasterProfile.vue'),
  
  loadingComponent: {
    template: `
      <div class="master-profile-widget-loading animate-pulse bg-white rounded-lg shadow-sm p-6">
        <div class="flex items-start gap-4">
          <div class="w-16 h-16 bg-gray-200 rounded-full"></div>
          <div class="flex-1 space-y-2">
            <div class="h-6 bg-gray-200 rounded w-1/2"></div>
            <div class="h-4 bg-gray-200 rounded w-3/4"></div>
            <div class="h-4 bg-gray-200 rounded w-1/4"></div>
          </div>
        </div>
        <div class="mt-6 grid grid-cols-2 gap-4">
          <div class="h-24 bg-gray-200 rounded"></div>
          <div class="h-24 bg-gray-200 rounded"></div>
        </div>
      </div>
    `
  },
  
  errorComponent: {
    template: `
      <div class="master-profile-widget-error bg-red-50 border border-red-200 rounded-lg p-6">
        <div class="text-red-800 font-medium mb-2">
          Ошибка загрузки профиля мастера
        </div>
        <div class="text-red-600 text-sm mb-4">
          Попробуйте обновить страницу или вернуться позже
        </div>
        <button 
          @click="$emit('retry')"
          class="bg-red-600 text-white px-4 py-2 rounded text-sm hover:bg-red-700 transition-colors"
        >
          Попробовать еще раз
        </button>
      </div>
    `
  },
  
  delay: 100,
  timeout: 5000,
  
  onError(error, retry, fail, attempts) {
    logger.error(`[MasterProfileWidget] Load failed (attempt ${attempts}):`, error)
    
    if (attempts <= 3) {
      // Retry with exponential backoff
      setTimeout(retry, Math.min(300 * attempts, 3000))
    } else {
      fail()
    }
  }
})

// Дополнительные экспорты для прямого использования
export { MasterProfileWidget }
export type { MasterProfileWidgetProps } from './types/masterProfile.types'
export { useMasterProfileWidget } from './composables/useMasterProfileWidget'
export { MasterProfileWidgetApi } from './api/masterProfileApi'