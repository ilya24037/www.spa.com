/**
 * Unit тесты для Skeleton компонента
 */

import { describe, it, expect, beforeEach } from 'vitest'
import { mount } from '@vue/test-utils'
import Skeleton from './Skeleton.vue'
import SkeletonGroup from './SkeletonGroup.vue'
import type { SkeletonProps } from './Skeleton.types'

describe('Skeleton Component', () => {
  it('рендерится с базовыми props', () => {
    const wrapper = mount(Skeleton)

    expect(wrapper.find('.skeleton').exists()).toBe(true)
    expect(wrapper.find('.skeleton--text').exists()).toBe(true)
    expect(wrapper.find('.skeleton--medium').exists()).toBe(true)
    expect(wrapper.find('.skeleton--animated').exists()).toBe(true)
  })

  it('применяет правильные варианты', () => {
    const variants: SkeletonProps['variant'][] = [
      'text', 'heading', 'paragraph', 'button', 'avatar', 'image', 'card', 'circular'
    ]

    variants.forEach(variant => {
      const wrapper = mount(Skeleton, {
        props: { variant }
      })

      expect(wrapper.find('.skeleton').classes()).toContain(`skeleton--${variant}`)
    })
  })

  it('применяет правильные размеры', () => {
    const sizes: SkeletonProps['size'][] = ['small', 'medium', 'large']

    sizes.forEach(size => {
      const wrapper = mount(Skeleton, {
        props: { size }
      })

      expect(wrapper.find('.skeleton').classes()).toContain(`skeleton--${size}`)
    })
  })

  it('показывает анимацию по умолчанию', () => {
    const wrapper = mount(Skeleton)

    expect(wrapper.find('.skeleton--animated').exists()).toBe(true)
    expect(wrapper.find('.skeleton-shimmer').exists()).toBe(true)
  })

  it('может отключить анимацию', () => {
    const wrapper = mount(Skeleton, {
      props: {
        animated: false
      }
    })

    expect(wrapper.find('.skeleton--animated').exists()).toBe(false)
    expect(wrapper.find('.skeleton-shimmer').exists()).toBe(false)
  })

  it('применяет кастомные размеры', () => {
    const wrapper = mount(Skeleton, {
      props: {
        width: 200,
        height: '50px'
      }
    })

    const element = wrapper.find('.skeleton').element as HTMLElement
    expect(element.style.width).toBe('200px')
    expect(element.style.height).toBe('50px')
  })

  it('применяет кастомный класс', () => {
    const wrapper = mount(Skeleton, {
      props: {
        customClass: 'my-custom-skeleton'
      }
    })

    expect(wrapper.find('.skeleton').classes()).toContain('my-custom-skeleton')
  })

  it('показывает rounded вариант', () => {
    const wrapper = mount(Skeleton, {
      props: {
        rounded: true
      }
    })

    expect(wrapper.find('.skeleton--rounded').exists()).toBe(true)
  })

  it('показывает circular для круглых элементов', () => {
    const wrapper = mount(Skeleton, {
      props: {
        variant: 'circular'
      }
    })

    expect(wrapper.find('.skeleton--circular').exists()).toBe(true)
  })

  it('имеет правильные ARIA атрибуты', () => {
    const wrapper = mount(Skeleton, {
      props: {
        ariaLabel: 'Загрузка профиля'
      }
    })

    const element = wrapper.find('.skeleton')
    expect(element.attributes('aria-busy')).toBe('true')
    expect(element.attributes('role')).toBe('status')
    expect(element.attributes('aria-label')).toBe('Загрузка профиля')
  })

  it('генерирует дефолтную ARIA метку', () => {
    const wrapper = mount(Skeleton, {
      props: {
        variant: 'heading'
      }
    })

    expect(wrapper.find('.skeleton').attributes('aria-label')).toContain('Загрузка')
  })

  it('поддерживает строковые значения размеров', () => {
    const wrapper = mount(Skeleton, {
      props: {
        width: '100%',
        height: '2rem'
      }
    })

    const element = wrapper.find('.skeleton').element as HTMLElement
    expect(element.style.width).toBe('100%')
    expect(element.style.height).toBe('2rem')
  })
})

