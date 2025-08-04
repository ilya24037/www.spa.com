import type { Meta, StoryObj } from '@storybook/vue3'
import Button from './Button.vue'

const meta: Meta<typeof Button> = {
  title: 'Shared/UI/Atoms/Button',
  component: Button,
  tags: ['autodocs'],
  argTypes: {
    variant: {
      control: 'select',
      options: ['primary', 'secondary', 'danger', 'success', 'warning', 'ghost', 'link']
    },
    size: {
      control: 'select',
      options: ['xs', 'sm', 'md', 'lg', 'xl']
    },
    rounded: {
      control: 'select',
      options: [false, true, 'sm', 'md', 'lg', 'full']
    },
    type: {
      control: 'select',
      options: ['button', 'submit', 'reset']
    }
  }
}

export default meta
type Story = StoryObj<typeof meta>

// Основная кнопка
export const Primary: Story = {
  args: {
    text: 'Нажми меня',
    variant: 'primary'
  }
}

// Вторичная кнопка
export const Secondary: Story = {
  args: {
    text: 'Вторичная кнопка',
    variant: 'secondary'
  }
}

// Кнопка с загрузкой
export const Loading: Story = {
  args: {
    text: 'Сохранить',
    loading: true,
    loadingText: 'Сохранение...'
  }
}

// Отключенная кнопка
export const Disabled: Story = {
  args: {
    text: 'Недоступно',
    disabled: true
  }
}

// Размеры кнопок
export const Sizes: Story = {
  render: () => ({
    components: { Button },
    template: `
      <div class="flex items-center gap-4">
        <Button size="xs" text="XS размер" />
        <Button size="sm" text="SM размер" />
        <Button size="md" text="MD размер" />
        <Button size="lg" text="LG размер" />
        <Button size="xl" text="XL размер" />
      </div>
    `
  })
}

// Варианты кнопок
export const Variants: Story = {
  render: () => ({
    components: { Button },
    template: `
      <div class="flex flex-wrap gap-4">
        <Button variant="primary" text="Primary" />
        <Button variant="secondary" text="Secondary" />
        <Button variant="danger" text="Danger" />
        <Button variant="success" text="Success" />
        <Button variant="warning" text="Warning" />
        <Button variant="ghost" text="Ghost" />
        <Button variant="link" text="Link" />
      </div>
    `
  })
}

// Кнопка на всю ширину
export const FullWidth: Story = {
  args: {
    text: 'Кнопка на всю ширину',
    fullWidth: true
  }
}

// Кнопки с иконками
export const WithIcons: Story = {
  render: () => ({
    components: { Button },
    template: `
      <div class="flex flex-col gap-4">
        <Button text="Скачать" :icon-left="'↓'" />
        <Button text="Далее" :icon-right="'→'" />
        <Button text="Обновить" :icon-left="'↻'" :icon-right="'→'" />
      </div>
    `
  })
}