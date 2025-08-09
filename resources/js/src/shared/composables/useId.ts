// useId.ts - Generate unique IDs
let counter = 0

export function useId(prefix = 'id') {
    return `${prefix}-${++counter}-${Date.now()}`
}