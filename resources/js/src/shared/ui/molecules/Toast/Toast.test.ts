/**
 * Unit тесты для Toast компонента
 */

import { describe, it, expect, beforeEach, vi } from 'vitest'
import { mount } from '@vue/test-utils'
import Toast from './Toast.vue'
import type { ToastProps } from './Toast.types'

// Mock setTimeout и clearTimeout
vi.useFakeTimers()

describe('Toast Component', () => {
  beforeEach(() => {
    vi.clearAllTimers()
  })

  it('рендерится с базовыми props', () => {
    const wrapper = mount(Toast, {
      props: {
        message: 'Тестовое сообщение'
      }
    })

    expect(wrapper.find('.toast-message').text()).toBe('Тестовое сообщение')
    expect(wrapper.find('.toast-container').classes()).toContain('toast-info')
  })

  it('показывает заголовок когда передан', () => {
    const wrapper = mount(Toast, {
      props: {
        title: 'Заголовок',
        message: 'Сообщение'
      }
    })

    expect(wrapper.find('.toast-title').text()).toBe('Заголовок')
  })

  it('применяет правильные классы для типов', () => {
    const types: Array<{ type: ToastProps['type'], class: string }> = [
      { type: 'success', class: 'toast-success' },
      { type: 'error', class: 'toast-error' },
      { type: 'warning', class: 'toast-warning' },
      { type: 'info', class: 'toast-info' }
    ]

    types.forEach(({ type, class: expectedClass }) => {
      const wrapper = mount(Toast, {
        props: {
          type,
          message: 'Тест'
        }
      })

      expect(wrapper.find('.toast-container').classes()).toContain(expectedClass)
    })
  })

  it('применяет правильные классы позиций', () => {
    const positions: Array<{ position: ToastProps['position'], class: string }> = [
      { position: 'top-left', class: 'toast-top-left' },
      { position: 'top-center', class: 'toast-top-center' },
      { position: 'top-right', class: 'toast-top-right' },
      { position: 'bottom-left', class: 'toast-bottom-left' },
      { position: 'bottom-center', class: 'toast-bottom-center' },
      { position: 'bottom-right', class: 'toast-bottom-right' }
    ]

    positions.forEach(({ position, class: expectedClass }) => {
      const wrapper = mount(Toast, {
        props: {
          position,
          message: 'Тест'
        }
      })

      expect(wrapper.find('.toast-container').classes()).toContain(expectedClass)
    })
  })

  it('показывает кнопку закрытия когда closable=true', () => {
    const wrapper = mount(Toast, {
      props: {
        message: 'Тест',
        closable: true
      }
    })

    expect(wrapper.find('.toast-close').exists()).toBe(true)
  })

  it('скрывает кнопку закрытия когда closable=false', () => {
    const wrapper = mount(Toast, {
      props: {
        message: 'Тест',
        closable: false
      }
    })

    expect(wrapper.find('.toast-close').exists()).toBe(false)
  })

  it('эмитит close при клике на кнопку закрытия', async () => {
    const wrapper = mount(Toast, {
      props: {
        message: 'Тест',
        closable: true
      }
    })

    await wrapper.find('.toast-close').trigger('click')

    expect(wrapper.emitted('close')).toBeTruthy()
    expect(wrapper.emitted('close')).toHaveLength(1)
  })

  it('показывает прогресс-бар когда duration > 0', () => {
    const wrapper = mount(Toast, {
      props: {
        message: 'Тест',
        duration: 5000
      }
    })

    expect(wrapper.find('.toast-progress').exists()).toBe(true)
    expect(wrapper.find('.toast-progress-bar').exists()).toBe(true)
  })

  it('скрывает прогресс-бар когда duration = 0', () => {
    const wrapper = mount(Toast, {
      props: {
        message: 'Тест',
        duration: 0
      }
    })

    expect(wrapper.find('.toast-progress').exists()).toBe(false)
  })

  it('автоматически закрывается через duration', () => {
    const wrapper = mount(Toast, {
      props: {
        message: 'Тест',
        duration: 1000
      }
    })

    // Проверяем что таймер установлен
    expect(vi.getTimerCount()).toBe(1)

    // Пропускаем время
    vi.advanceTimersByTime(1000)

    // Проверяем что close эмитится
    expect(wrapper.emitted('close')).toBeTruthy()
  })

  it('не устанавливает таймер когда duration = 0', () => {
    mount(Toast, {
      props: {
        message: 'Тест',
        duration: 0
      }
    })

    expect(vi.getTimerCount()).toBe(0)
  })

  it('очищает таймер при размонтировании', () => {
    const wrapper = mount(Toast, {
      props: {
        message: 'Тест',
        duration: 5000
      }
    })

    expect(vi.getTimerCount()).toBe(1)

    wrapper.unmount()

    expect(vi.getTimerCount()).toBe(0)
  })

  it('поддерживает методы show/close через defineExpose', () => {
    const wrapper = mount(Toast, {
      props: {
        message: 'Тест'
      }
    })

    const vm = wrapper.vm as any

    expect(typeof vm.show).toBe('function')
    expect(typeof vm.close).toBe('function')

    // Тестируем close метод
    vm.close()
    expect(wrapper.emitted('close')).toBeTruthy()
  })

  it('правильно применяет aria атрибуты', () => {
    const wrapper = mount(Toast, {
      props: {
        message: 'Тест'
      }
    })

    const container = wrapper.find('.toast-container')
    expect(container.attributes('role')).toBe('alert')
    expect(container.attributes('aria-live')).toBe('polite')
  })

  it('применяет правильные цвета для типов в прогресс-баре', () => {
    const wrapper = mount(Toast, {
      props: {
        type: 'success',
        message: 'Тест',
        duration: 1000
      }
    })

    const container = wrapper.find('.toast-container')
    expect(container.classes()).toContain('toast-success')
  })
})