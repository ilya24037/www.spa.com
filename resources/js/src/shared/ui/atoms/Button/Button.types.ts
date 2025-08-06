import type { Component } from 'vue'
// import type { RouteLocationRaw } from 'vue-router'

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
  // Р’РЅРµС€РЅРёР№ РІРёРґ
  variant?: ButtonVariant
  size?: ButtonSize
  fullWidth?: boolean
  rounded?: ButtonRounded
  
  // РЎРѕСЃС‚РѕСЏРЅРёСЏ
  loading?: boolean
  loadingText?: string
  disabled?: boolean
  
  // РЎРѕРґРµСЂР¶РёРјРѕРµ
  text?: string
  iconLeft?: Component | string
  iconRight?: Component | string
  
  // Р”РµР№СЃС‚РІРёСЏ
  type?: ButtonType
  href?: string
  to?: string | any
  
  // Р”РѕСЃС‚СѓРїРЅРѕСЃС‚СЊ
  ariaLabel?: string
}

export interface ButtonEmits {
  click: [event: MouseEvent]
}