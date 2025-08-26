// Main BookingCalendar component
export { default as BookingCalendar } from './BookingCalendar.vue'

// Calendar subcomponents
export { default as CalendarHeader } from './components/CalendarHeader.vue'
export { default as CalendarGrid } from './components/CalendarGrid.vue'
export { default as CalendarDayComponent } from './components/CalendarDay.vue'
export { default as CalendarLegend } from './components/CalendarLegend.vue'
export { default as MobileDateList } from './components/MobileDateList.vue'

// Calendar composables
export {
  useCalendar
} from './composables/useCalendar'

export {
  useDateSelection
} from './composables/useDateSelection'

export {
  useBookingStatus
} from './composables/useBookingStatus'

// Re-export types from model
export type {
  BookingCalendarProps,
  BookingCalendarEmits,
  CalendarDay as CalendarDayType,
  CalendarState,
  CalendarNavigation,
  DateBookingInfo,
  DateAvailabilityStatus,
  AvailableDateItem,
  CalendarLegendItem,
  CalendarConfig,
  FullCalendarConfig,
  CalendarDisplayConfig,
  CalendarAccessibility,
  AvailableDatesOptions,
  DateValidationResult,
  DateUtils
} from '../../model/calendar.types'