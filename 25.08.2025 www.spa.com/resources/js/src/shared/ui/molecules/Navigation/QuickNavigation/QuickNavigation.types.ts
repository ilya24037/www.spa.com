export interface NavigationLink {
  label: string
  href?: string
  to?: string | object
  icon?: string
  badge?: string | number
  active?: boolean
  disabled?: boolean
}

export interface QuickNavigationProps {
  links: NavigationLink[]
  variant?: 'horizontal' | 'vertical'
  size?: 'small' | 'medium' | 'large'
  className?: string
}