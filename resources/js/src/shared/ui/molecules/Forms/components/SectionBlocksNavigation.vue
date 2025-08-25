<template>
  <div class="section-blocks-navigation">
    <div class="blocks-container">
      <button
        v-for="(block, index) in blocks"
        :key="block.key"
        class="block-button"
        :class="{ 'active': activeBlock === block.key }"
        @click="scrollToBlock(block.key)"
      >
        <span class="block-title">{{ block.title }}</span>
      </button>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'

const props = defineProps({
  activeBlock: {
    type: String,
    default: 'basic'
  }
})

const emit = defineEmits(['block-changed'])

// Определение блоков секций
const blocks = [
  {
    key: 'basic',
    title: 'ОСНОВНОЕ',
    sections: ['serviceProvider', 'workFormat', 'experience', 'clients', 'description']
  },
  {
    key: 'personal',
    title: 'ПАРАМЕТРЫ',
    sections: ['parameters']
  },
  {
    key: 'services',
    title: 'УСЛУГИ',
    sections: ['services', 'price']
  },
  {
    key: 'media',
    title: 'МЕДИА',
    sections: ['media']
  },
  {
    key: 'location',
    title: 'ГЕОГРАФИЯ',
    sections: ['geo', 'schedule']
  },
  {
    key: 'marketing',
    title: 'МАРКЕТИНГ',
    sections: ['features', 'promo']
  },
  {
    key: 'contacts',
    title: 'КОНТАКТЫ',
    sections: ['contacts']
  }
]

const scrollToBlock = (blockKey) => {
  const block = blocks.find(b => b.key === blockKey)
  if (block && block.sections.length > 0) {
    const firstSectionKey = block.sections[0]
    const element = document.querySelector(`[data-section="${firstSectionKey}"]`)
    
    if (element) {
      element.scrollIntoView({ 
        behavior: 'smooth', 
        block: 'start',
        inline: 'nearest'
      })
    }
    
    emit('block-changed', blockKey)
  }
}
</script>

<style scoped>
.section-blocks-navigation {
  background: white;
  border-radius: 12px;
  padding: 16px;
  margin-bottom: 24px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.blocks-container {
  display: flex;
  gap: 8px;
  overflow-x: auto;
  padding: 4px 0;
}

.block-button {
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 8px 12px;
  border: 2px solid #e5e7eb;
  border-radius: 8px;
  background: white;
  cursor: pointer;
  transition: all 0.2s ease;
  min-width: 80px;
  white-space: nowrap;
}

.block-button:hover {
  border-color: #10b981;
  background: #f0fdf4;
}

.block-button.active {
  border-color: #10b981;
  background: #10b981;
  color: white;
}

.block-title {
  font-size: 11px;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.3px;
  text-align: center;
  line-height: 1.2;
}

.block-button.active .block-title {
  color: white;
}

/* Responsive */
@media (max-width: 768px) {
  .blocks-container {
    gap: 4px;
  }
  
  .block-button {
    min-width: 70px;
    padding: 6px 8px;
  }
  
  .block-title {
    font-size: 9px;
  }
}
</style>