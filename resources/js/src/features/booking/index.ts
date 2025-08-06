// Main booking feature exports

// BookingCalendar component and related
export {
  BookingCalendar,
  CalendarHeader,
  CalendarGrid,
  CalendarDayComponent
} from './ui/BookingCalendar'

export type {
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
} from './model/calendar.types'

// Booking domain model (if needed)
// export { bookingStore } from './model/booking.store'