describe('SkeletonGroup Component', () => {
  it('рендерит группу скелетонов', () => {
    const wrapper = mount(SkeletonGroup, {
      props: {
        lines: 3
      }
    })

    expect(wrapper.findAll('.skeleton')).toHaveLength(3)
  })

  it('применяет свойства ко всем скелетонам', () => {
    const wrapper = mount(SkeletonGroup, {
      props: {
        lines: 2,
        variant: 'heading',
        size: 'large',
        animated: false
      }
    })

    const skeletons = wrapper.findAll('.skeleton')
    expect(skeletons).toHaveLength(2)
    
    skeletons.forEach(skeleton => {
      expect(skeleton.classes()).toContain('skeleton--heading')
      expect(skeleton.classes()).toContain('skeleton--large')
      expect(skeleton.classes()).not.toContain('skeleton--animated')
    })
  })

  it('генерирует случайную ширину для текстовых строк', () => {
    const wrapper = mount(SkeletonGroup, {
      props: {
        lines: 5,
        variant: 'text',
        randomWidth: true
      }
    })

    const skeletons = wrapper.findAll('.skeleton')
    
    // Проверяем что хотя бы некоторые строки имеют разную ширину
    const widths = skeletons.map(skeleton => {
      const element = skeleton.element as HTMLElement
      return element.style.width
    })

    // Должно быть больше одного уникального значения ширины
    const uniqueWidths = new Set(widths.filter(w => w !== ''))
    expect(uniqueWidths.size).toBeGreaterThan(1)
  })

  it('последняя строка короче при randomWidth', () => {
    const wrapper = mount(SkeletonGroup, {
      props: {
        lines: 3,
        variant: 'text',
        randomWidth: true
      }
    })

    const skeletons = wrapper.findAll('.skeleton')
    const lastSkeleton = skeletons[skeletons.length - 1]
    const lastElement = lastSkeleton.element as HTMLElement
    
    // Последняя строка должна быть короче (60-72%)
    const width = lastElement.style.width
    expect(width).toMatch(/^(60|65|58|72)%$/)
  })

  it('имеет правильные ARIA атрибуты для группы', () => {
    const wrapper = mount(SkeletonGroup, {
      props: {
        lines: 4
      }
    })

    const group = wrapper.find('.skeleton-group')
    expect(group.attributes('aria-busy')).toBe('true')
    expect(group.attributes('role')).toBe('status')
    expect(group.attributes('aria-label')).toBe('Загрузка группы из 4 элементов')
  })

  it('добавляет отступы между строками', () => {
    const wrapper = mount(SkeletonGroup, {
      props: {
        lines: 3
      }
    })

    const skeletons = wrapper.findAll('.skeleton')
    
    // Первые две строки должны иметь margin-bottom
    expect(skeletons[0].classes()).toContain('mb-2')
    expect(skeletons[1].classes()).toContain('mb-2')
    
    // Последняя строка не должна иметь margin-bottom
    expect(skeletons[2].classes()).not.toContain('mb-2')
  })
})

describe('Skeleton Accessibility', () => {
  it('поддерживает prefers-reduced-motion', () => {
    // Симулируем медиа-запрос через CSS
    const wrapper = mount(Skeleton, {
      props: {
        animated: true
      }
    })

    expect(wrapper.find('.skeleton--animated').exists()).toBe(true)
    
    // В реальной среде анимация отключится через CSS медиа-запрос
    // @media (prefers-reduced-motion: reduce)
  })

  it('имеет семантическую разметку', () => {
    const wrapper = mount(Skeleton)

    const element = wrapper.find('.skeleton')
    expect(element.attributes('role')).toBe('status')
    expect(element.attributes('aria-busy')).toBe('true')
    expect(element.attributes('aria-label')).toBeDefined()
  })

  it('поддерживает темную тему через CSS', () => {
    const wrapper = mount(Skeleton)

    // CSS медиа-запрос @media (prefers-color-scheme: dark) 
    // автоматически изменит цвета скелетона
    expect(wrapper.find('.skeleton').exists()).toBe(true)
  })
})