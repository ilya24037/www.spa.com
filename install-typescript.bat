@echo off
echo ========================================
echo Installing TypeScript dependencies...
echo ========================================

echo.
echo Installing TypeScript and Vue TypeScript support...
npm install -D typescript @types/node vue-tsc @vue/tsconfig

echo.
echo Installing type definitions...
npm install -D @types/lodash @inertiajs/core @inertiajs/vue3

echo.
echo TypeScript installation complete!
echo.
echo Run "npm run type-check" to check types
echo Run "npm run build" to build with type checking
echo.
pause