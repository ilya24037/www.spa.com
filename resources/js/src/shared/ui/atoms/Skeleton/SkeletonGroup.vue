<template>
  <div
    class="skeleton-group"
    :aria-busy="true"
    role="status"
    :aria-label="groupAriaLabel"
  >
    <Skeleton
      v-for="(line, index) in linesArray"
      :key="`skeleton-${index}`"
      :variant="line.variant"
      :size="line.size"
      :width="line.width"
      :animated="line.animated"
      :custom-class="line.customClass"
              :aria-label="`Загрузка строки ${index + 1} из ${linesArray.length}`"
    />
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import Skeleton from './Skeleton.vue'
import type { SkeletonGroupProps, SkeletonProps } from './Skeleton.types'

const props = withDefaults(defineProps<SkeletonGroupProps>(), {
    lines: 3,
    variant: 'text',
    size: 'medium',
    animated: true,
    randomWidth: true
})

const linesArray = computed(() => {
    const lines: (SkeletonProps & { customClass?: string })[] = []
  
    for (let i = 0; i < props.lines; i++) {
        const line: SkeletonProps & { customClass?: string } = {
            variant: props.variant,
            size: props.size,
            animated: props.animated
        }
    
        // РЎР»СѓС‡Р°Р№РЅР°СЏ С€РёСЂРёРЅР° РґР»СЏ Р±РѕР»РµРµ СЂРµР°Р»РёСЃС‚РёС‡РЅРѕРіРѕ РІРёРґР°
        if (props.randomWidth && props.variant === 'text') {
            const widths = ['85%', '92%', '76%', '88%', '95%', '82%', '90%']
            line.width = widths[i % widths.length]
      
            // РџРѕСЃР»РµРґРЅСЏСЏ СЃС‚СЂРѕРєР° С‡Р°СЃС‚Рѕ РєРѕСЂРѕС‡Рµ
            if (i === props.lines - 1) {
                line.width = ['60%', '65%', '58%', '72%'][Math.floor(Math.random() * 4)]
            }
        }
    
        // Р”РѕР±Р°РІР»СЏРµРј РѕС‚СЃС‚СѓРї РјРµР¶РґСѓ СЃС‚СЂРѕРєР°РјРё
        if (i < props.lines - 1) {
            line.customClass = 'mb-2'
        }
    
        lines.push(line)
    }
  
    return lines
})

const groupAriaLabel = computed(() => 
            `Загрузка группы из ${props.lines} элементов`
)
</script>

<style scoped>
.skeleton-group {
  width: 100%;
}

.skeleton-group > * + * {
  margin-top: 0.5rem;
}

/* РЎРїРµС†РёР°Р»СЊРЅС‹Рµ РѕС‚СЃС‚СѓРїС‹ РґР»СЏ СЂР°Р·РЅС‹С… РІР°СЂРёР°РЅС‚РѕРІ */
.skeleton-group .skeleton--heading + .skeleton--text {
  margin-top: 0.75rem;
}

.skeleton-group .skeleton--paragraph + .skeleton--paragraph {
  margin-top: 0.25rem;
}
</style>
