<template>
  <nav 
    :class="containerClasses"
    role="navigation"
    :aria-label="ariaLabel"
  >
    <ol :class="listClasses">
      <li 
        v-for="(item, index) in safeItems" 
        :key="getItemKey(item, index)" 
        :class="itemClasses"
      >
        <!-- РРєРѕРЅРєР° (РµСЃР»Рё РµСЃС‚СЊ) -->
        <component
          v-if="item.icon && showIcons"
          :is="item.icon"
          :class="iconClasses"
          aria-hidden="true"
        />
        
        <!-- РЎСЃС‹Р»РєР° (РЅРµ РїРѕСЃР»РµРґРЅРёР№ СЌР»РµРјРµРЅС‚) -->
        <component
          v-if="index < safeItems.length - 1"
          :is="item.to ? 'router-link' : 'a'"
          v-bind="getLinkProps(item)"
          :class="linkClasses"
          @click="handleItemClick(item, index)"
        >
          {{ item.title }}
        </component>
        
        <!-- РўРµРєСѓС‰РёР№ СЌР»РµРјРµРЅС‚ (РїРѕСЃР»РµРґРЅРёР№) -->
        <span 
          v-else 
          :class="currentClasses"
          :aria-current="'page'"
        >
          {{ item.title }}
        </span>
        
        <!-- Р Р°Р·РґРµР»РёС‚РµР»СЊ -->
        <component
          v-if="index < safeItems.length - 1"
          :is="separatorIcon || 'svg'"
          :class="separatorClasses"
          v-bind="separatorIcon ? {} : {
            fill: 'none',
            stroke: 'currentColor',
            viewBox: '0 0 24 24',
            'aria-hidden': 'true'
          }"
        >
          <path 
            v-if="!separatorIcon"
            stroke-linecap="round" 
            stroke-linejoin="round" 
            stroke-width="2" 
            :d="getSeparatorPath()" 
          />
        </component>
      </li>
    </ol>
    
    <!-- РЎС…РµРјР°С‚РёС‡РµСЃРєР°СЏ СЂР°Р·РјРµС‚РєР° РґР»СЏ SEO - Р’Р Р•РњР•РќРќРћ РћРўРљР›Р®Р§Р•РќРћ -->
    <!-- <template v-if="enableJsonLd">
      <script type="application/ld+json" v-html="jsonLdSchema"></script>
    </template> -->
  </nav>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import type { BreadcrumbsProps, BreadcrumbsEmits, BreadcrumbItem } from './Breadcrumbs.types'

const props = withDefaults(defineProps<BreadcrumbsProps>(), {
  size: 'medium',
  separator: 'chevron',
  showIcons: false,
  showHome: false,
  maxItems: 0,
  enableJsonLd: false,
  ariaLabel: 'РќР°РІРёРіР°С†РёРѕРЅРЅР°СЏ С†РµРїРѕС‡РєР°'
})

const emit = defineEmits<BreadcrumbsEmits>()

// Р‘РµР·РѕРїР°СЃРЅС‹Р№ РјР°СЃСЃРёРІ СЌР»РµРјРµРЅС‚РѕРІ
const safeItems = computed(() => {
  if (!Array.isArray(props.items)) return []
  
  let items = [...props.items]
  
  // Р”РѕР±Р°РІР»СЏРµРј РґРѕРјР°С€РЅСЋСЋ СЃС‚СЂР°РЅРёС†Сѓ РµСЃР»Рё РЅСѓР¶РЅРѕ
  if (props.showHome && !items.some(item => item.isHome)) {
    items.unshift({
      title: 'Р“Р»Р°РІРЅР°СЏ',
      href: '/',
      isHome: true
    })
  }
  
  // РћРіСЂР°РЅРёС‡РёРІР°РµРј РєРѕР»РёС‡РµСЃС‚РІРѕ СЌР»РµРјРµРЅС‚РѕРІ
  if (props.maxItems > 0 && items.length > props.maxItems) {
    const first = items[0]
    const last = items.slice(-2) // РџРѕСЃР»РµРґРЅРёРµ 2 СЌР»РµРјРµРЅС‚Р°
    if (first) items = [first, { title: '...', href: '', isEllipsis: true }, ...last]
  }
  
  return items
})

// Р’С‹С‡РёСЃР»СЏРµРјС‹Рµ РєР»Р°СЃСЃС‹
const containerClasses = computed(() => [
  'breadcrumbs',
  `breadcrumbs--${props.size}`,
  props.customClass
])

const listClasses = computed(() => [
  'breadcrumbs__list'
])

const itemClasses = computed(() => [
  'breadcrumbs__item'
])

