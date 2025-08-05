import type { Component } from 'vue'
import type { RouteLocationRaw } from '@vue/router'

export type ButtonVariant = 
  | 'primary' 
  | 'secondary' 
  | 'danger' 
  | 'success' 
  | 'warning' 
  | 'ghost' 
  | 'link'

export type ButtonSize = 'xs' | 'sm' | 'md' | 'lg' | 'xl'

export type ButtonType = 'button' | 'submit' | 'reset'

export type ButtonRounded = boolean | 'sm' | 'md' | 'lg' | 'full'

export interface ButtonProps {
  // Внешний вид
  variant?: ButtonVariant
  size?: ButtonSize
  fullWidth?: boolean
  rounded?: ButtonRounded
  
  // Состояния
  loading?: boolean
  loadingText?: string
  disabled?: boolean
  
  // Содержимое
  text?: string
  iconLeft?: Component | string
  iconRight?: Component | string
  
  // Действия
  type?: ButtonType
  href?: string
  to?: string | RouteLocationRaw
  
  // Доступность
  ariaLabel?: string
}

export interface ButtonEmits {
  click: [event: MouseEvent]
}