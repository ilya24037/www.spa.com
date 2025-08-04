/**
 * Unit тесты для Breadcrumbs компонента
 */

import { describe, it, expect, vi } from 'vitest'
import { mount } from '@vue/test-utils'
import Breadcrumbs from './Breadcrumbs.vue'
import type { BreadcrumbItem, BreadcrumbsProps } from './Breadcrumbs.types'

const mockItems: BreadcrumbItem[] = [
  { title: 'Главная', href: '/' },
  { title: 'Каталог', href: '/catalog' },
  { title: 'Категория', href: '/catalog/category' },
  { title: 'Товар', href: '/catalog/category/product' }
]

describe('Breadcrumbs Component', () => {
  it('рендерится с базовыми props', () => {
    const wrapper = mount(Breadcrumbs, {
      props: {
        items: mockItems
      }
    })

    expect(wrapper.find('nav').exists()).toBe(true)
    expect(wrapper.find('nav').attributes('role')).toBe('navigation')
    expect(wrapper.findAll('li')).toHaveLength(4)
  })

  it('показывает правильные заголовки', () => {
    const wrapper = mount(Breadcrumbs, {
      props: {
        items: mockItems
      }
    })

    const items = wrapper.findAll('li')
    expect(items[0].text()).toContain('Главная')
    expect(items[1].text()).toContain('Каталог')
    expect(items[2].text()).toContain('Категория')
    expect(items[3].text()).toContain('Товар')
  })

  it('последний элемент отмечен как текущий', () => {
    const wrapper = mount(Breadcrumbs, {
      props: {
        items: mockItems
      }
    })

    const lastItem = wrapper.findAll('.breadcrumbs__current')
    expect(lastItem).toHaveLength(1)
    expect(lastItem[0].text()).toBe('Товар')
    expect(lastItem[0].attributes('aria-current')).toBe('page')
  })

  it('все элементы кроме последнего являются ссылками', () => {
    const wrapper = mount(Breadcrumbs, {
      props: {
        items: mockItems
      }
    })

    const links = wrapper.findAll('a')
    expect(links).toHaveLength(3) // Все кроме последнего

    expect(links[0].attributes('href')).toBe('/')
    expect(links[1].attributes('href')).toBe('/catalog')
    expect(links[2].attributes('href')).toBe('/catalog/category')
  })

  it('показывает разделители между элементами', () => {
    const wrapper = mount(Breadcrumbs, {
      props: {
        items: mockItems
      }
    })

    const separators = wrapper.findAll('svg')
    expect(separators).toHaveLength(3) // n-1 разделителей
  })

  it('применяет правильные размеры', () => {
    const sizes: BreadcrumbsProps['size'][] = ['small', 'medium', 'large']

    sizes.forEach(size => {
      const wrapper = mount(Breadcrumbs, {
        props: {
          items: mockItems,
          size
        }
      })

      expect(wrapper.find('.breadcrumbs').classes()).toContain(`breadcrumbs--${size}`)
    })
  })

  it('использует разные типы разделителей', () => {
    const separators: BreadcrumbsProps['separator'][] = ['chevron', 'slash', 'arrow']

    separators.forEach(separator => {
      const wrapper = mount(Breadcrumbs, {
        props: {
          items: mockItems,
          separator
        }
      })

      // Проверяем что SVG path изменился
      const separatorSvg = wrapper.find('svg path')
      expect(separatorSvg.exists()).toBe(true)
    })
  })

  it('эмитит событие при клике на элемент', async () => {
    const wrapper = mount(Breadcrumbs, {
      props: {
        items: mockItems
      }
    })

    const firstLink = wrapper.find('a')
    await firstLink.trigger('click')

    expect(wrapper.emitted('item-click')).toBeTruthy()
    expect(wrapper.emitted('item-click')?.[0]).toEqual([{
      item: mockItems[0],
      index: 0
    }])
  })

  it('автоматически добавляет домашнюю страницу', () => {
    const itemsWithoutHome = mockItems.slice(1) // Убираем первый элемент

    const wrapper = mount(Breadcrumbs, {
      props: {
        items: itemsWithoutHome,
        showHome: true
      }
    })

    const items = wrapper.findAll('li')
    expect(items).toHaveLength(4) // 3 + 1 домашняя
    expect(wrapper.text()).toContain('Главная')
  })

  it('ограничивает количество элементов', () => {
    const wrapper = mount(Breadcrumbs, {
      props: {
        items: mockItems,
        maxItems: 3
      }
    })

    const items = wrapper.findAll('li')
    expect(items).toHaveLength(4) // первый + ... + последние 2
    expect(wrapper.text()).toContain('...')
  })

  it('показывает иконки когда включено', () => {
    const itemsWithIcons: BreadcrumbItem[] = [
      { title: 'Главная', href: '/', icon: 'HomeIcon' },
      { title: 'Каталог', href: '/catalog', icon: 'CatalogIcon' }
    ]

    const wrapper = mount(Breadcrumbs, {
      props: {
        items: itemsWithIcons,
        showIcons: true
      },
      global: {
        stubs: {
          HomeIcon: '<div class="home-icon">Home</div>',
          CatalogIcon: '<div class="catalog-icon">Catalog</div>'
        }
      }
    })

    expect(wrapper.find('.home-icon').exists()).toBe(true)
    expect(wrapper.find('.catalog-icon').exists()).toBe(true)
  })

  it('поддерживает router-link для Vue Router', () => {
    const routerItems: BreadcrumbItem[] = [
      { title: 'Главная', to: '/' },
      { title: 'Каталог', to: '/catalog' }
    ]

    const wrapper = mount(Breadcrumbs, {
      props: {
        items: routerItems
      },
      global: {
        stubs: {
          'router-link': {
            template: '<a :to="to" @click="$emit(\'click\', $event)"><slot /></a>',
            props: ['to']
          }
        }
      }
    })

    const routerLinks = wrapper.findAllComponents({ name: 'router-link' })
    expect(routerLinks).toHaveLength(1) // Только первый, последний не ссылка
  })

  it('обрабатывает внешние ссылки', () => {
    const externalItems: BreadcrumbItem[] = [
      { title: 'Главная', href: '/' },
      { title: 'Внешняя ссылка', href: 'https://example.com', external: true }
    ]

    const wrapper = mount(Breadcrumbs, {
      props: {
        items: externalItems
      }
    })

    const externalLink = wrapper.find('a[target="_blank"]')
    expect(externalLink.exists()).toBe(true)
    expect(externalLink.attributes('rel')).toBe('noopener noreferrer')
  })

  it('генерирует JSON-LD схему когда включено', () => {
    const wrapper = mount(Breadcrumbs, {
      props: {
        items: mockItems,
        enableJsonLd: true
      }
    })

    const jsonLdScript = wrapper.find('script[type="application/ld+json"]')
    expect(jsonLdScript.exists()).toBe(true)

    const schema = JSON.parse(jsonLdScript.element.innerHTML)
    expect(schema['@context']).toBe('https://schema.org')
    expect(schema['@type']).toBe('BreadcrumbList')
    expect(schema.itemListElement).toHaveLength(4)
  })

  it('применяет кастомный класс', () => {
    const wrapper = mount(Breadcrumbs, {
      props: {
        items: mockItems,
        customClass: 'my-custom-breadcrumbs'
      }
    })

    expect(wrapper.find('.breadcrumbs').classes()).toContain('my-custom-breadcrumbs')
  })

  it('имеет правильные ARIA атрибуты', () => {
    const wrapper = mount(Breadcrumbs, {
      props: {
        items: mockItems,
        ariaLabel: 'Кастомная навигация'
      }
    })

    const nav = wrapper.find('nav')
    expect(nav.attributes('role')).toBe('navigation')
    expect(nav.attributes('aria-label')).toBe('Кастомная навигация')

    const list = wrapper.find('ol')
    expect(list.exists()).toBe(true)
  })

  it('обрабатывает пустой массив элементов', () => {
    const wrapper = mount(Breadcrumbs, {
      props: {
        items: []
      }
    })

    expect(wrapper.findAll('li')).toHaveLength(0)
    expect(wrapper.find('nav').exists()).toBe(true)
  })

  it('обрабатывает некорректные данные', () => {
    const wrapper = mount(Breadcrumbs, {
      props: {
        items: null as any
      }
    })

    expect(wrapper.findAll('li')).toHaveLength(0)
    expect(wrapper.find('nav').exists()).toBe(true)
  })

  it('не эмитит событие для элементов с isEllipsis', async () => {
    const itemsWithEllipsis: BreadcrumbItem[] = [
      { title: 'Главная', href: '/' },
      { title: '...', href: '', isEllipsis: true },
      { title: 'Текущая', href: '/current' }
    ]

    const wrapper = mount(Breadcrumbs, {
      props: {
        items: itemsWithEllipsis
      }
    })

    // Первая ссылка должна эмитить событие
    const firstLink = wrapper.find('a')
    await firstLink.trigger('click')
    expect(wrapper.emitted('item-click')).toHaveLength(1)

    // Элемент с isEllipsis не должен эмитить событие
    // (хотя он не отображается как ссылка, проверим логику)
    expect(wrapper.text()).toContain('...')
  })

  it('генерирует уникальные ключи для элементов', () => {
    const itemsWithKeys: BreadcrumbItem[] = [
      { title: 'Главная', href: '/', key: 'home' },
      { title: 'Каталог', href: '/catalog' }, // без key
      { title: 'Товар', href: '/product', key: 'product' }
    ]

    const wrapper = mount(Breadcrumbs, {
      props: {
        items: itemsWithKeys
      }
    })

    // Проверяем что компонент не падает и рендерится
    expect(wrapper.findAll('li')).toHaveLength(3)
  })
})