const iconClasses = computed(() => [
  'breadcrumbs__icon'
])

const linkClasses = computed(() => [
  'breadcrumbs__link'
])

const currentClasses = computed(() => [
  'breadcrumbs__current'
])

const separatorClasses = computed(() => [
  'breadcrumbs__separator'
])

// РњРµС‚РѕРґС‹
const getItemKey = (item: BreadcrumbItem, index: number): string => {
  return item.key || item.href || `breadcrumb-${index}`
}

const getLinkProps = (item: BreadcrumbItem) => {
  if (item.to) {
    return { to: item.to }
  }
  return { 
    href: item.href,
    target: item.external ? '_blank' : undefined,
    rel: item.external ? 'noopener noreferrer' : undefined
  }
}

const handleItemClick = (item: BreadcrumbItem, index: number) => {
  if (item.isEllipsis) return
  emit('item-click', { item, index })
}

const getSeparatorPath = (): string => {
  const separatorPaths = {
    chevron: 'M9 5l7 7-7 7',
    slash: 'M5 12h14',
    arrow: 'M13 7l5 5-5 5M6 12h12'
  }
  return separatorPaths[props.separator] || separatorPaths.chevron
}

// JSON-LD СЃС…РµРјР° РґР»СЏ SEO
const jsonLdSchema = computed(() => {
  if (!props.enableJsonLd) return ''
  
  const listItems = safeItems.value
    .filter(item => !item.isEllipsis)
    .map((item, index) => ({
      '@type': 'ListItem',
      position: index + 1,
      name: item.title,
      item: item.href ? new URL(item.href, window.location.origin).href : undefined
    }))
  
  const schema = {
    '@context': 'https://schema.org',
    '@type': 'BreadcrumbList',
    itemListElement: listItems
  }
  
  return JSON.stringify(schema)
})
</script>

<style scoped>
.breadcrumbs {
  color: #6b7280;
  font-size: 0.875rem;
}

.breadcrumbs--small {
  font-size: 0.75rem;
}

.breadcrumbs--medium {
  font-size: 0.875rem;
}

.breadcrumbs--large {
  font-size: 1rem;
}

.breadcrumbs__list {
  display: flex;
  align-items: center;
  flex-wrap: wrap;
  gap: 0.5rem;
  margin: 0;
  padding: 0;
  list-style: none;
}

.breadcrumbs__item {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.breadcrumbs__icon {
  width: 1rem;
  height: 1rem;
  flex-shrink: 0;
}

.breadcrumbs__link {
  color: inherit;
  text-decoration: none;
  transition: color 0.2s ease;
  border-radius: 0.25rem;
  padding: 0.125rem 0.25rem;
  margin: -0.125rem -0.25rem;
}

.breadcrumbs__link:hover {
  color: #374151;
  background-color: #f3f4f6;
}

.breadcrumbs__link:focus {
  outline: 2px solid #3b82f6;
  outline-offset: 2px;
}

.breadcrumbs__current {
  color: #374151;
  font-weight: 500;
}

.breadcrumbs__separator {
  width: 0.75rem;
  height: 0.75rem;
  color: #9ca3af;
  flex-shrink: 0;
}

/* РђРґР°РїС‚РёРІРЅРѕСЃС‚СЊ */
@media (max-width: 768px) {
  .breadcrumbs {
    font-size: 0.75rem;
  }
  
  .breadcrumbs--large {
    font-size: 0.875rem;
  }
  
  .breadcrumbs__list {
    gap: 0.25rem;
  }
  
  .breadcrumbs__separator {
    width: 0.625rem;
    height: 0.625rem;
  }
  
  .breadcrumbs__icon {
    width: 0.875rem;
    height: 0.875rem;
  }
}

/* РўРµРјРЅР°СЏ С‚РµРјР° */
@media (prefers-color-scheme: dark) {
  .breadcrumbs {
    color: #9ca3af;
  }
  
  .breadcrumbs__link:hover {
    color: #f3f4f6;
    background-color: #374151;
  }
  
  .breadcrumbs__current {
    color: #f3f4f6;
  }
  
  .breadcrumbs__separator {
    color: #6b7280;
  }
}

/* РЎРѕСЃС‚РѕСЏРЅРёСЏ РїРµС‡Р°С‚Рё */
@media print {
  .breadcrumbs__link {
    text-decoration: underline;
    color: inherit;
  }
  
  .breadcrumbs__separator {
    display: none;
  }
  
  .breadcrumbs__item:not(:last-child):after {
    content: ' > ';
    margin: 0 0.25rem;
  }
}
</style>

