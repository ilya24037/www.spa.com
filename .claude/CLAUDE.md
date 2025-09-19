# SPA Platform - Production Laravel + Vue.js Application

## Project Overview
- **Type**: Service marketplace platform (massage services)
- **Size**: 37,000+ lines of production code
- **Path**: C:\www.spa.com
- **Stack**: Laravel 12, Vue 3, TypeScript, MySQL, Tailwind CSS

## Core Principles
1. **KISS** - Keep It Simple, Stupid (простота решений)
2. **SOLID** - Clean architecture (чистая архитектура)
3. **DRY** - Don't Repeat Yourself (без дублирования)
4. **Test-first** - Tests before code (сначала тесты)
5. **Security by default** - Built-in security (безопасность)
6. **Performance first** - Optimize from start (оптимизация)

## Architecture

### Backend (DDD):
```
app/Domain/
├── User/           # User domain
│   ├── Models/     # Eloquent models
│   ├── Services/   # Business logic
│   └── Actions/    # Complex operations
├── Master/         # Master profiles
├── Booking/        # Bookings
├── Ad/             # Advertisements
└── Media/          # Media files
```

### Frontend (FSD):
```
resources/js/src/
├── shared/         # UI kit, layouts, helpers
├── entities/       # master, booking, ad, user
├── features/       # filters, forms, auth
├── widgets/        # catalog, profile
└── pages/          # home, masters, profile
```

## Critical Commands
```bash
# Development
npm run dev              # Vite dev server
php artisan serve        # Laravel server
npm run type-check       # TypeScript check

# Database
php artisan migrate
php artisan migrate:fresh --seed
php artisan tinker

# Testing
php artisan test
npm run test

# Production
npm run build
php artisan optimize
```

## Development Standards

### PHP/Laravel
```php
// ✅ CORRECT: Service layer
public function store(StoreRequest $request) {
    return $this->service->create($request->validated());
}

// ❌ WRONG: Logic in controller
public function store(Request $request) {
    // business logic here - NO!
}
```

### Vue/TypeScript
```vue
<script setup lang="ts">
// ✅ ALWAYS: Typed props with defaults
interface Props {
  master: Master
  loading?: boolean
}
const props = withDefaults(defineProps<Props>(), {
  loading: false
})

// ✅ ALWAYS: Protected computed
const safeMaster = computed(() => props.master || {} as Master)
</script>
```

## Critical Checklist

### Before ANY Component
- [ ] TypeScript interfaces for props
- [ ] Default values for optional props
- [ ] Loading/error/empty states
- [ ] v-if protection from null/undefined
- [ ] Skeleton loader
- [ ] Mobile responsive (sm: md: lg:)

### Before ANY Backend Change
- [ ] Service layer, not controller
- [ ] DTO for data transfer
- [ ] Transaction for multiple DB ops
- [ ] Validation in Request class
- [ ] Test coverage

## Quick Wins
- **Business errors** → `grep -r "error text" app/Domain/*/Actions/`
- **New component** → Add watcher for auto-save
- **Complex task** → Break into small steps
- **Performance issue** → Profile first, optimize second
- **Data not saving** → Check: Component → Watcher → Emit → Backend → DB
- **Status validation** → Find Action, remove strict check

## Debug Quick Guide
```bash
# Business logic errors
grep -r "error text" app/Domain/*/Actions/

# Data not saving
# Check: Component → Watcher → Emit → Backend → DB

# Frontend issues
# Check: Browser console → Vue DevTools → Network tab

# Backend issues
tail -f storage/logs/laravel.log
```

## Project-Specific Features
- **Maps**: Yandex Maps API integration
- **Booking**: Time slots, calendar
- **Gallery**: Photo uploads, moderation
- **Filters**: Districts, metro, services
- **Reviews**: Rating system

## Key Files
- `AI_CONTEXT.md` - Current tasks
- `storage/ai-sessions/` - Work logs
- `.claude/templates/` - Task templates
- `quality.json` - Quality metrics

## Common Patterns
```bash
# Component location
entities/master/ui/MasterCard/

# Service location
app/Domain/Master/Services/MasterService.php

# Action location
app/Domain/Ad/Actions/PublishAdAction.php
```

## Git Convention
```bash
feat: add booking calendar       # New feature
fix: resolve validation error    # Bug fix
refactor: migrate to FSD        # Refactoring
docs: update API documentation  # Documentation
```

## Critical Reminders
- **Ask first**: "Visual change or DB change needed?"
- **Check watchers**: New v-model needs watcher for save
- **Test chain**: UI → API → DB → UI
- **Keep working**: Don't break existing features
- **Russian comments**: For complex business logic

## Quick Links
- **Dev**: http://spa.test
- **Maps**: http://spa.test/maps/address-picker/
- **Logs**: C:\www.spa.com\storage\logs\
- **Backup**: C:\www.spa.com\_backup\