import { ref, computed } from 'vue'
import type { ButtonProps } from './Button.types'

/**
 * Composable для управления состоянием кнопки
 */
export function useButton(props: ButtonProps) {
  const isPressed = ref(false)
  const isFocused = ref(false)
  const isHovered = ref(false)
  
  // Проверка доступности кнопки
  const isDisabled = computed(() => {
    return props.disabled || props.loading
  })
  
  // Обработчики событий
  const handleMouseDown = () => {
    if (!isDisabled.value) {
      isPressed.value = true
    }
  }
  
  const handleMouseUp = () => {
    isPressed.value = false
  }
  
  const handleMouseEnter = () => {
    if (!isDisabled.value) {
      isHovered.value = true
    }
  }
  
  const handleMouseLeave = () => {
    isHovered.value = false
    isPressed.value = false
  }
  
  const handleFocus = () => {
    if (!isDisabled.value) {
      isFocused.value = true
    }
  }
  
  const handleBlur = () => {
    isFocused.value = false
  }
  
  return {
    // Состояния
    isPressed,
    isFocused,
    isHovered,
    isDisabled,
    
    // Обработчики
    handleMouseDown,
    handleMouseUp,
    handleMouseEnter,
    handleMouseLeave,
    handleFocus,
    handleBlur
  }
}