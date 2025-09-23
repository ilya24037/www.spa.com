<!-- ItemActions - действия с объявлением -->
<template>
  <div class="item-actions">
    <!-- Кнопки для ожидающих оплаты -->
    <template v-if="item.status === 'waiting_payment'">
      <Button 
        @click="$emit('pay')"
        variant="success"
        size="sm"
      >
        Оплатить размещение
      </Button>
      
      <!-- Dropdown меню с дополнительными действиями -->
      <ActionDropdown aria-label="Дополнительные действия">
        <ActionDropdownItem 
          text="Оплатить размещение"
          @click="$emit('pay')"
        />
        <ActionDropdownItem 
          text="Уже не актуально"
          @click="$emit('mark-irrelevant')"
        />
        <div class="dropdown-divider" />
        <ActionDropdownItem 
          text="Редактировать"
          @click="$emit('edit')"
        />
        <ActionDropdownItem 
          text="Удалить"
          variant="danger"
          @click="$emit('delete')"
        />
      </ActionDropdown>
    </template>
    
    <!-- Кнопка и dropdown для активных объявлений -->
    <template v-else-if="item.status === 'active'">
      <Button 
        @click="$emit('promote')"
        variant="primary"
        size="sm"
      >
        Поднять просмотры
      </Button>
      
      <ActionDropdown aria-label="Дополнительные действия">
        <ActionDropdownItem 
          text="Поднять просмотры"
          @click="$emit('promote')"
        />
        <ActionDropdownItem 
          text="Редактировать"
          @click="$emit('edit')"
        />
        <ActionDropdownItem 
          text="Забронировать"
          @click="$emit('book')"
        />
        <div class="dropdown-divider" />
        <ActionDropdownItem 
          text="Снять с публикации"
          variant="danger"
          @click="$emit('deactivate')"
        />
      </ActionDropdown>
    </template>
    
    <!-- Кнопки для черновиков -->
    <template v-else-if="item.status === 'draft'">
      <Button 
        @click="$emit('edit')"
        variant="light"
        size="sm"
        class="btn-edit-draft"
      >
        Редактировать
      </Button>
      
      <!-- Dropdown меню с дополнительными действиями -->
      <ActionDropdown aria-label="Дополнительные действия">
        <ActionDropdownItem 
          text="Редактировать"
          @click="$emit('edit')"
        />
        <div class="dropdown-divider" />
        <ActionDropdownItem 
          text="Удалить"
          variant="danger"
          @click="$emit('delete')"
        />
      </ActionDropdown>
    </template>
    
    <!-- Кнопки для архивированных объявлений -->
    <template v-else-if="item.status === 'archived'">
      <Button 
        @click="$emit('restore')"
        variant="success"
        size="sm"
      >
        Восстановить
      </Button>
      <Button 
        @click="$emit('delete')"
        variant="danger"
        size="sm"
      >
        Удалить
      </Button>
    </template>

    <!-- Кнопки для объявлений, ожидающих действий (отклоненные, на модерации, истекшие) -->
    <template v-else-if="['rejected', 'pending_moderation', 'expired'].includes(item.status)">
      <Button
        @click="$emit('publish')"
        variant="primary"
        size="sm"
      >
        Опубликовать
      </Button>

      <ActionDropdown aria-label="Дополнительные действия">
        <ActionDropdownItem
          text="Опубликовать"
          @click="$emit('publish')"
        />
        <ActionDropdownItem
          text="Редактировать"
          @click="$emit('edit')"
        />
        <ActionDropdownItem
          text="Удалить"
          variant="danger"
          @click="$emit('delete')"
        />
        <ActionDropdownItem
          text="Уже не актуально"
          @click="$emit('mark-irrelevant')"
        />
      </ActionDropdown>
    </template>

    <!-- Кнопки для неактивных и прочих статусов -->
    <template v-else-if="item.status === 'inactive' || item.status === 'old'">
      <Button
        @click="$emit('edit')"
        variant="light"
        size="sm"
      >
        Редактировать
      </Button>
      <Button
        @click="$emit('delete')"
        variant="danger"
        size="sm"
      >
        Удалить
      </Button>
    </template>
  </div>
</template>

<script setup lang="ts">
import Button from '@/src/shared/ui/atoms/Button/Button.vue'
import { ActionDropdown, ActionDropdownItem } from '@/src/shared/ui/molecules/ActionDropdown'
import type { AdItem } from '../ItemCard.types'

interface Props {
  item: AdItem
}

defineProps<Props>()

defineEmits<{
  pay: []
  promote: []
  edit: []
  deactivate: []
  delete: []
  'mark-irrelevant': []
  book: []
  restore: []
  publish: []
}>()
</script>

<style scoped>
.item-actions {
  display: flex;
  gap: 8px;
  margin-top: 12px;
}

/* Кастомизация для кнопки редактирования черновика */
.btn-edit-draft {
  padding-left: 16px !important;
  padding-right: 16px !important;
}

/* Разделитель в dropdown */
.dropdown-divider {
  @apply my-1 border-t border-gray-200;
}

/* Мобильная адаптация */
@media (max-width: 640px) {
  .item-actions {
    flex-wrap: wrap;
  }
}
</style>