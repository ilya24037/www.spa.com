/// <reference types="vite/client" />

// Глобальные типы для проекта SPA Platform

// Ziggy router
interface Route {
  (name: string, params?: any, absolute?: boolean): string
  current(): string
  current(name: string): boolean
}

declare global {
  interface Window {
    route: Route
    axios: any
    Echo?: any
  }
  
  const route: Route
}

// Переменные окружения
interface ImportMetaEnv { 
  readonly VITE_APP_NAME: string
  readonly VITE_APP_URL: string
  readonly VITE_PUSHER_APP_KEY?: string
  readonly VITE_PUSHER_APP_CLUSTER?: string
  readonly MODE: string
}

interface ImportMeta { 
  readonly env: ImportMetaEnv
}

// Vue компоненты
declare module '*.vue' {
  import type { DefineComponent } from 'vue'
  const component: DefineComponent<{}, {}, any>
  export default component
}

// Изображения и медиа
declare module '*.svg' {
  const content: string
  export default content
}

declare module '*.png' {
  const content: string
  export default content
}

declare module '*.jpg' {
  const content: string
  export default content
}

declare module '*.jpeg' {
  const content: string
  export default content
}

declare module '*.gif' {
  const content: string
  export default content
}

declare module '*.webp' {
  const content: string
  export default content
}

// CSS модули
declare module '*.module.css' {
  const classes: { readonly [key: string]: string }
  export default classes
}

declare module '*.module.scss' {
  const classes: { readonly [key: string]: string }
  export default classes
}

export {}