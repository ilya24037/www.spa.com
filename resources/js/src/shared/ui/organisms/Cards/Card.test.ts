/**
 * Unit тесты для Card компонента
 */

import { describe, it, expect, vi } from 'vitest'
import { mount } from '@vue/test-utils'
import Card from './Card.vue'
import type { CardProps } from './Card.types'

describe('Card Component', () => {
  it('рендерится с базовыми props', () => {
    const wrapper = mount(Card, {
      slots: {
        default: 'Контент карточки'
      }
    })

    expect(wrapper.find('.card').exists()).toBe(true)
    expect(wrapper.find('.card-body').text()).toBe('Контент карточки')
    expect(wrapper.find('.card').classes()).toContain('card--default')
    expect(wrapper.find('.card').classes()).toContain('card--medium')
  })

  it('показывает заголовок', () => {
    const wrapper = mount(Card, {
      props: {
        title: 'Заголовок карточки'
      },
      slots: {
        default: 'Контент'
      }
    })

    expect(wrapper.find('.card-title').text()).toBe('Заголовок карточки')
    expect(wrapper.find('.card-header').exists()).toBe(true)
  })

  it('применяет правильные варианты', () => {
    const variants: CardProps['variant'][] = ['default', 'bordered', 'elevated', 'outlined']

    variants.forEach(variant => {
      const wrapper = mount(Card, {
        props: { variant },
        slots: { default: 'Контент' }
      })

      expect(wrapper.find('.card').classes()).toContain(`card--${variant}`)
    })
  })

  it('применяет правильные размеры', () => {
    const sizes: CardProps['size'][] = ['small', 'medium', 'large']

    sizes.forEach(size => {
      const wrapper = mount(Card, {
        props: { size },
        slots: { default: 'Контент' }
      })

      expect(wrapper.find('.card').classes()).toContain(`card--${size}`)
    })
  })

  it('показывает hoverable эффект', () => {
    const wrapper = mount(Card, {
      props: {
        hoverable: true
      },
      slots: {
        default: 'Контент'
      }
    })

    expect(wrapper.find('.card').classes()).toContain('card--hoverable')
  })

  it('показывает состояние загрузки', () => {
    const wrapper = mount(Card, {
      props: {
        loading: true
      },
      slots: {
        default: 'Контент'
      }
    })

    expect(wrapper.find('.card').classes()).toContain('card--loading')
    expect(wrapper.find('.card-loading').exists()).toBe(true)
    expect(wrapper.find('.card-spinner').exists()).toBe(true)
    expect(wrapper.find('.card').attributes('aria-busy')).toBe('true')
  })

  it('показывает заблокированное состояние', () => {
    const wrapper = mount(Card, {
      props: {
        disabled: true
      },
      slots: {
        default: 'Контент'
      }
    })

    expect(wrapper.find('.card').classes()).toContain('card--disabled')
    expect(wrapper.find('.card').attributes('aria-disabled')).toBe('true')
  })

  it('не показывает hoverable когда disabled', () => {
    const wrapper = mount(Card, {
      props: {
        hoverable: true,
        disabled: true
      },
      slots: {
        default: 'Контент'
      }
    })

    expect(wrapper.find('.card').classes()).not.toContain('card--hoverable')
  })

  it('не показывает hoverable когда loading', () => {
    const wrapper = mount(Card, {
      props: {
        hoverable: true,
        loading: true
      },
      slots: {
        default: 'Контент'
      }
    })

    expect(wrapper.find('.card').classes()).not.toContain('card--hoverable')
  })

  it('эмитит click событие', async () => {
    const wrapper = mount(Card, {
      slots: {
        default: 'Контент'
      }
    })

    await wrapper.find('.card').trigger('click')

    expect(wrapper.emitted('click')).toBeTruthy()
    expect(wrapper.emitted('click')).toHaveLength(1)
  })

  it('не эмитит click когда disabled', async () => {
    const wrapper = mount(Card, {
      props: {
        disabled: true
      },
      slots: {
        default: 'Контент'
      }
    })

    await wrapper.find('.card').trigger('click')

    expect(wrapper.emitted('click')).toBeFalsy()
  })

  it('не эмитит click когда loading', async () => {
    const wrapper = mount(Card, {
      props: {
        loading: true
      },
      slots: {
        default: 'Контент'
      }
    })

    await wrapper.find('.card').trigger('click')

    expect(wrapper.emitted('click')).toBeFalsy()
  })

  it('рендерит слот header', () => {
    const wrapper = mount(Card, {
      slots: {
        header: '<div class="custom-header">Кастомный заголовок</div>',
        default: 'Контент'
      }
    })

    expect(wrapper.find('.custom-header').exists()).toBe(true)
    expect(wrapper.find('.custom-header').text()).toBe('Кастомный заголовок')
  })

  it('рендерит слот footer', () => {
    const wrapper = mount(Card, {
      slots: {
        default: 'Контент',
        footer: '<div class="custom-footer">Кастомный футер</div>'
      }
    })

    expect(wrapper.find('.custom-footer').exists()).toBe(true)
    expect(wrapper.find('.custom-footer').text()).toBe('Кастомный футер')
    expect(wrapper.find('.card-footer').exists()).toBe(true)
  })

  it('применяет кастомный класс', () => {
    const wrapper = mount(Card, {
      props: {
        customClass: 'my-custom-class'
      },
      slots: {
        default: 'Контент'
      }
    })

    expect(wrapper.find('.card').classes()).toContain('my-custom-class')
  })

  it('имеет правильные aria атрибуты', () => {
    const wrapper = mount(Card, {
      props: {
        loading: true,
        disabled: true
      },
      slots: {
        default: 'Контент'
      }
    })

    const card = wrapper.find('.card')
    expect(card.attributes('role')).toBe('article')
    expect(card.attributes('aria-busy')).toBe('true')
    expect(card.attributes('aria-disabled')).toBe('true')
  })

  it('показывает только нужные секции', () => {
    // Только body
    const wrapper1 = mount(Card, {
      slots: { default: 'Контент' }
    })
    
    expect(wrapper1.find('.card-header').exists()).toBe(false)
    expect(wrapper1.find('.card-body').exists()).toBe(true)
    expect(wrapper1.find('.card-footer').exists()).toBe(false)

    // С заголовком
    const wrapper2 = mount(Card, {
      props: { title: 'Заголовок' },
      slots: { default: 'Контент' }
    })
    
    expect(wrapper2.find('.card-header').exists()).toBe(true)
    expect(wrapper2.find('.card-body').exists()).toBe(true)
    expect(wrapper2.find('.card-footer').exists()).toBe(false)

    // С футером
    const wrapper3 = mount(Card, {
      slots: { 
        default: 'Контент',
        footer: 'Футер'
      }
    })
    
    expect(wrapper3.find('.card-header').exists()).toBe(false)
    expect(wrapper3.find('.card-body').exists()).toBe(true)
    expect(wrapper3.find('.card-footer').exists()).toBe(true)
  })
})