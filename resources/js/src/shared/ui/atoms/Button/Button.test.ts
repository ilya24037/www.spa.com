import { describe, it, expect, vi } from 'vitest'
import { mount } from '@vue/test-utils'
import Button from './Button.vue'

describe('Button', () => {
  // Тест рендеринга
  it('renders properly', () => {
    const wrapper = mount(Button, {
      props: {
        text: 'Click me'
      }
    })
    
    expect(wrapper.text()).toContain('Click me')
    expect(wrapper.element.tagName).toBe('BUTTON')
  })
  
  // Тест вариантов
  it('applies correct variant class', () => {
    const wrapper = mount(Button, {
      props: {
        variant: 'primary'
      }
    })
    
    expect(wrapper.classes()).toContain('button--primary')
  })
  
  // Тест размеров
  it('applies correct size class', () => {
    const wrapper = mount(Button, {
      props: {
        size: 'lg'
      }
    })
    
    expect(wrapper.classes()).toContain('button--lg')
  })
  
  // Тест состояния загрузки
  it('shows loading state', () => {
    const wrapper = mount(Button, {
      props: {
        loading: true,
        loadingText: 'Loading...'
      }
    })
    
    expect(wrapper.text()).toContain('Loading...')
    expect(wrapper.find('.button-spinner').exists()).toBe(true)
    expect(wrapper.attributes('aria-busy')).toBe('true')
  })
  
  // Тест отключенного состояния
  it('disables button when disabled prop is true', () => {
    const wrapper = mount(Button, {
      props: {
        disabled: true
      }
    })
    
    expect(wrapper.attributes('disabled')).toBeDefined()
    expect(wrapper.attributes('aria-disabled')).toBe('true')
  })
  
  // Тест клика
  it('emits click event when clicked', async () => {
    const wrapper = mount(Button)
    
    await wrapper.trigger('click')
    
    expect(wrapper.emitted()).toHaveProperty('click')
    expect(wrapper.emitted('click')).toHaveLength(1)
  })
  
  // Тест блокировки клика при disabled
  it('does not emit click when disabled', async () => {
    const wrapper = mount(Button, {
      props: {
        disabled: true
      }
    })
    
    await wrapper.trigger('click')
    
    expect(wrapper.emitted('click')).toBeUndefined()
  })
  
  // Тест блокировки клика при loading
  it('does not emit click when loading', async () => {
    const wrapper = mount(Button, {
      props: {
        loading: true
      }
    })
    
    await wrapper.trigger('click')
    
    expect(wrapper.emitted('click')).toBeUndefined()
  })
  
  // Тест ссылки
  it('renders as anchor when href is provided', () => {
    const wrapper = mount(Button, {
      props: {
        href: 'https://example.com',
        text: 'Link'
      }
    })
    
    expect(wrapper.element.tagName).toBe('A')
    expect(wrapper.attributes('href')).toBe('https://example.com')
  })
  
  // Тест полной ширины
  it('applies full width class', () => {
    const wrapper = mount(Button, {
      props: {
        fullWidth: true
      }
    })
    
    expect(wrapper.classes()).toContain('button--full-width')
  })
  
  // Тест скругления
  it('applies rounded class', () => {
    const wrapper = mount(Button, {
      props: {
        rounded: 'full'
      }
    })
    
    expect(wrapper.classes()).toContain('button--rounded-full')
  })
  
  // Тест ARIA label
  it('sets aria-label when provided', () => {
    const wrapper = mount(Button, {
      props: {
        ariaLabel: 'Custom label'
      }
    })
    
    expect(wrapper.attributes('aria-label')).toBe('Custom label')
  })
  
  // Тест типа кнопки
  it('sets button type', () => {
    const wrapper = mount(Button, {
      props: {
        type: 'submit'
      }
    })
    
    expect(wrapper.attributes('type')).toBe('submit')
  })
})