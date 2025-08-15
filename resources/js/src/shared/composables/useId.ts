// useId.ts - Generate unique IDs
export function useId(prefix = 'field') {
    return `${prefix}-${Math.random().toString(36).slice(2, 8)}`
}